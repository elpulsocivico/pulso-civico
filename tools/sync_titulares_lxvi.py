"""
Script integral para sincronizar e indexar a todos los legisladores federales titulares
de la LXVI Legislatura (2024-2027) que cuentan con suplente en funciones o licencia.
Descarga y enriquece expedientes oficiales del SIL SEGOB respetando rate limits.
"""

import os
import sys
import time
import json
import re
import html
import unicodedata
import urllib.request
import urllib.parse
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
JSON_DIR = BASE_DIR / "storage" / "data" / "json"
RAW_HTML_DIR = BASE_DIR / "storage" / "raw" / "html"
RAW_PHOTOS_DIR = BASE_DIR / "storage" / "raw" / "photos"

RAW_HTML_DIR.mkdir(parents=True, exist_ok=True)
RAW_PHOTOS_DIR.mkdir(parents=True, exist_ok=True)

ENTIDADES_MAP = {
    "aguascalientes": 1,
    "baja california": 2, "baja-california": 2,
    "baja california sur": 3, "baja-california-sur": 3,
    "campeche": 4,
    "coahuila": 5, "coahuila-de-zaragoza": 5,
    "colima": 6,
    "chiapas": 7,
    "chihuahua": 8,
    "ciudad de mexico": 9, "ciudad-de-mexico": 9, "distrito federal": 9, "distrito-federal": 9, "cdmx": 9,
    "durango": 10,
    "guanajuato": 11,
    "guerrero": 12,
    "hidalgo": 13,
    "jalisco": 14,
    "mexico": 15, "estado de mexico": 15, "estado-de-mexico": 15, "edomex": 15,
    "michoacan": 16, "michoacan-de-ocampo": 16,
    "morelos": 17,
    "nayarit": 18,
    "nuevo leon": 19, "nuevo-leon": 19,
    "oaxaca": 20,
    "puebla": 21,
    "queretaro": 22,
    "quintana roo": 23, "quintana-roo": 23,
    "san luis potosi": 24, "san-luis-potosi": 24,
    "sinaloa": 25,
    "sonora": 26,
    "tabasco": 27,
    "tamaulipas": 28,
    "tlaxcala": 29,
    "veracruz": 30, "veracruz-de-ignacio-de-la-llave": 30,
    "yucatan": 31,
    "zacatecas": 32
}

def slugify(text: str) -> str:
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", str(text)).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text.lower()).strip()
    return re.sub(r"[-\s]+", "-", text)

def clean_html(text: str) -> str:
    if not text:
        return ""
    text = re.sub(r"<[^>]+>", " ", text)
    text = html.unescape(text)
    return re.sub(r"\s+", " ", text).strip()

def ordenar_apellidos_nombres(nombre_natural: str) -> str:
    """Invierte 'Nombres Apellidos' a 'Apellidos Nombres' de forma estándar respetando apellidos compuestos."""
    n = clean_html(nombre_natural)
    n = re.sub(r"^(?:Diputad[ao]|Senador[a]?|Lic\.|Dr\.|Dra\.|Ing\.|Mtro\.|Mtra\.)\s+", "", n, flags=re.I).strip()
    casos_especiales = {
        "Ifigenia Martha Martínez y Hernández": "Martínez y Hernández Ifigenia Martha",
        "Juan Hugo De la Rosa García": "De la Rosa García Juan Hugo",
        "María de los Ángeles Ballesteros García": "Ballesteros García María de los Ángeles",
        "María del Carmen Pinete Vargas": "Pinete Vargas María del Carmen",
        "Verónica del Carmen Díaz Robles": "Díaz Robles Verónica del Carmen",
    }
    if n in casos_especiales:
        return casos_especiales[n]
    partes = n.split()
    if len(partes) == 3:
        return f"{partes[1]} {partes[2]} {partes[0]}"
    elif len(partes) == 4:
        return f"{partes[2]} {partes[3]} {partes[0]} {partes[1]}"
    elif len(partes) == 5:
        return f"{partes[3]} {partes[4]} {partes[0]} {partes[1]} {partes[2]}"
    elif len(partes) >= 6:
        return f"{partes[-2]} {partes[-1]} {' '.join(partes[:-2])}"
    return n

def descargar_perfil_sil(ref: str) -> str:
    html_file = RAW_HTML_DIR / f"profile_{ref}.html"
    if html_file.exists() and html_file.stat().st_size > 1000:
        return html_file.read_text(encoding="latin1", errors="replace")

    url = f"http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia={ref}"
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) ElPulsoCivico/1.0",
            "Accept": "text/html,application/xhtml+xml",
        }
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            content = resp.read().decode("latin1", errors="replace")
            if len(content) > 500:
                html_file.write_text(content, encoding="latin1")
                # Respetar rate limit oficial
                time.sleep(1.0)
                return content
    except Exception as e:
        print(f"  [!] Error descargando SIL {ref}: {e}")
    return ""

def descargar_foto_sil(ref: str):
    photo_file = RAW_PHOTOS_DIR / f"{ref}.jpg"
    if photo_file.exists() and photo_file.stat().st_size > 500:
        return str(photo_file)

    url = f"http://sil.gobernacion.gob.mx/Archivos/Fotos/{ref}.jpg"
    req = urllib.request.Request(url, headers={"User-Agent": "ElPulsoCivico/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = resp.read()
            if len(data) > 500:
                photo_file.write_bytes(data)
                return str(photo_file)
    except Exception:
        pass
    return None

def parsear_perfil_sil(ref: str, raw_html: str, suplente_info: dict) -> dict:
    if not raw_html:
        return {}

    # Nombre
    m_name = re.search(r"<b>(?:Diputad[ao]|Senador[a]?)\s+(.*?)</b>", raw_html, re.I)
    nombre_raw = clean_html(m_name.group(1)) if m_name else suplente_info.get("titular_nombre", "")
    nombre_raw = re.sub(r"^[-–—\s]+$", "", nombre_raw).strip()
    if not re.search(r"[a-zA-ZáéíóúñÁÉÍÓÚÑ]", nombre_raw):
        return {}

    nombre_ordenado = ordenar_apellidos_nombres(nombre_raw)

    # Cámara
    camara = "Senadores" if ("Senador" in raw_html[:2000] and "Diputad" not in raw_html[:2000]) or suplente_info.get("camara") == "Senadores" else "Diputados"

    # Partido
    m_part = re.search(r"Partido:</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    partido = clean_html(m_part.group(1)) if m_part else suplente_info.get("partido", "Morena")

    # Entidad de Elección
    # Buscar bloque de principio de elección / entidad
    m_ent_elec = re.search(r"Entidad:\s*([^\r\n<|]+)", raw_html, re.I)
    entidad = clean_html(m_ent_elec.group(1)) if m_ent_elec else suplente_info.get("entidad", "México")

    # Principio de Elección
    m_princ = re.search(r"Principio de elecci[oó]n:</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    principio = clean_html(m_princ.group(1)) if m_princ else suplente_info.get("principio_eleccion", "Mayoría Relativa")

    # Estudios
    m_grado = re.search(r"<b>\s*[ÚU]ltimo grado de estudios:\s*</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    ultimo_grado = clean_html(m_grado.group(1)) if m_grado else None
    if ultimo_grado and ultimo_grado.lower() in ("no disponible", "sin registro", "n/a"):
        ultimo_grado = None

    m_prep = re.search(r"<b>\s*Preparaci[oó]n acad[eé]mica:\s*</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    preparacion = clean_html(m_prep.group(1)) if m_prep else None

    # Nacimiento
    m_nac = re.search(r"<b>\s*Nacimiento:\s*</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    fecha_nacimiento = clean_html(m_nac.group(1)) if m_nac else None

    # Correo y teléfono
    m_correo = re.search(r"<b>\s*Correo electr[oó]nico:\s*</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    correo = clean_html(m_correo.group(1)) if m_correo else None

    m_tel = re.search(r"<b>\s*Tel[eé]fono:\s*</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    telefono = clean_html(m_tel.group(1)) if m_tel else None

    # Ubicación oficina
    m_ubi = re.search(r"<b>\s*Ubicaci[oó]n:\s*</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    ubicacion = clean_html(m_ubi.group(1)) if m_ubi else ("Palacio Legislativo de San Lázaro" if camara == "Diputados" else "Senado de la República")

    # Suplente
    m_sup = re.search(r"<b>\s*Suplente:\s*</b>\s*</td>\s*<td[^>]*>(.*?)</td>", raw_html, re.I)
    suplente_nom = clean_html(m_sup.group(1)) if m_sup else suplente_info.get("suplente_nombre", None)

    # Trayectoria legislativa y administrativa
    trayectoria_legislativa = []
    trayectoria_politica = []
    trayectoria_admin = []
    escolaridad_detalle = []

    # Extraer secciones
    sections = re.findall(r"<td[^>]*class=[\"'](?:sub)?titulos[\"'][^>]*>.*?<b>(.*?)</b>.*?</td>\s*</tr>\s*<tr>\s*<td[^>]*>\s*<table[^>]*>(.*?)</table>", raw_html, re.I | re.S)
    for title_raw, tbl in sections:
        sec_title = clean_html(title_raw).upper()
        rows = re.findall(r"<tr[^>]*>(.*?)</tr>", tbl, re.I | re.S)
        for r in rows:
            cells = [clean_html(c) for c in re.findall(r"<td[^>]*>(.*?)</td>", r, re.I | re.S)]
            if not cells or not any(cells):
                continue
            
            exp_text = " — ".join([c for c in cells if c])
            item = {"del_ano": None, "al_ano": None, "experiencia": exp_text}
            
            if "LEGISLATIVA" in sec_title:
                trayectoria_legislativa.append(item)
            elif "POLÍTICA" in sec_title or "POLITICA" in sec_title:
                trayectoria_politica.append(item)
            elif "ADMINISTRATIVA" in sec_title or "PÚBLICA" in sec_title:
                trayectoria_admin.append(item)
            elif "ESCOLARIDAD" in sec_title or "ACADÉMICA" in sec_title or "ACADEMICA" in sec_title:
                escolaridad_detalle.append(item)

    # Si hay preparación académica o grado pero no escolaridad detalle
    if not escolaridad_detalle and (ultimo_grado or preparacion):
        texto_estudios = f"{ultimo_grado or ''} en {preparacion or ''}".strip()
        if texto_estudios.startswith("en "):
            texto_estudios = texto_estudios[3:]
        escolaridad_detalle.append({"del_ano": None, "al_ano": None, "experiencia": texto_estudios})

    # Asegurar comisiones
    comisiones = []
    m_coms = re.findall(r"pp_Comisiones\.php\?[^']*'[^>]*>(.*?)</a>", raw_html, re.I)
    for com_nom in m_coms:
        c_clean = clean_html(com_nom)
        if c_clean:
            comisiones.append({
                "comision": c_clean,
                "puesto": "Integrante",
                "fecha_inicio": "01/09/2024",
                "fecha_fin": "31/08/2027",
                "estatus": "Activo"
            })

    photo_path = descargar_foto_sil(ref)

    return {
        "referencia_sil": ref,
        "nombre_completo": nombre_ordenado,
        "camara": camara,
        "legislatura": 66,
        "partido": partido,
        "principio_eleccion": principio,
        "entidad": entidad,
        "distrito": None,
        "tipo_cargo": "Titular (Con Licencia)",
        "suplente_nombre": suplente_nom,
        "fecha_toma_protesta": "29/08/2024",
        "fecha_nacimiento": fecha_nacimiento,
        "correo_institucional": correo,
        "telefono": telefono,
        "ubicacion_oficina": ubicacion,
        "ultimo_grado_estudios": ultimo_grado,
        "preparacion_academica": preparacion,
        "experiencia_legislativa_resumen": None,
        "foto_oficial_url": f"http://sil.gobernacion.gob.mx/Archivos/Fotos/{ref}.jpg",
        "foto_local_path": photo_path,
        "total_comisiones": len(comisiones),
        "comisiones": comisiones,
        "trayectoria_politica": trayectoria_politica,
        "trayectoria_legislativa": trayectoria_legislativa,
        "trayectoria_administrativa": trayectoria_admin,
        "escolaridad_detalle": escolaridad_detalle,
        "licencias": [],
        "iniciativas": [],
        "puntos_acuerdo": [],
        "redes_sociales": [],
        "metadata": {
            "fuente": "SIL SEGOB",
            "titular_referencia_sil": None,
            "titular_nombre": None,
            "suplente_referencia_sil": suplente_info.get("suplente_ref"),
            "estatus_licencia": "Con Licencia",
            "fecha_extraccion": time.strftime("%Y-%m-%d %H:%M:%S")
        }
    }

def main():
    print("=" * 60)
    print("SINCRONIZADOR MASIVO DE LEGISLADORES TITULARES (LXVI LEGISLATURA)")
    print("=" * 60)

    fed_file = JSON_DIR / "congreso_union_leg_66.json"
    if not fed_file.exists():
        print(f"Error: No existe {fed_file}")
        sys.exit(1)

    with open(fed_file, "r", encoding="utf-8") as f:
        fed_data = json.load(f)

    all_suplentes = []
    for item in fed_data.get("diputados", []) + fed_data.get("senadores", []):
        meta = item.get("metadata", {})
        t_ref = str(meta.get("titular_referencia_sil") or "").strip()
        t_nom = str(meta.get("titular_nombre") or "").strip()
        if t_ref and re.match(r"^\d+$", t_ref):
            all_suplentes.append({
                "titular_ref": t_ref,
                "titular_nombre": t_nom,
                "suplente_ref": item.get("referencia_sil"),
                "suplente_nombre": item.get("nombre_completo"),
                "camara": item.get("camara"),
                "entidad": item.get("entidad"),
                "partido": item.get("partido"),
                "principio_eleccion": item.get("principio_eleccion")
            })

    print(f"Total de suplencias registradas con referencia de titular: {len(all_suplentes)}")

    # Agrupar por titular_ref único
    titulares_dict = {}
    for s in all_suplentes:
        titulares_dict[s["titular_ref"]] = s

    # Buscar existentes
    existing_refs = set()
    for d in fed_data.get("diputados", []):
        if d.get("referencia_sil"):
            existing_refs.add(str(d["referencia_sil"]))
    for s in fed_data.get("senadores", []):
        if s.get("referencia_sil"):
            existing_refs.add(str(s["referencia_sil"]))

    ingestados = 0
    actualizados = 0

    for ref, info in titulares_dict.items():
        nom_prelim = info.get("titular_nombre") or f"Titular {ref}"
        print(f"Procesando titular SIL {ref} ({nom_prelim})...")

        raw_html = descargar_perfil_sil(ref)
        perfil = parsear_perfil_sil(ref, raw_html, info)
        if not perfil or not perfil.get("nombre_completo"):
            print(f"  [!] Omitido: Nombre vacío o referencia inválida en SIL ({ref})")
            continue

        camara_key = "senadores" if perfil["camara"] == "Senadores" else "diputados"
        lista = fed_data[camara_key]

        # Verificar si ya existe en la lista
        idx_existente = -1
        for i, leg in enumerate(lista):
            if str(leg.get("referencia_sil")) == str(ref):
                idx_existente = i
                break

        if idx_existente >= 0:
            lista[idx_existente] = perfil
            actualizados += 1
            print(f"  [OK] Actualizado perfil: {perfil['nombre_completo']} ({perfil['partido']} - {perfil['entidad']})")
        else:
            lista.append(perfil)
            ingestados += 1
            print(f"  [+] Ingestado nuevo titular: {perfil['nombre_completo']} ({perfil['partido']} - {perfil['entidad']})")

        # También sincronizar en el archivo de entidad federativa correspondiente
        entidad_slug = slugify(perfil["entidad"])
        ent_id = ENTIDADES_MAP.get(entidad_slug)
        if ent_id:
            ent_file = JSON_DIR / f"legisladores_entidad_{ent_id}_leg_66.json"
            if ent_file.exists():
                try:
                    with open(ent_file, "r", encoding="utf-8") as ef:
                        ent_data = json.load(ef)
                    e_lista = ent_data.get(camara_key, [])
                    e_idx = -1
                    for ei, eleg in enumerate(e_lista):
                        if str(eleg.get("referencia_sil")) == str(ref):
                            e_idx = ei
                            break
                    if e_idx >= 0:
                        e_lista[e_idx] = perfil
                    else:
                        e_lista.append(perfil)
                    ent_data[camara_key] = e_lista
                    with open(ent_file, "w", encoding="utf-8") as ef:
                        json.dump(ent_data, ef, ensure_ascii=False, indent=4)
                except Exception as ex:
                    print(f"  [!] Error actualizando archivo de entidad {ent_id}: {ex}")

    # Actualizar metadata del archivo consolidado
    fed_data["metadata"]["total_diputados"] = len(fed_data.get("diputados", []))
    fed_data["metadata"]["total_senadores"] = len(fed_data.get("senadores", []))
    fed_data["metadata"]["total_general"] = fed_data["metadata"]["total_diputados"] + fed_data["metadata"]["total_senadores"]
    fed_data["metadata"]["fecha_sincronizacion_titulares"] = time.strftime("%Y-%m-%d %H:%M:%S")

    with open(fed_file, "w", encoding="utf-8") as f:
        json.dump(fed_data, f, ensure_ascii=False, indent=4)

    print("=" * 60)
    print(f"Sincronización completada con éxito.")
    print(f"Nuevos titulares ingestados: {ingestados}")
    print(f"Titulares actualizados: {actualizados}")
    print(f"Total actual en LXVI: {fed_data['metadata']['total_diputados']} Diputados, {fed_data['metadata']['total_senadores']} Senadores (Total: {fed_data['metadata']['total_general']})")
    print("=" * 60)

if __name__ == "__main__":
    main()
