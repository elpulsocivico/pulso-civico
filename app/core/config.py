import os
from pathlib import Path
from pydantic_settings import BaseSettings

# Directorio raíz del proyecto (e:\xampp\htdocs\elpulso)
BASE_DIR = Path(__file__).resolve().parent.parent.parent

class Settings(BaseSettings):
    APP_NAME: str = "Pulso Cívico"
    APP_ENV: str = "development"
    DEBUG: bool = True
    PORT: int = 8000
    HOST: str = "127.0.0.1"

    # Rutas de almacenamiento existentes
    STORAGE_DATA_DIR: Path = BASE_DIR / "storage" / "data" / "json"
    STORAGE_RAW_PHOTOS: Path = BASE_DIR / "storage" / "raw" / "photos"
    STORAGE_ELECTORAL_DIR: Path = BASE_DIR / "storage" / "electoral" / "actas"
    TEMPLATES_DIR: Path = BASE_DIR / "app" / "templates"
    ASSETS_DIR: Path = BASE_DIR / "assets"

    # Límite de subida de fotos (15 MB)
    MAX_UPLOAD_SIZE_BYTES: int = 15 * 1024 * 1024

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"
        extra = "ignore"

settings = Settings()
