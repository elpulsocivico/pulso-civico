import os
import sys
import re
import json
import time
import hashlib
import urllib.request
import ssl
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed

BASE_DIR = Path(r"e:\xampp\htdocs\elpulso")
if str(BASE_DIR) not in sys.path:
    sys.path.insert(0, str(BASE_DIR))

VENDOR_DIR = BASE_DIR / "public" / "assets" / "vendor"
PHOTOS_DIR = BASE_DIR / "storage" / "raw" / "photos"
LOCALES_PHOTOS_DIR = PHOTOS_DIR / "locales"
JSON_DIR = BASE_DIR / "storage" / "data" / "json"
LOG_FILE = BASE_DIR / "storage" / "logs" / "photo_downloader.log"

VENDOR_DIR.mkdir(parents=True, exist_ok=True)
LOCALES_PHOTOS_DIR.mkdir(parents=True, exist_ok=True)
LOG_FILE.parent.mkdir(parents=True, exist_ok=True)

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Accept": "image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8",
    "Accept-Language": "es-MX,es;q=0.9,en;q=0.8",
    "Referer": "http://sil.gobernacion.gob.mx/",
}


def log(msg: str):
    ts = time.strftime("%Y-%m-%d %H:%M:%S")
    line = f"[{ts}] {msg}"
    print(line, flush=True)
    with open(LOG_FILE, "a", encoding="utf-8") as lf:
        lf.write(line + "\n")


def fetch_bytes(url: str, timeout: int = 10, custom_headers: dict = None) -> bytes:
    hdrs = dict(HEADERS)
    if custom_headers:
        hdrs.update(custom_headers)
    req = urllib.request.Request(url, headers=hdrs)
    with urllib.request.urlopen(req, timeout=timeout, context=ctx) as resp:
        return resp.read()


def is_valid_image(data: bytes) -> bool:
    if not data or len(data) < 400:
        return False
    head = data[:64].lstrip().lower()
    if head.startswith(b"<!doctype") or head.startswith(b"<html") or head.startswith(b"<head") or b"incident id" in head:
        return False
    return True


def is_sil_waf_block(data: bytes) -> bool:
    if not data:
        return False
    low = data[:300].lower()
    return b"this page can't be displayed" in low or b"incident id" in low


def download_one_sil_polite(ref: str, url: str) -> tuple[str, bool, int, bool]:
    """
    Descarga una foto de SIL SEGOB respetando rate_limit >= 1000ms (Regla #10 AGENTS.md).
    Retorna (ref, exito, bytes, waf_blocked).
    """
    dest = PHOTOS_DIR / f"{ref}.jpg"
    if dest.exists() and dest.stat().st_size > 400:
        return (ref, True, dest.stat().st_size, False)
    try:
        data = fetch_bytes(url, timeout=10)
        if is_sil_waf_block(data):
            return (ref, False, 0, True)
        if is_valid_image(data):
            dest.write_bytes(data)
            return (ref, True, len(data), False)
    except Exception:
        pass
    return (ref, False, 0, False)


def download_one_state_photo(url: str) -> tuple[str, str, int]:
    h = hashlib.md5(url.encode("utf-8")).hexdigest()[:16]
    ext = ".jpg"
    low = url.lower()
    if ".png" in low:
        ext = ".png"
    elif ".webp" in low:
        ext = ".webp"
    elif ".gif" in low:
        ext = ".gif"
    fname = f"loc_{h}{ext}"
    dest = LOCALES_PHOTOS_DIR / fname
    local_rel = f"/storage/raw/photos/locales/{fname}"
    if dest.exists() and dest.stat().st_size > 400:
        return (url, local_rel, dest.stat().st_size)
    try:
        from urllib.parse import urlparse
        parsed = urlparse(url)
        origin_ref = f"{parsed.scheme}://{parsed.netloc}/"
        data = fetch_bytes(url, timeout=10, custom_headers={"Referer": origin_ref})
        if is_valid_image(data):
            dest.write_bytes(data)
            return (url, local_rel, len(data))
    except Exception:
        pass
    return (url, "", 0)


def ensure_data_service_photo_sanitization():
    ds_file = BASE_DIR / "app" / "services" / "data_service.py"
    txt = ds_file.read_text(encoding="utf-8")
    if 'elif "sil.gobernacion.gob.mx" in str(leg.get("foto_oficial_url"' in txt:
        return
    for nl in ("\r\n", "\n"):
        target = (
            f'                        leg["legislatura"] = leg_num{nl}'
            f'                        slug = leg.get("slug") or slugify(nombre){nl}'
            f'                        leg["slug"] = slug'
        )
        replacement = (
            f'                        leg["legislatura"] = leg_num{nl}'
            f'                        slug = leg.get("slug") or slugify(nombre){nl}'
            f'                        leg["slug"] = slug{nl}'
            f'                        if ref and self._has_raw_photo(f"{{ref}}.jpg"):{nl}'
            f'                            leg["foto_oficial_url"] = f"/photos/{{ref}}.jpg"{nl}'
            f'                            leg["foto_local_path"] = f"/photos/{{ref}}.jpg"{nl}'
            f'                        elif "sil.gobernacion.gob.mx" in str(leg.get("foto_oficial_url") or ""):{nl}'
            f'                            leg["foto_oficial_url"] = ""'
        )
        if target in txt:
            txt = txt.replace(target, replacement, 1)
            ds_file.write_text(txt, encoding="utf-8")
            log("[DataService Python] Sanitizacion automatica de foto_oficial_url activada.")
            break


def run_full_download():
    log("============================================================")
    log("VERIFICANDO Y SINCRONIZANDO FOTOS LOCALES, FEDERALES Y VENDOR")
    log("============================================================")

    ensure_data_service_photo_sanitization()

    # 1. Probar si SIL SEGOB responde o tiene cooldown de WAF activo
    waf_active = False
    for leg in [65, 64, 63, 62, 61, 60, 59, 58, 57]:
        if waf_active:
            break
        f = JSON_DIR / f"congreso_union_leg_{leg}.json"
        if not f.exists():
            continue
        d = json.loads(f.read_text(encoding="utf-8"))
        pols = d.get("diputados", []) + d.get("senadores", [])
        to_dl = {}
        for p in pols:
            ref = str(p.get("referencia_sil") or "").strip()
            if ref and not (PHOTOS_DIR / f"{ref}.jpg").exists():
                to_dl[ref] = f"http://sil.gobernacion.gob.mx/Archivos/Fotos/{ref}.jpg"

        if not to_dl:
            log(f"[Leg {leg}] 100% de las fotos federales ya existen en disco.")
            continue

        log(f"[Leg {leg}] Verificando {len(to_dl)} fotos historicas en SIL SEGOB (rate_limit=1.1s)...")
        ok_count = 0
        fail_count = 0
        bytes_sum = 0
        consecutive_waf = 0
        for idx, (r, u) in enumerate(to_dl.items(), 1):
            _, ok, sz, is_waf = download_one_sil_polite(r, u)
            if is_waf:
                consecutive_waf += 1
                if consecutive_waf >= 2:
                    log(f"  -> [Aviso WAF SIL] El firewall de sil.gobernacion.gob.mx mantiene cooldown temporal de IP. Se pausa descarga historica en Leg {leg} para respetar el servidor gubernamental.")
                    waf_active = True
                    break
            else:
                consecutive_waf = 0
                if ok:
                    ok_count += 1
                    bytes_sum += sz
                else:
                    fail_count += 1
            time.sleep(1.1)

    # 2. Verificar/limpiar las fotos de Congresos Estatales y Ejecutivo
    state_files = [
        cf for cf in list(JSON_DIR.glob("congreso_*_leg_*.json")) + list((JSON_DIR / "ejecutivo").glob("*.json"))
        if not cf.name.startswith("congreso_union_")
    ]
    unique_urls = set()
    for cf in state_files:
        try:
            d = json.loads(cf.read_text(encoding="utf-8"))
            items = d.get("diputados", []) or d.get("legisladores", []) or d.get("mandatarios", [])
            for p in items:
                u = (p.get("foto_oficial_url") or p.get("foto_url") or "").strip()
                if u.startswith("http") and "sil.gobernacion.gob.mx" not in u and "ui-avatars.com" not in u and "via.placeholder.com" not in u:
                    unique_urls.add(u)
        except Exception:
            pass

    url_to_local = {}
    dead_urls = set()
    if unique_urls:
        log(f"[Estatales] Reintentando {len(unique_urls)} URLs estatales pendientes...")
        with ThreadPoolExecutor(max_workers=6) as ex:
            futs = {ex.submit(download_one_state_photo, u): u for u in unique_urls}
            for fut in as_completed(futs):
                u, loc_path, sz = fut.result()
                if loc_path:
                    url_to_local[u] = loc_path
                else:
                    dead_urls.add(u)

    # Actualizar los JSON estatales: si descargo -> ruta local; si es 404 muerto en el servidor estatal -> limpiar foto_oficial_url y respaldar en foto_origen_url
    updated_files = 0
    cleaned_dead = 0
    for cf in state_files:
        try:
            d = json.loads(cf.read_text(encoding="utf-8"))
            changed = False
            for key in ("diputados", "legisladores", "mandatarios"):
                if key in d and isinstance(d[key], list):
                    for p in d[key]:
                        for fkey in ("foto_oficial_url", "foto_url"):
                            u = (p.get(fkey) or "").strip()
                            if u in url_to_local:
                                p["foto_origen_url"] = u
                                p[fkey] = url_to_local[u]
                                changed = True
                            elif u in dead_urls:
                                p["foto_origen_url"] = u
                                p[fkey] = ""
                                cleaned_dead += 1
                                changed = True
            if changed:
                cf.write_text(json.dumps(d, ensure_ascii=False, indent=2), encoding="utf-8")
                updated_files += 1
        except Exception:
            pass
    log(f"[Estatales] Archivos actualizados: {updated_files} (URLs estatales 404 limpiadas a avatar local: {cleaned_dead})")

    # 3. Reconstruir indice universal
    try:
        from tools.build_universal_search_index import build_universal_index
        build_universal_index()
        log("[Indice Universal] Reconstruido exitosamente.")
    except Exception as e:
        log(f"[!] Error reconstruyendo indice universal: {e}")

    # 4. Reporte final de almacenamiento
    fed_photos = [p for p in PHOTOS_DIR.glob("*.jpg") if p.is_file()]
    loc_photos = [p for p in LOCALES_PHOTOS_DIR.glob("*.*") if p.is_file()]
    vendor_files = [p for p in VENDOR_DIR.rglob("*.*") if p.is_file()]

    fed_mb = sum(p.stat().st_size for p in fed_photos) / (1024 * 1024)
    loc_mb = sum(p.stat().st_size for p in loc_photos) / (1024 * 1024)
    vendor_mb = sum(p.stat().st_size for p in vendor_files) / (1024 * 1024)
    total_photos = len(fed_photos) + len(loc_photos)
    total_photos_mb = fed_mb + loc_mb

    log("============================================================")
    log(f"RESUMEN FINAL DE DESCARGAS EN DISCO:")
    log(f"  - Fotos Federales (storage/raw/photos/*.jpg): {len(fed_photos)} archivos ({fed_mb:.2f} MB)")
    log(f"  - Fotos Estatales (storage/raw/photos/locales/*): {len(loc_photos)} archivos ({loc_mb:.2f} MB)")
    log(f"  - TOTAL FOTOS EN DISCO: {total_photos} archivos ({total_photos_mb:.2f} MB)")
    log(f"  - Librerias Vendor (public/assets/vendor/*): {len(vendor_files)} archivos ({vendor_mb:.2f} MB)")
    log(f"  - PESO TOTAL (Fotos + Vendor): {total_photos_mb + vendor_mb:.2f} MB")
    log("============================================================")


if __name__ == "__main__":
    run_full_download()
