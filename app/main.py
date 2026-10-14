from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from app.core.config import settings
from app.routers import web, api, electoral

app = FastAPI(
    title=settings.APP_NAME,
    description="Observatorio del Congreso Mexicano y Auditoría Electoral Descentralizada",
    version="2.0.0"
)

# 1. Montar directorio de fotos existente (sin mover archivos)
if settings.STORAGE_RAW_PHOTOS.exists():
    app.mount("/photos", StaticFiles(directory=str(settings.STORAGE_RAW_PHOTOS)), name="photos")

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
