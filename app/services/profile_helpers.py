"""Helpers de procesamiento de perfiles curriculares, nombres propios y líneas de tiempo.

Concentra la lógica de deduplicación de trayectorias, cálculo de edades
y formateo de nombres ciudadanos a partir de fuentes institucionales.
"""
from datetime import date
from typing import Any, Dict, List, Optional

from app.core.config import settings
from app.services.party_helpers import (
    detect_party_from_text,
    get_party_color,
)


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
    palabras: List[str] = []
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
    """Calcula la edad en años a partir de fechas en formato DD/MM/AAAA o AAAA-MM-DD."""
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


def procesar_trayectoria_y_estudios(
    pol: Dict[str, Any],
    legislaturas_catalog: Optional[Dict[int, Dict[str, Any]]] = None
) -> Dict[str, Any]:
    """
    Deduplica y clasifica la trayectoria legislativa, política, administrativa
    y escolaridad para construir la cronología verificada del político.
    """
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
    leg_num = int(pol.get("legislatura") or settings.LEGISLATURA_ACTUAL)

    leg_romano = str(leg_num)
    if legislaturas_catalog and leg_num in legislaturas_catalog:
        leg_romano = legislaturas_catalog[leg_num].get("romano", str(leg_num))

    ano_inicio = 2024 if leg_num == settings.LEGISLATURA_ACTUAL else (2021 if leg_num == 65 else 2018)
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

    es_actual = (leg_num == settings.LEGISLATURA_ACTUAL)
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
