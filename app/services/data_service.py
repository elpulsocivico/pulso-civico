import re
import unicodedata
from pathlib import Path
from typing import List, Dict, Any, Optional
from datetime import date
from collections import defaultdict, Counter
import json
import orjson
from app.core.config import settings
from app.services.education_service import education_service

def slugify(text: str) -> str:
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text.lower()).strip()
    return re.sub(r"[-\s]+", "-", text)

def normalizar_entidad(entidad: str) -> str:
    """Normaliza nombres de estados resolviendo variantes oficiales e históricas."""
    if not entidad:
        return ""
    norm = slugify(entidad)
    if "coahuila" in norm: return "coahuila"
    if "michoacan" in norm: return "michoacan"
    if "veracruz" in norm: return "veracruz"
    if "nuevo-leon" in norm or "nuevoleon" in norm: return "nuevo-leon"
    if "san-luis" in norm: return "san-luis-potosi"
    if "quintana" in norm or "qroo" in norm: return "quintana-roo"
    if "baja-california-sur" in norm or norm in ("bcs", "baja-sur"): return "baja-california-sur"
    if "baja-california" in norm or norm in ("bc", "baja"): return "baja-california"
    if norm in ("distrito-federal", "df", "cdmx", "ciudad-de-mexico"):
        return "ciudad-de-mexico"
    if norm in ("mexico", "edomex", "estado-de-mexico") or ("mexico" in norm and "ciudad" not in norm):
        return "estado-de-mexico"
    return norm

def get_party_color(partido: str) -> str:
    if not partido:
        return "#6C757D"
    upper = partido.upper()
    if "MORENA" in upper: return "#8B1C3F"
    if "PAN" in upper and "PANAL" not in upper: return "#003893"
    if "VERDE" in upper or "PVEM" in upper: return "#009E49"
    if "PRI" in upper: return "#C8102E"
    if "PT" in upper or "TRABAJO" in upper: return "#DA291C"
    if "MC" in upper or "CIUDADANO" in upper or "CONVERGENCIA" in upper or "CD-PPN" in upper or "CD" in upper: return "#FF8300"
    if "PSN" in upper or "SOCIEDAD NACIONALISTA" in upper: return "#1E3A8A"
    if "PASC" in upper or "PAS" in upper or "ALTERNATIVA" in upper or "ALIANZA SOCIAL" in upper: return "#059669"
    if "PES" in upper: return "#582C83"
    if "PRD" in upper: return "#FFD100"
    if "PANAL" in upper or "NUEVA ALIANZA" in upper: return "#00A896"
    if "UYC" in upper or "USOS Y COSTUMBRES" in upper or "NORMATIVOS" in upper: return "#475569"
    if "INDEPENDIENTE" in upper or "SIN PARTIDO" in upper or upper in ("INDEP", "INDEP1", "IND.", "IND", "CANDIDATO INDEPENDIENTE"): return "#64748B"
    if "CONCEJO" in upper or "LITIGIO" in upper: return "#94A3B8"
    return "#6C757D"

def get_canonical_party_name(partido: str) -> str:
    p = (partido or "").strip()
    upper = p.upper()
    if not upper or upper in ("SIN PARTIDO", "INDEPENDIENTE", "INDEP", "INDEP1", "IND.", "IND", "CANDIDATO INDEPENDIENTE"):
        return "Independiente"
    if "UYC" in upper or "USOS Y COSTUMBRES" in upper or "NORMAS INDIGENAS" in upper:
        return "Usos y Costumbres (SNI)"
    if "CONCEJO MUNICIPAL" in upper:
        return "Concejo Municipal"
    if "LITIGIO" in upper:
        return "En Litigio Electoral"
    if "MORENA" in upper: return "Morena"
    if "PAN" in upper and "PANAL" not in upper: return "PAN"
    if "VERDE" in upper or "PVEM" in upper: return "PVEM"
    if "PRI" in upper: return "PRI"
    if "PT" in upper or "TRABAJO" in upper: return "PT"
    if "CD-PPN" in upper or "CONVERGENCIA" in upper: return "Convergencia"
    if "MC" in upper or "CIUDADANO" in upper: return "Movimiento Ciudadano"
    if "PSN" in upper: return "PSN"
    if "PASC" in upper or "ALTERNATIVA SOCIALDEMOCRATA" in upper: return "PASC"
    if "PAS" in upper or "ALIANZA SOCIAL" in upper: return "PAS"
    if "PES" in upper: return "PES"
    if "PRD" in upper: return "PRD"
    if "PANAL" in upper or "NUEVA ALIANZA" in upper: return "Nueva Alianza"
    return p

def get_party_micro_icon(partido: str) -> str:
    canonical = get_canonical_party_name(partido)
    if canonical == "Morena":
        return '<svg class="party-icon" viewBox="0 0 30 20" width="16" height="11" fill="none"><rect width="30" height="20" rx="3" fill="#8B1C3F"/><text x="15" y="14" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.3">morena</text></svg>'
    elif canonical == "PAN":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#003893"/><circle cx="10" cy="10" r="7" fill="#FFFFFF"/><text x="10" y="12.5" fill="#003893" font-size="6.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.4">PAN</text></svg>'
    elif canonical == "PRI":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect x="0" y="0" width="6.6" height="20" rx="2" fill="#008037"/><rect x="6.6" y="0" width="6.8" height="20" fill="#FFFFFF"/><rect x="13.4" y="0" width="6.6" height="20" rx="2" fill="#C8102E"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRI</text></svg>'
    elif canonical == "PRD":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#FFD100"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRD</text></svg>'
    elif canonical == "PVEM":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#009E49"/><path d="M10 3 C13 6 16 9 16 13 C16 16.5 13 18.5 10 18.5 C7 18.5 4 16.5 4 13 C4 9 7 6 10 3 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="12.5" fill="#FFFFFF" font-size="5.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PVEM</text></svg>'
    elif canonical == "PT":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#DA291C"/><polygon points="10,2 11.5,6.5 16,6.5 12.5,9.5 14,14 10,11.5 6,14 7.5,9.5 4,6.5 8.5,6.5" fill="#FFCC00" fill-opacity="0.4"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PT</text></svg>'
    elif canonical == "Movimiento Ciudadano":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#FF8300"/><path d="M3 10 Q10 5 17 10 Q10 14 3 10 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">MC</text></svg>'
    elif canonical == "PES":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#582C83"/><text x="10" y="12.5" fill="#FFFFFF" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PES</text></svg>'
    elif canonical == "Nueva Alianza":
        return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#00A896"/><text x="10" y="12.5" fill="#FFFFFF" font-size="5.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">NA</text></svg>'
    return '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#6C757D"/><circle cx="10" cy="8" r="3.5" fill="#FFFFFF"/><path d="M5 18 C5 14.5 7.5 13 10 13 C12.5 13 15 14.5 15 18 Z" fill="#FFFFFF"/></svg>'

def detect_party_from_text(text: str) -> Optional[str]:
    t = (text or "").strip()
    if not t:
        return None
    import re
    if re.search(r'\b(MORENA|Movimiento\s+de\s+Regeneraci[oó]n\s+Nacional|Regeneraci[oó]n\s+Nacional)\b', t, re.IGNORECASE):
        return "Morena"
    if re.search(r'\b(P\.?R\.?I\.?|Partido\s+Revolucionario\s+Institucional|Revolucionario\s+Institucional|C\.?N\.?O\.?P\.?|Confederaci[oó]n\s+Nacional\s+de\s+Organizaciones\s+Populares|C\.?T\.?M\.?|C\.?N\.?C\.?)\b', t) or re.search(r'\b(comit[eé]\s+directivo\s+(estatal|municipal|nacional)\s+del\s+pri|consejero\s+pol[ií]tico\s+.*del\s+pri|candidat[oa]\s+del\s+pri|militante\s+del\s+pri|bancada\s+del\s+pri|fracci[oó]n\s+.*del\s+pri)\b', t, re.IGNORECASE):
        return "PRI"
    if re.search(r'\b(Partido\s+Acci[oó]n\s+Nacional|Acci[oó]n\s+Nacional)\b', t, re.IGNORECASE) or re.search(r'\b(P\.?A\.?N\.?)\b', t) or re.search(r'\b(comit[eé]\s+directivo\s+.*del\s+pan|consejero\s+pol[ií]tico\s+.*del\s+pan|candidat[oa]\s+del\s+pan|militante\s+del\s+pan|bancada\s+del\s+pan|fracci[oó]n\s+.*del\s+pan)\b', t, re.IGNORECASE):
        return "PAN"
    if re.search(r'\b(P\.?R\.?D\.?|Partido\s+de\s+la\s+Revoluci[oó]n\s+Democr[aá]tica|Revoluci[oó]n\s+Democr[aá]tica)\b', t, re.IGNORECASE):
        return "PRD"
    if re.search(r'\b(P\.?V\.?E\.?M\.?|Partido\s+Verde\s+Ecologista(\s+de\s+M[eé]xico)?|Partido\s+Verde|Verde\s+Ecologista)\b', t, re.IGNORECASE):
        return "PVEM"
    if re.search(r'\b(Partido\s+del\s+Trabajo)\b', t, re.IGNORECASE) or re.search(r'\b(P\.?T\.?)\b', t):
        return "PT"
    if re.search(r'\b(Movimiento\s+Ciudadano|Partido\s+Convergencia|Convergencia\s+por\s+la\s+Democracia|\bConvergencia\b)\b', t, re.IGNORECASE) or re.search(r'\b(M\.?C\.?)\b', t):
        return "Movimiento Ciudadano"
    if re.search(r'\b(P\.?E\.?S\.?|Partido\s+Encuentro\s+Social|Partido\s+Encuentro\s+Solidario|Encuentro\s+Social|Encuentro\s+Solidario)\b', t, re.IGNORECASE):
        return "PES"
    if re.search(r'\b(Nueva\s+Alianza|P\.?A\.?N\.?A\.?L\.?)\b', t, re.IGNORECASE):
        return "Nueva Alianza"
    if re.search(r'\b(Candidatura\s+Independiente|Candidato\s+Independiente|Sin\s+Partido)\b', t, re.IGNORECASE):
        return "Independiente"
    return None

def render_party_timeline_badge(partido: str, es_actual: bool = False) -> str:
    canonical = get_canonical_party_name(partido)
    color = get_party_color(canonical)
    icon = get_party_micro_icon(canonical)
    tag_actual = ' <span class="badge bg-success text-white ms-1" style="font-size: 0.58rem; padding: 1px 4px; font-weight: 700;">Actual</span>' if es_actual else ""
    bg_style = 'background-color: #FFF9DB; color: #856404; border-color: #FFE066 !important;' if canonical == "PRD" else f'background-color: {color}14; color: {color}; border-color: {color}40 !important;'
    return f'<span class="party-timeline-badge d-inline-flex align-items-center gap-1 py-0 px-2 rounded-pill border" style="font-size: 0.68rem; font-weight: 600; line-height: 1.6; {bg_style}" title="Filiación política en este periodo: {canonical}">{icon}<span>{canonical}</span>{tag_actual}</span>'

def render_party_badge(partido: str) -> str:
    p = (partido or "").strip()
    upper = p.upper()
    if "MORENA" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 30 20" width="24" height="16" fill="none"><rect width="30" height="20" rx="4" fill="#8B1C3F"/><text x="15" y="14" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.3">morena</text></svg>'
        label = "Morena"
        bg = "#8B1C3F"
    elif "PAN" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#003893"/><circle cx="10" cy="10" r="7" fill="#FFFFFF"/><text x="10" y="12.5" fill="#003893" font-size="6.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.4">PAN</text></svg>'
        label = "PAN"
        bg = "#003893"
    elif "VERDE" in upper or "PVEM" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#009E49"/><path d="M10 3 C13 6 16 9 16 13 C16 16.5 13 18.5 10 18.5 C7 18.5 4 16.5 4 13 C4 9 7 6 10 3 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="12.5" fill="#FFFFFF" font-size="5.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PVEM</text></svg>'
        label = "PVEM"
        bg = "#009E49"
    elif "PRI" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect x="0" y="0" width="6.6" height="20" fill="#008037"/><rect x="6.6" y="0" width="6.8" height="20" fill="#FFFFFF"/><rect x="13.4" y="0" width="6.6" height="20" fill="#C8102E"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRI</text></svg>'
        label = "PRI"
        bg = "#C8102E"
    elif "PT" in upper or "TRABAJO" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#DA291C"/><polygon points="10,2 11.5,6.5 16,6.5 12.5,9.5 14,14 10,11.5 6,14 7.5,9.5 4,6.5 8.5,6.5" fill="#FFCC00" fill-opacity="0.4"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PT</text></svg>'
        label = "PT"
        bg = "#DA291C"
    elif "MC" in upper or "CIUDADANO" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#FF8300"/><path d="M3 10 Q10 5 17 10 Q10 14 3 10 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">MC</text></svg>'
        label = "Movimiento Ciudadano"
        bg = "#FF8300"
    elif "PES" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#582C83"/><text x="10" y="12.5" fill="#FFFFFF" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PES</text></svg>'
        label = "PES"
        bg = "#582C83"
    elif "PRD" in upper:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#FFD100"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRD</text></svg>'
        label = "PRD"
        bg = "#FFD100"
    else:
        icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#6C757D"/><circle cx="10" cy="8" r="3.5" fill="#FFFFFF"/></svg>'
        label = p or "Independiente"
        bg = "#6C757D"

    return f'<span class="party-badge" title="{label}" data-party-color="{bg}">{icon}<span>{label}</span></span>'

def formatear_nombre_natural(nombre_raw: str) -> str:
    """
    Convierte nombres del formato burocrático oficial SIL (ApellidoPaterno ApellidoMaterno Nombre(s))
    al orden natural ciudadano (Nombre(s) ApellidoPaterno ApellidoMaterno).
    """
    if not nombre_raw:
        return ""
    texto = " ".join(nombre_raw.strip().split())
    tokens = texto.split(" ")
    if len(tokens) <= 2:
        return texto

    particulas = {"de", "del", "de la", "de los", "de las", "san", "santa"}
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
        paterno, materno, nombre = palabras[0], palabras[1], palabras[2]
        return f"{nombre} {paterno} {materno}"
    elif len(palabras) == 4:
        paterno, materno, nom1, nom2 = palabras[0], palabras[1], palabras[2], palabras[3]
        return f"{nom1} {nom2} {paterno} {materno}"
    elif len(palabras) >= 5:
        paterno, materno = palabras[0], palabras[1]
        nombres = " ".join(palabras[2:])
        return f"{nombres} {paterno} {materno}"

    return texto

def calcular_edad(fecha_nac: Optional[str]) -> Optional[int]:
    if not fecha_nac:
        return None
    try:
        s = str(fecha_nac).strip()
        if "/" in s:
            partes = s.split("/")
            if len(partes) == 3:
                d, m, y = int(partes[0]), int(partes[1]), int(partes[2])
                born = date(y, m, d)
                today = date.today()
                return today.year - born.year - ((today.month, today.day) < (born.month, born.day))
        elif "-" in s:
            partes = s.split("-")
            if len(partes) == 3:
                y, m, d = int(partes[0]), int(partes[1]), int(partes[2])
                born = date(y, m, d)
                today = date.today()
                return today.year - born.year - ((today.month, today.day) < (born.month, born.day))
    except Exception:
        pass
    return None

def procesar_trayectoria_y_estudios(pol: Dict[str, Any]) -> Dict[str, Any]:
    escolaridad_map: Dict[str, Dict[str, Any]] = {}
    historial_map: Dict[str, Dict[str, Any]] = {}

    def es_estudio_academico(texto: str) -> bool:
        t = texto.lower()
        keywords = [
            'licenciatura', 'doctorado', 'maestr', 'ingenier', 'diplomado',
            'especialidad en', 'bachillerato', 'primaria', 'secundaria', 'estudios de'
        ]
        return any(k in t for k in keywords)

    # 1. Escolaridad oficial
    for e in pol.get("escolaridad_detalle") or []:
        exp = ""
        if isinstance(e, dict):
            exp = (e.get("experiencia") or e.get("estudio") or e.get("carrera") or "").strip()
        elif isinstance(e, str):
            exp = e.strip()
        if exp:
            norm = exp.lower()
            if norm not in escolaridad_map:
                escolaridad_map[norm] = {"experiencia": exp}

    # 2. Trayectorias
    todas = []
    for k in ["trayectoria_legislativa", "trayectoria_politica", "trayectoria_administrativa"]:
        val = pol.get(k) or []
        if isinstance(val, list):
            todas.extend(val)

    for item in todas:
        exp = ""
        del_ano = None
        al_ano = None
        if isinstance(item, dict):
            exp = (item.get("experiencia") or item.get("cargo") or item.get("descripcion") or "").strip()
            del_ano = item.get("del_ano")
            al_ano = item.get("al_ano")
        elif isinstance(item, str):
            exp = item.strip()

        if not exp:
            continue

        norm = exp.lower()
        if es_estudio_academico(exp):
            if norm not in escolaridad_map:
                escolaridad_map[norm] = {"experiencia": exp}
            continue

        key = f"{norm}_{del_ano or ''}_{al_ano or ''}"
        if key not in historial_map:
            historial_map[key] = {
                "experiencia": exp,
                "del_ano": str(del_ano) if del_ano else None,
                "al_ano": str(al_ano) if al_ano else None,
            }

    historial_filtrado = list(historial_map.values())

    def sort_key(item):
        try:
            return int(item.get("al_ano") or item.get("del_ano") or 0)
        except Exception:
            return 0

    historial_filtrado.sort(key=sort_key, reverse=True)

    for h in historial_filtrado:
        h["es_reciente"] = False
        h["es_cargo_actual"] = False
        d = h.get("del_ano")
        a = h.get("al_ano")
        if d and a:
            h["periodo"] = f"{d} — {a}"
        elif d:
            h["periodo"] = f"Desde {d}"
        elif a:
            h["periodo"] = f"Hasta {a}"
        else:
            h["periodo"] = "Trayectoria previa"

        p_det = detect_party_from_text(h.get("experiencia", ""))
        h["partido"] = p_det
        h["partido_color"] = get_party_color(p_det) if p_det else None

    # Incorporar el cargo parlamentario actual a la línea de tiempo
    camara = pol.get("camara", "Diputados")
    es_senador = (camara == "Senadores")
    leg_num = int(pol.get("legislatura") or 66)
    leg_romano = DataService.LEGISLATURAS_CATALOG.get(leg_num, {}).get("romano", str(leg_num))
    ano_inicio = 2024 if leg_num == 66 else (2021 if leg_num == 65 else 2018)
    ano_fin = ano_inicio + 3

    entidad = pol.get("entidad", "México")
    distrito = pol.get("distrito")
    es_suplente = (pol.get("tipo_cargo") == "Suplente")
    tiene_licencia = bool(pol.get("tiene_licencia"))
    partido_cargo_actual = pol.get("partido") or "Sin Partido"

    cargo_titulo = "Senador de la República" if es_senador else "Diputado Federal"
    if es_suplente:
        cargo_titulo += " (Suplente en Funciones)"
    elif tiene_licencia:
        cargo_titulo += " (Con Licencia)"

    desc_cargo = f"{cargo_titulo} por {entidad}"
    if distrito and "Senaduría" not in str(distrito):
        desc_cargo += f" ({distrito})"
    desc_cargo += f" — {leg_romano} Legislatura del Congreso de la Unión."

    es_actual = (leg_num == 66)
    cargo_actual = {
        "experiencia": desc_cargo,
        "del_ano": str(ano_inicio),
        "al_ano": "Presente" if es_actual else str(ano_fin),
        "periodo": f"{ano_inicio} — Presente" if es_actual else f"{ano_inicio} — {ano_fin}",
        "es_cargo_actual": es_actual,
        "es_reciente": es_actual,
        "en_licencia": tiene_licencia,
        "partido": partido_cargo_actual,
        "partido_color": get_party_color(partido_cargo_actual),
    }

    ya_existe = False
    for hf in historial_filtrado:
        if f"{leg_romano} Legislatura" in hf.get("experiencia", "") or f"{leg_num} Legislatura" in hf.get("experiencia", ""):
            ya_existe = True
            hf["es_cargo_actual"] = es_actual
            hf["es_reciente"] = es_actual
            if not hf.get("partido"):
                hf["partido"] = partido_cargo_actual
                hf["partido_color"] = get_party_color(partido_cargo_actual)
            break

    if not ya_existe:
        historial_filtrado.insert(0, cargo_actual)

    return {
        "linea_tiempo": historial_filtrado,
        "escolaridad_items": list(escolaridad_map.values())
    }

class DataService:
    LEGISLATURAS_CATALOG = {
        66: {
            "numero": 66,
            "romano": "LXVI",
            "periodo": "2024–2027",
            "periodo_senado": "2024–2030",
            "estatus": "activa",
            "estatus_label": "Actual • En Funciones",
            "descripcion": "Periodo constitucional en funciones del Congreso de la Unión. Integrado tras las elecciones federales del 2 de junio de 2024."
        },
        65: {
            "numero": 65,
            "romano": "LXV",
            "periodo": "2021–2024",
            "periodo_senado": "2018–2024",
            "estatus": "historica",
            "estatus_label": "Periodo Concluido",
            "descripcion": "Periodo constitucional anterior de la Cámara de Diputados (2021–2024) y segunda mitad del periodo de Senadores."
        },
        64: {
            "numero": 64,
            "romano": "LXIV",
            "periodo": "2018–2021",
            "periodo_senado": "2018–2024",
            "estatus": "historica",
            "estatus_label": "Periodo Concluido",
            "descripcion": "Primera legislatura con mayoría de Morena tras la elección federal de 2018."
        },
        63: {
            "numero": 63,
            "romano": "LXIII",
            "periodo": "2015–2018",
            "periodo_senado": "2012–2018",
            "estatus": "historica",
            "estatus_label": "Periodo Histórico",
            "descripcion": "Legislatura caracterizada por equilibrios parlamentarios del sexenio 2012-2018."
        },
        62: {
            "numero": 62,
            "romano": "LXII",
            "periodo": "2012–2015",
            "periodo_senado": "2012–2018",
            "estatus": "historica",
            "estatus_label": "Periodo Histórico",
            "descripcion": "Legislatura del Pacto por México."
        },
        61: {
            "numero": 61,
            "romano": "LXI",
            "periodo": "2009–2012",
            "periodo_senado": "2006–2012",
            "estatus": "historica",
            "estatus_label": "Periodo Histórico",
            "descripcion": "Periodo parlamentario federal 2009–2012."
        },
        60: {
            "numero": 60,
            "romano": "LX",
            "periodo": "2006–2009",
            "periodo_senado": "2006–2012",
            "estatus": "historica",
            "estatus_label": "Periodo Histórico",
            "descripcion": "Periodo legislativo marcado por reformas estructurales al régimen de pensiones (ISSSTE), reforma electoral de 2007-2008 y debates en materia de seguridad pública."
        },
        59: {
            "numero": 59,
            "romano": "LIX",
            "periodo": "2003–2006",
            "periodo_senado": "2000–2006",
            "estatus": "historica",
            "estatus_label": "Periodo Histórico",
            "descripcion": "Segunda mitad del sexenio de la alternancia 2000-2006 con amplia pluralidad parlamentaria y equilibrio de poderes entre Legislativo y Ejecutivo."
        },
        58: {
            "numero": 58,
            "romano": "LVIII",
            "periodo": "2000–2003",
            "periodo_senado": "2000–2006",
            "estatus": "historica",
            "estatus_label": "Periodo Histórico",
            "descripcion": "Primera legislatura tras la alternancia presidencial del año 2000, caracterizada por consensos multipartidistas y leyes de transparencia y acceso a la información pública."
        },
        57: {
            "numero": 57,
            "romano": "LVII",
            "periodo": "1997–2000",
            "periodo_senado": "1997–2000",
            "estatus": "historica",
            "estatus_label": "Periodo Histórico",
            "descripcion": "Legislatura histórica donde por primera vez en la época moderna el partido gobernante perdió la mayoría absoluta en San Lázaro, marcando el inicio de la pluralidad."
        }
    }

    # Directorio curricular y oficial de mandatarios estatales ejecutivos
    GOBERNADORES_DATA: Dict[str, Dict[str, Any]] = {
        # Baja California
        "marina-del-pilar-avila-olmeda": {
            "grado": "Maestría",
            "disciplina": "Derecho Público y Políticas Públicas",
            "institucion": "ITESM / UABC / CETYS",
            "codigo": "maestria"
        },
        "jaime-bonilla-valdez": {
            "grado": "Maestría",
            "disciplina": "Administración Pública",
            "institucion": "Universidad Nacional",
            "codigo": "maestria"
        },
        "francisco-arturo-vega-de-lamadrid": {
            "grado": "Licenciatura",
            "disciplina": "Administración de Empresas",
            "institucion": "CETYS Universidad",
            "codigo": "licenciatura"
        },
        "jose-guadalupe-osuna-millan": {
            "grado": "Maestría",
            "disciplina": "Economía de los Recursos Naturales",
            "institucion": "CIDE / UABC",
            "codigo": "maestria"
        },
        "eugenio-elorduy-walther": {
            "grado": "Licenciatura",
            "disciplina": "Administración de Empresas",
            "institucion": "CETYS Universidad",
            "codigo": "licenciatura"
        },
        "alejandro-gonzalez-alcocer": {
            "grado": "Licenciatura",
            "disciplina": "Derecho",
            "institucion": "UNAM",
            "codigo": "licenciatura"
        },
        "hector-teran-teran": {
            "grado": "Licenciatura",
            "disciplina": "Contaduría y Administración",
            "institucion": "Universidad de Sonora",
            "codigo": "licenciatura"
        },
        "ernesto-ruffo-appel": {
            "grado": "Licenciatura",
            "disciplina": "Administración de Empresas",
            "institucion": "CETYS Universidad",
            "codigo": "licenciatura"
        },
        # Mandatarios en funciones de las 32 entidades
        "clara-marina-brugada-molina": {
            "grado": "Licenciatura",
            "disciplina": "Economía",
            "institucion": "UAM Iztapalapa",
            "codigo": "licenciatura"
        },
        "maria-teresa-jimenez-esquivel": {
            "grado": "Doctorado",
            "disciplina": "Derecho Constitucional",
            "institucion": "Universidad Autónoma de Durango",
            "codigo": "doctorado"
        },
        "victor-manuel-castro-cosio": {
            "grado": "Licenciatura",
            "disciplina": "Educación e Historia",
            "institucion": "Escuela Normal Superior de México",
            "codigo": "licenciatura"
        },
        "layda-elena-sansores-san-roman": {
            "grado": "Licenciatura",
            "disciplina": "Psicología",
            "institucion": "UNAM",
            "codigo": "licenciatura"
        },
        "oscar-eduardo-ramirez-aguilar": {
            "grado": "Doctorado",
            "disciplina": "Ciencias Políticas",
            "institucion": "UNAM",
            "codigo": "doctorado"
        },
        "maria-eugenia-campos-galvan": {
            "grado": "Maestría",
            "disciplina": "Administración Pública",
            "institucion": "ITESM / Georgetown University",
            "codigo": "maestria"
        },
        "manolo-jimenez-salinas": {
            "grado": "Maestría",
            "disciplina": "Administración Pública",
            "institucion": "ITESM",
            "codigo": "maestria"
        },
        "indira-vizcaino-silva": {
            "grado": "Licenciatura",
            "disciplina": "Derecho",
            "institucion": "Universidad de Colima",
            "codigo": "licenciatura"
        },
        "esteban-alejandro-villegas-villarreal": {
            "grado": "Licenciatura",
            "disciplina": "Médico Cirujano",
            "institucion": "UJED",
            "codigo": "licenciatura"
        },
        "delfina-gomez-alvarez": {
            "grado": "Maestría",
            "disciplina": "Administración de Instituciones Educativas",
            "institucion": "ITESM",
            "codigo": "maestria"
        },
        "libia-dennise-garcia-munoz-ledo": {
            "grado": "Maestría",
            "disciplina": "Derecho Constitucional",
            "institucion": "Universidad de La Salle",
            "codigo": "maestria"
        },
        "evelyn-cecia-salgado-pineda": {
            "grado": "Licenciatura",
            "disciplina": "Derecho",
            "institucion": "Universidad La Salle Cuernavaca",
            "codigo": "licenciatura"
        },
        "julio-ramon-menchaca-salazar": {
            "grado": "Licenciatura",
            "disciplina": "Derecho",
            "institucion": "UAEH",
            "codigo": "licenciatura"
        },
        "jesus-pablo-lemus-navarro": {
            "grado": "Licenciatura",
            "disciplina": "Administración de Empresas",
            "institucion": "ITESO / UP",
            "codigo": "licenciatura"
        },
        "alfredo-ramirez-bedolla": {
            "grado": "Maestría",
            "disciplina": "Propiedad Intelectual",
            "institucion": "Universidad Complutense de Madrid",
            "codigo": "maestria"
        },
        "margarita-gonzalez-saravia-calderon": {
            "grado": "Licenciatura",
            "disciplina": "Turismo",
            "institucion": "UAEM",
            "codigo": "licenciatura"
        },
        "miguel-angel-navarro-quintero": {
            "grado": "Especialidad",
            "disciplina": "Ginecología y Obstetricia",
            "institucion": "UNAM",
            "codigo": "maestria"
        },
        "samuel-alejandro-garcia-sepulveda": {
            "grado": "Doctorado",
            "disciplina": "Derecho Constitucional y Gobernabilidad",
            "institucion": "ITESM / UANL",
            "codigo": "doctorado"
        },
        "salomon-jara-cruz": {
            "grado": "Licenciatura",
            "disciplina": "Ingeniería Química",
            "institucion": "IPN",
            "codigo": "licenciatura"
        },
        "alejandro-armenta-mier": {
            "grado": "Doctorado",
            "disciplina": "Administración Pública",
            "institucion": "INAP",
            "codigo": "doctorado"
        },
        "mauricio-kuri-gonzalez": {
            "grado": "Licenciatura",
            "disciplina": "Derecho",
            "institucion": "UVM",
            "codigo": "licenciatura"
        },
        "maria-elena-hermelinda-lezama-espinosa": {
            "grado": "Licenciatura",
            "disciplina": "Ciencias de la Comunicación",
            "institucion": "Universidad Anáhuac",
            "codigo": "licenciatura"
        },
        "jose-ricardo-gallardo-cardona": {
            "grado": "Licenciatura",
            "disciplina": "Derecho",
            "institucion": "UCEM",
            "codigo": "licenciatura"
        },
        "ruben-rocha-moya": {
            "grado": "Doctorado",
            "disciplina": "Ciencias Sociales",
            "institucion": "UAS",
            "codigo": "doctorado"
        },
        "francisco-alfonso-durazo-montano": {
            "grado": "Doctorado",
            "disciplina": "Política Pública",
            "institucion": "ITESM",
            "codigo": "doctorado"
        },
        "javier-may-rodriguez": {
            "grado": "Preparatoria",
            "disciplina": "Educación Media Superior",
            "institucion": "Bachillerato",
            "codigo": "preparatoria"
        },
        "americo-villarreal-anaya": {
            "grado": "Especialidad",
            "disciplina": "Medicina y Cirugía / Cardiología",
            "institucion": "Universidad La Salle / CMN",
            "codigo": "maestria"
        },
        "lorena-cuellar-cisneros": {
            "grado": "Licenciatura",
            "disciplina": "Educación Especial",
            "institucion": "Escuela Normal",
            "codigo": "licenciatura"
        },
        "norma-rocio-nahle-garcia": {
            "grado": "Licenciatura",
            "disciplina": "Ingeniería Química Petroquímica",
            "institucion": "UAZ",
            "codigo": "licenciatura"
        },
        "joaquin-jesus-diaz-mena": {
            "grado": "Maestría",
            "disciplina": "Administración Pública",
            "institucion": "Universidad Marista",
            "codigo": "maestria"
        },
        "david-monreal-avila": {
            "grado": "Licenciatura",
            "disciplina": "Derecho",
            "institucion": "UAZ",
            "codigo": "licenciatura"
        }
    }

    # Directorio curricular y oficial de alcaldes y presidentes municipales con datos comprobados
    ALCALDES_DATA: Dict[str, Dict[str, Any]] = {
        "miriam-elizabeth-cano-nunez": {
            "nombre_oficial": "Miriam Elizabeth Cano Núñez",
            "foto_url": "/photos/miriam-elizabeth-cano-nunez.jpg",
            "grado": "Licenciatura",
            "disciplina": "Informática",
            "institucion": "Instituto Tecnológico de Tijuana (ITT)",
            "cedula": "12737398",
            "codigo": "licenciatura",
            "redes_sociales": [
                {"label": "Facebook Oficial", "url": "https://www.facebook.com/MiriamCanoBC", "icono": "facebook", "color": "#1877F2"},
                {"label": "Instagram Oficial", "url": "https://www.instagram.com/miriamcanosq", "icono": "instagram", "color": "#E4405F"}
            ],
            "linea_tiempo": [
                {
                    "experiencia": "Presidenta Municipal Constitucional de San Quintín, Baja California",
                    "periodo": "2024–2027",
                    "partido": "Morena",
                    "partido_color": "#8B1C3F",
                    "es_cargo_actual": True,
                    "es_reciente": True
                },
                {
                    "experiencia": "Secretaria de Inclusión Social e Igualdad de Género (SISIG) del Gobierno del Estado de Baja California",
                    "periodo": "2021–2024",
                    "partido": "Morena",
                    "partido_color": "#8B1C3F",
                    "es_cargo_actual": False,
                    "es_reciente": False
                },
                {
                    "experiencia": "Diputada Local en la XXIII Legislatura del Congreso del Estado de Baja California (Distrito XVII)",
                    "periodo": "2019–2021",
                    "partido": "Morena",
                    "partido_color": "#8B1C3F",
                    "es_cargo_actual": False,
                    "es_reciente": False
                }
            ],
            "escolaridad_items": [
                {
                    "experiencia": "Licenciatura en Informática — Instituto Tecnológico de Tijuana (Cédula Profesional Federal: 12737398)",
                    "estudio": "Licenciatura en Informática",
                    "institucion": "Instituto Tecnológico de Tijuana (ITT)",
                    "cedula": "12737398"
                }
            ]
        }
    }

    def __init__(self):
        # Indexación por legislatura federal
        self.politicos_by_leg: Dict[int, List[Dict[str, Any]]] = {}
        self.slug_index_by_leg: Dict[int, Dict[str, Dict[str, Any]]] = {}
        
        # Historial de mandatos para cada político único: slug -> List[Dict]
        self.mandatos_historicos: Dict[str, List[Dict[str, Any]]] = {}
        # Perfil maestro unificado por slug (con historial completo)
        self.politicos_master: Dict[str, Dict[str, Any]] = {}

        self.estados_catalog: Dict[str, Dict[str, Any]] = {}
        self.comisiones_by_leg: Dict[int, Dict[str, Dict[str, Any]]] = {}
        self.comisiones_master: Dict[str, Dict[str, Any]] = {}
        self.mexico_paths: List[Dict[str, Any]] = []

        # Datasets adicionales
        self.asistencias_nominales: Dict[str, Dict[str, Any]] = {}
        self.asistencias: Dict[str, Dict[str, Any]] = {}  # Alias de compatibilidad
        self.votaciones: List[Dict[str, Any]] = []
        self.iniciativas: List[Dict[str, Any]] = []
        self.indice_multinivel: Dict[str, List[Dict[str, Any]]] = {}
        self.indice_busqueda_universal: List[Dict[str, Any]] = []
        self._universal_by_slug: Dict[str, Dict[str, Any]] = {}
        self._explorador_catalogo_cache: Optional[List[Dict[str, Any]]] = None
        self._capas_mapa_cache: Optional[Dict[str, Any]] = None
        self._resumen_360_cache: Optional[Dict[str, Any]] = None

        self._init_estados_catalog()
        self.load_mexico_paths()
        self.load_asistencias_y_votaciones()
        self.load_all_legislaturas()

    def _init_estados_catalog(self):
        estados_raw = [
            (1, 'Aguascalientes', 'aguascalientes', 'AGS'),
            (2, 'Baja California', 'baja-california', 'BC'),
            (3, 'Baja California Sur', 'baja-california-sur', 'BCS'),
            (4, 'Campeche', 'campeche', 'CAMP'),
            (5, 'Coahuila', 'coahuila', 'COAH'),
            (6, 'Colima', 'colima', 'COL'),
            (7, 'Chiapas', 'chiapas', 'CHIS'),
            (8, 'Chihuahua', 'chihuahua', 'CHIH'),
            (9, 'Ciudad de México', 'ciudad-de-mexico', 'CDMX'),
            (10, 'Durango', 'durango', 'DGO'),
            (11, 'Guanajuato', 'guanajuato', 'GTO'),
            (12, 'Guerrero', 'guerrero', 'GRO'),
            (13, 'Hidalgo', 'hidalgo', 'HGO'),
            (14, 'Jalisco', 'jalisco', 'JAL'),
            (15, 'Estado de México', 'estado-de-mexico', 'EDOMEX'),
            (16, 'Michoacán', 'michoacan', 'MICH'),
            (17, 'Morelos', 'morelos', 'MOR'),
            (18, 'Nayarit', 'nayarit', 'NAY'),
            (19, 'Nuevo León', 'nuevo-leon', 'NL'),
            (20, 'Oaxaca', 'oaxaca', 'OAX'),
            (21, 'Puebla', 'puebla', 'PUE'),
            (22, 'Querétaro', 'queretaro', 'QRO'),
            (23, 'Quintana Roo', 'quintana-roo', 'QROO'),
            (24, 'San Luis Potosí', 'san-luis-potosi', 'SLP'),
            (25, 'Sinaloa', 'sinaloa', 'SIN'),
            (26, 'Sonora', 'sonora', 'SON'),
            (27, 'Tabasco', 'tabasco', 'TAB'),
            (28, 'Tamaulipas', 'tamaulipas', 'TAMPS'),
            (29, 'Tlaxcala', 'tlaxcala', 'TLAX'),
            (30, 'Veracruz', 'veracruz', 'VER'),
            (31, 'Yucatán', 'yucatan', 'YUC'),
            (32, 'Zacatecas', 'zacatecas', 'ZAC'),
        ]
        for item_id, nombre, slug, abbr in estados_raw:
            self.estados_catalog[slug] = {
                "id": item_id,
                "nombre": nombre,
                "slug": slug,
                "abbr": abbr,
                "total_representantes": 0,
                "partido_mayoria": "Morena",
                "conteo_partidos": {},
                "disponible": True
            }

    def load_mexico_paths(self):
        paths_file = settings.BASE_DIR / "storage" / "data" / "mexico_paths.json"
        if paths_file.exists():
            try:
                with open(paths_file, "rb") as f:
                    self.mexico_paths = orjson.loads(f.read())
            except Exception:
                self.mexico_paths = []

    def load_asistencias_y_votaciones(self):
        json_dir = settings.STORAGE_DATA_DIR
        if not json_dir.exists():
            return

        # 1. Cargar asistencias nominales
        for af in json_dir.glob("asistencias_*_leg_66.json"):
            try:
                with open(af, "rb") as f:
                    raw = orjson.loads(f.read())
                col = raw.get("diputados") or raw.get("senadores") or {}
                if isinstance(col, dict):
                    self.asistencias_nominales.update(col)
            except Exception:
                continue

        # 2. Cargar votaciones del Pleno
        self.votaciones = []
        for vf in sorted(json_dir.glob("votaciones_periodo_*_leg_66.json")):
            try:
                with open(vf, "rb") as f:
                    raw = orjson.loads(f.read())
                self.votaciones.extend(raw.get("votaciones", []))
            except Exception:
                continue

        # 3. Cargar iniciativas
        ini_file = json_dir / "iniciativas_bc.json"
        if ini_file.exists():
            try:
                with open(ini_file, "rb") as f:
                    self.iniciativas = orjson.loads(f.read())
            except Exception:
                self.iniciativas = []

        # 4. Cargar índice de cargos y trayectorias multinivel (Alcaldías, Congresos Locales y Poderes Estatales)
        multi_file = json_dir / "indice_cargos_multinivel.json"
        if multi_file.exists():
            try:
                with open(multi_file, "rb") as f:
                    raw_multi = orjson.loads(f.read())
                self.indice_multinivel = raw_multi.get("indice", {})
            except Exception:
                self.indice_multinivel = {}

        # 5. Cargar índice de búsqueda universal (Congreso de la Unión, Congresos Estatales, Gobernadores, Alcaldes)
        uni_file = json_dir / "indice_busqueda_universal.json"
        if uni_file.exists():
            try:
                with open(uni_file, "rb") as f:
                    self.indice_busqueda_universal = orjson.loads(f.read())
                self._universal_by_slug = {it["slug"]: it for it in self.indice_busqueda_universal if "slug" in it}
            except Exception:
                self.indice_busqueda_universal = []
                self._universal_by_slug = {}

        self.asistencias = self.asistencias_nominales

    def load_all_legislaturas(self):
        json_dir = settings.STORAGE_DATA_DIR
        if not json_dir.exists():
            return

        # Pre-indexar iniciativas por promovente_slug para cruce O(1) instantáneo
        ini_por_promovente = defaultdict(list)
        for ini in self.iniciativas:
            p_prom = ini.get("promovente_principal", "")
            if p_prom:
                p_slug = slugify(p_prom)
                ini_por_promovente[p_slug].append(ini)
        iniciativas_match_cache: Dict[str, List[Dict[str, Any]]] = {}

        # Cargamos en orden cronológico inverso para que la versión más reciente sea el perfil base
        for leg_num in [66, 65, 64, 63, 62, 61, 60, 59, 58, 57]:
            self.politicos_by_leg[leg_num] = []
            self.slug_index_by_leg[leg_num] = {}
            self.comisiones_by_leg[leg_num] = {}

            dedup = set()
            files: List[Path] = []

            # 1. Archivos consolidados nacionales federales
            consolidado = json_dir / f"congreso_union_leg_{leg_num}.json"
            if consolidado.exists():
                files.append(consolidado)

            # 2. Archivos federales por entidad (representantes federales de cada estado)
            files.extend(list(json_dir.glob(f"legisladores_entidad_*_leg_{leg_num}.json")))

            # REGLA DE INTEGRIDAD: NO cargamos congreso_*_leg_X.json aquí, ya que son congresos
            # locales con su propia numeración no federal que inflaban los conteos de la República.

            for fp in files:
                if fp.stat().st_size < 1000:
                    continue
                try:
                    with open(fp, "rb") as f:
                        raw = orjson.loads(f.read())
                    if not isinstance(raw, dict):
                        continue
                    
                    # Únicamente consideramos representantes federales: Diputados y Senadores
                    dips = raw.get("diputados", [])
                    sens = raw.get("senadores", [])

                    for leg in dips + sens:
                        camara = leg.get("camara", "")
                        if camara not in ("Diputados", "Senadores"):
                            continue

                        ref = str(leg.get("referencia_sil") or "")
                        nombre = leg.get("nombre_completo", "")
                        dedup_key = f"{ref}_{nombre}_{camara}"
                        if dedup_key in dedup:
                            continue
                        dedup.add(dedup_key)

                        leg["legislatura"] = leg_num
                        slug = leg.get("slug") or slugify(nombre)
                        leg["slug"] = slug

                        # Normalizar entidad
                        entidad_cruda = leg.get("entidad", "")
                        leg["entidad_slug"] = normalizar_entidad(entidad_cruda)
                        if leg["entidad_slug"] == "ciudad-de-mexico" and ("Distrito Federal" in entidad_cruda or "DF" in entidad_cruda):
                            leg["entidad"] = "Ciudad de México"

                        # Procesar edad, estatus de licencia, línea de tiempo y escolaridad
                        leg["edad"] = calcular_edad(leg.get("fecha_nacimiento"))
                        tipo_cargo = leg.get("tipo_cargo") or "Propietario"
                        leg["tiene_licencia"] = (
                            "Licencia" in tipo_cargo
                            or (leg.get("metadata", {}).get("estatus_licencia") == "Con Licencia")
                            or bool(leg.get("licencias"))
                        )
                        hist = procesar_trayectoria_y_estudios(leg)
                        leg["linea_tiempo"] = hist["linea_tiempo"]
                        leg["escolaridad_items"] = hist["escolaridad_items"]

                        # Auditoría académica (con escolaridad_items ya poblada)
                        leg["perfil_academico"] = education_service.clasificar_politico(leg)

                        # Cruzar asistencia real si existe
                        asist_info = self.asistencias_nominales.get(slug)
                        if asist_info:
                            pct = float(asist_info.get("porcentaje", 0))
                            leg["asistencia_kpi"] = {
                                "porcentaje": pct,
                                "asistencias": asist_info.get("asistencias", 0),
                                "faltas": asist_info.get("faltas", 0),
                                "total_sesiones": asist_info.get("total_sesiones", 0),
                                "semaforo": "verde" if pct >= 90 else ("amarillo" if pct >= 75 else "rojo")
                            }

                        # Sanitizar suplente_nombre (eliminar \u00a0 y espacios duplicados)
                        suplente_raw = leg.get("suplente_nombre")
                        if suplente_raw and isinstance(suplente_raw, str):
                            suplente_clean = " ".join(suplente_raw.replace("\u00a0", " ").split()).strip()
                            leg["suplente_nombre"] = suplente_clean or None

                        # Cruzar iniciativas de forma ultra-rápida (con índice y caché)
                        norm_nom = slugify(nombre)
                        if norm_nom in iniciativas_match_cache:
                            mis_iniciativas = iniciativas_match_cache[norm_nom]
                        else:
                            mis_iniciativas = []
                            for p_slug, inis in ini_por_promovente.items():
                                if norm_nom in p_slug:
                                    mis_iniciativas.extend(inis)
                            iniciativas_match_cache[norm_nom] = mis_iniciativas
                        leg["iniciativas"] = mis_iniciativas

                        self.politicos_by_leg[leg_num].append(leg)
                        self.slug_index_by_leg[leg_num][slug] = leg

                        # Registrar mandato histórico
                        romano = self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("romano", str(leg_num))
                        periodo = self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("periodo", "")
                        mandato = {
                            "legislatura": leg_num,
                            "romano": romano,
                            "periodo": periodo,
                            "camara": camara,
                            "partido": leg.get("partido", "Sin Partido"),
                            "principio_eleccion": leg.get("principio_eleccion", "Mayoría Relativa"),
                            "entidad": leg.get("entidad", "")
                        }
                        self.mandatos_historicos.setdefault(slug, []).append(mandato)

                        # Enriquecer perfil maestro unificado
                        if slug not in self.politicos_master or leg_num == 66:
                            self.politicos_master[slug] = dict(leg)

                        # Indexar comisiones por legislatura y maestro global
                        for com in leg.get("comisiones", []):
                            c_nombre = (com.get("comision") or com.get("nombre") or "").strip()
                            if not c_nombre:
                                continue
                            c_slug = slugify(c_nombre)
                            com["slug"] = c_slug

                            puesto = com.get("puesto") or "Integrante"
                            member_rec = {
                                "nombre_completo": leg.get("nombre_completo"),
                                "slug": slug,
                                "referencia_sil": leg.get("referencia_sil", ""),
                                "partido": leg.get("partido", "Sin Partido"),
                                "foto_oficial_url": leg.get("foto_oficial_url"),
                                "foto_local_path": leg.get("foto_local_path"),
                                "puesto": puesto,
                                "camara": camara,
                                "entidad": leg.get("entidad", ""),
                                "legislatura": leg_num,
                                "romano": self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("romano", str(leg_num))
                            }

                            if c_slug not in self.comisiones_by_leg[leg_num]:
                                self.comisiones_by_leg[leg_num][c_slug] = {
                                    "nombre": c_nombre,
                                    "slug": c_slug,
                                    "camara": camara,
                                    "integrantes": []
                                }
                            self.comisiones_by_leg[leg_num][c_slug]["integrantes"].append(member_rec)

                            if c_slug not in self.comisiones_master:
                                self.comisiones_master[c_slug] = {
                                    "nombre": c_nombre,
                                    "slug": c_slug,
                                    "camara": camara,
                                    "integrantes": []
                                }
                            self.comisiones_master[c_slug]["integrantes"].append(member_rec)
                except Exception:
                    continue

        # Inyectar mandatos históricos ordenados en cada político
        for slug in self.mandatos_historicos:
            self.mandatos_historicos[slug].sort(key=lambda m: m["legislatura"], reverse=True)

        for slug, pol in self.politicos_master.items():
            pol["mandatos_historicos"] = self.mandatos_historicos.get(slug, [])

        # Cargar catálogo consolidado maestro de comisiones si existe
        coms_master_file = json_dir / "comisiones_master.json"
        if coms_master_file.exists():
            try:
                with open(coms_master_file, "rb") as f:
                    self.comisiones_master = orjson.loads(f.read())
            except Exception:
                pass

        # Enriquecer comisiones de cada legislatura con metadatos de presidencia y bancadas
        for leg_num in self.comisiones_by_leg:
            for c_slug, com in self.comisiones_by_leg[leg_num].items():
                self._enrich_comision_entry(com)

    def _enrich_comision_entry(self, com_dict: Dict[str, Any]) -> Dict[str, Any]:
        nombre = com_dict.get("nombre", "")
        cam = com_dict.get("camara", "")
        cam_upper = nombre.upper()
        if "DIPUTADOS" in cam_upper or cam == "Diputados":
            camara_tag = "Diputados"
        elif "SENAD" in cam_upper or cam == "Senadores":
            camara_tag = "Senadores"
        else:
            camara_tag = "Congreso"

        integrantes = com_dict.get("integrantes", [])
        presidencia = com_dict.get("presidente")
        if not presidencia:
            for it in integrantes:
                if "PRESID" in str(it.get("puesto", "")).upper():
                    presidencia = it
                    break

        foto_pres = "https://via.placeholder.com/150"
        if presidencia:
            ref_sil = str(presidencia.get("referencia_sil") or "")
            if ref_sil and (settings.STORAGE_RAW_PHOTOS / f"{ref_sil}.jpg").exists():
                foto_pres = f"/photos/{ref_sil}.jpg"
            elif presidencia.get("foto_oficial_url"):
                foto_pres = presidencia["foto_oficial_url"]
            elif presidencia.get("nombre_completo"):
                nom = presidencia["nombre_completo"].replace(" ", "+")
                foto_pres = f"https://ui-avatars.com/api/?name={nom}&background=E5E5EA&color=1C1C1E"

        partidos_conteo: Dict[str, int] = {}
        for it in integrantes:
            p = (it.get("partido") or "Sin Partido").strip()
            if p:
                partidos_conteo[p] = partidos_conteo.get(p, 0) + 1

        partidos_sorted = sorted(partidos_conteo.items(), key=lambda x: x[1], reverse=True)
        top_partidos = [
            {"nombre": p_name, "count": p_count, "color": get_party_color(p_name)}
            for p_name, p_count in partidos_sorted[:4]
        ]

        com_dict["camara_tag"] = camara_tag
        com_dict["total_integrantes"] = len(integrantes)
        com_dict["presidente"] = presidencia
        com_dict["foto_presidencia"] = foto_pres
        com_dict["top_partidos"] = top_partidos
        com_dict["partidos_conteo"] = partidos_conteo
        return com_dict

    def get_all_politicos(self) -> List[Dict[str, Any]]:
        return list(self.politicos_master.values())

    def get_politicos(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return self.politicos_by_leg.get(legislatura, [])

    def get_directorio_politicos(self, legislatura: Optional[int] = None) -> List[Dict[str, Any]]:
        """
        Retorna el catálogo enriquecido de figuras públicas para el directorio,
        utilizando caché en memoria y el índice precompilado para velocidad instantánea.
        """
        cache_file = Path(settings.BASE_DIR) / "storage" / "cache" / "directorio_politicos_index.json"
        cache_mtime = cache_file.stat().st_mtime if cache_file.exists() else 0
        if not hasattr(self, "_directorio_index_cache") or getattr(self, "_directorio_index_cache_mtime", 0) != cache_mtime:
            if cache_file.exists():
                try:
                    with open(cache_file, "r", encoding="utf-8") as f:
                        self._directorio_index_cache = json.load(f)
                        self._directorio_index_cache_mtime = cache_mtime
                        for item in self._directorio_index_cache:
                            if not item.get("entidad_slug"):
                                item["entidad_slug"] = normalizar_entidad(item.get("entidad") or "")
                            if not item.get("foto_url"):
                                ref_sil = str(item.get("referencia_sil") or "")
                                if item.get("tiene_foto_local") and ref_sil:
                                    item["foto_url"] = f"/photos/{ref_sil}.jpg"
                                elif item.get("foto_oficial_url"):
                                    item["foto_url"] = item.get("foto_oficial_url")
                                else:
                                    item["foto_url"] = ""
                except Exception:
                    self._directorio_index_cache = None
            else:
                self._directorio_index_cache = None

        if getattr(self, "_directorio_index_cache", None) is not None:
            if legislatura and legislatura > 0:
                return [p for p in self._directorio_index_cache if int(p.get("legislatura") or 0) == legislatura or legislatura in (p.get("legislaturas_lista") or [])]
            return self._directorio_index_cache

        if legislatura and legislatura > 0:
            base_pols = self.get_politicos(legislatura)
        else:
            base_pols = self.get_all_politicos()

        directorio = []
        for p in base_pols:
            slug = p.get("slug") or slugify(p.get("nombre_completo", ""))
            nombre = p.get("nombre_completo", "")
            if not slug or not nombre:
                continue

            camara_actual = p.get("camara", "Diputados")
            leg_num = int(p.get("legislatura") or 66)
            mandatos = self.mandatos_historicos.get(slug, [])
            legs_disponibles = sorted(list({m["legislatura"] for m in mandatos} | {leg_num}), reverse=True)
            if not legs_disponibles:
                legs_disponibles = [leg_num]

            # Analizar textos de trayectoria pública
            textos = []
            for lt in p.get("linea_tiempo", []):
                textos.append(lt.get("experiencia", ""))
            for tl in p.get("trayectoria_legislativa", []):
                textos.append(tl if isinstance(tl, str) else (tl.get("cargo") or tl.get("experiencia") or ""))
            for tp in p.get("trayectoria_politica", []):
                textos.append(tp if isinstance(tp, str) else (tp.get("cargo") or tp.get("experiencia") or ""))
            for ta in p.get("trayectoria_administrativa", []):
                textos.append(ta if isinstance(ta, str) else (ta.get("cargo") or ta.get("experiencia") or ""))
            corpus = " | ".join(textos).lower()

            # 1. Senador
            has_senador = (camara_actual == "Senadores") or any(m.get("camara") == "Senadores" for m in mandatos)
            if not has_senador and re.search(r"\b(senador|senadora|senaduria)\b", corpus, re.IGNORECASE):
                has_senador = True

            # 2. Diputado Federal
            has_dip_fed = (camara_actual == "Diputados") or any(m.get("camara") == "Diputados" for m in mandatos)
            if not has_dip_fed and re.search(r"\b(diputad[ao]\s+federal|congreso\s+de\s+la\s+union|camara\s+de\s+diputados)\b", corpus, re.IGNORECASE):
                has_dip_fed = True

            # 3. Diputado Local
            has_dip_local = bool(re.search(r"\b(diputad[ao]\s+local|congreso\s+del?\s+estado|legislatura\s+local|asamblea\s+legislativa)\b", corpus, re.IGNORECASE))

            # 4. Alcalde / Munícipe Titular y Ediles de Cabildo
            has_alcalde = False
            has_edil = False
            municipio_alcalde = None
            municipio_edil = None
            cargo_edil_label = None

            re_negativo = re.compile(r'\b(?:candidat[ao]|precandidat[ao]|aspirante|campa[ñn]a|precampa[ñn]a|elecci[oó]n|elecciones|coordinador[a]?|secretari[ao]|asesor[a]?|asistente|auxiliar|chofer|jefe\s+de\s+oficina|director[a]?|suplente|comit[eé]|cdm|cde|cen|espos[ao]|hij[ao])\b', re.IGNORECASE)

            for t in textos:
                t_trim = (t or "").strip()
                if not t_trim:
                    continue

                # 4.1 Presidente Municipal / Alcalde Titular
                m_alc = re.search(r'\b(?:president[ea]\s+municipal(?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]))?|alcald[ea](?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]))?|jef[ea]\s+delegacional)\s+(?:de|del|en)\s+([A-ZÁÉÍÓÚÑa-záéíóúñ\s]+?)(?:,|\.|\(|\bde\s+la\b|\bdel\s+estado\b|$)', t_trim, re.IGNORECASE)
                if m_alc:
                    pos = t_trim.lower().find(m_alc.group(0).lower())
                    prefijo = t_trim[:pos] if pos > 0 else ""
                    es_invalido = bool(re_negativo.search(prefijo)) or bool(re.search(r'\b(?:suplente|candidat[ao]|precandidat[ao]|durante\s+la\s+elecci[oó]n|en\s+la\s+elecci[oó]n|comit[eé]\s+directivo|cdm|cde|cen)\b', t_trim, re.IGNORECASE))
                    if not es_invalido:
                        cand = m_alc.group(1).strip()
                        cand = re.sub(r'\s+(?:b\.?c\.?|baja california|sonora|jalisco|nuevo león|méxico|edo\.?\s*méx\.?|cdmx|d\.?f\.?|chihuahua|puebla|veracruz|guerrero|oaxaca|chiapas|yucatán|tabasco|tamaulipas|coahuila|durango|sinaloa|nayarit|zacatecas|san luis potosí|aguascalientes|querétaro|hidalgo|tlaxcala|morelos|michoacán|colima|guanajuato|campeche|quintana roo)$', '', cand, flags=re.IGNORECASE).strip()
                        cand = re.sub(r'^(?:el|la|los|las)\s+', '', cand, flags=re.IGNORECASE).strip()
                        if cand and 2 < len(cand) < 32 and not re.search(r'\b(partido|comite|directivo|consejo|gobierno|dif|camara|administracion|estado|republica)\b', cand, re.IGNORECASE):
                            municipio_alcalde = cand.title()
                            has_alcalde = True
                            break

                # 4.2 Ediles de Cabildo (Regidor o Síndico)
                if not has_alcalde:
                    m_ed = re.search(r'\b(?:regidor[a]?|s[ií]ndic[ao](?:\s+procurador[a]?)?)\s+(?:de|del|en)\s+(?:el\s+(?:h\.\s*)?ayuntamiento\s+de\s+)?([A-ZÁÉÍÓÚÑa-záéíóúñ\s]+?)(?:,|\.|\(|$)', t_trim, re.IGNORECASE)
                    if m_ed:
                        pos_ed = t_trim.lower().find(m_ed.group(0).lower())
                        prefijo_ed = t_trim[:pos_ed] if pos_ed > 0 else ""
                        es_invalido_ed = bool(re_negativo.search(prefijo_ed)) or bool(re.search(r'\b(?:candidat[ao]|precandidat[ao]|suplente|durante\s+la\s+elecci[oó]n|comit[eé]\s+directivo|cdm)\b', t_trim, re.IGNORECASE))
                        if not es_invalido_ed:
                            cand = m_ed.group(1).strip()
                            cand = re.sub(r'\s+(?:b\.?c\.?|baja california|sonora|jalisco|nuevo león|méxico|edo\.?\s*méx\.?|cdmx|d\.?f\.?)$', '', cand, flags=re.IGNORECASE).strip()
                            cand = re.sub(r'^(?:el|la|los|las)\s+', '', cand, flags=re.IGNORECASE).strip()
                            if cand and 2 < len(cand) < 32 and not re.search(r'\b(partido|comite|directivo|consejo|gobierno|dif)\b', cand, re.IGNORECASE):
                                municipio_edil = cand.title()
                                has_edil = True
                                cargo_edil_label = "Síndico" if re.search(r'\bs[ií]ndic', m_ed.group(0), re.IGNORECASE) else "Regidor"

            # 5. Gobernador / Jefe de Gobierno titular (Poder Ejecutivo)
            re_gob_exclusiones = re.compile(
                r'\b(?:candidat[ao]|precandidat[ao]|aspirante|campa[ñn]a|precampa[ñn]a|elecci[oó]n|elecciones|'
                r'asesor[a]?|secretari[ao]|subsecretari[ao]|director[a]?|coordinador[a]?|chofer|auxiliar|asistente|'
                r'jefe\s+de\s+(?:oficina|departamento|unidad|gabinete)|jefatura\s+de\s+departamento|representante|'
                r'comisi[oó]n\s+de\s+gobierno|secretar[ií]a\s+(?:general\s+)?de\s+gobierno|gabinete|administraci[oó]n\s+del|'
                r'espos[ao]|hij[ao]|suplente|enlace|palacio\s+de\s+gobierno|casa\s+de\s+gobierno|informe\s+de\s+gobierno|'
                r'capacitador[a]?|consejer[ao]|delegad[ao]|agenda\s+del|equipo\s+de|comisionad[ao]|durante\s+la\s+elecci[oó]n)\b',
                re.IGNORECASE
            )
            re_gob_titular = re.compile(
                r'\b(?:gobernador[a]?\s+(?:constitucional|interin[ao]|sustitut[ao]|provisional|del?\s+estado|elect[ao])|'
                r'jef[ea]\s+de\s+gobierno(?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]|del\s+distrito\s+federal|de\s+la\s+ciudad\s+de\s+m[eé]xico|del\s+d\.?f\.?|de\s+la\s+cdmx))?)\b',
                re.IGNORECASE
            )

            has_gobernador = False
            for t_item in textos:
                t_str = (t_item or "").strip()
                if t_str and re_gob_titular.search(t_str) and not re_gob_exclusiones.search(t_str):
                    has_gobernador = True
                    break

            badges = []
            if has_senador:
                badges.append({
                    "label": "Senador",
                    "icono": "bi-bank",
                    "tipo": "senador",
                    "badge_class": "bg-info-subtle text-info-emphasis border border-info-subtle"
                })
            if has_dip_fed:
                badges.append({
                    "label": "Dip. Federal",
                    "icono": "bi-building",
                    "tipo": "diputado_federal",
                    "badge_class": "bg-primary-subtle text-primary border border-primary-subtle"
                })
            if has_dip_local:
                badges.append({
                    "label": "Dip. Local",
                    "icono": "bi-geo-alt",
                    "tipo": "diputado_local",
                    "badge_class": "bg-secondary-subtle text-secondary border border-secondary-subtle"
                })
            if has_alcalde:
                label_alcalde = f"Alcalde • {municipio_alcalde}" if municipio_alcalde else "Alcalde"
                badges.append({
                    "label": label_alcalde,
                    "icono": "bi-houses",
                    "tipo": "alcalde",
                    "badge_class": "bg-warning-subtle text-warning-emphasis border border-warning-subtle"
                })
            elif has_edil:
                label_edil = f"{cargo_edil_label} • {municipio_edil}" if municipio_edil else (cargo_edil_label or "Edil de Cabildo")
                badges.append({
                    "label": label_edil,
                    "icono": "bi-people",
                    "tipo": "edil",
                    "badge_class": "bg-secondary-subtle text-dark border border-secondary-subtle"
                })
            if has_gobernador:
                badges.append({
                    "label": "Gobernador",
                    "icono": "bi-shield-shaded",
                    "tipo": "gobernador",
                    "badge_class": "bg-danger-subtle text-danger border border-danger-subtle"
                })

            es_activo = (66 in legs_disponibles)
            leg_romano = self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("romano", str(leg_num))
            cargo_tit = "Senador de la República" if camara_actual == "Senadores" else "Diputado Federal"
            cargo_resumen = f"{cargo_tit} en funciones • {leg_romano} Legislatura" if es_activo else f"Ex-{cargo_tit} • {leg_romano} Legislatura"

            # Resolver foto
            ref_sil = str(p.get("referencia_sil") or "")
            foto_local = p.get("foto_local_path")
            if ref_sil and (settings.STORAGE_RAW_PHOTOS / f"{ref_sil}.jpg").exists():
                foto_url = f"/photos/{ref_sil}.jpg"
            elif foto_local:
                foto_url = f"/{foto_local.lstrip('/')}"
            else:
                foto_url = p.get("foto_oficial_url") or ""

            item = dict(p)
            item["slug"] = slug
            item["entidad_slug"] = p.get("entidad_slug") or normalizar_entidad(p.get("entidad") or "")
            item["nombre_natural"] = formatear_nombre_natural(nombre)
            item["foto_url"] = foto_url
            item["municipio_alcalde"] = municipio_alcalde or municipio_edil or ""
            item["roles_flags"] = {
                "senador": has_senador,
                "diputado_federal": has_dip_fed,
                "diputado_local": has_dip_local,
                "alcalde": has_alcalde,
                "edil": has_edil,
                "gobernador": has_gobernador
            }
            item["cargos_badges"] = badges
            item["es_activo"] = es_activo
            item["cargo_resumen"] = cargo_resumen
            item["municipio_alcalde"] = municipio_alcalde
            item["legislaturas_lista"] = legs_disponibles
            directorio.append(item)

        directorio.sort(key=lambda x: unicodedata.normalize("NFKD", x.get("nombre_completo", "")).encode("ascii", "ignore").lower())
        return directorio

    def get_directorio_estudios(self) -> List[Dict[str, Any]]:
        """
        Retorna el catálogo de figuras públicas del directorio con perfil académico
        clasificado y enriquecido para el observatorio educativo, utilizando caché en memoria.
        """
        if getattr(self, "_directorio_estudios_cache", None) is not None:
            return self._directorio_estudios_cache

        base = self.get_directorio_politicos(None)
        enriquecidos = []
        for p in base:
            item = dict(p)
            slug = item.get("slug")
            full = self.politicos_master.get(slug, item) if hasattr(self, "politicos_master") else item

            for fld in ("escolaridad_items", "escolaridad_detalle", "ultimo_grado_estudios", "preparacion_academica", "escolaridad", "trayectoria_academica", "distrito", "principio_eleccion"):
                if fld in full and fld not in item:
                    item[fld] = full[fld]

            analisis = education_service.clasificar_politico(item)
            item["analisis_academico"] = analisis
            item["categoria_grupo"] = education_service.determinar_categoria_agrupada(analisis.get("grado_maximo", "sin_registro"))

            roles_flags = item.get("roles_flags") or {}
            roles_lista = [rk for rk, rv in roles_flags.items() if rv]
            item["roles_lista"] = roles_lista
            item["roles_str"] = ",".join(roles_lista)

            nom = item.get("nombre_completo", "")
            nom_nat = item.get("nombre_natural", "")
            part = item.get("partido", "")
            ent = item.get("entidad", "")
            c_res = item.get("cargo_resumen", "")
            mun = item.get("municipio_alcalde", "")
            grado_lbl = analisis.get("grado_info", {}).get("label", "")
            tit = analisis.get("titulo_destacado", "")
            disc = analisis.get("disciplina_principal", "")
            alma = analisis.get("institucion_resumen", {}).get("alma_mater_destacada", "")
            item["search_corpus"] = f"{nom} {nom_nat} {part} {ent} {c_res} {mun} {grado_lbl} {tit} {disc} {alma}".lower()
            enriquecidos.append(item)

        self._directorio_estudios_cache = enriquecidos
        return self._directorio_estudios_cache

    def find_politico_by_nombre(self, nombre_raw: str) -> Optional[Dict[str, Any]]:
        if not nombre_raw:
            return None
        s = slugify(nombre_raw)
        if s in self.politicos_master:
            return self.politicos_master[s]

        tokens = [t for t in s.split("-") if t and len(t) > 1]
        if not tokens:
            return None

        # Permutaciones frecuentes de nombres en México (Nombre Apellido1 Apellido2 <-> Apellido1 Apellido2 Nombre)
        candidatos = []
        if len(tokens) == 3:
            candidatos.append(f"{tokens[1]}-{tokens[2]}-{tokens[0]}")
            candidatos.append(f"{tokens[2]}-{tokens[0]}-{tokens[1]}")
        elif len(tokens) == 4:
            candidatos.append(f"{tokens[2]}-{tokens[3]}-{tokens[0]}-{tokens[1]}")
            candidatos.append(f"{tokens[1]}-{tokens[2]}-{tokens[3]}-{tokens[0]}")
            candidatos.append(f"{tokens[3]}-{tokens[0]}-{tokens[1]}-{tokens[2]}")
        elif len(tokens) == 2:
            candidatos.append(f"{tokens[1]}-{tokens[0]}")

        for cand in candidatos:
            if cand in self.politicos_master:
                return self.politicos_master[cand]

        # Búsqueda por conjunto de tokens (subconjunto de apellidos y nombres)
        token_set = set(tokens)
        for k, pol in self.politicos_master.items():
            k_tokens = set(k.split("-"))
            if token_set.issubset(k_tokens):
                return pol
            if len(token_set) >= 3 and len(token_set.intersection(k_tokens)) >= len(token_set) - 1:
                return pol

        return None

    def _build_perfil_from_catalogo_item(self, it: Dict[str, Any]) -> Dict[str, Any]:
        """Construye una ficha de político oficial completa para figuras ejecutivas o municipales a partir del catálogo unificado."""
        slug = it.get("slug") or slugify(it.get("nombre") or "")
        nombre_completo = it.get("nombre_completo") or it.get("nombre") or ""
        nombre_natural = it.get("nombre") or formatear_nombre_natural(nombre_completo)
        cargo = it.get("cargo") or "Servidor(a) Público(a)"
        partido = it.get("partido_actual") or "Sin Partido"
        partido_color = it.get("partido_actual_color") or get_party_color(partido)
        entidad = it.get("entidad") or "México"
        entidad_slug = it.get("entidad_slug") or normalizar_entidad(entidad)
        periodo = it.get("periodo") or "2024–2027"
        es_actual = bool(it.get("es_actual", True))
        foto = it.get("foto_url") or ""
        nivel = it.get("nivel") or "Estatal"

        mandatos = [{
            "legislatura": 66 if es_actual else 0,
            "romano": "Constitucional",
            "periodo": periodo,
            "camara": cargo,
            "partido": partido,
            "principio_eleccion": it.get("principio_eleccion") or "Elección Popular Directa",
            "entidad": entidad
        }]

        if it.get("linea_tiempo"):
            linea_tiempo = list(it["linea_tiempo"])
        else:
            mun_str = it.get("municipio")
            exp_text = f"{cargo} de {mun_str}, {entidad}" if (mun_str and nivel == "Municipal") else f"{cargo} de {entidad}"
            linea_tiempo = [{
                "experiencia": exp_text,
                "periodo": periodo,
                "partido": partido,
                "partido_color": partido_color,
                "es_cargo_actual": es_actual,
                "es_reciente": True
            }]

        grado = it.get("grado_academico") or "Sin Registro"
        disciplina = it.get("disciplina") or "-"
        if it.get("escolaridad_items"):
            escolaridad_items = list(it["escolaridad_items"])
        else:
            escolaridad_items = []
            if grado != "Sin Registro":
                disc_label = f" en {disciplina}" if disciplina and disciplina != "-" else ""
                escolaridad_items.append({"experiencia": f"{grado}{disc_label}", "estudio": f"{grado}{disc_label}"})

        perfil_base = {
            "slug": slug,
            "nombre_completo": nombre_completo,
            "nombre_natural": nombre_natural,
            "cargo": cargo,
            "camara": cargo,
            "tipo_cargo": "Titular Electo",
            "nivel": nivel,
            "partido": partido,
            "partido_actual_color": partido_color,
            "entidad": entidad,
            "entidad_slug": entidad_slug,
            "distrito": it.get("municipio") or ("Poder Ejecutivo Estatal" if nivel == "Estatal" else ""),
            "municipio": it.get("municipio") or "",
            "foto_oficial_url": it.get("foto_oficial_url") or foto,
            "referencia_sil": None,
            "periodo": periodo,
            "legislatura": 66 if es_actual else 0,
            "es_actual": es_actual,
            "tiene_licencia": bool(it.get("tiene_licencia")),
            "principio_eleccion": it.get("principio_eleccion") or "Elección Popular Directa",
            "ultimo_grado_estudios": grado if grado != "Sin Registro" else None,
            "preparacion_academica": disciplina if disciplina != "-" else None,
            "grado_academico": grado,
            "disciplina": disciplina,
            "escolaridad_items": escolaridad_items,
            "linea_tiempo": linea_tiempo,
            "mandatos_historicos": mandatos,
            "legislaturas_disponibles": [66] if es_actual else [],
            "asistencia_kpi": None,
            "iniciativas": [],
            "comisiones": [],
            "redes_sociales": it.get("redes_sociales") or [],
            "disciplina_partidaria_pct": 100.0,
            "fuente_url": it.get("fuente_url") or ""
        }
        perfil_base["perfil_academico"] = education_service.clasificar_politico(perfil_base)
        return perfil_base

    def _build_perfil_from_universal_item(self, it: Dict[str, Any]) -> Dict[str, Any]:
        """Construye un perfil oficial completo para figuras de Congresos Locales, Alcaldías o Gubernaturas registradas en el índice universal."""
        slug = it.get("slug") or slugify(it.get("nombre", ""))
        nombre = it.get("nombre", "")
        cargo = it.get("cargo") or "Servidor(a) Público(a)"
        camara = it.get("camara") or cargo
        partido = get_canonical_party_name(it.get("partido") or "Sin Partido")
        partido_color = get_party_color(partido)
        entidad = it.get("entidad") or "México"
        entidad_slug = normalizar_entidad(entidad)
        municipio = it.get("municipio") or ""
        ambito = it.get("ambito") or "Estatal"
        foto = it.get("foto_url") or ""

        # Enriquecer datos si existe el dataset del congreso local correspondiente
        info_extra = {}
        if ambito == "Estatal" and "Leg." in cargo:
            m_leg = re.search(r"(\d+)\s*Leg", cargo)
            leg_num = m_leg.group(1) if m_leg else ""
            ent_norm = entidad_slug.replace("-", "")
            json_dir = settings.STORAGE_DATA_DIR
            c_files = list(json_dir.glob(f"congreso_{ent_norm}*_leg_{leg_num}.json")) if leg_num else []
            if not c_files and leg_num:
                c_files = list(json_dir.glob(f"congreso_*_leg_{leg_num}.json"))
            for cf in c_files:
                try:
                    with open(cf, "rb") as fp:
                        c_data = orjson.loads(fp.read())
                    for c_p in (c_data.get("diputados", []) or c_data.get("legisladores", [])):
                        if slugify(c_p.get("nombre_completo", "")) in (slug, slugify(nombre)):
                            info_extra = c_p
                            break
                    if info_extra:
                        break
                except Exception:
                    pass

        distrito = info_extra.get("distrito") or municipio
        principio = info_extra.get("principio_eleccion") or "Mayoría Relativa"
        periodo = info_extra.get("periodo") or it.get("periodo") or "Mandato Registrado"
        comisiones = info_extra.get("comisiones") or []
        ubicacion = info_extra.get("ubicacion_oficina") or (f"Edificio del Poder Legislativo de {entidad}" if ambito == "Estatal" else f"Palacio Municipal de {municipio}")

        mandatos = [{
            "legislatura": 0,
            "romano": cargo,
            "periodo": periodo,
            "camara": camara,
            "partido": partido,
            "principio_eleccion": principio,
            "entidad": entidad
        }]

        linea_tiempo = [{
            "experiencia": f"{cargo} por {entidad}" + (f" ({distrito})" if distrito else ""),
            "periodo": periodo,
            "partido": partido,
            "partido_color": partido_color,
            "es_cargo_actual": False,
            "es_reciente": True
        }]

        nombre_completo = info_extra.get("nombre_completo") or (f"Dip. {nombre}" if ambito == "Estatal" else nombre)
        nombre_natural = formatear_nombre_natural(nombre_completo)

        perfil = {
            "slug": slug,
            "nombre_completo": nombre_completo,
            "nombre_natural": nombre_natural,
            "cargo": cargo,
            "camara": camara,
            "tipo_cargo": info_extra.get("tipo_cargo") or "Titular Electo",
            "nivel": ambito,
            "partido": partido,
            "partido_actual_color": partido_color,
            "entidad": entidad,
            "entidad_slug": entidad_slug,
            "distrito": distrito,
            "municipio": municipio,
            "foto_oficial_url": info_extra.get("foto_oficial_url") or foto,
            "referencia_sil": info_extra.get("referencia_sil"),
            "periodo": periodo,
            "legislatura": 0,
            "es_actual": False,
            "tiene_licencia": False,
            "principio_eleccion": principio,
            "ubicacion_oficina": ubicacion,
            "ultimo_grado_estudios": info_extra.get("ultimo_grado_estudios"),
            "preparacion_academica": info_extra.get("preparacion_academica"),
            "grado_academico": info_extra.get("ultimo_grado_estudios") or "Sin Registro",
            "disciplina": info_extra.get("preparacion_academica") or "-",
            "escolaridad_items": [],
            "linea_tiempo": linea_tiempo,
            "mandatos_historicos": mandatos,
            "legislaturas_disponibles": [],
            "asistencia_kpi": None,
            "iniciativas": [],
            "comisiones": comisiones,
            "redes_sociales": [],
            "disciplina_partidaria_pct": 100.0,
            "fuente_url": info_extra.get("fuente_url") or it.get("fuente_url") or "Archivo Histórico Parlamentario Oficial"
        }
        perfil["perfil_academico"] = education_service.clasificar_politico(perfil)
        return perfil

    def get_politico_by_slug(self, slug: str, legislatura: Optional[int] = None) -> Optional[Dict[str, Any]]:
        norm_slug = slugify(slug)
        clean_slug = re.sub(r"-\d+$", "", norm_slug)

        p = None
        if legislatura and legislatura in self.slug_index_by_leg:
            p = (self.slug_index_by_leg[legislatura].get(norm_slug) 
                 or self.slug_index_by_leg[legislatura].get(clean_slug) 
                 or self.slug_index_by_leg[legislatura].get(slug))
        if not p:
            p = (self.politicos_master.get(norm_slug) 
                 or self.politicos_master.get(clean_slug) 
                 or self.politicos_master.get(slug))
        if not p:
            p = self.find_politico_by_nombre(slug)

        if not p:
            # Buscar en el catálogo unificado de figuras públicas (Gobernadores, Alcaldes, multinivel)
            self.get_explorador_catalogo()
            cat_it = (
                getattr(self, "_explorador_catalogo_index", {}).get(norm_slug)
                or getattr(self, "_explorador_catalogo_index", {}).get(clean_slug)
                or getattr(self, "_explorador_catalogo_index", {}).get(slug)
            )
            if cat_it:
                p = self._build_perfil_from_catalogo_item(cat_it)

        if not p and hasattr(self, "_universal_by_slug"):
            uni_it = (
                self._universal_by_slug.get(norm_slug)
                or self._universal_by_slug.get(clean_slug)
                or self._universal_by_slug.get(slug)
            )
            if uni_it:
                p = self._build_perfil_from_universal_item(uni_it)

        if p:
            real_slug = p.get("slug") or norm_slug
            # Mandatos e histórico
            mandatos = self.mandatos_historicos.get(real_slug, p.get("mandatos_historicos", []))
            p["mandatos_historicos"] = mandatos
            p["legislaturas_disponibles"] = [m["legislatura"] for m in mandatos if m.get("legislatura")]

            # Enlazar suplente
            suplente_nom = p.get("suplente_nombre")
            if suplente_nom:
                suplente_pol = self.find_politico_by_nombre(suplente_nom)
                p["suplente_politico"] = suplente_pol
                p["suplente_slug"] = suplente_pol["slug"] if suplente_pol else slugify(suplente_nom)

            # Enlazar titular
            titular_nom = p.get("metadata", {}).get("titular_nombre") or p.get("titular_nombre")
            if titular_nom:
                titular_pol = self.find_politico_by_nombre(titular_nom)
                p["titular_politico"] = titular_pol
                p["titular_slug"] = titular_pol["slug"] if titular_pol else slugify(titular_nom)

            # Resolver foto local vs remota oficial
            ref_sil = str(p.get("referencia_sil") or "")
            foto_local = p.get("foto_local_path")
            tiene_foto_local = False
            if ref_sil and (settings.STORAGE_RAW_PHOTOS / f"{ref_sil}.jpg").exists():
                foto_url = f"/photos/{ref_sil}.jpg"
                tiene_foto_local = True
            elif (settings.STORAGE_RAW_PHOTOS / f"{real_slug}.jpg").exists():
                foto_url = f"/photos/{real_slug}.jpg"
                tiene_foto_local = True
            elif foto_local and (Path(settings.BASE_DIR) / str(foto_local).lstrip("/\\")).exists():
                foto_url = f"/{str(foto_local).lstrip('/')}"
                tiene_foto_local = True
            elif p.get("foto_oficial_url"):
                foto_url = p.get("foto_oficial_url")
            elif p.get("foto_url"):
                foto_url = p.get("foto_url")
            else:
                foto_url = ""

            p["foto_url"] = foto_url
            p["tiene_foto_local"] = tiene_foto_local

        return p

    def get_diputados(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return [p for p in self.get_politicos(legislatura) if p.get("camara") == "Diputados"]

    def get_senadores(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return [p for p in self.get_politicos(legislatura) if p.get("camara") == "Senadores"]

    def get_votaciones(self, tema: Optional[str] = None, resultado: Optional[str] = None) -> List[Dict[str, Any]]:
        self.load_asistencias_y_votaciones()
        res = self.votaciones
        if tema:
            res = [v for v in res if (v.get("tema_clave") or "").lower() == tema.lower()]
        if resultado:
            res = [v for v in res if (v.get("resultado") or "").lower() == resultado.lower()]
        return res

    def get_comisiones(self, legislatura: Optional[int] = 66) -> Dict[str, Dict[str, Any]]:
        if legislatura == 0:
            return self.comisiones_master
        target_leg = 66 if legislatura is None else legislatura
        if target_leg in self.comisiones_by_leg:
            return self.comisiones_by_leg[target_leg]
        return self.comisiones_by_leg.get(66, self.comisiones_master)

    def get_comision_by_slug(self, slug: str, legislatura: Optional[int] = None) -> Optional[Dict[str, Any]]:
        target_norm = slugify(slug)
        raw_com = None
        active_leg = legislatura

        # Detectar qué legislaturas tienen registrada esta comisión
        legs_disponibles = []
        for l_num in sorted(self.comisiones_by_leg.keys(), reverse=True):
            if target_norm in self.comisiones_by_leg[l_num]:
                legs_disponibles.append(l_num)

        if legislatura and legislatura > 0 and legislatura in self.comisiones_by_leg:
            raw_com = self.comisiones_by_leg[legislatura].get(target_norm)
            active_leg = legislatura
        elif legislatura == 0:
            raw_com = self.comisiones_master.get(target_norm)
            active_leg = 0
        else:
            # Si no se especificó legislatura, priorizar la actual (66) si existe
            if 66 in self.comisiones_by_leg and target_norm in self.comisiones_by_leg[66]:
                raw_com = self.comisiones_by_leg[66].get(target_norm)
                active_leg = 66
            elif legs_disponibles:
                # Tomar la legislatura más reciente en la que estuvo activa
                active_leg = legs_disponibles[0]
                raw_com = self.comisiones_by_leg[active_leg].get(target_norm)

        if not raw_com:
            raw_com = self.comisiones_master.get(target_norm)
            if not raw_com:
                for k, v in self.comisiones_master.items():
                    if target_norm in k or k in target_norm:
                        raw_com = v
                        break

        if not raw_com:
            return None

        # Build clean enriched representation
        todos_integrantes = list(raw_com.get("integrantes", []))
        if active_leg and active_leg > 0:
            integrantes = [m for m in todos_integrantes if int(m.get("legislatura") or 0) == active_leg]
            if not integrantes and todos_integrantes:
                integrantes = todos_integrantes
        else:
            integrantes = todos_integrantes

        # Deduplicar integrantes por (slug, legislatura, puesto)
        seen = set()
        dedup_integrantes = []
        for m in integrantes:
            k = (m.get("slug"), m.get("legislatura", 0), m.get("puesto"))
            if k not in seen:
                seen.add(k)
                dedup_integrantes.append(m)

        def rank_puesto(puesto_str: str) -> int:
            up = (puesto_str or "").upper()
            if "PRESID" in up:
                return 0
            if "SECRET" in up:
                return 1
            return 2

        dedup_integrantes.sort(key=lambda m: (
            rank_puesto(m.get("puesto", "")),
            -int(m.get("legislatura") or 0),
            m.get("nombre_completo", "")
        ))

        for m in dedup_integrantes:
            if not m.get("foto_url"):
                ref = str(m.get("referencia_sil") or "")
                if ref and (settings.STORAGE_RAW_PHOTOS / f"{ref}.jpg").exists():
                    m["foto_url"] = f"/photos/{ref}.jpg"
                elif m.get("foto_oficial_url"):
                    m["foto_url"] = m["foto_oficial_url"]
                else:
                    nom = m.get("nombre_completo") or "Legislador"
                    m["foto_url"] = f"https://ui-avatars.com/api/?name={nom.replace(' ', '+')}&background=E5E5EA&color=1C1C1E"

        presidencia = [m for m in dedup_integrantes if rank_puesto(m.get("puesto", "")) == 0]
        secretarias = [m for m in dedup_integrantes if rank_puesto(m.get("puesto", "")) == 1]
        resto = [m for m in dedup_integrantes if rank_puesto(m.get("puesto", "")) == 2]

        partidos_conteo = {}
        for m in dedup_integrantes:
            part = (m.get("partido") or "Sin Partido").strip()
            if not part:
                part = "Sin Partido"
            partidos_conteo[part] = partidos_conteo.get(part, 0) + 1
        partidos_conteo = dict(sorted(partidos_conteo.items(), key=lambda item: item[1], reverse=True))

        total_int = len(dedup_integrantes)
        partidos_stats = []
        for part, qty in partidos_conteo.items():
            pct = round((qty / total_int) * 100, 1) if total_int > 0 else 0.0
            partidos_stats.append({
                "partido": part,
                "cantidad": qty,
                "porcentaje": pct,
                "color": get_party_color(part)
            })

        return {
            "nombre": raw_com.get("nombre"),
            "slug": raw_com.get("slug"),
            "camara": raw_com.get("camara", "Congreso"),
            "total_integrantes": total_int,
            "integrantes": dedup_integrantes,
            "presidencia": presidencia,
            "secretarias": secretarias,
            "resto_integrantes": resto,
            "partidos_conteo": partidos_conteo,
            "partidos_stats": partidos_stats,
            "legislatura": active_leg if active_leg is not None else 66,
            "meta_legislatura": self.LEGISLATURAS_CATALOG.get(active_leg if active_leg else 66, {"romano": "Histórico", "periodo": "1997–2027"}),
            "legislaturas_disponibles": legs_disponibles,
        }

    def get_by_estado(self, estado_slug: str, legislatura: int = 66) -> List[Dict[str, Any]]:
        target_norm = normalizar_entidad(estado_slug)
        return [
            p for p in self.get_politicos(legislatura)
            if p.get("entidad_slug") == target_norm
        ]

    def get_estado_by_slug(self, slug: str, legislatura: int = 66) -> Optional[Dict[str, Any]]:
        target_norm = normalizar_entidad(slug)
        estados = self.get_estados_data(legislatura)
        estado = estados.get(target_norm)
        if not estado:
            return None

        estado_dict = dict(estado)
        pols = self.get_by_estado(target_norm, legislatura)
        estado_dict["diputados"] = [p for p in pols if p.get("camara") == "Diputados"]
        estado_dict["senadores"] = [p for p in pols if p.get("camara") == "Senadores"]
        estado_dict["todos"] = pols
        estado_dict["escolaridad_stats"] = education_service.generar_metricas(pols)
        return estado_dict

    def get_estados_data(self, legislatura: int = 66) -> Dict[str, Dict[str, Any]]:
        """Calcula representación exacta por estado para la legislatura especificada."""
        estados = {slug: dict(info) for slug, info in self.estados_catalog.items()}
        politicos_leg = self.get_politicos(legislatura)

        politicos_por_estado: Dict[str, List[Dict[str, Any]]] = {}
        for p in politicos_leg:
            e_slug = p.get("entidad_slug", "")
            if e_slug in estados:
                politicos_por_estado.setdefault(e_slug, []).append(p)

        for slug, est in estados.items():
            pols = politicos_por_estado.get(slug, [])
            conteo: Dict[str, int] = {}
            for p in pols:
                partido = p.get("partido") or "Sin Partido"
                conteo[partido] = conteo.get(partido, 0) + 1
            sorted_conteo = dict(sorted(conteo.items(), key=lambda item: item[1], reverse=True))
            est["total_representantes"] = len(pols)
            est["partido_mayoria"] = next(iter(sorted_conteo), "Morena") if sorted_conteo else "Morena"
            est["conteo_partidos"] = sorted_conteo if sorted_conteo else {}

            # Métricas de escolaridad para radiografía estatal
            m_esc = education_service.generar_metricas(pols)
            est["escolaridad"] = {
                "posgrados": m_esc.get("resumen_posgrados", {}).get("total", 0),
                "licenciaturas": m_esc.get("resumen_licenciaturas", {}).get("total", 0),
                "media_trunca": m_esc.get("resumen_media_trunca", {}).get("total", 0),
                "pct_posgrados": m_esc.get("resumen_posgrados", {}).get("porcentaje", 0.0),
                "pct_licenciaturas": m_esc.get("resumen_licenciaturas", {}).get("porcentaje", 0.0),
                "pct_media_trunca": m_esc.get("resumen_media_trunca", {}).get("porcentaje", 0.0),
            }

        return estados

    def get_mapa_capas(self, legislatura: int = 66) -> Dict[str, Any]:
        """
        Retorna las 4 capas de poder político en México estructuradas para el mapa interactivo:
        1. 'gobernadores': Titulares del Poder Ejecutivo Estatal (32 mandatarios)
        2. 'alcaldias': Fuerza política mayoritaria en los +2,470 ayuntamientos y municipios
        3. 'congresos_locales': Mayoría parlamentaria en las 32 legislaturas de los estados
        4. 'federal': Senadores y Diputados Federales en funciones (Congreso de la Unión)
        """
        if self._capas_mapa_cache and legislatura == 66:
            return self._capas_mapa_cache

        estados_cat = self.estados_catalog
        slug_by_norm = {normalizar_entidad(est["nombre"]): slug for slug, est in estados_cat.items()}

        # ----------------------------------------------------
        # 1. Capa: GOBERNADORES Y JEFATURA DE GOBIERNO
        # ----------------------------------------------------
        gov_by_slug: Dict[str, Dict[str, Any]] = {}
        dir_ejecutivo = settings.STORAGE_DATA_DIR / "ejecutivo"
        if dir_ejecutivo.exists():
            for fpath in dir_ejecutivo.glob("gobernadores_*.json"):
                try:
                    with open(fpath, encoding="utf-8") as f:
                        d = json.load(f)
                    slug = slug_by_norm.get(normalizar_entidad(d.get("entidad", "")))
                    if slug and d.get("mandatarios"):
                        gov_by_slug[slug] = d["mandatarios"][-1]
                except Exception:
                    continue

            cdmx_f = dir_ejecutivo / "jefes_gobierno_cdmx_1997_2030.json"
            if cdmx_f.exists():
                try:
                    with open(cdmx_f, encoding="utf-8") as f:
                        d = json.load(f)
                    if d.get("mandatarios"):
                        gov_by_slug["ciudad-de-mexico"] = d["mandatarios"][-1]
                except Exception:
                    pass

        gov_estados = {}
        gov_parties_conteo: Dict[str, int] = {}
        for slug, est in estados_cat.items():
            g = gov_by_slug.get(slug, {})
            p_raw = g.get("partido") or "Morena"
            can_p = get_canonical_party_name(p_raw)
            gov_parties_conteo[can_p] = gov_parties_conteo.get(can_p, 0) + 1
            titular = g.get("nombre_completo") or "Titular del Poder Ejecutivo"
            cargo = g.get("cargo_oficial") or ("Jefa de Gobierno" if slug == "ciudad-de-mexico" else "Gobernador(a) Constitucional")
            ini = str(g.get("fecha_inicio", ""))[:4] or "2021"
            fin = str(g.get("fecha_conclusion", ""))[:4] or "2027"
            periodo = f"{ini} — {fin}"

            gov_estados[slug] = {
                "slug": slug,
                "nombre": est["nombre"],
                "abbr": est["abbr"],
                "titular": titular,
                "cargo": cargo,
                "periodo": periodo,
                "partido_mayoria": can_p,
                "color_mayoria": get_party_color(can_p),
                "total_representantes": 1,
                "subtitulo_card": "Poder Ejecutivo Estatal",
                "mayoria_label": f"Mandato Constitucional {periodo}",
                "conteo_partidos": {can_p: 1},
                "boton_url": f"/politicos?q={slugify(titular)}",
                "boton_text": f"Ver perfil de {' '.join(titular.split()[:3]) if len(titular.split()) >= 3 else titular}"
            }

        gov_ranking = sorted(gov_parties_conteo.items(), key=lambda x: x[1], reverse=True)

        # ----------------------------------------------------
        # 2. Capa: ALCALDÍAS Y MUNICIPIOS
        # ----------------------------------------------------
        muns_by_ent: Dict[str, Dict[str, int]] = {}
        dir_muns = settings.STORAGE_DATA_DIR / "municipios"
        if dir_muns.exists():
            for fpath in dir_muns.glob("*.json"):
                try:
                    with open(fpath, encoding="utf-8") as f:
                        d = json.load(f)
                    alc = d.get("alcalde_actual")
                    if alc and alc.get("nombre_completo"):
                        ent_slug = slug_by_norm.get(normalizar_entidad(d.get("entidad") or alc.get("entidad") or ""))
                        if not ent_slug:
                            cve = str(d.get("cve_ent") or "")
                            ent_slug = slug_by_norm.get(cve)
                        if ent_slug:
                            raw_p = alc.get("partido")
                            if ent_slug == "oaxaca" and (not raw_p or "UYC" in str(raw_p).upper() or str(raw_p).strip() in ("Sin Partido", "")):
                                p = "Usos y Costumbres (SNI)"
                            else:
                                p = get_canonical_party_name(raw_p or "Sin Partido")
                            muns_by_ent.setdefault(ent_slug, {})[p] = muns_by_ent.setdefault(ent_slug, {}).get(p, 0) + 1
                except Exception:
                    continue

        alc_estados = {}
        alc_parties_lead: Dict[str, int] = {}
        for slug, est in estados_cat.items():
            conteo = muns_by_ent.get(slug, {})
            sorted_conteo = dict(sorted(conteo.items(), key=lambda x: x[1], reverse=True)) if conteo else {"Morena": 1}
            top_p = next(iter(sorted_conteo), "Morena")
            alc_parties_lead[top_p] = alc_parties_lead.get(top_p, 0) + 1
            total_muns = sum(sorted_conteo.values())
            top_count = sorted_conteo.get(top_p, 0)

            alc_estados[slug] = {
                "slug": slug,
                "nombre": est["nombre"],
                "abbr": est["abbr"],
                "partido_mayoria": top_p,
                "color_mayoria": get_party_color(top_p),
                "total_representantes": total_muns,
                "subtitulo_card": f"{total_muns} alcaldías y presidencias municipales",
                "mayoria_label": f"{top_count} de {total_muns} municipios",
                "conteo_partidos": sorted_conteo,
                "boton_url": f"/politicos?cargo=alcalde&entidad={slug}",
                "boton_text": f"Explorar alcaldías de {est['nombre']}"
            }

        alc_ranking = sorted(alc_parties_lead.items(), key=lambda x: x[1], reverse=True)

        # ----------------------------------------------------
        # 3. Capa: CONGRESOS LOCALES (DIPUTADOS LOCALES)
        # ----------------------------------------------------
        cong_files = defaultdict(list)
        for fpath in settings.STORAGE_DATA_DIR.glob("congreso_*_leg_*.json"):
            m = re.search(r"congreso_([a-z0-9]+)_leg_(\d+)\.json", fpath.name)
            if m:
                st, num_leg = m.group(1), int(m.group(2))
                cong_files[st].append((num_leg, fpath))

        loc_by_slug: Dict[str, Dict[str, Any]] = {}
        for st_key, lst in cong_files.items():
            lst.sort(key=lambda x: x[0], reverse=True)
            try:
                with open(lst[0][1], encoding="utf-8") as f:
                    d = json.load(f)
                legs = d.get("legisladores", [])
                if legs:
                    c = Counter(get_canonical_party_name(l.get("partido") or "Sin Partido") for l in legs)
                    ent = legs[0].get("entidad") or st_key
                    s = slug_by_norm.get(normalizar_entidad(ent))
                    if s:
                        loc_by_slug[s] = {
                            "leg": lst[0][0],
                            "total": len(legs),
                            "conteo": dict(sorted(c.items(), key=lambda x: x[1], reverse=True))
                        }
            except Exception:
                continue

        loc_estados = {}
        loc_parties_lead: Dict[str, int] = {}
        for slug, est in estados_cat.items():
            info = loc_by_slug.get(slug)
            if info:
                conteo = info["conteo"]
                top_p = next(iter(conteo), "Morena")
                total_loc = info["total"]
                top_c = conteo.get(top_p, 0)
                leg_txt = f"Legislatura Local ({info['leg']})"
            else:
                conteo = {"Morena": 1}
                top_p = "Morena"
                total_loc = 1
                top_c = 1
                leg_txt = "Legislatura Local"

            loc_parties_lead[top_p] = loc_parties_lead.get(top_p, 0) + 1
            loc_estados[slug] = {
                "slug": slug,
                "nombre": est["nombre"],
                "abbr": est["abbr"],
                "partido_mayoria": top_p,
                "color_mayoria": get_party_color(top_p),
                "total_representantes": total_loc,
                "subtitulo_card": f"{total_loc} diputados locales ({leg_txt})",
                "mayoria_label": f"{top_c} de {total_loc} curules locales",
                "conteo_partidos": conteo,
                "boton_url": f"/explorador?nivel=estatal&entidad={slug}",
                "boton_text": f"Ver diputados locales de {est['nombre']}"
            }

        loc_ranking = sorted(loc_parties_lead.items(), key=lambda x: x[1], reverse=True)

        # ----------------------------------------------------
        # 4. Capa: CONGRESO FEDERAL (LXVI LEGISLATURA)
        # ----------------------------------------------------
        fed_estados_raw = self.get_estados_data(legislatura)
        fed_estados = {}
        fed_parties_lead: Dict[str, int] = {}
        meta_leg = self.LEGISLATURAS_CATALOG.get(legislatura, self.LEGISLATURAS_CATALOG.get(66, {}))
        for slug, est in fed_estados_raw.items():
            conteo = {get_canonical_party_name(k): v for k, v in est.get("conteo_partidos", {}).items()}
            sorted_conteo = dict(sorted(conteo.items(), key=lambda x: x[1], reverse=True)) if conteo else {"Morena": 1}
            top_p = next(iter(sorted_conteo), "Morena")
            fed_parties_lead[top_p] = fed_parties_lead.get(top_p, 0) + 1
            total_reps = est.get("total_representantes", 0)
            top_c = sorted_conteo.get(top_p, 0)

            fed_estados[slug] = {
                "slug": slug,
                "nombre": est["nombre"],
                "abbr": est["abbr"],
                "partido_mayoria": top_p,
                "color_mayoria": get_party_color(top_p),
                "total_representantes": total_reps,
                "subtitulo_card": f"{total_reps} representantes federales",
                "mayoria_label": f"{top_c} de {total_reps} curules federales",
                "conteo_partidos": sorted_conteo,
                "boton_url": f"/estados/{slug}" + (f"?leg={legislatura}" if legislatura != 66 else ""),
                "boton_text": f"Ver los {total_reps} legisladores"
            }

        fed_ranking = sorted(fed_parties_lead.items(), key=lambda x: x[1], reverse=True)

        capas = {
            "gobernadores": {
                "id": "gobernadores",
                "titulo": "Gobernadores y Jefatura de Gobierno",
                "badge": "Poder Ejecutivo Estatal (32)",
                "descripcion": "Titulares del Poder Ejecutivo Estatal en las 32 entidades federativas.",
                "icono": "bi-person-badge-fill",
                "estados": gov_estados,
                "fuerza_nacional": gov_ranking,
                "label_fuerza": "Gobiernos Estatales"
            },
            "alcaldias": {
                "id": "alcaldias",
                "titulo": "Alcaldías y Presidencias Municipales",
                "badge": "+2,470 Ayuntamientos",
                "descripcion": "Fuerza política predominante en los ayuntamientos y cabildos municipales.",
                "icono": "bi-building",
                "estados": alc_estados,
                "fuerza_nacional": alc_ranking,
                "label_fuerza": "Mayoría Municipal por Entidad"
            },
            "congresos_locales": {
                "id": "congresos_locales",
                "titulo": "Congresos de los Estados (Diputados Locales)",
                "badge": "32 Legislaturas Estatales",
                "descripcion": "Fuerza parlamentaria mayoritaria en las asambleas y legislaturas de los estados.",
                "icono": "bi-journal-bookmark-fill",
                "estados": loc_estados,
                "fuerza_nacional": loc_ranking,
                "label_fuerza": "Mayoría Parlamentaria Local"
            },
            "federal": {
                "id": "federal",
                "titulo": f"Congreso de la Unión ({meta_leg.get('romano', 'LXVI')} Legislatura)",
                "badge": "San Lázaro & Senado",
                "descripcion": "Distribución de los 500 Diputados Federales y 128 Senadores de la República.",
                "icono": "bi-bank",
                "estados": fed_estados,
                "fuerza_nacional": fed_ranking,
                "label_fuerza": "Primera Fuerza Federal"
            }
        }

        if legislatura == 66:
            self._capas_mapa_cache = capas

        return capas

    def get_resumen_360_estados(self, legislatura: int = 66) -> Dict[str, Dict[str, Any]]:
        """
        Calcula y cachea la radiografía multinivel consolidada para cada una de las 32 entidades:
        - Poder: Gobernador, Alcaldías, Congreso Local, Congreso Federal (Diputados + Senadores).
        - Transparencia: % y conteo de 'chapulines' (cambios de bancada documentados).
        - Nivel de Estudios: % y total con estudios superiores (Licenciatura / Posgrado).
        - Enlaces directos prefiltrados al Explorador de Reportes y a las fichas cívicas.
        """
        if self._resumen_360_cache is not None and legislatura == 66:
            return self._resumen_360_cache

        capas = self.get_mapa_capas(legislatura)
        estados_cat = self.estados_catalog
        catalogo = self.get_explorador_catalogo()

        items_por_entidad: Dict[str, List[Dict[str, Any]]] = {}
        for it in catalogo:
            e_slug = it.get("entidad_slug")
            if e_slug:
                items_por_entidad.setdefault(e_slug, []).append(it)

        resumen: Dict[str, Dict[str, Any]] = {}

        for slug, est_meta in estados_cat.items():
            items = items_por_entidad.get(slug, [])
            
            gov = capas.get("gobernadores", {}).get("estados", {}).get(slug, {})
            alc = capas.get("alcaldias", {}).get("estados", {}).get(slug, {})
            cong_loc = capas.get("congresos_locales", {}).get("estados", {}).get(slug, {})
            fed = capas.get("federal", {}).get("estados", {}).get(slug, {})

            pols_fed = self.get_by_estado(slug, legislatura)
            dip_fed_count = len([p for p in pols_fed if p.get("camara") == "Diputados"])
            sen_fed_count = len([p for p in pols_fed if p.get("camara") == "Senadores"])
            total_fed = len(pols_fed)
            
            alc_total = alc.get("total_representantes", 0)
            cong_loc_total = cong_loc.get("total_representantes", 0)

            total_cargos = 1 + alc_total + cong_loc_total + total_fed

            chapulines = [x for x in items if x.get("tiene_transicion")]
            total_evaluados = len(items)
            chap_count = len(chapulines)
            chap_pct = round((chap_count / total_evaluados) * 100, 1) if total_evaluados > 0 else 0.0

            superiores = [x for x in items if any(w in (x.get("grado_academico") or "").lower() for w in ("licenciatura", "ingeniería", "ingenieria", "maestría", "maestria", "doctorado", "especialidad"))]
            posgrados = [x for x in items if any(w in (x.get("grado_academico") or "").lower() for w in ("maestría", "maestria", "doctorado", "especialidad"))]
            
            sup_count = len(superiores)
            sup_pct = round((sup_count / total_evaluados) * 100, 1) if total_evaluados > 0 else 0.0
            pos_count = len(posgrados)
            pos_pct = round((pos_count / total_evaluados) * 100, 1) if total_evaluados > 0 else 0.0

            resumen[slug] = {
                "slug": slug,
                "nombre": est_meta.get("nombre", slug.replace("-", " ").title()),
                "abbr": est_meta.get("abbr", "MEX"),
                "total_cargos": total_cargos,
                "total_evaluados": total_evaluados,
                "gobernador": {
                    "titular": gov.get("titular") or "Titular del Poder Ejecutivo",
                    "partido": gov.get("partido_mayoria") or "Morena",
                    "color": gov.get("color_mayoria") or get_party_color(gov.get("partido_mayoria") or "Morena"),
                    "periodo": gov.get("periodo") or ""
                },
                "alcaldias": {
                    "total": alc_total,
                    "partido": alc.get("partido_mayoria") or "Morena",
                    "color": alc.get("color_mayoria") or get_party_color(alc.get("partido_mayoria") or "Morena")
                },
                "congreso_local": {
                    "total": cong_loc_total,
                    "partido": cong_loc.get("partido_mayoria") or "Morena",
                    "color": cong_loc.get("color_mayoria") or get_party_color(cong_loc.get("partido_mayoria") or "Morena")
                },
                "federal": {
                    "total": total_fed,
                    "diputados": dip_fed_count,
                    "senadores": sen_fed_count,
                    "partido": fed.get("partido_mayoria") or "Morena",
                    "color": fed.get("color_mayoria") or get_party_color(fed.get("partido_mayoria") or "Morena")
                },
                "estudios": {
                    "superiores_total": sup_count,
                    "superiores_pct": sup_pct,
                    "posgrados_total": pos_count,
                    "posgrados_pct": pos_pct,
                },
                "chapulines": {
                    "total": chap_count,
                    "pct": chap_pct,
                    "fieles_total": max(0, total_evaluados - chap_count)
                },
                "explorador_url": f"/explorador?entidad={slug}",
                "chapulines_url": f"/explorador?entidad={slug}&transicion=con_cambio",
                "estudios_url": f"/explorador?entidad={slug}&educacion=maestria",
                "alcaldias_url": f"/explorador?entidad={slug}&cargo=alcalde",
                "federal_url": f"/estados/{slug}"
            }

        if legislatura == 66:
            self._resumen_360_cache = resumen

        return resumen

    def get_estadisticas_escolaridad(self, legislatura: int = 66) -> Dict[str, Any]:
        pols = self.get_politicos(legislatura)
        return education_service.generar_metricas(pols)

    def buscar(self, query: str, legislatura: Optional[int] = None, limite: int = 20) -> List[Dict[str, Any]]:
        if not query or len(query.strip()) < 2:
            return []
        q_raw = query.strip()
        q = slugify(q_raw)
        palabras = [slugify(w) for w in q_raw.split() if len(w) >= 2]
        if not palabras:
            palabras = [q]

        resultados = []
        slugs_agregados = set()

        # 1. Búsqueda en el Índice Universal (Federales de todas las legislaturas, Locales, Gobernadores, Alcaldes)
        if hasattr(self, "indice_busqueda_universal") and self.indice_busqueda_universal:
            for item in self.indice_busqueda_universal:
                s_text = item.get("search", "")
                slug = item.get("slug")
                if not slug or slug in slugs_agregados:
                    continue

                # Coincidencia: si contiene el slug completo o todas las palabras buscadas
                if q in s_text or all(p in s_text for p in palabras):
                    slugs_agregados.add(slug)
                    cargo_fmt = item.get("cargo") or item.get("camara") or "Servidor Público"
                    resultados.append({
                        "nombre": item.get("nombre"),
                        "slug": slug,
                        "cargo": cargo_fmt,
                        "camara": cargo_fmt,
                        "partido": item.get("partido"),
                        "entidad": item.get("entidad"),
                        "ambito": item.get("ambito", "Público"),
                        "foto_url": item.get("foto_url") or "",
                        "url": item.get("url") or f"/politicos/{slug}"
                    })
                    if len(resultados) >= limite:
                        break
        else:
            for p in self.get_politicos(legislatura or 66):
                nombre_slug = slugify(p.get("nombre_completo", ""))
                partido_slug = slugify(p.get("partido", ""))
                entidad_slug = p.get("entidad_slug", "")
                if q in nombre_slug or q in partido_slug or q in entidad_slug:
                    resultados.append({
                        "nombre": p.get("nombre_completo"),
                        "slug": p.get("slug"),
                        "cargo": p.get("camara"),
                        "camara": p.get("camara"),
                        "partido": p.get("partido"),
                        "entidad": p.get("entidad"),
                        "ambito": "Federal",
                        "url": f"/politicos/{p.get('slug')}"
                    })
                    if len(resultados) >= limite:
                        break

        # 2. Búsqueda en Comisiones del Congreso si aún hay cupo
        if len(resultados) < limite:
            for c_slug, c in self.comisiones_master.items():
                if q in c_slug or q in slugify(c.get("nombre", "")):
                    resultados.append({
                        "nombre": c.get("nombre"),
                        "slug": c_slug,
                        "cargo": "Comisión Parlamentaria",
                        "camara": c.get("camara_tag", "Comisión"),
                        "partido": f"{c.get('total_integrantes', len(c.get('integrantes', [])))} integrantes",
                        "entidad": "Congreso de la Unión",
                        "ambito": "Federal",
                        "url": f"/comisiones/{c_slug}"
                    })
                    if len(resultados) >= limite:
                        break

        return resultados

    def _calcular_transicion_partidista(self, p: Dict[str, Any], slug: str) -> Dict[str, Any]:
        """Calcula cronológicamente los saltos y transiciones partidistas documentados de una figura pública."""
        partidos_cronologicos = []

        # 1. Mandatos históricos parlamentarios
        mandatos = self.mandatos_historicos.get(slug, [])
        for m in sorted(mandatos, key=lambda x: int(x.get("legislatura") or 0)):
            p_part = m.get("partido")
            if p_part:
                c_nom = get_canonical_party_name(p_part)
                if c_nom and c_nom not in ("Sin Partido", "Independiente"):
                    ano_aprox = 1997 + (int(m.get("legislatura") or 57) - 57) * 3
                    partidos_cronologicos.append((ano_aprox, c_nom))

        # 2. Trayectoria y línea de tiempo
        for lt in p.get("linea_tiempo", []):
            p_det = lt.get("partido") or detect_party_from_text(lt.get("experiencia", ""))
            if p_det:
                c_nom = get_canonical_party_name(p_det)
                del_ano = lt.get("del_ano")
                try:
                    ano_num = int(del_ano) if del_ano and str(del_ano).isdigit() else 2010
                except Exception:
                    ano_num = 2010
                partidos_cronologicos.append((ano_num, c_nom))

        # 3. Antecedentes multinivel
        hitos_multi = self.indice_multinivel.get(slug, [])
        for hm in hitos_multi:
            hm_part = hm.get("partido")
            if hm_part:
                c_nom = get_canonical_party_name(hm_part)
                del_ano = hm.get("del_ano")
                try:
                    ano_num = int(del_ano) if del_ano and str(del_ano).isdigit() else 2010
                except Exception:
                    ano_num = 2010
                partidos_cronologicos.append((ano_num, c_nom))

        # 4. Partido del cargo actual
        partido_actual = get_canonical_party_name(p.get("partido") or "Sin Partido")
        if partido_actual not in ("Sin Partido", "Independiente"):
            partidos_cronologicos.append((2024, partido_actual))

        partidos_cronologicos.sort(key=lambda x: x[0])

        dedup_lista = []
        for _, p_nom in partidos_cronologicos:
            if not dedup_lista or dedup_lista[-1] != p_nom:
                dedup_lista.append(p_nom)

        if not dedup_lista and partido_actual:
            dedup_lista = [partido_actual]

        tiene_transicion = len(dedup_lista) > 1
        texto = " ➔ ".join(dedup_lista) if tiene_transicion else (f"{dedup_lista[0]} (Fiel)" if dedup_lista else "Sin registro")
        badges = [{"nombre": p_n, "color": get_party_color(p_n)} for p_n in dedup_lista]

        return {
            "lista": dedup_lista,
            "tiene_transicion": tiene_transicion,
            "texto": texto,
            "badges": badges,
            "total_partidos": len(dedup_lista)
        }

    def get_explorador_catalogo(self) -> List[Dict[str, Any]]:
        """
        Genera y cachea en memoria el catálogo unificado para el Explorador y Constructor de Reportes,
        integrando representantes federales y antecedentes ejecutivos y municipales con todas sus
        dimensiones de auditoría:
        - Transición partidista (saltos de partido)
        - Principio de elección (Mayoría Relativa vs Plurinominal)
        - Estatus de licencia y suplencia en funciones
        - Reelección consecutiva
        - Asistencia nominal y semáforo
        - Propuestas e iniciativas
        - Escolaridad y disciplina
        - Comisiones
        """
        if self._explorador_catalogo_cache is not None:
            return self._explorador_catalogo_cache

        catalogo = []
        slugs_vistos = set()

        # 1. Base Federal: LXVI Legislatura (en funciones)
        pols_66 = self.get_politicos(66)
        for p in pols_66:
            slug = p.get("slug") or slugify(p.get("nombre_completo", ""))
            if not slug or slug in slugs_vistos:
                continue
            slugs_vistos.add(slug)

            camara = p.get("camara", "Diputados")
            es_senador = (camara == "Senadores")
            cargo_oficial = "Senador(a) de la República" if es_senador else "Diputado(a) Federal"
            cargo_tipo = "senador" if es_senador else "diputado_federal"

            nombre_completo = p.get("nombre_completo", "")
            nombre_natural = p.get("nombre_natural") or formatear_nombre_natural(nombre_completo)

            partido_actual = get_canonical_party_name(p.get("partido") or "Sin Partido")
            partido_color = get_party_color(partido_actual)

            transicion_info = self._calcular_transicion_partidista(p, slug)

            pe_raw = (p.get("principio_eleccion") or "").strip()
            pe_lower = pe_raw.lower()
            if "mayor" in pe_lower:
                principio = "Mayoría Relativa"
                principio_tipo = "mayoria"
            elif "proporcional" in pe_lower or "pluri" in pe_lower:
                principio = "Plurinominal"
                principio_tipo = "plurinominal"
            elif "primera minor" in pe_lower:
                principio = "Primera Minoría"
                principio_tipo = "primera_minoria"
            else:
                principio = pe_raw if pe_raw else "Mayoría Relativa"
                principio_tipo = "mayoria"

            tiene_licencia = bool(p.get("tiene_licencia"))
            tipo_cargo = p.get("tipo_cargo") or "Propietario"
            es_suplente = ("suplente" in tipo_cargo.lower())

            suplente_nom = p.get("suplente_nombre")
            if suplente_nom:
                suplente_nom = suplente_nom.strip()
            suplente_slug = slugify(suplente_nom) if suplente_nom else None

            titular_nom = p.get("metadata", {}).get("titular_nombre") or p.get("titular_nombre")
            if titular_nom:
                titular_nom = titular_nom.strip()
            titular_slug = slugify(titular_nom) if titular_nom else None

            if tiene_licencia:
                estatus_licencia = "Con Licencia"
                licencia_tipo = "con_licencia"
                licencia_badge = "bg-warning-subtle text-warning-emphasis border border-warning-subtle"
            elif es_suplente:
                estatus_licencia = "Suplente en Funciones"
                licencia_tipo = "suplente"
                licencia_badge = "bg-purple-subtle text-purple border"
            else:
                estatus_licencia = "En Funciones (Titular)"
                licencia_tipo = "en_funciones"
                licencia_badge = "bg-success-subtle text-success border border-success-subtle"

            mandatos = self.mandatos_historicos.get(slug, [])
            legs = {int(m.get("legislatura") or 0) for m in mandatos} | {int(p.get("legislatura") or 66)}
            es_reelecto = (66 in legs and 65 in legs)
            reeleccion_texto = "Reelecto (LXV ➔ LXVI)" if es_reelecto else "Primer Periodo"

            asist_kpi = p.get("asistencia_kpi") or {}
            asist_pct = asist_kpi.get("porcentaje")
            asist_pct_fmt = f"{round(asist_pct, 1)}%" if asist_pct is not None else "Sin registro"
            asist_sesiones = f"{asist_kpi.get('asistencias', 0)}/{asist_kpi.get('total_sesiones', 0)}" if asist_pct is not None else "-"
            asist_semaforo = asist_kpi.get("semaforo", "gris")

            inis = p.get("iniciativas") or []
            total_iniciativas = len(inis)

            perfil_acad = p.get("perfil_academico") or {}
            grado_max = perfil_acad.get("grado_maximo_label") or p.get("ultimo_grado_estudios") or "Sin Registro"
            disciplina = perfil_acad.get("disciplina_principal") or p.get("preparacion_academica") or "General"

            coms = p.get("comisiones") or []
            total_comisiones = len(coms)
            presidencias = [c.get("comision") or c.get("nombre") for c in coms if "President" in (c.get("puesto") or "")]
            preside_comision = len(presidencias) > 0
            presidencias_str = ", ".join(presidencias) if presidencias else ""

            ref_sil = str(p.get("referencia_sil") or "")
            foto_local = p.get("foto_local_path")
            if ref_sil and (settings.STORAGE_RAW_PHOTOS / f"{ref_sil}.jpg").exists():
                foto_url = f"/photos/{ref_sil}.jpg"
            elif foto_local:
                foto_url = f"/{foto_local.lstrip('/')}"
            else:
                foto_url = p.get("foto_oficial_url") or ""

            item = {
                "id": slug,
                "slug": slug,
                "nombre": nombre_natural,
                "nombre_completo": nombre_completo,
                "foto_url": foto_url,
                "nivel": "Federal",
                "nivel_tipo": "federal",
                "cargo": cargo_oficial,
                "cargo_tipo": cargo_tipo,
                "entidad": p.get("entidad") or "México",
                "entidad_slug": p.get("entidad_slug") or "mexico",
                "municipio": p.get("distrito") or "",
                "partido_actual": partido_actual,
                "partido_actual_color": partido_color,
                "partido_slug": slugify(partido_actual),
                "transicion_lista": transicion_info["lista"],
                "tiene_transicion": transicion_info["tiene_transicion"],
                "transicion_texto": transicion_info["texto"],
                "transicion_badges": transicion_info["badges"],
                "principio_eleccion": principio,
                "principio_tipo": principio_tipo,
                "estatus_licencia": estatus_licencia,
                "licencia_tipo": licencia_tipo,
                "licencia_badge": licencia_badge,
                "tiene_licencia": tiene_licencia,
                "es_suplente": es_suplente,
                "suplente_nombre": suplente_nom,
                "suplente_slug": suplente_slug,
                "titular_nombre": titular_nom,
                "titular_slug": titular_slug,
                "es_reelecto": es_reelecto,
                "reeleccion_texto": reeleccion_texto,
                "asistencia_pct": asist_pct,
                "asistencia_pct_fmt": asist_pct_fmt,
                "asistencia_sesiones": asist_sesiones,
                "asistencia_semaforo": asist_semaforo,
                "iniciativas_total": total_iniciativas,
                "grado_academico": grado_max,
                "disciplina": disciplina,
                "comisiones_total": total_comisiones,
                "preside_comision": preside_comision,
                "presidencias_str": presidencias_str,
                "periodo": "2024–2030" if es_senador else "2024–2027",
                "legislatura": 66,
                "es_actual": True,
                "url": f"/politicos/{slug}",
                "fuente_url": f"http://sil.gobernacion.gob.mx/Librerias/RptDetalleLegislador.php?SID=&Clave={ref_sil}" if ref_sil else ""
            }
            catalogo.append(item)

        # 2. Base Ejecutiva Estatal (Cronologías oficiales de Gobernadores de las 32 Entidades)
        ejecutivo_dir = settings.STORAGE_DATA_DIR / "ejecutivo"
        if ejecutivo_dir.exists():
            ejec_archivos = sorted(ejecutivo_dir.glob("gobernadores_*.json"))
            cdmx_archivo = ejecutivo_dir / "jefes_gobierno_cdmx_1997_2030.json"
            if cdmx_archivo.exists():
                ejec_archivos.append(cdmx_archivo)

            for ef in ejec_archivos:
                try:
                    with open(ef, "rb") as f:
                        raw_ejec = orjson.loads(f.read())
                except Exception:
                    continue

                ent_nom = raw_ejec.get("entidad") or ("Ciudad de México" if "cdmx" in ef.name else "México")
                ent_slug = normalizar_entidad(slugify(ent_nom))
                mandatarios = raw_ejec.get("mandatarios") or []

                for idx, m in enumerate(mandatarios):
                    nom_comp = (m.get("nombre_completo") or "").strip()
                    if not nom_comp:
                        continue
                    nom_nat = nom_comp
                    p_slug = slugify(nom_comp)

                    m_partido = get_canonical_party_name(m.get("partido") or "Sin Partido")
                    m_color = get_party_color(m_partido)

                    fc = m.get("fecha_conclusion") or ""
                    es_ultimo = (idx == len(mandatarios) - 1)
                    es_activo = es_ultimo and (fc >= "2024-10-01")

                    if es_activo:
                        estatus_lic = "En Funciones (Titular)"
                        lic_tipo = "en_funciones"
                        lic_badge = "bg-success-subtle text-success border border-success-subtle"
                    else:
                        estatus_lic = "Histórico / Concluido"
                        lic_tipo = "historico"
                        lic_badge = "bg-secondary-subtle text-secondary border"

                    fi_ano = (m.get("fecha_inicio") or "")[:4]
                    fc_ano = fc[:4]
                    periodo_str = f"{fi_ano}–{fc_ano}" if fi_ano and fc_ano else (m.get("periodo") or "Histórico")

                    item_id = f"gob_{ent_slug}_{p_slug}_{fi_ano or idx}"
                    if item_id in slugs_vistos:
                        continue
                    slugs_vistos.add(item_id)

                    pol_obj = self.politicos_master.get(p_slug) or self.find_politico_by_nombre(nom_comp)
                    edu_info = self.GOBERNADORES_DATA.get(p_slug)

                    # Foto oficial verificada
                    if pol_obj and pol_obj.get("foto_oficial_url"):
                        foto_url = pol_obj.get("foto_oficial_url")
                    elif pol_obj and pol_obj.get("referencia_sil") and (settings.STORAGE_RAW_PHOTOS / f"{pol_obj.get('referencia_sil')}.jpg").exists():
                        foto_url = f"/photos/{pol_obj.get('referencia_sil')}.jpg"
                    elif edu_info and edu_info.get("foto"):
                        foto_url = edu_info["foto"]
                    else:
                        nom_enc = nom_comp.replace(" ", "+")
                        foto_url = f"https://ui-avatars.com/api/?name={nom_enc}&background=F1F5F9&color=475569"

                    # Transición partidista
                    if pol_obj:
                        trans_info = self._calcular_transicion_partidista(pol_obj, pol_obj.get("slug") or p_slug)
                    else:
                        trans_info = {
                            "lista": [m_partido],
                            "tiene_transicion": False,
                            "texto": f"{m_partido} (Fiel)",
                            "badges": [{"nombre": m_partido, "color": m_color}],
                            "total_partidos": 1
                        }

                    # Grado académico y disciplina
                    grado_max = "Sin Registro"
                    disciplina = "-"
                    if pol_obj:
                        perfil_acad = pol_obj.get("perfil_academico") or education_service.clasificar_politico(pol_obj)
                        g_label = perfil_acad.get("grado_maximo_label") or pol_obj.get("ultimo_grado_estudios")
                        d_label = perfil_acad.get("disciplina_principal") or pol_obj.get("preparacion_academica")
                        if g_label and g_label != "Sin Registro":
                            grado_max = g_label
                            disciplina = d_label or "General"

                    if (grado_max == "Sin Registro" or not grado_max) and edu_info:
                        grado_max = edu_info.get("grado", "Sin Registro")
                        disciplina = edu_info.get("disciplina", "-")

                    url_destino = f"/politicos/{p_slug}"

                    catalogo.append({
                        "id": item_id,
                        "slug": p_slug,
                        "nombre": nom_nat,
                        "nombre_completo": nom_comp,
                        "foto_url": foto_url,
                        "nivel": "Estatal",
                        "nivel_tipo": "estatal",
                        "cargo": m.get("cargo_oficial") or f"Gobernador(a) Constitucional de {ent_nom}",
                        "cargo_tipo": "gobernador",
                        "entidad": ent_nom,
                        "entidad_slug": ent_slug,
                        "municipio": "Ámbito Estatal",
                        "partido_actual": m_partido,
                        "partido_actual_color": m_color,
                        "partido_slug": slugify(m_partido),
                        "transicion_lista": trans_info["lista"],
                        "tiene_transicion": trans_info["tiene_transicion"],
                        "transicion_texto": trans_info["texto"],
                        "transicion_badges": trans_info["badges"],
                        "principio_eleccion": "Elección Popular Directa",
                        "principio_tipo": "mayoria",
                        "estatus_licencia": estatus_lic,
                        "licencia_tipo": lic_tipo,
                        "licencia_badge": lic_badge,
                        "tiene_licencia": False,
                        "es_suplente": False,
                        "suplente_nombre": None,
                        "suplente_slug": None,
                        "titular_nombre": None,
                        "titular_slug": None,
                        "es_reelecto": False,
                        "reeleccion_texto": "Mandato Constitucional",
                        "asistencia_pct": None,
                        "asistencia_pct_fmt": "-",
                        "asistencia_sesiones": "-",
                        "asistencia_semaforo": "gris",
                        "iniciativas_total": len(pol_obj.get("iniciativas", [])) if pol_obj else 0,
                        "grado_academico": grado_max,
                        "disciplina": disciplina,
                        "comisiones_total": len(pol_obj.get("comisiones", [])) if pol_obj else 0,
                        "preside_comision": False,
                        "presidencias_str": "",
                        "periodo": periodo_str,
                        "legislatura": 0,
                        "es_actual": es_activo,
                        "url": url_destino,
                        "fuente_url": m.get("metadata", {}).get("fuente_url") or raw_ejec.get("portal_oficial") or ""
                    })

        # 3. Base Municipal Nacional (2,474 Alcaldías y Presidencias Municipales Vigentes 2024-2027)
        alcaldes_file = settings.STORAGE_DATA_DIR / "alcaldes" / "directorio_nacional_alcaldes_vigentes.json"
        if alcaldes_file.exists():
            try:
                with open(alcaldes_file, "rb") as f:
                    raw_alcaldes = orjson.loads(f.read())
                alcaldes_dict = raw_alcaldes.get("alcaldes") or {}
            except Exception:
                alcaldes_dict = {}

            for cvegeo, alc in alcaldes_dict.items():
                nom_comp = (alc.get("nombre_completo") or "").strip()
                if not nom_comp:
                    continue
                nom_nat = nom_comp
                p_slug = slugify(nom_comp)

                ent_nom = alc.get("entidad") or "México"
                ent_slug = normalizar_entidad(slugify(ent_nom))
                mun_nom = alc.get("municipio") or ""

                partido_actual = get_canonical_party_name(alc.get("partido") or "Sin Partido")
                partido_color = get_party_color(partido_actual)

                es_re = bool(alc.get("reeleccion"))
                re_txt = "Reelecto Consecutivo" if es_re else "Primer Periodo"

                item_id = f"alcalde_{cvegeo}"
                if item_id in slugs_vistos:
                    continue
                slugs_vistos.add(item_id)

                pol_obj = self.politicos_master.get(p_slug) or self.find_politico_by_nombre(nom_comp)
                alc_info = self.ALCALDES_DATA.get(p_slug)
                linea_tiempo_custom = None
                escolaridad_items_custom = None
                redes_sociales_custom = None

                if pol_obj:
                    foto_url = pol_obj.get("foto_oficial_url") or (f"/photos/{pol_obj.get('referencia_sil')}.jpg" if pol_obj.get('referencia_sil') else "")
                    trans_info = self._calcular_transicion_partidista(pol_obj, pol_obj.get("slug") or p_slug)
                    perfil_acad = pol_obj.get("perfil_academico") or education_service.clasificar_politico(pol_obj)
                    grado_max = perfil_acad.get("grado_maximo_label") or pol_obj.get("ultimo_grado_estudios") or "Sin Registro"
                    disciplina = perfil_acad.get("disciplina_principal") or pol_obj.get("preparacion_academica") or "General"
                elif alc_info:
                    nom_comp = alc_info.get("nombre_oficial") or nom_comp
                    nom_nat = alc_info.get("nombre_oficial") or nom_nat
                    foto_url = alc_info.get("foto_url") or f"https://ui-avatars.com/api/?name={nom_comp.replace(' ', '+')}&background=F1F5F9&color=475569"
                    grado_max = alc_info.get("grado") or "Sin Registro"
                    disciplina = alc_info.get("disciplina") or "-"
                    linea_tiempo_custom = alc_info.get("linea_tiempo")
                    escolaridad_items_custom = alc_info.get("escolaridad_items")
                    redes_sociales_custom = alc_info.get("redes_sociales")
                    trans_info = {
                        "lista": [partido_actual],
                        "tiene_transicion": False,
                        "texto": f"{partido_actual} (Fiel)",
                        "badges": [{"nombre": partido_actual, "color": partido_color}],
                        "total_partidos": 1
                    }
                else:
                    nom_enc = nom_comp.replace(" ", "+")
                    foto_url = f"https://ui-avatars.com/api/?name={nom_enc}&background=F1F5F9&color=475569"
                    trans_info = {
                        "lista": [partido_actual],
                        "tiene_transicion": False,
                        "texto": f"{partido_actual} (Fiel)",
                        "badges": [{"nombre": partido_actual, "color": partido_color}],
                        "total_partidos": 1
                    }
                    grado_max = "Sin Registro"
                    disciplina = "-"

                url_destino = f"/politicos/{p_slug}"

                catalogo.append({
                    "id": item_id,
                    "slug": p_slug,
                    "nombre": nom_nat,
                    "nombre_completo": nom_comp,
                    "foto_url": foto_url,
                    "nivel": "Municipal",
                    "nivel_tipo": "municipal",
                    "cargo": alc.get("tipo_cargo") or f"Presidente(a) Municipal Constitucional de {mun_nom}",
                    "cargo_tipo": "alcalde",
                    "entidad": ent_nom,
                    "entidad_slug": ent_slug,
                    "municipio": mun_nom,
                    "partido_actual": partido_actual,
                    "partido_actual_color": partido_color,
                    "partido_slug": slugify(partido_actual),
                    "transicion_lista": trans_info["lista"],
                    "tiene_transicion": trans_info["tiene_transicion"],
                    "transicion_texto": trans_info["texto"],
                    "transicion_badges": trans_info["badges"],
                    "principio_eleccion": "Mayoría Relativa",
                    "principio_tipo": "mayoria",
                    "estatus_licencia": "En Funciones (Titular)",
                    "licencia_tipo": "en_funciones",
                    "licencia_badge": "bg-success-subtle text-success border border-success-subtle",
                    "tiene_licencia": False,
                    "es_suplente": False,
                    "suplente_nombre": None,
                    "suplente_slug": None,
                    "titular_nombre": None,
                    "titular_slug": None,
                    "es_reelecto": es_re,
                    "reeleccion_texto": re_txt,
                    "asistencia_pct": None,
                    "asistencia_pct_fmt": "-",
                    "asistencia_sesiones": "-",
                    "asistencia_semaforo": "gris",
                    "iniciativas_total": len(pol_obj.get("iniciativas", [])) if pol_obj else 0,
                    "grado_academico": grado_max,
                    "disciplina": disciplina,
                    "comisiones_total": len(pol_obj.get("comisiones", [])) if pol_obj else 0,
                    "preside_comision": False,
                    "presidencias_str": "",
                    "periodo": alc.get("periodo") or "2024–2027",
                    "legislatura": 0,
                    "es_actual": True,
                    "url": url_destino,
                    "linea_tiempo": linea_tiempo_custom,
                    "escolaridad_items": escolaridad_items_custom,
                    "redes_sociales": redes_sociales_custom,
                    "fuente_url": alc.get("metadata", {}).get("fuente_url") or "https://github.com/emagar/elecRetrns / INAFED"
                })

        # 4. Base Multinivel Histórica Deduplicada (Congresos Locales y Alcaldías Anteriores)
        for p_slug, hitos in self.indice_multinivel.items():
            pol_obj = self.politicos_master.get(p_slug) or self.find_politico_by_nombre(p_slug.replace("-", " "))
            if pol_obj:
                base_nombre = pol_obj.get("nombre_completo") or p_slug.replace("-", " ").title()
                nombre_nat = pol_obj.get("nombre_natural") or formatear_nombre_natural(base_nombre)
                perfil_acad = pol_obj.get("perfil_academico") or education_service.clasificar_politico(pol_obj)
                grado_max = perfil_acad.get("grado_maximo_label") or pol_obj.get("ultimo_grado_estudios") or "Sin Registro"
                disciplina = perfil_acad.get("disciplina_principal") or pol_obj.get("preparacion_academica") or "General"
                foto_url = pol_obj.get("foto_oficial_url") or ""
            else:
                base_nombre = p_slug.replace("-", " ").title()
                nombre_nat = base_nombre
                grado_max = "Sin Registro"
                disciplina = "-"
                foto_url = ""

            for hito in hitos:
                h_nivel = hito.get("nivel")
                # Excluimos federales y gobernadores porque ya están cubiertos de forma oficial y exhaustiva
                if h_nivel in ("legislativo_federal", "ejecutivo_estatal"):
                    continue

                cargo_nom = hito.get("cargo") or ""
                nivel_label = "Municipal" if h_nivel == "municipal" else "Estatal"
                nivel_tipo = "municipal" if h_nivel == "municipal" else "estatal"
                c_tipo = "alcalde" if h_nivel == "municipal" else "diputado_local"

                h_entidad = hito.get("entidad") or "México"
                h_ent_slug = slugify(h_entidad)
                h_mun = hito.get("municipio") or ""
                del_ano = hito.get("del_ano") or 0

                # Clave canónica de deduplicación para evitar triplicados por permutaciones de nombres
                if h_nivel == "municipal":
                    canon_key = f"hist_mun_{h_ent_slug}_{slugify(h_mun)}_{del_ano}"
                else:
                    canon_nom = "-".join(sorted(p_slug.split("-")))
                    canon_key = f"hist_loc_{h_ent_slug}_{del_ano}_{canon_nom}"

                if canon_key in slugs_vistos:
                    continue
                slugs_vistos.add(canon_key)

                h_partido = get_canonical_party_name(hito.get("partido") or "Sin Partido")

                catalogo.append({
                    "id": canon_key,
                    "slug": p_slug,
                    "nombre": nombre_nat,
                    "nombre_completo": base_nombre,
                    "foto_url": foto_url,
                    "nivel": nivel_label,
                    "nivel_tipo": nivel_tipo,
                    "cargo": cargo_nom,
                    "cargo_tipo": c_tipo,
                    "entidad": h_entidad,
                    "entidad_slug": h_ent_slug,
                    "municipio": h_mun,
                    "partido_actual": h_partido,
                    "partido_actual_color": get_party_color(h_partido),
                    "partido_slug": slugify(h_partido),
                    "transicion_lista": [h_partido],
                    "tiene_transicion": False,
                    "transicion_texto": f"{h_partido} (Fiel)",
                    "transicion_badges": [{"nombre": h_partido, "color": get_party_color(h_partido)}],
                    "principio_eleccion": "Elección Popular Directa",
                    "principio_tipo": "mayoria",
                    "estatus_licencia": "Histórico / Concluido",
                    "licencia_tipo": "historico",
                    "licencia_badge": "bg-secondary-subtle text-secondary border",
                    "tiene_licencia": False,
                    "es_suplente": False,
                    "suplente_nombre": None,
                    "suplente_slug": None,
                    "titular_nombre": None,
                    "titular_slug": None,
                    "es_reelecto": False,
                    "reeleccion_texto": "Mandato Registrado",
                    "asistencia_pct": None,
                    "asistencia_pct_fmt": "-",
                    "asistencia_sesiones": "-",
                    "asistencia_semaforo": "gris",
                    "iniciativas_total": 0,
                    "grado_academico": grado_max,
                    "disciplina": disciplina,
                    "comisiones_total": 0,
                    "preside_comision": False,
                    "presidencias_str": "",
                    "periodo": hito.get("periodo") or "Histórico",
                    "legislatura": 0,
                    "es_actual": False,
                    "url": f"/politicos/{p_slug}",
                    "fuente_url": hito.get("fuente") or ""
                })

        self._explorador_catalogo_cache = catalogo
        # Construir índice rápido para resolución de perfiles y slugs
        idx = {}
        for it in catalogo:
            s = it.get("slug")
            if s and s not in idx:
                idx[s] = it
            s_nom = slugify(it.get("nombre") or "")
            if s_nom and s_nom not in idx:
                idx[s_nom] = it
            s_comp = slugify(it.get("nombre_completo") or "")
            if s_comp and s_comp not in idx:
                idx[s_comp] = it
        self._explorador_catalogo_index = idx
        return catalogo

    def consultar_explorador(
        self,
        q: str = "",
        nivel: str = "todos",
        cargo: str = "todos",
        entidad: str = "todos",
        partido: str = "todos",
        transicion: str = "todos",
        principio: str = "todos",
        licencia: str = "todos",
        reeleccion: str = "todos",
        periodo: str = "todos",
        educacion: str = "todos",
        page: int = 1,
        limit: int = 50
    ) -> Dict[str, Any]:
        """Ejecuta consulta multivariable con filtros cruzados, métricas instantáneas y paginación rápida."""
        todos = self.get_explorador_catalogo()
        q_norm = q.strip().lower()

        filtrados = []
        for it in todos:
            # 1. Filtro texto libre
            if q_norm:
                corpus = f"{it['nombre']} {it['nombre_completo']} {it['cargo']} {it['entidad']} {it['municipio']} {it['partido_actual']} {it['transicion_texto']} {it['principio_eleccion']}".lower()
                if q_norm not in corpus:
                    continue

            # 2. Filtro Nivel
            if nivel != "todos" and it["nivel_tipo"] != nivel.lower():
                continue

            # 3. Filtro Cargo
            if cargo != "todos" and it["cargo_tipo"] != cargo.lower():
                continue

            # 4. Filtro Entidad
            if entidad != "todos" and it["entidad_slug"] != entidad.lower():
                continue

            # 5. Filtro Partido
            if partido != "todos":
                p_slug = it["partido_slug"]
                if partido.lower() not in p_slug and p_slug not in partido.lower():
                    continue

            # 6. Filtro Transición Partidista
            if transicion == "con_cambio" and not it["tiene_transicion"]:
                continue
            if transicion == "sin_cambio" and it["tiene_transicion"]:
                continue

            # 7. Filtro Principio de Elección
            if principio != "todos" and it["principio_tipo"] != principio.lower():
                continue

            # 8. Filtro Estatus / Licencia (Estricto por licencia_tipo)
            if licencia == "en_funciones":
                if it.get("licencia_tipo") != "en_funciones":
                    continue
            elif licencia == "con_licencia":
                if it.get("licencia_tipo") != "con_licencia":
                    continue
            elif licencia == "suplentes":
                if it.get("licencia_tipo") != "suplente":
                    continue
            elif licencia == "historico":
                if it.get("licencia_tipo") != "historico":
                    continue

            # 9. Filtro Reelección
            if reeleccion == "reelecto" and not it["es_reelecto"]:
                continue
            if reeleccion == "primer_periodo" and it["es_reelecto"]:
                continue

            # 10. Filtro Periodo / Vigencia
            if periodo == "actuales":
                if not it.get("es_actual"):
                    continue
            elif periodo == "lxvi":
                if it.get("legislatura") != 66:
                    continue
            elif periodo == "2024_2027":
                if "2024–2027" not in str(it.get("periodo", "")) and "2024-2027" not in str(it.get("periodo", "")):
                    continue
            elif periodo == "2024_2030":
                if "2024–2030" not in str(it.get("periodo", "")) and "2024-2030" not in str(it.get("periodo", "")):
                    continue
            elif periodo == "historico":
                if it.get("es_actual"):
                    continue

            # 11. Filtro Educación / Grado Académico
            if educacion != "todos":
                g_str = (it.get("grado_academico") or "").lower()
                if educacion == "doctorado" and "doctor" not in g_str:
                    continue
                elif educacion == "maestria" and "maestr" not in g_str:
                    continue
                elif educacion == "especialidad" and "especialidad" not in g_str:
                    continue
                elif educacion == "licenciatura" and ("licenciatura" not in g_str or "trunca" in g_str or "pasante" in g_str):
                    continue
                elif educacion == "pasante_trunca" and ("pasante" not in g_str and "trunca" not in g_str):
                    continue
                elif educacion == "tecnico" and ("téc" not in g_str and "tec" not in g_str and "normalista" not in g_str):
                    continue
                elif educacion == "preparatoria" and ("preparatoria" not in g_str and "bachiller" not in g_str):
                    continue
                elif educacion == "secundaria" and "secundaria" not in g_str:
                    continue
                elif educacion == "sin_registro" and (g_str and "sin registro" not in g_str):
                    continue

            filtrados.append(it)

        total_filtrados = len(filtrados)
        total_paginas = (total_filtrados + limit - 1) // limit if limit > 0 else 1
        offset = (page - 1) * limit
        lote = filtrados[offset:offset + limit]

        kpis = {
            "total": total_filtrados,
            "con_transicion": sum(1 for x in filtrados if x["tiene_transicion"]),
            "con_licencia": sum(1 for x in filtrados if x.get("licencia_tipo") == "con_licencia"),
            "plurinominales": sum(1 for x in filtrados if x["principio_tipo"] == "plurinominal"),
            "mayoria": sum(1 for x in filtrados if x["principio_tipo"] == "mayoria"),
            "reelectos": sum(1 for x in filtrados if x["es_reelecto"]),
        }

        return {
            "ok": True,
            "page": page,
            "limit": limit,
            "total_catalogo": len(todos),
            "total_filtrados": total_filtrados,
            "total_paginas": total_paginas,
            "has_more": (page < total_paginas),
            "kpis": kpis,
            "items": lote
        }

data_service = DataService()
