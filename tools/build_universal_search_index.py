"""
Script para generar el índice consolidado de búsqueda universal:
Jerarquía de Gobernanza y Prioridad Estricta:
1. Federales en Funciones (LXVI Legislatura 2024-2027)
2. Gobernadores en Funciones (32 Entidades Federativas)
3. Presidencias Municipales en Funciones (2,474 Alcaldías)
4. Federales Históricos (Legislaturas 65 a 57) y Ex-Gobernadores
5. Diputados Locales (32 Congresos Estatales)
6. Comisiones Legislativas Ordinarias
"""
import glob
import os
import re
import time
import unicodedata
from pathlib import Path
try:
    import orjson
except ImportError:
    orjson = None
import json

def load_json_bytes(raw_bytes):
    if orjson:
        return orjson.loads(raw_bytes)
    return json.loads(raw_bytes.decode("utf-8"))

def write_json_file(file_path, obj):
    if orjson:
        with open(file_path, "wb") as fp:
            fp.write(orjson.dumps(obj, option=orjson.OPT_INDENT_2))
    else:
        with open(file_path, "w", encoding="utf-8") as fp:
            json.dump(obj, fp, ensure_ascii=False, indent=2)

def slugify(text) -> str:
    if text is None:
        return ""
    text = str(text)
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text.lower()).strip()
    return re.sub(r"[-\s]+", "-", text)

def formatear_nombre_natural(nombre_raw: str) -> str:
    if not nombre_raw:
        return ""
    texto = " ".join(nombre_raw.strip().split())
    tokens = texto.split(" ")
    if len(tokens) <= 2:
        return texto

    particulas = {"de", "del", "de la", "de los", "de las", "san", "santa", "y"}
    palabras = []
    i = 0
    while i < len(tokens):
        if i + 2 < len(tokens) and f"{tokens[i]} {tokens[i+1]}".lower() in particulas:
            palabras.append(f"{tokens[i]} {tokens[i+1]} {tokens[i+2]}")
            i += 3
        elif i + 1 < len(tokens) and tokens[i].lower() in particulas:
            palabras.append(f"{tokens[i]} {tokens[i+1]}")
            i += 2
        else:
            palabras.append(tokens[i])
            i += 1

    if len(palabras) == 3:
        return f"{palabras[2]} {palabras[0]} {palabras[1]}"
    elif len(palabras) >= 4:
        apellidos = " ".join(palabras[:2])
        nombres = " ".join(palabras[2:])
        return f"{nombres} {apellidos}"
    return texto

def ordenar_apellidos_nombres(nombre_natural: str) -> str:
    n = re.sub(r"^(?:Dip\.|Diputada|Diputado|Senador|Senadora|Lic\.|Dr\.|Dra\.|Ing\.|Mtro\.|Mtra\.)\s+", "", nombre_natural, flags=re.I).strip()
    partes = n.split()
    if len(partes) == 3:
        return f"{partes[1]} {partes[2]} {partes[0]}"
    elif len(partes) == 4:
        return f"{partes[2]} {partes[3]} {partes[0]} {partes[1]}"
    elif len(partes) >= 5:
        return f"{partes[-2]} {partes[-1]} {' '.join(partes[:-2])}"
    return n

def resolve_local_photo(p: dict, photos_dir: Path) -> str:
    ref = str(p.get("referencia_sil") or "").strip()
    if ref and (photos_dir / f"{ref}.jpg").exists():
        return f"/storage/raw/photos/{ref}.jpg"
    raw_url = (p.get("foto_oficial_url") or p.get("foto_url") or "").strip()
    if not raw_url or "sil.gobernacion.gob.mx" in raw_url:
        return ""
    return raw_url

def build_universal_index():
    base_dir = Path(__file__).resolve().parent.parent
    json_dir = base_dir / "storage" / "data" / "json"
    photos_dir = base_dir / "storage" / "raw" / "photos"
    
    t0 = time.time()
    entries = []
    seen_slugs = set()
    seen_aliases = set()

    # 1. Federales en funciones (LXVI Legislatura)
    fed_66_file = json_dir / "congreso_union_leg_66.json"
    if fed_66_file.exists():
        try:
            with open(fed_66_file, "rb") as fp:
                data66 = load_json_bytes(fp.read())
            for p in data66.get("diputados", []) + data66.get("senadores", []):
                nom = (p.get("nombre_completo") or p.get("titular_nombre") or "").strip()
                if not nom:
                    continue
                cam = "Senador(a)" if "Senad" in (p.get("camara") or "") else "Diputado(a) Federal"
                ent = p.get("entidad") or "México"
                part = p.get("partido") or "Sin Partido"
                formal_slug = p.get("slug") or slugify(nom)
                natural_nom = formatear_nombre_natural(nom)
                natural_slug = slugify(natural_nom)

                if formal_slug not in seen_slugs:
                    seen_slugs.add(formal_slug)
                    seen_aliases.add(formal_slug)
                    seen_aliases.add(natural_slug)
                    seen_aliases.add(slugify(nom))
                    seen_aliases.add(slugify(natural_nom))

                    cargo_badge = f"{cam} • Leg. 66"
                    search_terms = f"{formal_slug} {natural_slug} {slugify(nom)} {slugify(natural_nom)} {slugify(part)} {slugify(ent)} {slugify(cam)} federal"
                    entries.append({
                        "nombre": nom,
                        "slug": formal_slug,
                        "cargo": cargo_badge,
                        "camara": cam,
                        "partido": part,
                        "entidad": ent,
                        "ambito": "Federal",
                        "url": f"/politicos/{formal_slug}",
                        "foto_url": resolve_local_photo(p, photos_dir),
                        "search": search_terms
                    })
        except Exception as e:
            print(f"Error procesando leg 66: {e}")

    # 2. Gobernadores en Funciones (32 Titulares del Poder Ejecutivo Estatal)
    ejec_files = sorted(glob.glob(str(json_dir / "ejecutivo" / "*.json")))
    ex_gobernadores_pendientes = []
    for f in ejec_files:
        try:
            with open(f, "rb") as fp:
                data = load_json_bytes(fp.read())
        except Exception:
            continue
        ent = data.get("entidad") or ("Ciudad de México" if "cdmx" in f else "Estado")
        mandatarios = data.get("mandatarios", [])
        for idx, m in enumerate(mandatarios):
            nom = (m.get("nombre_completo") or "").strip()
            if not nom:
                continue
            part = m.get("partido") or "Sin Partido"
            fc = m.get("fecha_conclusion") or ""
            fi = m.get("fecha_inicio") or ""
            es_ultimo = (idx == len(mandatarios) - 1)
            es_activo = es_ultimo and (fc >= "2024-10-01")

            slug = slugify(nom)
            inv_slug = slugify(ordenar_apellidos_nombres(nom))

            if es_activo:
                if slug not in seen_slugs and inv_slug not in seen_slugs and slug not in seen_aliases and inv_slug not in seen_aliases:
                    seen_slugs.add(slug)
                    seen_aliases.add(slug)
                    seen_aliases.add(inv_slug)
                    cargo_gob = "Jefa de Gobierno de la Ciudad de México" if "Ciudad de México" in ent else f"Gobernador(a) de {ent}"
                    entries.append({
                        "nombre": nom,
                        "slug": slug,
                        "cargo": cargo_gob,
                        "camara": f"Gobierno de {ent}",
                        "partido": part,
                        "entidad": ent,
                        "ambito": "Estatal",
                        "url": f"/politicos/{slug}",
                        "foto_url": m.get("foto_url") or "",
                        "search": f"{slug} {inv_slug} {slugify(nom)} {slugify(part)} {slugify(ent)} gobernador gobernadora ejecutivo estatal"
                    })
            else:
                ex_gobernadores_pendientes.append((ent, m, nom, part, fi[:4], fc[:4], slug, inv_slug))

    # 3. Alcaldes en Funciones (Presidencias Municipales de México 2024-2027)
    alc_file = json_dir / "alcaldes" / "directorio_nacional_alcaldes_vigentes.json"
    if alc_file.exists():
        try:
            with open(alc_file, "rb") as fp:
                raw_alc = load_json_bytes(fp.read())
            if isinstance(raw_alc, list):
                alcs = raw_alc
            elif isinstance(raw_alc.get("alcaldes"), dict):
                alcs = list(raw_alc.get("alcaldes").values())
            else:
                alcs = raw_alc.get("alcaldes", [])
            for a in alcs:
                nom = (a.get("nombre_completo") or a.get("nombre") or "").strip()
                if not nom:
                    continue
                mun = a.get("municipio") or ""
                ent = a.get("entidad") or ""
                part = a.get("partido") or "Sin Partido"
                slug = a.get("slug") or slugify(nom)
                inv_slug = slugify(ordenar_apellidos_nombres(nom))
                if slug not in seen_slugs and inv_slug not in seen_slugs and slug not in seen_aliases and inv_slug not in seen_aliases:
                    seen_slugs.add(slug)
                    seen_aliases.add(slug)
                    seen_aliases.add(inv_slug)
                    entries.append({
                        "nombre": nom,
                        "slug": slug,
                        "cargo": f"Presidente(a) Municipal • {mun}",
                        "camara": f"Ayuntamiento de {mun}",
                        "partido": part,
                        "entidad": ent,
                        "municipio": mun,
                        "ambito": "Municipal",
                        "url": f"/politicos/{slug}",
                        "foto_url": a.get("foto_url") or "",
                        "search": f"{slug} {inv_slug} {slugify(nom)} {slugify(part)} {slugify(ent)} {slugify(mun)} alcalde alcaldesa presidente municipal"
                    })
        except Exception as e:
            print(f"Error procesando alcaldes: {e}")

    # 4. Federales Históricos (Legislaturas 65 a 57)
    for leg in [65, 64, 63, 62, 61, 60, 59, 58, 57]:
        f_path = json_dir / f"congreso_union_leg_{leg}.json"
        if not f_path.exists():
            continue
        try:
            with open(f_path, "rb") as fp:
                data_leg = load_json_bytes(fp.read())
            for p in data_leg.get("diputados", []) + data_leg.get("senadores", []):
                nom = (p.get("nombre_completo") or p.get("titular_nombre") or "").strip()
                if not nom:
                    continue
                cam = "Senador(a)" if "Senad" in (p.get("camara") or "") else "Diputado(a) Federal"
                ent = p.get("entidad") or "México"
                part = p.get("partido") or "Sin Partido"
                formal_slug = p.get("slug") or slugify(nom)
                natural_nom = formatear_nombre_natural(nom)
                natural_slug = slugify(natural_nom)

                if formal_slug not in seen_slugs and formal_slug not in seen_aliases and natural_slug not in seen_slugs and natural_slug not in seen_aliases:
                    seen_slugs.add(formal_slug)
                    seen_aliases.add(formal_slug)
                    seen_aliases.add(natural_slug)
                    seen_aliases.add(slugify(nom))
                    seen_aliases.add(slugify(natural_nom))

                    cargo_badge = f"{cam} • Leg. {leg}"
                    search_terms = f"{formal_slug} {natural_slug} {slugify(nom)} {slugify(natural_nom)} {slugify(part)} {slugify(ent)} {slugify(cam)} federal"
                    entries.append({
                        "nombre": nom,
                        "slug": formal_slug,
                        "cargo": cargo_badge,
                        "camara": cam,
                        "partido": part,
                        "entidad": ent,
                        "ambito": "Federal",
                        "url": f"/politicos/{formal_slug}",
                        "foto_url": resolve_local_photo(p, photos_dir),
                        "search": search_terms
                    })
        except Exception as e:
            print(f"Error procesando leg {leg}: {e}")

    # 4.5 Ex-Gobernadores Históricos que no fueron indexados como titulares activos o legisladores
    for ent, m, nom, part, fi_ano, fc_ano, slug, inv_slug in ex_gobernadores_pendientes:
        if slug not in seen_slugs and inv_slug not in seen_slugs and slug not in seen_aliases and inv_slug not in seen_aliases:
            seen_slugs.add(slug)
            seen_aliases.add(slug)
            seen_aliases.add(inv_slug)
            per_lbl = f" ({fi_ano}–{fc_ano})" if fi_ano and fc_ano else ""
            entries.append({
                "nombre": nom,
                "slug": slug,
                "cargo": f"Ex-Gobernador(a) de {ent}{per_lbl}",
                "camara": f"Gobierno de {ent}",
                "partido": part,
                "entidad": ent,
                "ambito": "Estatal",
                "url": f"/politicos/{slug}",
                "foto_url": m.get("foto_url") or "",
                "search": f"{slug} {inv_slug} {slugify(nom)} {slugify(part)} {slugify(ent)} exgobernador gobernador estatal"
            })

    # 5. Diputados Locales (32 Congresos Estatales)
    local_files = sorted(glob.glob(str(json_dir / "congreso_*_leg_*.json")))
    for f in local_files:
        if "congreso_union_" in f:
            continue
        try:
            with open(f, "rb") as fp:
                data = load_json_bytes(fp.read())
        except Exception:
            continue
        meta = data.get("metadata", {})
        ent = data.get("entidad") or meta.get("entidad") or "Estado"
        leg = data.get("legislatura") or meta.get("legislatura") or ""
        leg_num = re.sub(r"[^0-9]", "", str(leg)) if leg else ""
        cam_label = f"Congreso de {ent}"
        cargo_label = f"Diputado(a) Local • {leg_num} Leg." if leg_num else "Diputado(a) Local"
        
        dips = data.get("diputados", []) or data.get("legisladores", [])
        for p in dips:
            nom = (p.get("nombre_completo") or p.get("nombre") or "").strip()
            if not nom:
                continue
            clean_name = re.sub(r"^(Dip\.|Diputad[ao])\s+", "", nom, flags=re.I).strip()
            slug = p.get("slug") or slugify(clean_name)
            inv_slug = slugify(ordenar_apellidos_nombres(clean_name))
            part = p.get("partido") or "Sin Partido"
            dist = p.get("distrito") or ""

            if slug not in seen_slugs and inv_slug not in seen_slugs and slug not in seen_aliases and inv_slug not in seen_aliases:
                seen_slugs.add(slug)
                seen_aliases.add(slug)
                seen_aliases.add(inv_slug)
                entries.append({
                    "nombre": clean_name,
                    "slug": slug,
                    "cargo": cargo_label,
                    "camara": cam_label,
                    "partido": part,
                    "entidad": ent,
                    "municipio": dist,
                    "ambito": "Estatal",
                    "url": f"/politicos/{slug}",
                    "foto_url": resolve_local_photo(p, photos_dir),
                    "search": f"{slug} {inv_slug} {slugify(clean_name)} {slugify(part)} {slugify(ent)} diputado local estatal"
                })

    # 6. Comisiones Legislativas
    if fed_66_file.exists():
        try:
            with open(fed_66_file, "rb") as fp:
                data66 = load_json_bytes(fp.read())
            coms_seen = set()
            for p in data66.get("diputados", []) + data66.get("senadores", []):
                cam_p = p.get("camara") or "Diputados"
                for c in p.get("comisiones", []):
                    c_nom = (c.get("comision") or c.get("nombre") or "").strip()
                    if not c_nom:
                        continue
                    c_slug = slugify(c_nom)
                    if c_slug and c_slug not in coms_seen:
                        coms_seen.add(c_slug)
                        entries.append({
                            "nombre": c_nom,
                            "slug": c_slug,
                            "cargo": f"Comisión de {cam_p}",
                            "camara": cam_p,
                            "partido": "Órgano Legislativo",
                            "entidad": "Congreso de la Unión",
                            "ambito": "Comisión",
                            "url": f"/comisiones/{c_slug}",
                            "foto_url": "",
                            "search": f"{c_slug} {slugify(c_nom)} comision {slugify(cam_p)}"
                        })
        except Exception:
            pass

    out_file = json_dir / "indice_busqueda_universal.json"
    write_json_file(out_file, entries)
    print(f"Indice universal reconstruido con {len(entries)} registros unicos en {time.time() - t0:.2f}s.")

if __name__ == "__main__":
    build_universal_index()
