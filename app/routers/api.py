import csv
import io
from decimal import Decimal, ROUND_HALF_EVEN
from typing import List, Dict, Any, Optional
from fastapi import APIRouter, BackgroundTasks, HTTPException, Query, Request, Response
from app.core.config import settings
from app.core.security import get_client_ip
from app.services.data_service import data_service
from app.services.audit_service import audit_service
from app.services.remuneraciones_service import remuneraciones_service, TWOPLACES
from app.services.integrity_service import integrity_service

router = APIRouter(tags=["API Pública"])

@router.get("/politicos", response_model=List[Dict[str, Any]])
async def listar_politicos(
    camara: Optional[str] = Query(None, description="Filtrar por cámara: Diputados o Senadores"),
    estado: Optional[str] = Query(None, description="Slug del estado"),
    leg: int = Query(settings.LEGISLATURA_ACTUAL, description="Legislatura"),
    limite: int = Query(50, ge=1, le=500)
):
    todos = data_service.get_politicos(leg)
    if camara:
        todos = [p for p in todos if p.get("camara", "").lower() == camara.lower()]
    if estado:
        todos = [p for p in todos if p.get("entidad_slug") == estado]
    return todos[:limite]

@router.get("/politicos/{slug}")
async def detalle_politico(slug: str, leg: Optional[int] = Query(None)):
    p = data_service.get_politico_by_slug(slug, legislatura=leg)
    if not p:
        raise HTTPException(status_code=404, detail="Legislador no encontrado")
    return p

@router.get("/buscar")
async def buscar_politicos(q: str = Query(..., min_length=2), leg: int = Query(settings.LEGISLATURA_ACTUAL)):
    resultados = data_service.buscar(q, legislatura=leg)
    return {
        "query": q,
        "legislatura": leg,
        "total": len(resultados),
        "resultados": resultados
    }

@router.get("/estados")
async def listar_estados(leg: int = Query(settings.LEGISLATURA_ACTUAL)):
    return list(data_service.get_estados_data(leg).values())

@router.get("/estados/{slug}")
async def legisladores_por_estado(slug: str, leg: int = Query(settings.LEGISLATURA_ACTUAL)):
    estados = data_service.get_estados_data(leg)
    estado = estados.get(slug)
    if not estado:
        raise HTTPException(status_code=404, detail="Estado no encontrado")
    legisladores = data_service.get_by_estado(slug, legislatura=leg)
    return {
        "estado": estado,
        "legislatura": leg,
        "total": len(legisladores),
        "legisladores": legisladores
    }

@router.get("/comisiones")
async def listar_comisiones(leg: int = Query(settings.LEGISLATURA_ACTUAL)):
    coms = data_service.get_comisiones(leg)
    return {
        "legislatura": leg,
        "total": len(coms),
        "comisiones": [
            {"nombre": c["nombre"], "slug": c["slug"], "total_integrantes": len(c["integrantes"])}
            for c in coms.values()
        ]
    }

@router.post("/reportar-anomalia")
async def reportar_anomalia(request: Request, response: Response, background_tasks: BackgroundTasks):
    client_ip = get_client_ip(request)
    user_agent = request.headers.get("user-agent", "Unknown")

    content_type = request.headers.get("content-type", "")
    if "application/json" in content_type:
        payload = await request.json()
    else:
        form = await request.form()
        payload = dict(form)
        if "checkmarks[]" in form:
            payload["checkmarks"] = form.getlist("checkmarks[]")

    result = audit_service.submit_report(payload, client_ip, user_agent, async_telegram=True)
    if result.get("banned"):
        response.status_code = 429
        result["redirect"] = "/baneado"
        return result

    if not result.get("ok"):
        response.status_code = 400
        return result

    record = result.pop("record", None)
    telegram_enabled = result.pop("telegram_enabled", False)
    if telegram_enabled and record:
        background_tasks.add_task(audit_service.dispatch_telegram, record)

    return result

@router.get("/explorador")
async def api_consultar_explorador(
    q: str = Query("", description="Búsqueda libre"),
    nivel: str = Query("todos", description="Nivel: todos, federal, estatal, municipal"),
    cargo: str = Query("todos", description="Cargo: todos, senador, diputado_federal, alcalde, etc."),
    entidad: str = Query("todos", description="Slug entidad"),
    partido: str = Query("todos", description="Partido o coalición"),
    transicion: str = Query("todos", description="Transición partidista: todos, con_cambio, sin_cambio"),
    principio: str = Query("todos", description="Principio: todos, mayoria, plurinominal, primera_minoria"),
    licencia: str = Query("todos", description="Licencia: todos, con_licencia, en_funciones, suplentes"),
    reeleccion: str = Query("todos", description="Reelección: todos, reelecto, primer_periodo"),
    periodo: str = Query("todos", description="Periodo o vigencia: todos, actuales, lxvi, 2024_2027, 2024_2030, historico"),
    educacion: str = Query("todos", description="Grado académico: todos, doctorado, maestria, especialidad, licenciatura, pasante_trunca, tecnico, preparatoria, secundaria, sin_registro"),
    page: int = Query(1, ge=1),
    limit: int = Query(50, ge=1, le=500)
):
    return data_service.consultar_explorador(
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
        limit=limit
    )

@router.get("/explorador/exportar.csv")
async def api_exportar_explorador_csv(
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
    columnas: str = Query("nombre,cargo,nivel,entidad,partido,transicion,principio,licencia,asistencia,iniciativas")
):
    res = data_service.consultar_explorador(
        q=q, nivel=nivel, cargo=cargo, entidad=entidad, partido=partido,
        transicion=transicion, principio=principio, licencia=licencia, reeleccion=reeleccion,
        periodo=periodo, educacion=educacion,
        page=1, limit=5000
    )
    items = res.get("items", [])
    cols = [c.strip() for c in columnas.split(",") if c.strip()]

    col_map = {
        "nombre": ("Político / Funcionario", lambda x: x["nombre"]),
        "nivel": ("Nivel de Gobierno", lambda x: x["nivel"]),
        "cargo": ("Cargo Oficial", lambda x: x["cargo"]),
        "entidad": ("Entidad Federativa", lambda x: x["entidad"]),
        "municipio": ("Municipio / Distrito", lambda x: x["municipio"]),
        "partido": ("Partido Actual", lambda x: x["partido_actual"]),
        "transicion": ("Transición Partidista", lambda x: x["transicion_texto"]),
        "principio": ("Principio de Elección", lambda x: x["principio_eleccion"]),
        "licencia": ("Estatus de Licencia", lambda x: x["estatus_licencia"]),
        "suplente": ("Suplente en Funciones", lambda x: x["suplente_nombre"] or ""),
        "reeleccion": ("Reelección", lambda x: x["reeleccion_texto"]),
        "asistencia": ("% Asistencia (LXVI)", lambda x: f"{x['asistencia_pct']}%" if x['asistencia_pct'] is not None else ""),
        "iniciativas": ("Total Iniciativas", lambda x: x["iniciativas_total"]),
        "estudios": ("Grado Académico", lambda x: f"{x['grado_academico']} - {x['disciplina']}"),
        "comisiones": ("Presidencias de Comisión", lambda x: x["presidencias_str"]),
        "periodo": ("Periodo Constitucional", lambda x: x["periodo"]),
        "fuente": ("Fuente Oficial", lambda x: x["fuente_url"])
    }

    active_cols = [c for c in cols if c in col_map]
    if not active_cols:
        active_cols = ["nombre", "cargo", "nivel", "entidad", "partido", "transicion", "principio", "licencia", "asistencia", "iniciativas"]

    output = io.StringIO()
    output.write("\ufeff")
    writer = csv.writer(output)
    writer.writerow([col_map[c][0] for c in active_cols])

    for it in items:
        row = [col_map[c][1](it) for c in active_cols]
        writer.writerow(row)

    output.seek(0)
    return Response(
        content=output.getvalue(),
        media_type="text/csv; charset=utf-8",
        headers={"Content-Disposition": 'attachment; filename="pulso_civico_reporte.csv"'}
    )

@router.get("/votaciones", response_model=Dict[str, Any])
async def listar_votaciones(
    tema: Optional[str] = Query(None, description="Filtrar por tema clave"),
    resultado: Optional[str] = Query(None, description="Filtrar por resultado: Aprobado o Desechado"),
    leg: int = Query(settings.LEGISLATURA_ACTUAL, description="Legislatura (exclusivamente disponible para la legislatura actual)"),
    limite: int = Query(100, ge=1, le=500)
):
    votaciones = data_service.get_votaciones(tema=tema, resultado=resultado, legislatura=leg)
    return {
        "ok": True,
        "legislatura": leg,
        "disponible_solo_lxvi": True,
        "total": len(votaciones),
        "filtros": {
            "tema": tema,
            "resultado": resultado,
            "legislatura": leg,
        },
        "votaciones": votaciones[:limite]
    }


@router.get("/integridad", response_model=Dict[str, Any])
async def auditar_integridad_sistema():
    """Endpoint de auditoría forense aritmética, relacional y criptográfica."""
    return integrity_service.ejecutar_auditoria_integral()


# ==========================================
# ENDPOINTS OFICIALES DE REMUNERACIONES Y COSTO POLÍTICO
# ==========================================

@router.get("/remuneraciones/totales", response_model=Dict[str, Any])
async def obtener_totales_remuneraciones():
    return {
        "ok": True,
        "metadata": remuneraciones_service.get_metadata(),
        "totales": remuneraciones_service.get_totales_nacionales()
    }

@router.get("/remuneraciones/cargos", response_model=Dict[str, Any])
async def obtener_cargos_remuneraciones():
    return {
        "ok": True,
        "federales": remuneraciones_service.get_cargos_federales(),
        "gobernadores": remuneraciones_service.get_gobernadores(),
        "congresos_locales": remuneraciones_service.get_congresos_locales(),
        "alcaldias": remuneraciones_service.get_alcaldias_categorias()
    }

@router.get("/remuneraciones/bancadas", response_model=Dict[str, Any])
async def obtener_bancadas_remuneraciones():
    return {
        "ok": True,
        "bancadas": remuneraciones_service.get_bancadas_distribucion()
    }

@router.get("/remuneraciones/estados", response_model=Dict[str, Any])
async def obtener_estados_remuneraciones(sort_by: str = Query("costo_total_anual")):
    ranking = remuneraciones_service.get_estados_ranking(sort_by=sort_by)
    return {
        "ok": True,
        "total_estados": len(ranking),
        "ranking": ranking
    }

@router.get("/remuneraciones/estados/{slug}", response_model=Dict[str, Any])
async def detalle_estado_remuneraciones(slug: str):
    detalle = remuneraciones_service.get_estado_detalle(slug)
    if not detalle:
        raise HTTPException(status_code=404, detail="Estado no encontrado")
    return {
        "ok": True,
        "estado": detalle
    }

@router.get("/remuneraciones/simulador", response_model=Dict[str, Any])
async def calcular_simulador_remuneraciones(
    dips_fed: int = Query(0, ge=0, le=500),
    sens: int = Query(0, ge=0, le=128),
    gobs: int = Query(0, ge=0, le=32),
    dips_loc: int = Query(0, ge=0, le=1123),
    alcaldes: int = Query(0, ge=0, le=2491)
):
    costos = remuneraciones_service.get_costos_unitarios_oficiales()
    monto_anual_dec = (
        (Decimal(dips_fed) * costos["diputado_federal"]) +
        (Decimal(sens) * costos["senador_republica"]) +
        (Decimal(gobs) * costos["gobernador_promedio"]) +
        (Decimal(dips_loc) * costos["diputado_local_promedio"]) +
        (Decimal(alcaldes) * costos["alcalde_promedio"])
    ).quantize(TWOPLACES, rounding=ROUND_HALF_EVEN)
    monto_mensual_dec = (monto_anual_dec / Decimal(12)).quantize(TWOPLACES, rounding=ROUND_HALF_EVEN)

    equivalencias = remuneraciones_service.calcular_equivalencias_ciudadanas(monto_anual_dec)

    return {
        "ok": True,
        "inputs": {
            "diputados_federales": dips_fed,
            "senadores": sens,
            "gobernadores": gobs,
            "diputados_locales": dips_loc,
            "alcaldes": alcaldes
        },
        "monto_anual_mxn": float(monto_anual_dec),
        "monto_mensual_mxn": float(monto_mensual_dec),
        "monto_anual_exacto": str(monto_anual_dec),
        "monto_mensual_exacto": str(monto_mensual_dec),
        "equivalencias": equivalencias
    }
