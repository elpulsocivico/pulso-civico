import re
import unicodedata
from pathlib import Path
from typing import List, Dict, Any, Optional
import orjson
from app.core.config import settings

def slugify(text: str) -> str:
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("utf-8")
    text = re.sub(r"[^\w\s-]", "", text.lower()).strip()
    return re.sub(r"[-\s]+", "-", text)

class DataService:
    def __init__(self):
        self.politicos_index: List[Dict[str, Any]] = []
        self.slug_index: Dict[str, Dict[str, Any]] = {}
        self.estados_catalog: Dict[str, Dict[str, Any]] = {}
        self.partidos_index: Dict[str, List[Dict[str, Any]]] = {}
        self.iniciativas: List[Dict[str, Any]] = []
        self.votaciones: List[Dict[str, Any]] = []
        self.sesiones: List[Dict[str, Any]] = []
        self.asistencias: Dict[str, Dict[str, Any]] = {}

        self._init_estados_catalog()
        self.load_data()

    def _init_estados_catalog(self):
        estados_raw = [
            (1, 'Aguascalientes', 'aguascalientes', 'AGS'),
            (2, 'Baja California', 'baja-california', 'BC'),
            (3, 'Baja California Sur', 'baja-california-sur', 'BCS'),
            (4, 'Campeche', 'campeche', 'CAMP'),
            (5, 'Coahuila', 'coahuila', 'COAH'),
            (6, 'Colima', 'colima', 'COL'),
            (7, 'Chiapas', 'chiapas', 'CHIS'),
            (8, 'Chihuahua', 'chihuahua', 'CHIH'),
            (9, 'Ciudad de México', 'ciudad-de-mexico', 'CDMX'),
            (10, 'Durango', 'durango', 'DGO'),
            (11, 'Guanajuato', 'guanajuato', 'GTO'),
            (12, 'Guerrero', 'guerrero', 'GRO'),
            (13, 'Hidalgo', 'hidalgo', 'HGO'),
            (14, 'Jalisco', 'jalisco', 'JAL'),
            (15, 'Estado de México', 'estado-de-mexico', 'EDOMEX'),
            (16, 'Michoacán', 'michoacan', 'MICH'),
            (17, 'Morelos', 'morelos', 'MOR'),
            (18, 'Nayarit', 'nayarit', 'NAY'),
            (19, 'Nuevo León', 'nuevo-leon', 'NL'),
            (20, 'Oaxaca', 'oaxaca', 'OAX'),
            (21, 'Puebla', 'puebla', 'PUE'),
            (22, 'Querétaro', 'queretaro', 'QRO'),
            (23, 'Quintana Roo', 'quintana-roo', 'QROO'),
            (24, 'San Luis Potosí', 'san-luis-potosi', 'SLP'),
            (25, 'Sinaloa', 'sinaloa', 'SIN'),
            (26, 'Sonora', 'sonora', 'SON'),
            (27, 'Tabasco', 'tabasco', 'TAB'),
            (28, 'Tamaulipas', 'tamaulipas', 'TAMPS'),
            (29, 'Tlaxcala', 'tlaxcala', 'TLAX'),
            (30, 'Veracruz', 'veracruz', 'VER'),
            (31, 'Yucatán', 'yucatan', 'YUC'),
            (32, 'Zacatecas', 'zacatecas', 'ZAC'),
        ]
        for item_id, nombre, slug, abbr in estados_raw:
            self.estados_catalog[slug] = {
                "id": item_id,
                "nombre": nombre,
                "slug": slug,
                "abbr": abbr
            }

    def load_data(self):
        json_dir = settings.STORAGE_DATA_DIR
        if not json_dir.exists():
            return

        # 1. Cargar iniciativas
        iniciativas_file = json_dir / "iniciativas_bc.json"
        if iniciativas_file.exists():
            try:
                with open(iniciativas_file, "rb") as f:
                    self.iniciativas = orjson.loads(f.read())
            except Exception:
                self.iniciativas = []

        # 2. Cargar legisladores
        todos = []
        dedup = set()

        json_files = list(json_dir.glob("congreso_union_leg_*.json"))
        if not json_files:
            json_files = list(json_dir.glob("legisladores_entidad_*_leg_*.json"))
        else:
            json_files.extend(list(json_dir.glob("legisladores_entidad_*_leg_*.json")))

        for sf in json_dir.glob("congreso_*_leg_*.json"):
            if not sf.name.startswith("congreso_union_"):
                json_files.append(sf)

        for filepath in json_files:
            if filepath.stat().st_size < 1000:
                continue
            try:
                with open(filepath, "rb") as f:
                    raw = orjson.loads(f.read())
                if not isinstance(raw, dict):
                    continue
                dips = raw.get("diputados", [])
                sens = raw.get("senadores", [])
                locales = raw.get("legisladores", [])
                for leg in dips + sens + locales:
                    ref = str(leg.get("referencia_sil", ""))
                    leg_num = int(leg.get("legislatura", 66))
                    dedup_key = f"{ref}_{leg_num}"
                    if dedup_key not in dedup:
                        dedup.add(dedup_key)
                        
                        # Asignar slug canónico si no lo tiene
                        if not leg.get("slug"):
                            leg["slug"] = slugify(leg.get("nombre_completo", ""))
                        todos.append(leg)
            except Exception:
                continue

        self.politicos_index = todos
        self.slug_index = {l["slug"]: l for l in todos if l.get("slug")}

        # Indexar partidos
        self.partidos_index.clear()
        for p in self.politicos_index:
            partido = p.get("partido", "Sin Partido")
            self.partidos_index.setdefault(partido, []).append(p)

    def get_all_politicos(self) -> List[Dict[str, Any]]:
        return self.politicos_index

    def get_politico_by_slug(self, slug: str) -> Optional[Dict[str, Any]]:
        return self.slug_index.get(slug)

    def get_diputados(self) -> List[Dict[str, Any]]:
        return [p for p in self.politicos_index if p.get("camara") == "Diputados"]

    def get_senadores(self) -> List[Dict[str, Any]]:
        return [p for p in self.politicos_index if p.get("camara") == "Senadores"]

    def get_by_estado(self, estado_slug: str) -> List[Dict[str, Any]]:
        target = self.estados_catalog.get(estado_slug)
        if not target:
            return []
        nombre_estado = target["nombre"].lower()
        return [
            p for p in self.politicos_index
            if p.get("entidad") and p["entidad"].lower() == nombre_estado
        ]

    def buscar(self, query: str) -> List[Dict[str, Any]]:
        if not query or len(query.strip()) < 2:
            return []
        q = slugify(query)
        resultados = []
        for p in self.politicos_index:
            nombre_slug = slugify(p.get("nombre_completo", ""))
            partido_slug = slugify(p.get("partido", ""))
            entidad_slug = slugify(p.get("entidad", ""))
            if q in nombre_slug or q in partido_slug or q in entidad_slug:
                resultados.append(p)
                if len(resultados) >= 25:
                    break
        return resultados

data_service = DataService()
