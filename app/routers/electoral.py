from datetime import datetime, timezone
from fastapi import APIRouter, UploadFile, File, Form, HTTPException, BackgroundTasks
from app.schemas.acta import ActaRecepcionResponse
from app.services.image_service import image_service
from app.core.config import settings

router = APIRouter(tags=["Electoral"])

def async_procesar_acta(bytes_data: bytes, seccion: str, casilla: str):
    image_service.sanitizar_y_guardar_acta(bytes_data, seccion, casilla)

@router.post("/actas", response_model=ActaRecepcionResponse)
async def recibir_acta_casilla(
    background_tasks: BackgroundTasks,
    seccion: str = Form(..., description="Número de sección electoral (ej. 0450)"),
    casilla: str = Form(..., description="Tipo de casilla (B, C1, E1, etc.)"),
    foto: UploadFile = File(..., description="Fotografía de la sábana o acta de casilla")
):
    """
    Recepción descentralizada y anónima de actas electorales.
    - Sanitiza metadatos EXIF.
    - Calcula hash SHA-256 para auditoría inmutable.
    - Convierte a WebP para sincronización ligera entre nodos.
    """
    contenido = await foto.read()
    
    if len(contenido) > settings.MAX_UPLOAD_SIZE_BYTES:
        raise HTTPException(
            status_code=400,
            detail=f"El archivo excede el tamaño máximo permitido de {settings.MAX_UPLOAD_SIZE_BYTES // (1024*1024)} MB"
        )
    
    # Procesar de inmediato o en background
    metadata = image_service.sanitizar_y_guardar_acta(contenido, seccion.strip(), casilla.strip().upper())
    
    return ActaRecepcionResponse(
        status="ok",
        hash_sha256=metadata["hash_sha256"],
        seccion=seccion,
        casilla=casilla,
        mensaje="Acta recibida, sanitizada y asegurada criptográficamente con éxito.",
        timestamp=metadata["fecha_subida"]
    )
