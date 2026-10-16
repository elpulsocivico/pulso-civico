"""
Script de enriquecimiento de fechas de nacimiento y edad para Pulso Cívico.
Paso 1: Consulta masiva a Wikidata (rápida, cubre miles en segundos).
Paso 2: Barrido quirúrgico a SIL SEGOB para legisladores sin fecha en la legislatura activa (LXVI).
"""

import sys
import time
import json
import re
import unicodedata
import urllib.request
import urllib.parse
from pathlib import Path
from typing import Dict, Any, Optional

BASE_DIR = Path(__file__).resolve().parent.parent
STORAGE_JSON_DIR = BASE_DIR / "storage" / "data" / "json"

def slugify(text: str) -> str:
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text.lower()).strip()
    return re.sub(r"[-\s]+", "-", text)

def get_wikidata_dob_map() -> Dict[str, str]:
    print(">>> Paso 1: Consultando API de Wikidata (SPARQL)...")
    query = """
    SELECT DISTINCT ?item ?itemLabel ?birthDate WHERE {
      ?item wdt:P31 wd:Q5;
            wdt:P27 wd:Q96;
            wdt:P106 wd:Q82955;
            wdt:P569 ?birthDate.
      SERVICE wikibase:label { bd:serviceParam wikibase:language "es,en". }
    }
    LIMIT 15000
    """
    url = "https://query.wikidata.org/sparql?" + urllib.parse.urlencode({
        "query": query,
        "format": "json"
    })
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": "ElPulsoCivicoBot/1.0 (observatorio ciudadano; contacto@elpulso.mx)",
            "Accept": "application/sparql-results+json"
        }
    )

    wiki_map: Dict[str, str] = {}
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            bindings = data.get("results", {}).get("bindings", [])
            print(f"    Recibidos {len(bindings)} registros de Wikidata.")
            for b in bindings:
                label = b.get("itemLabel", {}).get("value", "")
                dob_raw = b.get("birthDate", {}).get("value", "")
                if label and dob_raw:
                    # 1958-06-09T00:00:00Z -> 1958-06-09
                    dob = dob_raw.split("T")[0]
                    # Validar año razonable (1900 - 2010)
                    partes = dob.split("-")
                    if len(partes) == 3:
                        try:
                            ano = int(partes[0])
                            if 1910 <= ano <= 2010:
                                slug = slugify(label)
                                if slug:
                                    wiki_map[slug] = dob
                        except ValueError:
                            pass
        print(f"    Total en índice de Wikidata normalizado: {len(wiki_map)} políticos.")
    except Exception as e:
        print(f"    Error consultando Wikidata: {e}")
    return wiki_map

def fetch_sil_birthdate(ref_id: str) -> Optional[str]:
    """Extrae la fecha de nacimiento oficial desde el SIL de SEGOB."""
    if not ref_id or str(ref_id) in ("None", "null", "0", ""):
        return None
    url = f"http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia={ref_id}"
    req = urllib.request.Request(
        url,
        headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"}
    )
    try:
        with urllib.request.urlopen(req, timeout=12) as resp:
            html = resp.read().decode("latin1", errors="ignore")
            # Buscar en celdas de tabla: Nacimiento: </td><td> DD/MM/AAAA </td>
            m = re.search(r"nacimiento.*?</t[dh]>\s*<t[dh][^>]*>(.*?)</t[dh]>", html, re.IGNORECASE | re.DOTALL)
            if m:
                val = re.sub(r"<[^>]+>", "", m.group(1)).strip()
                if val and val.upper() not in ("N/A", "NO DISPONIBLE", "SIN REGISTRO", ""):
                    # Normalizar a YYYY-MM-DD si viene DD/MM/AAAA
                    if "/" in val:
                        p = val.split("/")
                        if len(p) == 3:
                            return f"{p[2]}-{p[1].zfill(2)}-{p[0].zfill(2)}"
                    return val
    except Exception:
        pass
    return None

def process_and_enrich_legislatura(leg_num: int, wiki_map: Dict[str, str], query_sil: bool = True):
    main_file = STORAGE_JSON_DIR / f"congreso_union_leg_{leg_num}.json"
    if not main_file.exists():
        print(f"[-] Archivo no encontrado: {main_file.name}")
        return

    print(f"\n=======================================================")
    print(f"Procesando Legislatura {leg_num}: {main_file.name}")
    print(f"=======================================================")

    with open(main_file, "r", encoding="utf-8") as f:
        data = json.load(f)

    diputados = data.get("diputados", [])
    senadores = data.get("senadores", [])
    todos = diputados + senadores

    total_politicos = len(todos)
    ya_con_fecha = sum(1 for p in todos if p.get("fecha_nacimiento"))
    actualizados_wiki = 0
    actualizados_sil = 0

    print(f"Total legisladores: {total_politicos} (Previamente con fecha: {ya_con_fecha})")

    # 1. Enriquecer con Wikidata
    for p in todos:
        if p.get("fecha_nacimiento"):
            continue

        nombre = p.get("nombre_completo", "")
        slug = slugify(nombre)
        tokens = nombre.split()
        slug_alt = None
        if len(tokens) >= 3:
            alt_nom = f"{' '.join(tokens[2:])} {tokens[0]} {tokens[1]}"
            slug_alt = slugify(alt_nom)

        dob = wiki_map.get(slug) or (wiki_map.get(slug_alt) if slug_alt else None)
        if dob:
            p["fecha_nacimiento"] = dob
            actualizados_wiki += 1

    print(f"Actualizados via Wikidata: {actualizados_wiki}")

    # 2. Enriquecer con SIL SEGOB los que aún faltan
    if query_sil:
        faltantes_sil = [p for p in todos if not p.get("fecha_nacimiento") and p.get("referencia_sil")]
        print(f"Consultando SIL SEGOB para {len(faltantes_sil)} legisladores faltantes...")

        for idx, p in enumerate(faltantes_sil, 1):
            ref = str(p.get("referencia_sil"))
            dob_sil = fetch_sil_birthdate(ref)
            if dob_sil:
                p["fecha_nacimiento"] = dob_sil
                actualizados_sil += 1
                print(f"  [{idx}/{len(faltantes_sil)}] SIL {ref} ({p.get('nombre_completo')}): {dob_sil}")
            else:
                print(f"  [{idx}/{len(faltantes_sil)}] SIL {ref} ({p.get('nombre_completo')}): No disponible en SIL")
            
            # Respetar servidor con pausa corta
            time.sleep(0.3)

    total_final = sum(1 for p in todos if p.get("fecha_nacimiento"))
    print(f"Resultado final Legislatura {leg_num}: {total_final}/{total_politicos} con fecha ({total_final/total_politicos*100:.1f}%)")

    # Guardar archivo actualizado
    with open(main_file, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=4)
    print(f"[OK] Guardado {main_file.name}")

    # Replicar a los archivos por entidad si existen
    entidad_files = list(STORAGE_JSON_DIR.glob(f"legisladores_entidad_*_leg_{leg_num}.json"))
    if entidad_files:
        # Construir mapa de fechas por referencia_sil y nombre
        fechas_map = {}
        for p in todos:
            if p.get("fecha_nacimiento"):
                fechas_map[p.get("referencia_sil")] = p.get("fecha_nacimiento")
                fechas_map[slugify(p.get("nombre_completo"))] = p.get("fecha_nacimiento")

        for ef in entidad_files:
            try:
                with open(ef, "r", encoding="utf-8") as f:
                    edata = json.load(f)
                modificado = False
                for ep in edata.get("diputados", []) + edata.get("senadores", []) + edata.get("legisladores", []):
                    if not ep.get("fecha_nacimiento"):
                        f_dob = fechas_map.get(ep.get("referencia_sil")) or fechas_map.get(slugify(ep.get("nombre_completo")))
                        if f_dob:
                            ep["fecha_nacimiento"] = f_dob
                            modificado = True
                if modificado:
                    with open(ef, "w", encoding="utf-8") as f:
                        json.dump(edata, f, ensure_ascii=False, indent=4)
            except Exception:
                pass
        print(f"[OK] Sincronizados {len(entidad_files)} archivos estatales de la leg {leg_num}.")

def main():
    wiki_map = get_wikidata_dob_map()

    # Procesar la LXVI Legislatura (actual en funciones) con Wikidata + SIL
    process_and_enrich_legislatura(66, wiki_map, query_sil=True)

    # Procesar legislaturas históricas (LXV, LXIV) con Wikidata
    for leg in [65, 64]:
        process_and_enrich_legislatura(leg, wiki_map, query_sil=False)

    print("\n>>> Proceso completado exitosamente.")

if __name__ == "__main__":
    main()
