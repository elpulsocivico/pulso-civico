"""Servicio de cálculo y agregación para las 4 capas del mapa interactivo
y la radiografía política 360 de las 32 entidades federativas.
"""
import json
import re
from collections import Counter, defaultdict
from typing import Any, Dict, List, Optional

from app.core.config import settings
from app.services.education_service import education_service
from app.services.party_helpers import (
    get_canonical_party_name,
    get_party_color,
    normalizar_entidad,
)


class MapService:
    """Gestiona la agregación territorial y multicapa del observatorio político."""

    def __init__(self):
        self._capas_mapa_cache: Optional[Dict[str, Any]] = None
        self._resumen_360_cache: Optional[Dict[str, Dict[str, Any]]] = None

    def get_estados_data(self, ds, legislatura: int = settings.LEGISLATURA_ACTUAL) -> Dict[str, Dict[str, Any]]:
        """Calcula representación exacta por estado para la legislatura especificada."""
        estados = {slug: dict(info) for slug, info in ds.estados_catalog.items()}
        politicos_leg = ds.get_politicos(legislatura)

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
            est["partido_mayoria"] = next(iter(sorted_conteo), "Sin Dato") if sorted_conteo else "Sin Dato"
            est["conteo_partidos"] = sorted_conteo if sorted_conteo else {}

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

    def get_mapa_capas(self, ds, legislatura: int = settings.LEGISLATURA_ACTUAL) -> Dict[str, Any]:
        """
        Retorna las 4 capas de poder político en México estructuradas para el mapa interactivo:
        1. 'gobernadores': Titulares del Poder Ejecutivo Estatal (32 mandatarios)
        2. 'alcaldias': Fuerza política mayoritaria en los +2,470 ayuntamientos y municipios
        3. 'congresos_locales': Mayoría parlamentaria en las 32 legislaturas de los estados
        4. 'federal': Senadores y Diputados Federales en funciones (Congreso de la Unión)
        """
        if self._capas_mapa_cache and legislatura == settings.LEGISLATURA_ACTUAL:
            return self._capas_mapa_cache

        estados_cat = ds.estados_catalog
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
            p_raw = g.get("partido") or "Sin Dato"
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
                "partido_mayoria": can_p,
                "color_mayoria": get_party_color(can_p),
                "total_representantes": 1,
                "titular": titular,
                "cargo": cargo,
                "periodo": periodo,
                "subtitulo_card": "Poder Ejecutivo Estatal",
                "mayoria_label": f"Mandato Constitucional {periodo}",
                "conteo_partidos": {can_p: 1},
                "boton_url": f"/politicos?q={titular.replace(' ', '+')}",
                "boton_text": f"Ver perfil de {titular.split()[0]}"
            }

        gov_ranking = sorted(gov_parties_conteo.items(), key=lambda x: x[1], reverse=True)

        # ----------------------------------------------------
        # 2. Capa: ALCALDÍAS Y PRESIDENCIAS MUNICIPALES
        # ----------------------------------------------------
        muns_by_ent: Dict[str, Dict[str, int]] = {}
        dir_muns = settings.STORAGE_DATA_DIR / "municipios"
        if dir_muns.exists():
            for fpath in dir_muns.glob("*.json"):
                try:
                    with open(fpath, encoding="utf-8") as f:
                        d = json.load(f)
                    muns_list = d.get("municipios", [])
                    for alc in muns_list:
                        ent_nom = alc.get("entidad") or d.get("entidad") or ""
                        ent_slug = slug_by_norm.get(normalizar_entidad(ent_nom))
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
            sorted_conteo = dict(sorted(conteo.items(), key=lambda x: x[1], reverse=True)) if conteo else {"Sin Dato": 0}
            top_p = next(iter(sorted_conteo), "Sin Dato")
            total_muns = sum(sorted_conteo.values())
            if total_muns > 0:
                alc_parties_lead[top_p] = alc_parties_lead.get(top_p, 0) + 1
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
                top_p = next(iter(conteo), "Sin Dato")
                total_loc = info["total"]
                top_c = conteo.get(top_p, 0)
                leg_txt = f"Legislatura Local ({info['leg']})"
                loc_parties_lead[top_p] = loc_parties_lead.get(top_p, 0) + 1
            else:
                conteo = {"Sin Dato": 0}
                top_p = "Sin Dato"
                total_loc = 0
                top_c = 0
                leg_txt = "Legislatura Local"

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
        fed_estados_raw = self.get_estados_data(ds, legislatura)
        fed_estados = {}
        fed_parties_lead: Dict[str, int] = {}
        meta_leg = ds.LEGISLATURAS_CATALOG.get(legislatura, ds.LEGISLATURAS_CATALOG.get(settings.LEGISLATURA_ACTUAL, {}))
        for slug, est in fed_estados_raw.items():
            conteo = {get_canonical_party_name(k): v for k, v in est.get("conteo_partidos", {}).items()}
            sorted_conteo = dict(sorted(conteo.items(), key=lambda x: x[1], reverse=True)) if conteo else {"Sin Dato": 0}
            top_p = next(iter(sorted_conteo), "Sin Dato")
            total_reps = est.get("total_representantes", 0)
            if total_reps > 0:
                fed_parties_lead[top_p] = fed_parties_lead.get(top_p, 0) + 1
            top_c = sorted_conteo.get(top_p, 0) if total_reps > 0 else 0

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
                "boton_url": f"/estados/{slug}" + (f"?leg={legislatura}" if legislatura != settings.LEGISLATURA_ACTUAL else ""),
                "boton_text": f"Ver los {total_reps} legisladores"
            }

        fed_ranking = sorted(fed_parties_lead.items(), key=lambda x: x[1], reverse=True)

        capas = {
            "gobernadores": {
                "titulo": "Gubernaturas y Jefatura de Gobierno",
                "descripcion": "Filiación política oficial de las 32 personas titulares del Poder Ejecutivo Estatal.",
                "total": 32,
                "conteo_partidos": dict(gov_parties_conteo),
                "ranking_partidos": gov_ranking,
                "estados": gov_estados
            },
            "alcaldias": {
                "titulo": "Alcaldías y Presidencias Municipales",
                "descripcion": "Fuerza política con mayor número de presidencias municipales y cabildos en cada entidad.",
                "total": sum(sum(e["conteo_partidos"].values()) for e in alc_estados.values()),
                "ranking_partidos": alc_ranking,
                "estados": alc_estados
            },
            "congresos_locales": {
                "titulo": "Congresos Locales (Diputados Estatales)",
                "descripcion": "Fuerza política mayoritaria en las 32 legislaturas locales vigentes de los estados.",
                "total": sum(e["total_representantes"] for e in loc_estados.values()),
                "ranking_partidos": loc_ranking,
                "estados": loc_estados
            },
            "federal": {
                "titulo": f"Congreso de la Unión ({meta_leg.get('romano', 'LXVI')} Legislatura)",
                "descripcion": "Fuerza con mayor cantidad de curules y escaños federales por entidad.",
                "total": sum(e["total_representantes"] for e in fed_estados.values()),
                "ranking_partidos": fed_ranking,
                "estados": fed_estados
            }
        }

        if legislatura == settings.LEGISLATURA_ACTUAL:
            self._capas_mapa_cache = capas

        return capas

    def get_resumen_360_estados(self, ds, legislatura: int = settings.LEGISLATURA_ACTUAL) -> Dict[str, Dict[str, Any]]:
        """
        Genera una radiografía analítica integral de cada una de las 32 entidades federativas
        cruzando las 4 capas de representación y el índice universal.
        """
        if self._resumen_360_cache is not None and legislatura == settings.LEGISLATURA_ACTUAL:
            return self._resumen_360_cache

        capas = self.get_mapa_capas(ds, legislatura)
        catalogo = ds.get_explorador_catalogo()

        items_por_estado = defaultdict(list)
        for it in catalogo:
            e = it.get("entidad_slug")
            if e:
                items_por_estado[e].append(it)

        pols_fed_por_estado = defaultdict(list)
        for p in ds.get_politicos(legislatura):
            e = p.get("entidad_slug")
            if e:
                pols_fed_por_estado[e].append(p)

        resumen: Dict[str, Dict[str, Any]] = {}

        for slug, est_meta in ds.estados_catalog.items():
            gov = capas["gobernadores"]["estados"].get(slug, {})
            alc = capas["alcaldias"]["estados"].get(slug, {})
            cong_loc = capas["congresos_locales"]["estados"].get(slug, {})
            fed = capas["federal"]["estados"].get(slug, {})

            items = items_por_estado.get(slug, [])
            pols_fed = pols_fed_por_estado.get(slug, [])

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
                    "partido": gov.get("partido_mayoria") or "Sin Dato",
                    "color": gov.get("color_mayoria") or get_party_color(gov.get("partido_mayoria") or "Sin Dato"),
                    "periodo": gov.get("periodo") or ""
                },
                "alcaldias": {
                    "total": alc_total,
                    "partido": alc.get("partido_mayoria") or "Sin Dato",
                    "color": alc.get("color_mayoria") or get_party_color(alc.get("partido_mayoria") or "Sin Dato")
                },
                "congreso_local": {
                    "total": cong_loc_total,
                    "partido": cong_loc.get("partido_mayoria") or "Sin Dato",
                    "color": cong_loc.get("color_mayoria") or get_party_color(cong_loc.get("partido_mayoria") or "Sin Dato")
                },
                "federal": {
                    "total": total_fed,
                    "diputados": dip_fed_count,
                    "senadores": sen_fed_count,
                    "partido": fed.get("partido_mayoria") or "Sin Dato",
                    "color": fed.get("color_mayoria") or get_party_color(fed.get("partido_mayoria") or "Sin Dato")
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

        if legislatura == settings.LEGISLATURA_ACTUAL:
            self._resumen_360_cache = resumen

        return resumen
