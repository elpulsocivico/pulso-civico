"""Servicio del Explorador Universal de Políticos y Cargos de México.

Indexa, consolida y filtra representantes federales, mandatarios estatales
y presidentes municipales, permitiendo búsquedas y agregaciones analíticas.
"""
from pathlib import Path
from typing import Any, Dict, List, Optional
import orjson

from app.core.config import settings
from app.services.education_service import education_service
from app.services.party_helpers import (
    get_canonical_party_name,
    get_party_color,
    normalizar_entidad,
    slugify,
)
from app.services.profile_helpers import formatear_nombre_natural


class ExplorerService:
    """Gestiona el catálogo universal y las consultas facetadas del explorador cívico."""

    def __init__(self):
        self._explorador_catalogo_cache: Optional[List[Dict[str, Any]]] = None

    def get_explorador_catalogo(self, ds) -> List[Dict[str, Any]]:
        """
        Genera y cachea en memoria el catálogo unificado para el Explorador y Constructor de Reportes,
        integrando representantes federales y antecedentes ejecutivos y municipales con todas sus
        dimensiones de auditoría.
        """
        if self._explorador_catalogo_cache is not None:
            return self._explorador_catalogo_cache

        catalogo = []
        slugs_vistos = set()

        # 1. Base Federal: LXVI Legislatura (en funciones)
        pols_66 = ds.get_politicos(settings.LEGISLATURA_ACTUAL)
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

            transicion_info = ds._calcular_transicion_partidista(p, slug)

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

            mandatos = ds.mandatos_historicos.get(slug, [])
            legs = {int(m.get("legislatura") or 0) for m in mandatos} | {int(p.get("legislatura") or settings.LEGISLATURA_ACTUAL)}
            es_reelecto = (settings.LEGISLATURA_ACTUAL in legs and (settings.LEGISLATURA_ACTUAL - 1) in legs)
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
            if ref_sil and ds._has_raw_photo(f"{ref_sil}.jpg"):
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
                "legislatura": settings.LEGISLATURA_ACTUAL,
                "es_actual": True,
                "url": f"/politicos/{slug}",
                "fuente_url": f"http://sil.gobernacion.gob.mx/Librerias/RptDetalleLegislador.php?SID=&Clave={ref_sil}" if ref_sil else ""
            }
            catalogo.append(item)

        # 2. Base Ejecutiva Estatal
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
                        lic_est = "En Funciones (Titular)"
                        lic_tip = "en_funciones"
                        lic_bad = "bg-success-subtle text-success border border-success-subtle"
                    else:
                        lic_est = "Mandato Concluido"
                        lic_tip = "concluido"
                        lic_bad = "bg-light text-secondary border"

                    ini = str(m.get("fecha_inicio", ""))[:4]
                    fin = str(m.get("fecha_conclusion", ""))[:4]
                    periodo_str = f"{ini}–{fin}" if ini and fin else "Histórico"

                    cargo_ofic = m.get("cargo_oficial") or ("Jefa de Gobierno" if ent_slug == "ciudad-de-mexico" else "Gobernador(a) Constitucional")

                    edu_info = ds.GOBERNADORES_DATA.get(p_slug)
                    g_acad = edu_info.get("grado", "Licenciatura") if edu_info else "Sin Registro"
                    disc = edu_info.get("disciplina", "Administración Pública / Derecho") if edu_info else "General"

                    ejec_id = f"gob-{p_slug}"
                    foto_url = m.get("foto_url") or m.get("foto_oficial_url") or ""
                    if not foto_url and ds._has_raw_photo(f"{p_slug}.jpg"):
                        foto_url = f"/photos/{p_slug}.jpg"

                    item = {
                        "id": ejec_id,
                        "slug": p_slug,
                        "nombre": nom_nat,
                        "nombre_completo": nom_comp,
                        "foto_url": foto_url,
                        "nivel": "Estatal",
                        "nivel_tipo": "estatal",
                        "cargo": cargo_ofic,
                        "cargo_tipo": "gobernador",
                        "entidad": ent_nom,
                        "entidad_slug": ent_slug,
                        "municipio": "Poder Ejecutivo Estatal",
                        "partido_actual": m_partido,
                        "partido_actual_color": m_color,
                        "partido_slug": slugify(m_partido),
                        "transicion_lista": [m_partido],
                        "tiene_transicion": False,
                        "transicion_texto": "Sin cambios",
                        "transicion_badges": [m_partido],
                        "principio_eleccion": "Elección Popular Directa",
                        "principio_tipo": "mayoria",
                        "estatus_licencia": lic_est,
                        "licencia_tipo": lic_tip,
                        "licencia_badge": lic_bad,
                        "tiene_licencia": False,
                        "es_suplente": False,
                        "suplente_nombre": None,
                        "suplente_slug": None,
                        "titular_nombre": None,
                        "titular_slug": None,
                        "es_reelecto": False,
                        "reeleccion_texto": "No Reelecto",
                        "asistencia_pct": None,
                        "asistencia_pct_fmt": "-",
                        "asistencia_sesiones": "-",
                        "asistencia_semaforo": "gris",
                        "iniciativas_total": 0,
                        "grado_academico": g_acad,
                        "disciplina": disc,
                        "comisiones_total": 0,
                        "preside_comision": False,
                        "presidencias_str": "",
                        "periodo": periodo_str,
                        "legislatura": 0,
                        "es_actual": es_activo,
                        "url": f"/politicos?q={nom_comp.replace(' ', '+')}",
                        "fuente_url": "https://www.inegi.org.mx/ / Portales Oficiales de Gobierno"
                    }
                    catalogo.append(item)

        # 3. Base Municipal Nacional
        alcaldes_file = settings.STORAGE_DATA_DIR / "alcaldes" / "directorio_nacional_alcaldes_vigentes.json"
        if alcaldes_file.exists():
            try:
                with open(alcaldes_file, "rb") as af:
                    raw_alcaldes = orjson.loads(af.read())
                alcaldes_dict = raw_alcaldes.get("alcaldes") if isinstance(raw_alcaldes, dict) else {}
                if not isinstance(alcaldes_dict, dict):
                    alcaldes_dict = {}
            except Exception:
                alcaldes_dict = {}

            for cvegeo, alc in alcaldes_dict.items():
                if not isinstance(alc, dict):
                    continue
                nom_comp = (alc.get("nombre_completo") or alc.get("nombre") or "").strip()
                if not nom_comp:
                    continue
                nom_nat = nom_comp
                p_slug = slugify(nom_comp)

                ent_nom = alc.get("entidad") or "M?xico"
                ent_slug = normalizar_entidad(slugify(ent_nom))
                mun_nom = alc.get("municipio") or ""

                part_raw = alc.get("partido") or "Sin Partido"
                part_can = get_canonical_party_name(part_raw)
                part_col = get_party_color(part_can)

                es_re = bool(alc.get("reeleccion"))
                re_txt = "Reelecto Consecutivo" if es_re else "Primer Periodo"

                item_id = f"alcalde_{cvegeo}"
                if item_id in slugs_vistos:
                    continue
                slugs_vistos.add(item_id)

                pol_obj = ds.politicos_master.get(p_slug) or ds.find_politico_by_nombre(nom_comp)
                alc_info = ds.ALCALDES_DATA.get(p_slug)

                if pol_obj:
                    foto_url = pol_obj.get("foto_oficial_url") or (f"/photos/{pol_obj.get('referencia_sil')}.jpg" if pol_obj.get('referencia_sil') else "")
                    trans_info = ds._calcular_transicion_partidista(pol_obj, pol_obj.get("slug") or p_slug)
                    perfil_acad = pol_obj.get("perfil_academico") or education_service.clasificar_politico(pol_obj)
                    grado_max = perfil_acad.get("grado_maximo_label") or pol_obj.get("ultimo_grado_estudios") or "Sin Registro"
                    disciplina = perfil_acad.get("disciplina_principal") or pol_obj.get("preparacion_academica") or "General"
                elif alc_info:
                    nom_comp = alc_info.get("nombre_oficial") or nom_comp
                    nom_nat = alc_info.get("nombre_oficial") or nom_nat
                    foto_url = alc_info.get("foto_url") or "data:image/svg+xml;utf8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27 viewBox=%270 0 150 150%27%3E%3Crect width=%27150%27 height=%27150%27 fill=%27%23F1F5F9%27/%3E%3Ccircle cx=%2775%27 cy=%2758%27 r=%2726%27 fill=%27%2394A3B8%27/%3E%3Cpath d=%27M25 138 C25 103 125 103 125 138%27 fill=%27%2394A3B8%27/%3E%3C/svg%3E"
                    grado_max = alc_info.get("grado") or "Sin Registro"
                    disciplina = alc_info.get("disciplina") or "General"
                    trans_info = {
                        "lista": [part_can],
                        "tiene_transicion": False,
                        "texto": f"{part_can} (Fiel)",
                        "badges": [{"nombre": part_can, "color": part_col}],
                        "total_partidos": 1
                    }
                else:
                    foto_url = "data:image/svg+xml;utf8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27 viewBox=%270 0 150 150%27%3E%3Crect width=%27150%27 height=%27150%27 fill=%27%23F1F5F9%27/%3E%3Ccircle cx=%2775%27 cy=%2758%27 r=%2726%27 fill=%27%2394A3B8%27/%3E%3Cpath d=%27M25 138 C25 103 125 103 125 138%27 fill=%27%2394A3B8%27/%3E%3C/svg%3E"
                    trans_info = {
                        "lista": [part_can],
                        "tiene_transicion": False,
                        "texto": f"{part_can} (Fiel)",
                        "badges": [{"nombre": part_can, "color": part_col}],
                        "total_partidos": 1
                    }
                    grado_max = "Sin Registro"
                    disciplina = "General"

                item = {
                    "id": item_id,
                    "slug": p_slug,
                    "nombre": nom_nat,
                    "nombre_completo": nom_comp,
                    "foto_url": foto_url,
                    "nivel": "Municipal",
                    "nivel_tipo": "municipal",
                    "cargo": alc.get("tipo_cargo") or (f"Presidente(a) Municipal ({mun_nom})" if mun_nom else "Presidente(a) Municipal"),
                    "cargo_tipo": "alcalde",
                    "entidad": ent_nom,
                    "entidad_slug": ent_slug,
                    "municipio": mun_nom,
                    "partido_actual": part_can,
                    "partido_actual_color": part_col,
                    "partido_slug": slugify(part_can),
                    "transicion_lista": trans_info["lista"],
                    "tiene_transicion": trans_info["tiene_transicion"],
                    "transicion_texto": trans_info["texto"],
                    "transicion_badges": trans_info["badges"],
                    "principio_eleccion": "Mayor?a Relativa",
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
                    "iniciativas_total": 0,
                    "grado_academico": grado_max,
                    "disciplina": disciplina,
                    "comisiones_total": 0,
                    "preside_comision": False,
                    "presidencias_str": "",
                    "periodo": "2024?2027",
                    "legislatura": 0,
                    "es_actual": True,
                    "url": f"/politicos/{p_slug}",
                    "fuente_url": "https://www.gob.mx/inafed"
                }
                catalogo.append(item)

        self._explorador_catalogo_cache = catalogo
        return catalogo

    def consultar_explorador(
        self,
        ds,
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
        limit: int = 50,
        **kwargs
    ) -> Dict[str, Any]:
        """Ejecuta consulta multivariable con filtros cruzados, m?tricas instant?neas y paginaci?n r?pida."""
        if entidad == "todos" and "estado" in kwargs and kwargs["estado"] != "todos":
            entidad = kwargs["estado"]
        if kwargs.get("solo_activos") and periodo == "todos":
            periodo = "actuales"

        todos = self.get_explorador_catalogo(ds)
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

            # 6. Filtro Transici?n Partidista
            if transicion == "con_cambio" and not it["tiene_transicion"]:
                continue
            if transicion == "sin_cambio" and it["tiene_transicion"]:
                continue

            # 7. Filtro Principio de Elecci?n
            if principio != "todos" and it["principio_tipo"] != principio.lower():
                continue

            # 8. Filtro Estatus / Licencia
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

            # 9. Filtro Reelecci?n
            if reeleccion == "reelecto" and not it["es_reelecto"]:
                continue
            if reeleccion == "primer_periodo" and it["es_reelecto"]:
                continue

            # 10. Filtro Periodo / Vigencia
            if periodo == "actuales":
                if not it.get("es_actual"):
                    continue
            elif periodo == "lxvi":
                if it.get("legislatura") != settings.LEGISLATURA_ACTUAL:
                    continue
            elif periodo == "2024_2027":
                if "2024?2027" not in str(it.get("periodo", "")) and "2024-2027" not in str(it.get("periodo", "")):
                    continue
            elif periodo == "2024_2030":
                if "2024?2030" not in str(it.get("periodo", "")) and "2024-2030" not in str(it.get("periodo", "")):
                    continue
            elif periodo == "historico":
                if it.get("es_actual"):
                    continue

            # 11. Filtro Educaci?n / Grado Acad?mico
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
                elif educacion == "tecnico" and ("t?c" not in g_str and "tec" not in g_str and "normalista" not in g_str):
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
