from typing import Optional, List, Dict, Any
from pydantic import BaseModel, Field

class LegisladorSchema(BaseModel):
    referencia_sil: Optional[str] = None
    nombre_completo: str
    camara: Optional[str] = "Diputados"
    legislatura: Optional[int] = 66
    partido: Optional[str] = "Sin Partido"
    principio_eleccion: Optional[str] = None
    entidad: Optional[str] = None
    distrito: Optional[str] = None
    tipo_cargo: Optional[str] = "Propietario"
    suplente_nombre: Optional[str] = None
    fecha_toma_protesta: Optional[str] = None
    fecha_nacimiento: Optional[str] = None
    correo_institucional: Optional[str] = None
    telefono: Optional[str] = None
    ubicacion_oficina: Optional[str] = None
    ultimo_grado_estudios: Optional[str] = None
    preparacion_academica: Optional[str] = None
    experiencia_legislativa_resumen: Optional[str] = None
    foto_oficial_url: Optional[str] = None
    foto_local_path: Optional[str] = None
    slug: Optional[str] = None
    comisiones: List[Dict[str, Any]] = Field(default_factory=list)
    trayectoria_politica: List[Dict[str, Any]] = Field(default_factory=list)
    trayectoria_legislativa: List[Dict[str, Any]] = Field(default_factory=list)
    trayectoria_administrativa: List[Dict[str, Any]] = Field(default_factory=list)
    escolaridad_detalle: List[Dict[str, Any]] = Field(default_factory=list)
    licencias: List[Dict[str, Any]] = Field(default_factory=list)
    iniciativas: List[Dict[str, Any]] = Field(default_factory=list)
    puntos_acuerdo: List[Dict[str, Any]] = Field(default_factory=list)
    redes_sociales: List[Dict[str, Any]] = Field(default_factory=list)
    metadata: Dict[str, Any] = Field(default_factory=dict)

    class Config:
        extra = "allow"
