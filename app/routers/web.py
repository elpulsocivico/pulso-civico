from fastapi import APIRouter, Request, HTTPException
from fastapi.templating import Jinja2Templates
from app.core.config import settings
from app.services.data_service import data_service

router = APIRouter(tags=["Vistas Web"])
templates = Jinja2Templates(directory=str(settings.TEMPLATES_DIR))

@router.get("/")
async def home_view(request: Request):
    todos = data_service.get_all_politicos()
    diputados = [p for p in todos if p.get("camara") == "Diputados"]
    senadores = [p for p in todos if p.get("camara") == "Senadores"]

    return templates.TemplateResponse(
        request=request,
        name="home.html",
        context={
            "seo": {
                "title": "El Pulso de la Política — Observatorio del Congreso Mexicano",
                "description": "Radiografía y monitoreo ciudadano del Congreso de la Unión."
            },
            "total_diputados": len(diputados),
            "total_senadores": len(senadores),
            "estados": data_service.estados_catalog,
            "politicos_muestra": todos[:9]
        }
    )

@router.get("/politicos/{slug}")
async def politico_view(request: Request, slug: str):
    pol = data_service.get_politico_by_slug(slug)
    if not pol:
        raise HTTPException(status_code=404, detail="Legislador no encontrado")

    return templates.TemplateResponse(
        request=request,
        name="politico.html",
        context={
            "seo": {
                "title": f"{pol.get('nombre_completo')} — Pulso Cívico",
                "description": f"Perfil oficial y comisiones de {pol.get('nombre_completo')} ({pol.get('camara')}, {pol.get('partido')})."
            },
            "politico": pol
        }
    )

@router.get("/diputados")
async def diputados_view(request: Request):
    diputados = data_service.get_diputados()
    return templates.TemplateResponse(
        request=request,
        name="home.html",
        context={
            "seo": {"title": "Diputados Federales — LXVI Legislatura"},
            "total_diputados": len(diputados),
            "total_senadores": len(data_service.get_senadores()),
            "estados": data_service.estados_catalog,
            "politicos_muestra": diputados[:30]
        }
    )

@router.get("/senadores")
async def senadores_view(request: Request):
    senadores = data_service.get_senadores()
    return templates.TemplateResponse(
        request=request,
        name="home.html",
        context={
            "seo": {"title": "Senadores de la República — LXVI Legislatura"},
            "total_diputados": len(data_service.get_diputados()),
            "total_senadores": len(senadores),
            "estados": data_service.estados_catalog,
            "politicos_muestra": senadores[:30]
        }
    )

@router.get("/estados/{slug}")
async def estado_view(request: Request, slug: str):
    estado = data_service.estados_catalog.get(slug)
    if not estado:
        raise HTTPException(status_code=404, detail="Estado no encontrado")

    legisladores = data_service.get_by_estado(slug)
    return templates.TemplateResponse(
        request=request,
        name="home.html",
        context={
            "seo": {"title": f"Legisladores por {estado.get('nombre')} — Pulso Cívico"},
            "total_diputados": len([p for p in legisladores if p.get("camara") == "Diputados"]),
            "total_senadores": len([p for p in legisladores if p.get("camara") == "Senadores"]),
            "estados": data_service.estados_catalog,
            "politicos_muestra": legisladores
        }
    )

@router.get("/electoral")
async def electoral_view(request: Request):
    return templates.TemplateResponse(
        request=request,
        name="electoral.html",
        context={
            "seo": {
                "title": "Auditoría Electoral Ciudadana — Pulso Cívico",
                "description": "Sube y protege fotografías de las actas de tu casilla electoral de forma descentralizada."
            }
        }
    )

