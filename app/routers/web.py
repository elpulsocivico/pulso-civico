from datetime import datetime, timezone
from fastapi import APIRouter, Request, HTTPException, Query, Response
from fastapi.templating import Jinja2Templates
from typing import Optional
from app.core.config import settings
from app.services.data_service import (
    data_service, get_party_color, render_party_badge,
    render_party_timeline_badge, get_canonical_party_name,
    get_party_micro_icon, normalizar_entidad
)
from app.services.education_service import education_service
from app.services.audit_service import audit_service

router = APIRouter(tags=["Vistas Web"])
templates = Jinja2Templates(directory=str(settings.TEMPLATES_DIR))
templates.env.globals["app_name"] = settings.APP_NAME
templates.env.globals["app_short_name"] = settings.APP_SHORT_NAME
templates.env.globals["app_tagline"] = settings.APP_TAGLINE
templates.env.globals["settings"] = settings

@router.get("/")
async def home_view(request: Request, leg: int = Query(66, description="Número de legislatura")):
    legislatura = leg if leg in data_service.politicos_by_leg else 66
    estados = data_service.get_estados_data(legislatura)
    valores_estados = list(estados.values())
    default_estado = estados.get("baja-california") or (valores_estados[0] if valores_estados else {})
    comisiones = data_service.get_comisiones(legislatura)
    top_comisiones = list(comisiones.items())[:5]
    meta_leg = data_service.LEGISLATURAS_CATALOG.get(legislatura, data_service.LEGISLATURAS_CATALOG[66])

    partidos_conteo_mapa = {}
    for est in estados.values():
        p_may = est.get("partido_mayoria")
        if p_may:
            p_nom = get_canonical_party_name(p_may)
            partidos_conteo_mapa[p_nom] = partidos_conteo_mapa.get(p_nom, 0) + 1
    partidos_en_mapa = sorted(partidos_conteo_mapa.items(), key=lambda x: x[1], reverse=True)

    total_politicos_master = len(data_service.politicos_master) if data_service.politicos_master else 4386
    capas_mapa = data_service.get_mapa_capas(legislatura)
    resumen_estados_360 = data_service.get_resumen_360_estados(legislatura)
    default_slug = default_estado.get("slug", "baja-california")
    valores_resumen = list(resumen_estados_360.values())
    default_resumen = resumen_estados_360.get(default_slug) or resumen_estados_360.get("baja-california") or (valores_resumen[0] if valores_resumen else {})

    return templates.TemplateResponse(
        request=request,
        name="home.html",
        context={
            "seo": {
                "title": f"{settings.APP_NAME} — Gobernadores, Alcaldes y Congreso de México ({meta_leg['romano']} Legislatura)",
                "description": "Observatorio comunitario e independiente de +4,300 servidores públicos en México. Toda la información es comprobable y extraída de fuentes oficiales: gobernadores, alcaldes, diputados y senadores.",
                "canonical": "/"
            },
            "legislatura": legislatura,
            "meta_legislatura": meta_leg,
            "estados": estados,
            "partidos_en_mapa": partidos_en_mapa,
            "default_estado": default_estado,
            "mexico_paths": data_service.mexico_paths,
            "capas_mapa": capas_mapa,
            "resumen_estados_360": resumen_estados_360,
            "default_resumen": default_resumen,
            "total_politicos_master": total_politicos_master,
            "total_comisiones": len(comisiones) if len(comisiones) > 0 else 546,
            "top_comisiones": top_comisiones,
            "party_color_func": get_party_color,
            "party_badge_func": render_party_badge
        }
    )

@router.get("/metodologia")
@router.get("/fuentes")
async def metodologia_view(request: Request):
    return templates.TemplateResponse(
        request=request,
        name="metodologia.html",
        context={
            "seo": {
                "title": f"Metodología y Fuentes Oficiales | {settings.APP_NAME}",
                "description": f"Metodología, neutralidad y catálogo exhaustivo de fuentes oficiales primarias (federal, estatal y municipal) empleadas en {settings.APP_NAME}.",
                "canonical": "/metodologia"
            },
            "legislatura": 66,
            "meta_legislatura": data_service.LEGISLATURAS_CATALOG.get(66)
        }
    )

@router.get("/legislaturas")
async def legislaturas_index_view(request: Request):
    return templates.TemplateResponse(
        request=request,
        name="legislaturas_index.html",
        context={
            "seo": {
                "title": f"Legislaturas del Congreso de la Unión — Archivo Histórico | {settings.APP_NAME}",
                "description": "Explora los periodos constitucionales del Congreso de la Unión: LXVI (actual), LXV, LXIV y archivo histórico federal."
            },
            "catalogo": data_service.LEGISLATURAS_CATALOG
        }
    )

@router.get("/legislaturas/{num}")
async def legislatura_view(request: Request, num: int):
    if num not in data_service.LEGISLATURAS_CATALOG:
        raise HTTPException(status_code=404, detail="Legislatura no encontrada")
    return await home_view(request, leg=num)

@router.get("/politicos")
async def politicos_view(request: Request, leg: Optional[str] = Query(None)):
    num_leg = int(leg) if leg and leg.isdigit() and int(leg) > 0 else None
    todos = data_service.get_directorio_politicos(num_leg)
    catalog = data_service.LEGISLATURAS_CATALOG
    estados = list(data_service.estados_catalog.values())

    return templates.TemplateResponse(
        request=request,
        name="politicos.html",
        context={
            "seo": {
                "title": f"Directorio de Políticos y Representantes de México | {settings.APP_NAME}",
                "description": f"Directorio ciudadano con radiografía, trayectoria política y cargos acumulados de {len(todos)} figuras públicas mexicanas.",
                "canonical": "/politicos"
            },
            "politicos": todos[:32],  # Lote inicial SSR para descarga instantánea (de 25MB a 180KB)
            "catalog_legislaturas": catalog,
            "estados_catalog": estados,
            "total_politicos": len(todos),
            "selected_leg": str(num_leg) if num_leg else "todas",
            "party_badge_func": render_party_badge,
            "party_color_func": get_party_color
        }
    )

@router.get("/api/politicos")
async def api_politicos_paginados(
    q: str = Query("", description="Búsqueda libre"),
    leg: Optional[str] = Query(None, description="Legislatura"),
    cargo: str = Query("todos", description="Cargo"),
    estado: str = Query("todos", description="Estado"),
    partido: str = Query("todos", description="Partido"),
    activo: Optional[str] = Query(None, description="Solo activos"),
    page: int = Query(1, ge=1),
    limit: int = Query(32, ge=1, le=100)
):
    num_leg = int(leg) if leg and leg.isdigit() and int(leg) > 0 else None
    todos = data_service.get_directorio_politicos(None)
    q_norm = q.strip().lower()
    solo_activos = (activo == "1" or activo == "true")

    def normalizar_p_key(p_slug: str) -> str:
        s = (p_slug or "").lower()
        if "morena" in s: return "morena"
        if "pan" in s or "accion-nacional" in s: return "pan"
        if "pri" in s or "revolucionario" in s: return "pri"
        if "pvem" in s or "verde" in s: return "pvem"
        if "pt" in s or "trabajo" in s: return "pt"
        if "mc" in s or "ciudadano" in s: return "mc"
        if "prd" in s or "democratica" in s: return "prd"
        if "independiente" in s or "sin-partido" in s: return "independiente"
        return s

    CARGOS_LEGISLATIVOS_FEDERALES = {"senador", "diputado_federal"}
    CARGOS_LOCALES_MUNICIPALES = {"diputado_local", "alcalde", "edil", "gobernador", "cambio_partido"}

    # Si se selecciona un cargo estrictamente local o municipal, la legislatura federal no aplica
    if cargo in CARGOS_LOCALES_MUNICIPALES:
        num_leg = None

    filtrados = []
    facetas_partidos = {}
    facetas_estados = {}
    facetas_cargos = {}
    facetas_legislaturas = {}

    for pol in todos:
        nombre_pol = pol.get("nombre_completo") or ""
        nombre_mostrar = pol.get("nombre_natural") or nombre_pol
        partido_pol = pol.get("partido") or ""
        entidad_pol = pol.get("entidad") or ""
        camara_pol = pol.get("camara") or ""
        cargo_resumen = pol.get("cargo_resumen") or ""
        mun_alcalde = pol.get("municipio_alcalde") or ""

        p_slug_raw = pol.get("partido_slug") or partido_pol.lower()
        p_key = normalizar_p_key(p_slug_raw)

        entidad_slug = pol.get("entidad_slug") or normalizar_entidad(pol.get("entidad") or "") or "mexico"
        roles = pol.get("roles_flags") or {}
        legs = pol.get("legislaturas_lista") or [int(pol.get("legislatura") or 66)]
        es_activo = bool(pol.get("es_activo"))

        corpus = f"{nombre_mostrar} {nombre_pol} {partido_pol} {entidad_pol} {camara_pol} {cargo_resumen} {mun_alcalde}".lower()

        pass_texto = (q_norm == "") or (q_norm in corpus)
        pass_leg = (num_leg is None) or (num_leg in legs) or (int(pol.get("legislatura") or 0) == num_leg)

        # Regla de congruencia: si hay legislatura federal o solo activos, cargos municipales o locales no son aplicables
        if (num_leg is not None or solo_activos) and cargo in CARGOS_LOCALES_MUNICIPALES:
            pass_cargo = False
        else:
            pass_cargo = (cargo == "todos") or bool(roles.get(cargo))

        pass_estado = (estado == "todos") or (entidad_slug == estado)
        pass_partido = (partido == "todos") or (p_key == partido) or (partido in p_slug_raw)
        pass_activo = (not solo_activos) or es_activo

        # 1. Faceta Partidos (cumple todo excepto partido)
        if pass_texto and pass_leg and pass_cargo and pass_estado and pass_activo:
            facetas_partidos[p_key] = facetas_partidos.get(p_key, 0) + 1

        # 2. Faceta Estados (cumple todo excepto estado)
        if pass_texto and pass_leg and pass_cargo and pass_partido and pass_activo:
            facetas_estados[entidad_slug] = facetas_estados.get(entidad_slug, 0) + 1

        # 3. Faceta Cargos (cumple todo excepto cargo)
        if pass_texto and pass_leg and pass_estado and pass_partido and pass_activo:
            for rk, rv in roles.items():
                if rv:
                    # En una legislatura federal o en activos, no exponer cargos municipales ni locales
                    if (num_leg is not None or solo_activos) and rk not in CARGOS_LEGISLATIVOS_FEDERALES:
                        continue
                    facetas_cargos[rk] = facetas_cargos.get(rk, 0) + 1

        # 4. Faceta Legislaturas (cumple todo excepto legislatura)
        if pass_texto and pass_cargo and pass_estado and pass_partido and pass_activo:
            # Si el cargo es municipal o local, no existen legislaturas federales asociadas
            if cargo not in CARGOS_LOCALES_MUNICIPALES:
                for lL in legs:
                    facetas_legislaturas[str(lL)] = facetas_legislaturas.get(str(lL), 0) + 1

        # Coincidencia completa
        if pass_texto and pass_leg and pass_cargo and pass_estado and pass_partido and pass_activo:
            filtrados.append(pol)

    total_filtrados = len(filtrados)
    total_pages = (total_filtrados + limit - 1) // limit if limit > 0 else 1
    offset = (page - 1) * limit
    lote = filtrados[offset:offset + limit]

    card_tmpl = templates.env.get_template("partials/card_politico.html")
    html_cards = "".join(card_tmpl.render({"pol": p, "party_badge_func": render_party_badge}) for p in lote)

    return {
        "ok": True,
        "page": page,
        "limit": limit,
        "total": len(todos),
        "total_filtrados": total_filtrados,
        "total_pages": total_pages,
        "has_more": (page < total_pages),
        "html": html_cards,
        "facetas": {
            "partidos": list(facetas_partidos.keys()),
            "estados": list(facetas_estados.keys()),
            "cargos": list(facetas_cargos.keys()),
            "legislaturas": list(facetas_legislaturas.keys()),
        }
    }

@router.get("/explorador")
@router.get("/reportes")
async def explorador_view(
    request: Request,
    q: str = Query(""),
    nivel: str = Query("todos"),
    cargo: str = Query("todos"),
    entidad: str = Query("todos"),
    partido: str = Query("todos"),
    transicion: str = Query("todos"),
    principio: str = Query("todos"),
    licencia: str = Query("todos"),
    reeleccion: str = Query("todos"),
    periodo: str = Query("todos"),
    educacion: str = Query("todos"),
    page: int = Query(1, ge=1)
):
    estados = list(data_service.estados_catalog.values())
    resultado = data_service.consultar_explorador(
        q=q,
        nivel=nivel,
        cargo=cargo,
        entidad=entidad,
        partido=partido,
        transicion=transicion,
        principio=principio,
        licencia=licencia,
        reeleccion=reeleccion,
        periodo=periodo,
        educacion=educacion,
        page=page,
        limit=50
    )

    partidos_lista = [
        {"nombre": "Morena", "slug": "morena", "color": "#8B1C3F"},
        {"nombre": "PAN", "slug": "pan", "color": "#003893"},
        {"nombre": "PRI", "slug": "pri", "color": "#C8102E"},
        {"nombre": "PVEM", "slug": "pvem", "color": "#009E49"},
        {"nombre": "PT", "slug": "pt", "color": "#DA291C"},
        {"nombre": "Movimiento Ciudadano", "slug": "movimiento-ciudadano", "color": "#FF8300"},
        {"nombre": "PRD", "slug": "prd", "color": "#FFD100"},
        {"nombre": "Independiente", "slug": "independiente", "color": "#64748B"}
    ]

    return templates.TemplateResponse(
        request=request,
        name="explorador.html",
        context={
            "seo": {
                "title": f"Explorador de Datos y Constructor de Reportes Ciudadanos | {settings.APP_NAME}",
                "description": "Herramienta de auditoría ciudadana para filtrar, personalizar columnas y exportar información de legisladores federales, locales, alcaldes y gobernadores.",
                "canonical": "/explorador"
            },
            "estados_catalog": estados,
            "partidos_catalog": partidos_lista,
            "resultado": resultado,
            "items": resultado.get("items", []),
            "kpis": resultado.get("kpis", {}),
            "total_filtrados": resultado.get("total_filtrados", 0),
            "filtros": {
                "q": q,
                "nivel": nivel,
                "cargo": cargo,
                "entidad": entidad,
                "partido": partido,
                "transicion": transicion,
                "principio": principio,
                "licencia": licencia,
                "reeleccion": reeleccion,
                "periodo": periodo,
                "educacion": educacion,
                "page": page
            },
            "party_badge_func": render_party_badge,
            "party_color_func": get_party_color
        }
    )

@router.get("/politicos/{slug}")
async def politico_view(request: Request, slug: str, leg: Optional[int] = Query(None)):
    pol = data_service.get_politico_by_slug(slug, legislatura=leg)
    if not pol:
        raise HTTPException(status_code=404, detail="Legislador no encontrado")

    pol_leg = int(pol.get("legislatura") or (leg if leg else 66))
    meta_leg = data_service.LEGISLATURAS_CATALOG.get(pol_leg, data_service.LEGISLATURAS_CATALOG[66])

    return templates.TemplateResponse(
        request=request,
        name="politico.html",
        context={
            "seo": {
                "title": f"{pol.get('nombre_completo')} ({pol.get('partido')}) — {pol.get('camara')} ({meta_leg['romano']} Legislatura) | {settings.APP_NAME}",
                "description": f"Expediente oficial de {pol.get('nombre_completo')}, {pol.get('camara')} de la {meta_leg['romano']} Legislatura por {pol.get('entidad')}. Bancada {pol.get('partido')}, comisiones legislativas e iniciativas."
            },
            "politico": pol,
            "legislatura": pol_leg,
            "meta_legislatura": meta_leg,
            "party_badge_func": render_party_badge,
            "party_color_func": get_party_color,
            "party_timeline_badge_func": render_party_timeline_badge,
            "canonical_party_name_func": get_canonical_party_name
        }
    )

@router.get("/estados/{slug}")
async def estado_view(request: Request, slug: str, leg: int = Query(66)):
    legislatura = leg if leg in data_service.politicos_by_leg else 66
    estado = data_service.get_estado_by_slug(slug, legislatura)
    if not estado:
        raise HTTPException(status_code=404, detail="Estado no encontrado")

    meta_leg = data_service.LEGISLATURAS_CATALOG.get(legislatura, data_service.LEGISLATURAS_CATALOG[66])

    return templates.TemplateResponse(
        request=request,
        name="estado.html",
        context={
            "seo": {
                "title": f"Legisladores Federales de {estado.get('nombre')} ({meta_leg['romano']} Legislatura) | {settings.APP_NAME}",
                "description": f"Radiografía completa de los senadores y diputados federales que representan a {estado.get('nombre')} en el Congreso de la Unión durante la {meta_leg['romano']} Legislatura."
            },
            "legislatura": legislatura,
            "meta_legislatura": meta_leg,
            "estado": estado,
            "party_color_func": get_party_color,
            "party_badge_func": render_party_badge,
            "catalogo_legislaturas": data_service.LEGISLATURAS_CATALOG
        }
    )

@router.get("/comisiones")
async def comisiones_view(request: Request, leg: Optional[int] = Query(None)):
    if leg == 0:
        legislatura = 0
        comisiones = data_service.get_comisiones(0)
        meta_leg = {"romano": "Histórico", "periodo": "1997–2027", "activa": False}
    else:
        legislatura = leg if leg and leg in data_service.politicos_by_leg else 66
        comisiones = data_service.get_comisiones(legislatura)
        meta_leg = data_service.LEGISLATURAS_CATALOG.get(legislatura, data_service.LEGISLATURAS_CATALOG[66])

    total_comisiones = len(comisiones)
    total_diputados = sum(1 for c in comisiones.values() if c.get("camara_tag") == "Diputados" or "DIPUTADOS" in c.get("nombre", "").upper())
    total_senado = sum(1 for c in comisiones.values() if c.get("camara_tag") == "Senadores" or "SENAD" in c.get("nombre", "").upper())

    return templates.TemplateResponse(
        request=request,
        name="comisiones.html",
        context={
            "seo": {
                "title": f"Comisiones del Congreso de la Unión ({meta_leg['romano']} Legislatura) | {settings.APP_NAME}" if legislatura != 0 else f"Comisiones del Congreso de la Unión (Catálogo Histórico) | {settings.APP_NAME}",
                "description": f"Directorio y observatorio ciudadano de los órganos técnicos encargados de estudiar y dictaminar iniciativas en el Congreso de la Unión ({meta_leg['romano']}).",
                "canonical": f"/comisiones{f'?leg={legislatura}' if legislatura != 66 else ''}"
            },
            "comisiones": comisiones,
            "total_comisiones": total_comisiones,
            "total_diputados": total_diputados,
            "total_senado": total_senado,
            "legislatura": legislatura,
            "meta_legislatura": meta_leg,
            "catalogo_legislaturas": data_service.LEGISLATURAS_CATALOG,
            "party_color_func": get_party_color,
            "party_badge_func": render_party_badge
        }
    )

@router.get("/comisiones/{slug}")
async def comision_detalle_view(request: Request, slug: str, leg: Optional[int] = Query(None)):
    legislatura = leg if (leg is not None and (leg in data_service.politicos_by_leg or leg == 0)) else None
    comision = data_service.get_comision_by_slug(slug, legislatura)
    if not comision:
        raise HTTPException(status_code=404, detail="Comisión no encontrada")

    meta_leg = comision.get("meta_legislatura") or data_service.LEGISLATURAS_CATALOG[66]

    return templates.TemplateResponse(
        request=request,
        name="comision.html",
        context={
            "seo": {
                "title": f"{comision.get('nombre')} — Integrantes y Mesa Directiva | {settings.APP_NAME}",
                "description": f"Composición, presidencia e integrantes de la {comision.get('nombre')} en el Congreso de la Unión."
            },
            "comision": comision,
            "legislatura": comision.get("legislatura", 66),
            "meta_legislatura": meta_leg,
            "catalogo_legislaturas": data_service.LEGISLATURAS_CATALOG,
            "party_color_func": get_party_color,
            "party_badge_func": render_party_badge
        }
    )

@router.get("/estudios")
@router.get("/estudios/{categoria}")
async def estudios_view(request: Request, categoria: Optional[str] = None, leg: Optional[int] = Query(None)):
    categoria_activa = categoria if categoria in ["todos", "posgrados", "licenciatura", "media-trunca"] else "todos"

    todos = data_service.get_directorio_estudios()
    estados_catalog = data_service.get_estados_data(66)
    estados_lista = sorted(estados_catalog.values(), key=lambda e: e.get("nombre", ""))

    # Conteo inicial de disciplinas
    disciplinas_conteo = {}
    for p in todos:
        disc = p.get("analisis_academico", {}).get("disciplina_principal") or "General / Diversa"
        if disc not in ("Otra / General", "No especificada"):
            disciplinas_conteo[disc] = disciplinas_conteo.get(disc, 0) + 1
    disciplinas_lista = sorted(disciplinas_conteo.items(), key=lambda x: x[1], reverse=True)[:15]

    # Estadísticas globales iniciales
    total_todos = len(todos)
    pos_tot = sum(1 for p in todos if p.get("categoria_grupo") == "posgrados")
    lic_tot = sum(1 for p in todos if p.get("categoria_grupo") == "licenciatura")
    med_tot = sum(1 for p in todos if p.get("categoria_grupo") == "media-trunca")

    stats = {
        "total_legisladores": total_todos,
        "resumen_posgrados": {
            "total": pos_tot,
            "porcentaje": round(pos_tot / total_todos * 100, 1) if total_todos > 0 else 0.0
        },
        "resumen_licenciaturas": {
            "total": lic_tot,
            "porcentaje": round(lic_tot / total_todos * 100, 1) if total_todos > 0 else 0.0
        },
        "resumen_media_trunca": {
            "total": med_tot,
            "porcentaje": round(med_tot / total_todos * 100, 1) if total_todos > 0 else 0.0
        }
    }

    # Filtrar por categoría inicial si se accedió por ruta /estudios/posgrados, etc.
    if categoria_activa != "todos":
        politicos_filtrados = [p for p in todos if p.get("categoria_grupo") == categoria_activa]
    else:
        politicos_filtrados = todos

    titulos_seo = {
        "todos": "Nivel de Estudios y Grados Académicos de la Clase Política Mexicana",
        "posgrados": "Representantes y Políticos con Posgrado (Maestría y Doctorado)",
        "licenciatura": "Representantes y Políticos con Licenciatura e Ingeniería",
        "media-trunca": "Políticos con Educación Media Superior y Carrera Trunca",
    }

    return templates.TemplateResponse(
        request=request,
        name="estudios.html",
        context={
            "seo": {
                "title": f"{titulos_seo.get(categoria_activa, titulos_seo['todos'])} — {settings.APP_NAME}",
                "description": f"Radiografía curricular, títulos universitarios y grados académicos de {total_todos} legisladores federales, locales, alcaldes y gobernadores de México."
            },
            "stats": stats,
            "categoria_activa": categoria_activa,
            "total_politicos": len(politicos_filtrados),
            "politicos": politicos_filtrados[:32],  # Lote inicial SSR para render instantáneo
            "estados_catalog": estados_catalog,
            "estados_lista": estados_lista,
            "disciplinas_lista": disciplinas_lista,
            "party_badge_func": render_party_badge,
            "party_micro_icon_func": get_party_micro_icon,
            "canonical_party_name_func": get_canonical_party_name
        }
    )

@router.get("/api/estudios")
async def api_estudios_paginados(
    q: str = Query("", description="Búsqueda libre"),
    categoria: str = Query("todos", description="Nivel académico: todos, posgrados, licenciatura, media-trunca"),
    cargos: str = Query("todos", description="Cargos seleccionados separados por coma"),
    estado: str = Query("todos", description="Slug de entidad"),
    partido: str = Query("todos", description="Partido político"),
    disciplina: str = Query("todas", description="Área o disciplina"),
    institucion: str = Query("todas", description="Pública, privada o mixta"),
    activo: Optional[str] = Query(None, description="Solo en funciones LXVI"),
    page: int = Query(1, ge=1),
    limit: int = Query(32, ge=1, le=100)
):
    todos = data_service.get_directorio_estudios()
    q_norm = q.strip().lower()
    solo_activos = (activo == "1" or activo == "true")
    cargos_set = {c.strip().lower() for c in cargos.split(",") if c.strip()} if cargos and cargos != "todos" else set()

    def normalizar_p_key(p_slug: str) -> str:
        s = (p_slug or "").lower()
        if "morena" in s: return "morena"
        if "pan" in s or "accion-nacional" in s: return "pan"
        if "pri" in s or "revolucionario" in s: return "pri"
        if "pvem" in s or "verde" in s: return "pvem"
        if "pt" in s or "trabajo" in s: return "pt"
        if "mc" in s or "ciudadano" in s: return "mc"
        if "prd" in s or "democratica" in s: return "prd"
        if "independiente" in s or "sin-partido" in s: return "independiente"
        return s

    filtrados = []
    partido_req_norm = partido.strip().lower()

    for pol in todos:
        # 1. Texto libre
        if q_norm:
            corpus = pol.get("search_corpus") or ""
            if q_norm not in corpus:
                continue

        # 2. Solo activos
        if solo_activos and not pol.get("es_activo"):
            continue

        # 3. Cargos (checkboxes múltiples con lógica OR)
        if cargos_set:
            roles = pol.get("roles_flags") or {}
            if not any(roles.get(c) for c in cargos_set):
                continue

        # 4. Estado
        if estado != "todos" and pol.get("entidad_slug") != estado:
            continue

        # 5. Partido
        if partido_req_norm != "todos":
            p_slug = pol.get("partido_slug") or ""
            p_name = pol.get("partido") or ""
            p_key = normalizar_p_key(p_slug)
            p_can = get_canonical_party_name(p_name).lower()
            if (partido_req_norm not in p_key and 
                partido_req_norm not in p_slug.lower() and 
                partido_req_norm not in p_can):
                continue

        # 6. Disciplina
        if disciplina != "todas":
            pol_disc = pol.get("analisis_academico", {}).get("disciplina_principal", "")
            if pol_disc != disciplina:
                continue

        # 7. Tipo de institución
        if institucion != "todas":
            pol_inst = pol.get("analisis_academico", {}).get("institucion_resumen", {}).get("tipo_predominante", "")
            if pol_inst != institucion:
                continue

        filtrados.append(pol)

    # Métricas y conteos reactivos sobre el universo filtrado por filtros secundarios
    total_filtrados_universo = len(filtrados)
    posgrados_count = sum(1 for p in filtrados if p.get("categoria_grupo") == "posgrados")
    licenciaturas_count = sum(1 for p in filtrados if p.get("categoria_grupo") == "licenciatura")
    media_trunca_count = sum(1 for p in filtrados if p.get("categoria_grupo") == "media-trunca")

    pct_pos = round((posgrados_count / total_filtrados_universo * 100), 1) if total_filtrados_universo > 0 else 0.0
    pct_lic = round((licenciaturas_count / total_filtrados_universo * 100), 1) if total_filtrados_universo > 0 else 0.0
    pct_med = round((media_trunca_count / total_filtrados_universo * 100), 1) if total_filtrados_universo > 0 else 0.0

    # Aplicar filtro de categoría académica para el lote visible
    if categoria != "todos":
        items_mostrados = [p for p in filtrados if p.get("categoria_grupo") == categoria]
    else:
        items_mostrados = filtrados

    total_mostrados = len(items_mostrados)
    total_pages = (total_mostrados + limit - 1) // limit if limit > 0 else 1
    offset = (page - 1) * limit
    lote = items_mostrados[offset:offset + limit]

    card_tmpl = templates.env.get_template("partials/card_estudios.html")
    html_cards = "".join(card_tmpl.render({
        "p": p,
        "party_badge_func": render_party_badge,
        "canonical_party_name_func": get_canonical_party_name
    }) for p in lote)

    estado_info = None
    if estado != "todos":
        est_obj = data_service.estados_catalog.get(estado)
        if est_obj:
            estado_info = {
                "slug": estado,
                "nombre": est_obj.get("nombre", estado.title())
            }

    return {
        "ok": True,
        "page": page,
        "limit": limit,
        "total_filtrados": total_mostrados,
        "total_universo": total_filtrados_universo,
        "total_pages": total_pages,
        "has_more": (page < total_pages),
        "html": html_cards,
        "stats": {
            "total": total_filtrados_universo,
            "posgrados": posgrados_count,
            "pct_posgrados": pct_pos,
            "licenciaturas": licenciaturas_count,
            "pct_licenciaturas": pct_lic,
            "media_trunca": media_trunca_count,
            "pct_media_trunca": pct_med,
            "por_categoria": {
                "todos": total_filtrados_universo,
                "posgrados": posgrados_count,
                "licenciatura": licenciaturas_count,
                "media-trunca": media_trunca_count
            }
        },
        "estado_info": estado_info
    }

@router.get("/sitemap.xml")
async def sitemap_xml():
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d")
    base = "https://elpulso.mx"
    urls = [
        f"<url><loc>{base}/</loc><lastmod>{now}</lastmod><priority>1.0</priority></url>",
        f"<url><loc>{base}/explorador</loc><lastmod>{now}</lastmod><priority>0.95</priority></url>",
        f"<url><loc>{base}/legislaturas</loc><lastmod>{now}</lastmod><priority>0.9</priority></url>",
        f"<url><loc>{base}/estudios</loc><lastmod>{now}</lastmod><priority>0.9</priority></url>",
        f"<url><loc>{base}/comisiones</loc><lastmod>{now}</lastmod><priority>0.9</priority></url>",
    ]
    for p in data_service.get_politicos(66)[:200]:
        urls.append(f"<url><loc>{base}/politicos/{p['slug']}</loc><lastmod>{now}</lastmod><priority>0.8</priority></url>")

    xml_content = f'<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + "\n".join(urls) + "\n</urlset>"
    return Response(content=xml_content, media_type="application/xml")

@router.get("/electoral")
async def electoral_view(request: Request):
    return templates.TemplateResponse(
        request=request,
        name="electoral.html",
        context={
            "seo": {
                "title": f"Auditoría Electoral Ciudadana — {settings.APP_NAME}",
                "description": "Sube y protege fotografías de las actas de tu casilla electoral de forma descentralizada."
            }
        }
    )

@router.get("/baneado")
async def baneado_view(request: Request):
    forwarded = request.headers.get("x-forwarded-for")
    if forwarded:
        client_ip = forwarded.split(",")[0].strip()
    else:
        client_ip = request.client.host if request.client else "127.0.0.1"

    ban_status = audit_service.check_ban_status(client_ip)
    return templates.TemplateResponse(
        request=request,
        name="baneado.html",
        context={
            "seo": {
                "title": f"Acceso a Reportes Restringido — {settings.APP_NAME}",
                "description": "Suspensión temporal de 24 horas por límite de velocidad en solicitudes de reporte."
            },
            "ip": client_ip,
            "ban_status": ban_status
        }
    )
