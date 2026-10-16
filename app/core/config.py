import os
from pathlib import Path
from pydantic_settings import BaseSettings

# Directorio raíz del proyecto (e:\xampp\htdocs\elpulso)
BASE_DIR = Path(__file__).resolve().parent.parent.parent

class Settings(BaseSettings):
    BASE_DIR: Path = BASE_DIR
    APP_NAME: str = "El Pulso de la Política"
    APP_SHORT_NAME: str = "El Pulso"
    APP_TAGLINE: str = "Radiografía ciudadana de Gobernadores, Alcaldes, Diputados y Senadores"
    APP_ENV: str = "development"
    DEBUG: bool = True
    PORT: int = 8000
    HOST: str = "0.0.0.0"

    # Rutas de almacenamiento existentes
    STORAGE_DATA_DIR: Path = BASE_DIR / "storage" / "data" / "json"
    STORAGE_RAW_PHOTOS: Path = BASE_DIR / "storage" / "raw" / "photos"
    STORAGE_ELECTORAL_DIR: Path = BASE_DIR / "storage" / "electoral" / "actas"
    TEMPLATES_DIR: Path = BASE_DIR / "app" / "templates"
    ASSETS_DIR: Path = BASE_DIR / "assets"

    # Límite de subida de fotos (15 MB)
    MAX_UPLOAD_SIZE_BYTES: int = 15 * 1024 * 1024

    # Candados de protección de recursos y API
    ENABLE_API_DOCS: bool = False  # Oculta /docs y /openapi.json en producción salvo activación explícita
    API_RATE_LIMIT_PER_MINUTE: int = 60  # Máximo de consultas por minuto por IP en rutas /api
    ALLOWED_ORIGINS: list[str] = [
        "http://localhost:8000",
        "http://127.0.0.1:8000",
        "http://localhost",
        "http://127.0.0.1",
    ]

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"
        extra = "ignore"

settings = Settings()
