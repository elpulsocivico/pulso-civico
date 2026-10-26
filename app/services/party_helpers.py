"""Helpers de normalización política, identificación de partidos, colores y badges HTML/SVG.

Este módulo concentra la lógica visual y canónica de partidos políticos en México,
evitando duplicación y manteniendo neutralidad cromática e institucional.
"""
import re
import unicodedata
from typing import Optional


def slugify(text: str) -> str:
    """Convierte cualquier cadena de texto en un slug alfanumérico limpio."""
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
    if "coahuila" in norm:
        return "coahuila"
    if "michoacan" in norm:
        return "michoacan"
    if "veracruz" in norm:
        return "veracruz"
    if "nuevo-leon" in norm or "nuevoleon" in norm:
        return "nuevo-leon"
    if "san-luis" in norm:
        return "san-luis-potosi"
    if "quintana" in norm or "qroo" in norm:
        return "quintana-roo"
    if "baja-california-sur" in norm or norm in ("bcs", "baja-sur"):
        return "baja-california-sur"
    if "baja-california" in norm or norm in ("bc", "baja"):
        return "baja-california"
    if norm in ("distrito-federal", "df", "cdmx", "ciudad-de-mexico"):
        return "ciudad-de-mexico"
    if norm in ("mexico", "edomex", "estado-de-mexico") or ("mexico" in norm and "ciudad" not in norm):
        return "estado-de-mexico"
    return norm


def get_party_color(partido: str) -> str:
    """Retorna el código de color HEX oficial/identitario del partido político."""
    if not partido:
        return "#6C757D"
    upper = partido.upper()
    if "MORENA" in upper:
        return "#8B1C3F"
    if "PAN" in upper and "PANAL" not in upper:
        return "#003893"
    if "VERDE" in upper or "PVEM" in upper:
        return "#009E49"
    if "PRI" in upper:
        return "#C8102E"
    if "PT" in upper or "TRABAJO" in upper:
        return "#DA291C"
    if "MC" in upper or "CIUDADANO" in upper or "CONVERGENCIA" in upper or "CD-PPN" in upper or "CD" in upper:
        return "#FF8300"
    if "PSN" in upper or "SOCIEDAD NACIONALISTA" in upper:
        return "#1E3A8A"
    if "PASC" in upper or "PAS" in upper or "ALTERNATIVA" in upper or "ALIANZA SOCIAL" in upper:
        return "#059669"
    if "PES" in upper:
        return "#582C83"
    if "PRD" in upper:
        return "#FFD100"
    if "PANAL" in upper or "NUEVA ALIANZA" in upper:
        return "#00A896"
    if "UYC" in upper or "USOS Y COSTUMBRES" in upper or "NORMATIVOS" in upper:
        return "#475569"
    if "INDEPENDIENTE" in upper or "SIN PARTIDO" in upper or upper in ("INDEP", "INDEP1", "IND.", "IND", "CANDIDATO INDEPENDIENTE"):
        return "#64748B"
    if "CONCEJO" in upper or "LITIGIO" in upper:
        return "#94A3B8"
    return "#6C757D"


def get_canonical_party_name(partido: str) -> str:
    """Resuelve el nombre canónico y normalizado del partido político."""
    p = (partido or "").strip()
    upper = p.upper()
    if not upper or upper in ("SIN PARTIDO", "INDEPENDIENTE", "INDEP", "INDEP1", "IND.", "IND", "CANDIDATO INDEPENDIENTE"):
        return "Independiente"
    if "SIN DATO" in upper:
        return "Sin Dato"
    if "UYC" in upper or "USOS Y COSTUMBRES" in upper or "NORMAS INDIGENAS" in upper:
        return "Usos y Costumbres (SNI)"
    if "CONCEJO MUNICIPAL" in upper:
        return "Concejo Municipal"
    if "LITIGIO" in upper:
        return "En Litigio Electoral"
    if "MORENA" in upper:
        return "Morena"
    if "PAN" in upper and "PANAL" not in upper:
        return "PAN"
    if "VERDE" in upper or "PVEM" in upper:
        return "PVEM"
    if "PRI" in upper:
        return "PRI"
    if "PT" in upper or "TRABAJO" in upper:
        return "PT"
    if "CD-PPN" in upper or "CONVERGENCIA" in upper:
        return "Convergencia"
    if "MC" in upper or "CIUDADANO" in upper:
        return "Movimiento Ciudadano"
    if "PSN" in upper:
        return "PSN"
    if "PASC" in upper or "ALTERNATIVA SOCIALDEMOCRATA" in upper:
        return "PASC"
    if "PAS" in upper or "ALIANZA SOCIAL" in upper:
        return "PAS"
    if "PES" in upper:
        return "PES"
    if "PRD" in upper:
        return "PRD"
    if "PANAL" in upper or "NUEVA ALIANZA" in upper:
        return "Nueva Alianza"
    return p


def get_party_micro_icon(partido: str) -> str:
    """Genera un micro-ícono SVG optimizado para badges de cronología."""
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
    """Detecta menciones a partidos o filiaciones partidistas en textos libres de trayectoria."""
    t = (text or "").strip()
    if not t:
        return None
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
    """Badge de filiación política adaptado para la línea de tiempo curricular."""
    canonical = get_canonical_party_name(partido)
    color = get_party_color(canonical)
    icon = get_party_micro_icon(canonical)
    tag_actual = ' <span class="badge bg-success text-white ms-1" style="font-size: 0.58rem; padding: 1px 4px; font-weight: 700;">Actual</span>' if es_actual else ""
    bg_style = 'background-color: #FFF9DB; color: #856404; border-color: #FFE066 !important;' if canonical == "PRD" else f'background-color: {color}14; color: {color}; border-color: {color}40 !important;'
    return f'<span class="party-timeline-badge d-inline-flex align-items-center gap-1 py-0 px-2 rounded-pill border" style="font-size: 0.68rem; font-weight: 600; line-height: 1.6; {bg_style}" title="Filiación política en este periodo: {canonical}">{icon}<span>{canonical}</span>{tag_actual}</span>'


def render_party_badge(partido: str) -> str:
    """Renderiza el badge HTML oficial con ícono vectorial y colores oficiales del partido."""
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
