"""Carga de catálogos estáticos (legislaturas, estudios de gobernadores, alcaldes destacados)
desde archivos JSON en ``storage/data/json/catalogos/``.

Cada archivo tiene la forma::

    {"_meta": {"fuente_url": ..., "fecha_migracion": ..., ...}, "datos": {...}}

Solo ``datos`` se expone al resto de la aplicación; ``_meta`` documenta la procedencia.
"""
import json
from pathlib import Path
from typing import Any, Dict

from app.core.config import settings

CATALOGOS_DIR: Path = settings.STORAGE_DATA_DIR / "catalogos"


def load_catalog(nombre: str, catalogos_dir: Path = CATALOGOS_DIR) -> Dict[Any, Any]:
    """Lee ``<nombre>.json`` y devuelve su sección ``datos``.

    Lanza FileNotFoundError / ValueError si el archivo falta o es inválido, para no
    arrancar la aplicación con catálogos vacíos de forma silenciosa.
    """
    ruta = catalogos_dir / f"{nombre}.json"
    with ruta.open("r", encoding="utf-8") as fh:
        contenido = json.load(fh)
    datos = contenido.get("datos") if isinstance(contenido, dict) else None
    if not isinstance(datos, dict):
        raise ValueError(f"Catálogo inválido (falta 'datos'): {ruta.name}")
    return datos


def load_catalog_legislaturas(catalogos_dir: Path = CATALOGOS_DIR) -> Dict[int, Dict[str, Any]]:
    """Catálogo de legislaturas con las claves convertidas a ``int`` (JSON solo admite texto)."""
    return {int(k): v for k, v in load_catalog("legislaturas", catalogos_dir).items()}
