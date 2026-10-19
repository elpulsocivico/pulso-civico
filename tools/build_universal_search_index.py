"""
Script para generar el índice consolidado de búsqueda universal:
- Legisladores Federales (todas las legislaturas del Congreso de la Unión)
- Diputados Locales (los 32 Congresos Estatales de la República)
- Poder Ejecutivo Estatal (Gobernadores de los 32 Estados)
- Presidencias Municipales (2,474 Alcaldías de México)
- Comisiones Legislativas
"""
import glob
import os
import re
import time
import unicodedata
from pathlib import Path
import orjson

def slugify(text) -> str:
    if text is None:
        return ""
    text = str(text)
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text.lower()).strip()
    return re.sub(r"[-\s]+", "-", text)

def build_universal_index():
    base_dir = Path(__file__).resolve().parent.parent
    json_dir = base_dir / "storage" / "data" / "json"
    
    t0 = time.time()
    entries = []
    seen = set()

    # 1. Federales (Congreso de la Unión: todas las legislaturas 66 a 57)
    fed_files = sorted(glob.glob(str(json_dir / "congreso_union_leg_*.json")), reverse=True)
    for f in fed_files:
        leg_match = re.search(r"leg_(\d+)", f)
        leg_num = int(leg_match.group(1)) if leg_match else 66
        try:
            with open(f, "rb") as fp:
                data = orjson.loads(fp.read())
        except Exception:
            continue
        
        for p in data.get("diputados", []) + data.get("senadores", []):
            nom = (p.get("nombre_completo") or p.get("titular_nombre") or "").strip()
            if not nom:
                continue
            cam = "Senador(a)" if "Senad" in (p.get("camara") or "") else "Diputado(a) Federal"
            ent = p.get("entidad") or "México"
            part = p.get("partido") or "Sin Partido"
            slug = p.get("slug") or slugify(nom)
            
            key = f"fed_{slug}"
            if key not in seen:
                seen.add(key)
                cargo_badge = f"{cam} • Leg. {leg_num}"
                entries.append({
                    "nombre": nom,
                    "slug": slug,
                    "cargo": cargo_badge,
                    "camara": cam,
                    "partido": part,
                    "entidad": ent,
                    "ambito": "Federal",
                    "url": f"/politicos/{slug}",
                    "foto_url": p.get("foto_oficial_url") or "",
                    "search": f"{slug} {slugify(nom)} {slugify(part)} {slugify(ent)} {slugify(cam)} federal"
                })

    # 2. Locales (Congresos Estatales: 32 entidades)
    local_files = glob.glob(str(json_dir / "congreso_*.json"))
    for f in local_files:
        if "congreso_union" in f:
            continue
        leg_match = re.search(r"leg_(\d+)", f)
        leg_num = leg_match.group(1) if leg_match else ""
        try:
            with open(f, "rb") as fp:
                data = orjson.loads(fp.read())
        except Exception:
            continue
        
        ent = data.get("entidad") or "Estado"
        for p in (data.get("diputados", []) or data.get("legisladores", [])):
            raw_nom = (p.get("nombre_completo") or "").strip()
            nom = re.sub(r"^(Dip\.|Diputada|Diputado)\s+", "", raw_nom).strip()
            if not nom:
                continue
            p_ent = p.get("entidad") or ent
            part = p.get("partido") or "Sin Partido"
            slug = p.get("slug") or slugify(nom)
            distrito = p.get("distrito") or ""
            
            key = f"loc_{slug}_{slugify(p_ent)}"
            fed_key = f"fed_{slug}"
            if key not in seen and fed_key not in seen:
                seen.add(key)
                leg_label = f" ({leg_num} Leg.)" if leg_num else ""
                entries.append({
                    "nombre": nom,
                    "slug": slug,
                    "cargo": f"Diputado(a) Local{leg_label}",
                    "camara": f"Congreso de {p_ent}",
                    "partido": part,
                    "entidad": p_ent,
                    "municipio": distrito,
                    "ambito": "Estatal",
                    "url": f"/politicos/{slug}",
                    "foto_url": p.get("foto_oficial_url") or p.get("foto_url") or "",
                    "search": f"{slug} {slugify(nom)} {slugify(part)} {slugify(p_ent)} {slugify(distrito)} diputado local legislador congreso"
                })

    # 3. Gobernadores (Poder Ejecutivo Estatal)
    ejec_files = glob.glob(str(json_dir / "ejecutivo" / "*.json"))
    for f in ejec_files:
        try:
            with open(f, "rb") as fp:
                data = orjson.loads(fp.read())
        except Exception:
            continue
        ent = data.get("entidad") or "Estado"
        for m in data.get("mandatarios", []):
            nom = (m.get("nombre_completo") or "").strip()
            if not nom:
                continue
            part = m.get("partido") or "Sin Partido"
            per = m.get("periodo") or ""
            slug = slugify(nom)
            key = f"gob_{slug}"
            fed_key = f"fed_{slug}"
            if key not in seen and fed_key not in seen:
                seen.add(key)
                entries.append({
                    "nombre": nom,
                    "slug": slug,
                    "cargo": f"Gobernador(a) ({per})" if per else "Gobernador(a)",
                    "camara": "Poder Ejecutivo Estatal",
                    "partido": part,
                    "entidad": ent,
                    "ambito": "Estatal",
                    "url": f"/politicos/{slug}",
                    "foto_url": m.get("foto_url") or "",
                    "search": f"{slug} {slugify(nom)} {slugify(part)} {slugify(ent)} gobernador gobernadora ejecutivo"
                })

    # 4. Alcaldes (Presidencias Municipales 2024-2027)
    alc_file = json_dir / "alcaldes" / "directorio_nacional_alcaldes_vigentes.json"
    if alc_file.exists():
        try:
            with open(alc_file, "rb") as fp:
                data = orjson.loads(fp.read())
            for cve, alc in data.get("alcaldes", {}).items():
                nom = (alc.get("nombre_completo") or "").strip()
                if not nom:
                    continue
                mun = alc.get("municipio") or ""
                ent = alc.get("entidad") or ""
                part = alc.get("partido") or "Sin Partido"
                slug = slugify(nom)
                key = f"alc_{slug}"
                fed_key = f"fed_{slug}"
                if key not in seen and fed_key not in seen:
                    seen.add(key)
                    entries.append({
                        "nombre": nom,
                        "slug": slug,
                        "cargo": f"Presidente(a) Municipal • {mun}",
                        "camara": f"Ayuntamiento de {mun}",
                        "partido": part,
                        "entidad": f"{mun}, {ent}",
                        "municipio": mun,
                        "ambito": "Municipal",
                        "url": f"/politicos/{slug}",
                        "foto_url": alc.get("foto_url") or "",
                        "search": f"{slug} {slugify(nom)} {slugify(part)} {slugify(mun)} {slugify(ent)} alcalde alcaldesa presidente municipal ayuntamiento"
                    })
        except Exception as e:
            print("Error cargando alcaldes:", e)

    out_file = json_dir / "indice_busqueda_universal.json"
    with open(out_file, "wb") as fp:
        fp.write(orjson.dumps(entries))

    print(f"Indice universal generado con exito: {len(entries)} figuras publicas en {time.time()-t0:.2f}s")
    print(f"Archivo guardado en: {out_file}")

if __name__ == "__main__":
    build_universal_index()
