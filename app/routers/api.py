from typing import List, Dict, Any, Optional
from fastapi import APIRouter, HTTPException, Query
from app.services.data_service import data_service

router = APIRouter(tags=["API Pública"])

@router.get("/politicos", response_model=List[Dict[str, Any]])
async def listar_politicos(
    camara: Optional[str] = Query(None, description="Filtrar por cámara: Diputados o Senadores"),
    estado: Optional[str] = Query(None, description="Slug del estado"),
    limite: int = Query(50, ge=1, le=500)
):
    todos = data_service.get_all_politicos()
    if camara:
        todos = [p for p in todos if p.get("camara", "").lower() == camara.lower()]
    if estado:
        target = data_service.estados_catalog.get(estado)
        if target:
            nombre = target["nombre"].lower()
            todos = [p for p in todos if p.get("entidad", "").lower() == nombre]
    return todos[:limite]

@router.get("/politicos/{slug}")
async def detalle_politico(slug: str):
    p = data_service.get_politico_by_slug(slug)
    if not p:
        raise HTTPException(status_code=404, detail="Legislador no encontrado")
    return p

@router.get("/buscar")
async def buscar_politicos(q: str = Query(..., min_length=2)):
    return {
        "query": q,
        "total": len(data_service.buscar(q)),
        "resultados": data_service.buscar(q)
    }

@router.get("/estados")
async def listar_estados():
    return list(data_service.estados_catalog.values())

@router.get("/estados/{slug}")
async def legisladores_por_estado(slug: str):
    estado = data_service.estados_catalog.get(slug)
    if not estado:
        raise HTTPException(status_code=404, detail="Estado no encontrado")
    legisladores = data_service.get_by_estado(slug)
    return {
        "estado": estado,
        "total": len(legisladores),
        "legisladores": legisladores
    }
