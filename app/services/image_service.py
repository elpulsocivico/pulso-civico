import io
import hashlib
from datetime import datetime, timezone
from pathlib import Path
from typing import Tuple, Dict, Any
from PIL import Image
from app.core.config import settings

class ImageService:
    @staticmethod
    def sanitizar_y_guardar_acta(contenido_bytes: bytes, seccion: str, casilla: str) -> Dict[str, Any]:
        """
        1. Genera hash SHA-256 inmutable de la imagen original recibida.
        2. Elimina al 100% metadatos EXIF (geolocalización GPS, modelo de cámara, número de serie).
        3. Convierte a formato WebP optimizado para máxima velocidad de distribución P2P.
        """
        sha256_hash = hashlib.sha256(contenido_bytes).hexdigest()
        
        # Abrir imagen en memoria
        image = Image.open(io.BytesIO(contenido_bytes))
        
        # Crear imagen nueva completamente limpia (sin EXIF de origen)
        clean_img = Image.new("RGB", image.size)
        if image.mode in ("RGBA", "P"):
            image = image.convert("RGB")
        clean_img.paste(image)
        
        # Ruta destino
        settings.STORAGE_ELECTORAL_DIR.mkdir(parents=True, exist_ok=True)
        filename = f"{seccion}_{casilla}_{sha256_hash[:12]}.webp"
        target_path = settings.STORAGE_ELECTORAL_DIR / filename
        
        # Guardar en WebP comprimido
        clean_img.save(target_path, "WEBP", quality=85, method=6)
        
        file_size = target_path.stat().st_size
        ahora = datetime.now(timezone.utc).isoformat()
        
        return {
            "hash_sha256": sha256_hash,
            "filename": filename,
            "seccion": seccion,
            "casilla": casilla,
            "tamano_bytes": file_size,
            "fecha_subida": ahora,
            "archivo_relativo": f"/electoral/actas/{filename}"
        }

image_service = ImageService()
