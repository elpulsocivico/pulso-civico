from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.cors import CORSMiddleware
from app.core.config import settings
from app.core.security import RateLimitMiddleware
from app.routers import web, api, electoral

# Candado 1: Ocultar documentación pública en producción (o si ENABLE_API_DOCS es False)
docs_url = "/docs" if (settings.DEBUG and settings.ENABLE_API_DOCS) else None
openapi_url = "/openapi.json" if (settings.DEBUG and settings.ENABLE_API_DOCS) else None

app = FastAPI(
    title=settings.APP_NAME,
    description="Observatorio del Congreso Mexicano y Auditoría Electoral Descentralizada",
    version="2.0.0",
    docs_url=docs_url,
    redoc_url=None,
    openapi_url=openapi_url
)

# Candado 2: Restringir consumo desde sitios externos (CORS)
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)

# Candado 3: Freno de mano contra bots y saturación de CPU/memoria (Rate Limit por IP)
app.add_middleware(
    RateLimitMiddleware,
    requests_limit=settings.API_RATE_LIMIT_PER_MINUTE,
    window_seconds=60
)

# 1. Montar directorio de fotos existente (sin mover archivos)
if settings.STORAGE_RAW_PHOTOS.exists():
    app.mount("/photos", StaticFiles(directory=str(settings.STORAGE_RAW_PHOTOS)), name="photos")
    app.mount("/storage/raw/photos", StaticFiles(directory=str(settings.STORAGE_RAW_PHOTOS)), name="storage_photos")

# 2. Montar directorio de actas electorales
settings.STORAGE_ELECTORAL_DIR.mkdir(parents=True, exist_ok=True)
app.mount("/electoral/actas", StaticFiles(directory=str(settings.STORAGE_ELECTORAL_DIR)), name="actas")

# 3. Montar assets estáticos si existen
if settings.ASSETS_DIR.exists():
    app.mount("/assets", StaticFiles(directory=str(settings.ASSETS_DIR)), name="assets")

# 4. Registrar routers
app.include_router(web.router)
app.include_router(api.router, prefix="/api")
app.include_router(electoral.router, prefix="/api/electoral")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("app.main:app", host=settings.HOST, port=settings.PORT, reload=settings.DEBUG)
