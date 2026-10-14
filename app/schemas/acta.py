from typing import Optional, List, Dict, Any
from pydantic import BaseModel, Field

class ActaRecepcionResponse(BaseModel):
    status: str = "ok"
    hash_sha256: str
    seccion: str
    casilla: str
    mensaje: str = "Acta recibida y protegida con éxito"
    timestamp: str

class ActaMetadata(BaseModel):
    seccion: str
    casilla: str
    entidad: Optional[str] = None
    distrito: Optional[str] = None
    hash_sha256: str
    tamano_bytes: int
    fecha_subida: str
    archivo_relativo: str
    verificado_por_nodos: List[str] = Field(default_factory=list)
