import re
import unicodedata
from pathlib import Path
from typing import List, Dict, Any, Optional
from datetime import date
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
    """Normaliza nombres de estados resolviendo variantes históricas como Distrito Federal."""
    if not entidad:
        return ""
    norm = slugify(entidad)
    if norm in ("distrito-federal", "df", "cdmx"):
        return "ciudad-de-mexico"
    if norm in ("mexico", "edomex"):
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
    if "MC" in upper or "CIUDADANO" in upper or "CONVERGENCIA" in upper: return "#FF8300"
    if "PES" in upper: return "#582C83"
    if "PRD" in upper: return "#FFD100"
    if "PANAL" in upper or "NUEVA ALIANZA" in upper: return "#00A896"
    if "INDEPENDIENTE" in upper or "SIN PARTIDO" in upper: return "#64748B"
    return "#6C757D"

def get_canonical_party_name(partido: str) -> str:
    p = (partido or "").strip()
    upper = p.upper()
    if "MORENA" in upper: return "Morena"
    if "PAN" in upper and "PANAL" not in upper: return "PAN"
    if "VERDE" in upper or "PVEM" in upper: return "PVEM"
    if "PRI" in upper: return "PRI"
    if "PT" in upper or "TRABAJO" in upper: return "PT"
    if "MC" in upper or "CIUDADANO" in upper or "CONVERGENCIA" in upper: return "Movimiento Ciudadano"
    if "PES" in upper: return "PES"
    if "PRD" in upper: return "PRD"
    if "PANAL" in upper or "NUEVA ALIANZA" in upper: return "Nueva Alianza"
    if "INDEPENDIENTE" in upper or "SIN PARTIDO" in upper: return "Independiente"
    return p or "Independiente"

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
        for vf in json_dir.glob("votaciones_periodo_*_leg_66.json"):
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

        self.asistencias = self.asistencias_nominales

    def load_all_legislaturas(self):
        json_dir = settings.STORAGE_DATA_DIR
        if not json_dir.exists():
            return

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

                        # Cruzar iniciativas
                        norm_nom = slugify(nombre)
                        mis_iniciativas = [
                            ini for ini in self.iniciativas
                            if norm_nom in slugify(ini.get("promovente_principal", ""))
                        ]
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
        computando sus roles acumulados a lo largo de su carrera pública
        (Senador, Diputado Federal, Diputado Local, Alcalde, Gobernador) y estatus en funciones.
        """
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

            # 4. Alcalde / Munícipe
            has_alcalde = bool(re.search(r"\b(president[ea]\s+municipal|alcald[ea]|jef[ea]\s+delegacional|municipe)\b", corpus, re.IGNORECASE))

            # 5. Gobernador
            has_gobernador = bool(re.search(r"\b(gobernador[a]?\s+constitucional|jef[ea]\s+de\s+gobierno|gobernador[a]?\s+interin[ao])\b", corpus, re.IGNORECASE))

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
                badges.append({
                    "label": "Alcalde / Edil",
                    "icono": "bi-houses",
                    "tipo": "alcalde",
                    "badge_class": "bg-warning-subtle text-warning-emphasis border border-warning-subtle"
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
            item["foto_url"] = foto_url
            item["roles_flags"] = {
                "senador": has_senador,
                "diputado_federal": has_dip_fed,
                "diputado_local": has_dip_local,
                "alcalde": has_alcalde,
                "gobernador": has_gobernador
            }
            item["cargos_badges"] = badges
            item["es_activo"] = es_activo
            item["cargo_resumen"] = cargo_resumen
            item["legislaturas_lista"] = legs_disponibles
            directorio.append(item)

        directorio.sort(key=lambda x: unicodedata.normalize("NFKD", x.get("nombre_completo", "")).encode("ascii", "ignore").lower())
        return directorio

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

    def get_politico_by_slug(self, slug: str, legislatura: Optional[int] = None) -> Optional[Dict[str, Any]]:
        p = None
        if legislatura and legislatura in self.slug_index_by_leg:
            p = self.slug_index_by_leg[legislatura].get(slug)
        if not p:
            p = self.politicos_master.get(slug)

        if p:
            # Mandatos e histórico
            mandatos = self.mandatos_historicos.get(slug, [])
            p["mandatos_historicos"] = mandatos
            p["legislaturas_disponibles"] = [m["legislatura"] for m in mandatos]

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

        return p

    def get_diputados(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return [p for p in self.get_politicos(legislatura) if p.get("camara") == "Diputados"]

    def get_senadores(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return [p for p in self.get_politicos(legislatura) if p.get("camara") == "Senadores"]

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

        return estados

    def get_estadisticas_escolaridad(self, legislatura: int = 66) -> Dict[str, Any]:
        pols = self.get_politicos(legislatura)
        return education_service.generar_metricas(pols)

    def buscar(self, query: str, legislatura: int = 66, limite: int = 20) -> List[Dict[str, Any]]:
        if not query or len(query.strip()) < 2:
            return []
        q = slugify(query)
        resultados = []
        for p in self.get_politicos(legislatura):
            nombre_slug = slugify(p.get("nombre_completo", ""))
            partido_slug = slugify(p.get("partido", ""))
            entidad_slug = p.get("entidad_slug", "")
            if q in nombre_slug or q in partido_slug or q in entidad_slug:
                resultados.append({
                    "nombre": p.get("nombre_completo"),
                    "slug": p.get("slug"),
                    "camara": p.get("camara"),
                    "partido": p.get("partido"),
                    "entidad": p.get("entidad"),
                    "url": f"/politicos/{p.get('slug')}"
                })
                if len(resultados) >= limite:
                    break

        if len(resultados) < limite:
            for c_slug, c in self.comisiones_master.items():
                if q in c_slug or q in slugify(c.get("nombre", "")):
                    resultados.append({
                        "nombre": c.get("nombre"),
                        "slug": c_slug,
                        "camara": c.get("camara_tag", "Comisión"),
                        "partido": f"{c.get('total_integrantes', len(c.get('integrantes', [])))} integrantes",
                        "entidad": "Congreso de la Unión",
                        "url": f"/comisiones/{c_slug}"
                    })
                    if len(resultados) >= limite:
                        break

        return resultados

data_service = DataService()
