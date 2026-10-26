import re
import unicodedata
from pathlib import Path
from typing import List, Dict, Any, Optional
from datetime import date
from collections import defaultdict, Counter
import time
import json
import orjson
from app.core.config import settings
from app.services.education_service import education_service
from app.services.catalog_loader import load_catalog, load_catalog_legislaturas
from app.services.map_service import MapService
from app.services.explorer_service import ExplorerService

# Subm?dulos desacoplados (Paso 3 de Modularizaci?n)
from app.services.party_helpers import (
    slugify,
    normalizar_entidad,
    get_party_color,
    get_canonical_party_name,
    get_party_micro_icon,
    detect_party_from_text,
    render_party_timeline_badge,
    render_party_badge,
)
from app.services.profile_helpers import (
    formatear_nombre_natural,
    calcular_edad,
    procesar_trayectoria_y_estudios as _procesar_trayectoria_base,
)

def procesar_trayectoria_y_estudios(pol: Dict[str, Any]) -> Dict[str, Any]:
    return _procesar_trayectoria_base(pol, DataService.LEGISLATURAS_CATALOG)


class DataService:
    # Cat?logos est?ticos externalizados en storage/data/json/catalogos/ (ver catalog_loader.py)
    LEGISLATURAS_CATALOG: Dict[int, Dict[str, Any]] = load_catalog_legislaturas()

    # Directorio curricular y oficial de mandatarios estatales ejecutivos
    GOBERNADORES_DATA: Dict[str, Dict[str, Any]] = load_catalog("gobernadores_estudios")

    # Directorio curricular y oficial de alcaldes y presidentes municipales con datos comprobados
    ALCALDES_DATA: Dict[str, Dict[str, Any]] = load_catalog("alcaldes_destacados")

    def __init__(self):
        # Indexación por legislatura federal
        self.politicos_by_leg: Dict[int, List[Dict[str, Any]]] = {}
        self.slug_index_by_leg: Dict[int, Dict[str, Dict[str, Any]]] = {}
        
        # Historial de mandatos para cada político único: slug -> List[Dict]
        self.mandatos_historicos: Dict[str, List[Dict[str, Any]]] = {}
        # Perfil maestro unificado por slug (con historial completo)
        self.politicos_master: Dict[str, Dict[str, Any]] = {}

        self.estados_catalog: Dict[str, Dict[str, Any]] = {}
        self.comisiones_by_leg: Dict[int, Dict[str, Dict[str, Any]]] = {}
        self.comisiones_master: Dict[str, Dict[str, Any]] = {}
        self.mexico_paths: List[Dict[str, Any]] = []

        # Datasets adicionales
        self.asistencias_nominales: Dict[str, Dict[str, Any]] = {}
        self._map_service = MapService()
        self._explorer_service = ExplorerService()
        self.asistencias: Dict[str, Dict[str, Any]] = {}  # Alias de compatibilidad
        self.votaciones: List[Dict[str, Any]] = []
        self.iniciativas: List[Dict[str, Any]] = []
        self.indice_multinivel: Dict[str, List[Dict[str, Any]]] = {}
        self.indice_busqueda_universal: List[Dict[str, Any]] = []
        self._universal_by_slug: Dict[str, Dict[str, Any]] = {}
        self._local_congreso_index: Dict[tuple, Dict[str, Any]] = {}
        self._raw_photos_set: set[str] = set()
        self._explorador_catalogo_cache: Optional[List[Dict[str, Any]]] = None
        self._capas_mapa_cache: Optional[Dict[str, Any]] = None
        self._resumen_360_cache: Optional[Dict[str, Any]] = None

        self._init_raw_photos_index()
        self._init_estados_catalog()
        self.load_mexico_paths()
        self.load_asistencias_y_votaciones()
        self.load_all_legislaturas()

    def _init_raw_photos_index(self):
        if settings.STORAGE_RAW_PHOTOS.exists():
            try:
                self._raw_photos_set = {f.name for f in settings.STORAGE_RAW_PHOTOS.iterdir() if f.is_file()}
            except Exception:
                self._raw_photos_set = set()

    def _has_raw_photo(self, filename: str) -> bool:
        return filename in self._raw_photos_set

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
                "abbr": abbr,
                "total_representantes": 0,
                "partido_mayoria": "Sin Dato",
                "conteo_partidos": {},
                "disponible": True
            }

    def load_mexico_paths(self):
        paths_file = settings.BASE_DIR / "storage" / "data" / "mexico_paths.json"
        if paths_file.exists():
            try:
                with open(paths_file, "rb") as f:
                    self.mexico_paths = orjson.loads(f.read())
            except Exception:
                self.mexico_paths = []

    def load_asistencias_y_votaciones(self):
        json_dir = settings.STORAGE_DATA_DIR
        if not json_dir.exists():
            return

        # 1. Cargar asistencias nominales exclusivamente de la LXVI Legislatura (actual)
        self.asistencias_nominales = {}
        for af in json_dir.glob("asistencias_*_leg_66.json"):
            try:
                with open(af, "rb") as f:
                    raw = orjson.loads(f.read())
                col = raw.get("diputados") or raw.get("senadores") or {}
                if isinstance(col, dict):
                    self.asistencias_nominales.update(col)
            except Exception:
                continue

        # 2. Cargar votaciones del Pleno exclusivamente de la LXVI Legislatura (actual)
        self.votaciones = []
        for vf in sorted(json_dir.glob("votaciones_periodo_*_leg_66.json")):
            try:
                with open(vf, "rb") as f:
                    raw = orjson.loads(f.read())
                for v in raw.get("votaciones", []):
                    if int(v.get("legislatura") or 66) == 66:
                        self.votaciones.append(v)
            except Exception:
                continue

        # 3. No cruzar iniciativas locales (iniciativas_bc.json) con legisladores federales
        self.iniciativas = []

        # 4. Cargar índice de cargos y trayectorias multinivel (Alcaldías, Congresos Locales y Poderes Estatales)
        multi_file = json_dir / "indice_cargos_multinivel.json"
        if multi_file.exists():
            try:
                with open(multi_file, "rb") as f:
                    raw_multi = orjson.loads(f.read())
                self.indice_multinivel = raw_multi.get("indice", {})
            except Exception:
                self.indice_multinivel = {}

        # 5. Cargar índice de búsqueda universal (Congreso de la Unión, Congresos Estatales, Gobernadores, Alcaldes)
        uni_file = json_dir / "indice_busqueda_universal.json"
        if uni_file.exists():
            try:
                with open(uni_file, "rb") as f:
                    self.indice_busqueda_universal = orjson.loads(f.read())
                self._universal_by_slug = {it["slug"]: it for it in self.indice_busqueda_universal if "slug" in it}
            except Exception:
                self.indice_busqueda_universal = []
                self._universal_by_slug = {}

        self.asistencias = self.asistencias_nominales

    def load_all_legislaturas(self):
        json_dir = settings.STORAGE_DATA_DIR
        if not json_dir.exists():
            return

        # Pre-indexar congresos locales en memoria para evitar I/O por request y falsos positivos entre estados
        self._local_congreso_index = {}
        alias_ent_map = {
            "bcs": "bajacaliforniasur",
            "bc": "bajacalifornia",
            "cdmx": "ciudaddemexico",
            "cdmxaldf": "ciudaddemexico",
            "aldf": "ciudaddemexico",
            "chis": "chiapas",
            "edomex": "estadodemexico",
            "nl": "nuevoleon",
            "qroo": "quintanaroo",
            "slp": "sanluispotosi",
            "yuc": "yucatan",
        }
        for cf in json_dir.glob("congreso_*_leg_*.json"):
            if cf.name.startswith("congreso_union_"):
                continue
            m_cf = re.match(r"^congreso_([a-z0-9_]+)_leg_(\d+)\.json$", cf.name)
            if not m_cf:
                continue
            raw_ent_key = m_cf.group(1).replace("_", "")
            ent_key = alias_ent_map.get(raw_ent_key, raw_ent_key)
            leg_str = m_cf.group(2)
            try:
                with open(cf, "rb") as fp:
                    c_data = orjson.loads(fp.read())
                for c_p in (c_data.get("diputados", []) or c_data.get("legisladores", [])):
                    raw_nom = (c_p.get("nombre_completo") or c_p.get("nombre") or "").strip()
                    clean_nom = re.sub(r"^(?:Dip\.|Diputad[ao])\s+", "", raw_nom, flags=re.I).strip()
                    c_slug = slugify(raw_nom)
                    c_clean_slug = slugify(clean_nom)
                    for ek in {raw_ent_key, ent_key}:
                        if c_slug:
                            self._local_congreso_index[(ek, leg_str, c_slug)] = c_p
                        if c_clean_slug:
                            self._local_congreso_index[(ek, leg_str, c_clean_slug)] = c_p
            except Exception:
                continue

        # Cargamos en orden cronológico inverso para que la versión más reciente sea el perfil base
        for leg_num in [66, 65, 64, 63, 62, 61, 60, 59, 58, 57]:
            self.politicos_by_leg[leg_num] = []
            self.slug_index_by_leg[leg_num] = {}
            self.comisiones_by_leg[leg_num] = {}

            dedup = set()
            files: List[Path] = []

            # 1. Archivos consolidados nacionales federales
            consolidado = json_dir / f"congreso_union_leg_{leg_num}.json"
            if consolidado.exists():
                files.append(consolidado)

            # 2. Archivos federales por entidad (representantes federales de cada estado)
            files.extend(list(json_dir.glob(f"legisladores_entidad_*_leg_{leg_num}.json")))

            # REGLA DE INTEGRIDAD: NO cargamos congreso_*_leg_X.json aquí, ya que son congresos
            # locales con su propia numeración no federal que inflaban los conteos de la República.

            for fp in files:
                if fp.stat().st_size < 1000:
                    continue
                try:
                    with open(fp, "rb") as f:
                        raw = orjson.loads(f.read())
                    if not isinstance(raw, dict):
                        continue
                    
                    # Únicamente consideramos representantes federales: Diputados y Senadores
                    dips = raw.get("diputados", [])
                    sens = raw.get("senadores", [])

                    for leg in dips + sens:
                        camara = leg.get("camara", "")
                        if camara not in ("Diputados", "Senadores"):
                            continue

                        ref = str(leg.get("referencia_sil") or "")
                        nombre = leg.get("nombre_completo", "")
                        dedup_key = f"{ref}_{nombre}_{camara}"
                        if dedup_key in dedup:
                            continue
                        dedup.add(dedup_key)

                        leg["legislatura"] = leg_num
                        slug = leg.get("slug") or slugify(nombre)
                        leg["slug"] = slug
                        if ref and self._has_raw_photo(f"{ref}.jpg"):
                            leg["foto_oficial_url"] = f"/photos/{ref}.jpg"
                            leg["foto_local_path"] = f"/photos/{ref}.jpg"
                        elif "sil.gobernacion.gob.mx" in str(leg.get("foto_oficial_url") or ""):
                            leg["foto_oficial_url"] = ""

                        # Normalizar entidad
                        entidad_cruda = leg.get("entidad", "")
                        leg["entidad_slug"] = normalizar_entidad(entidad_cruda)
                        if leg["entidad_slug"] == "ciudad-de-mexico" and ("Distrito Federal" in entidad_cruda or "DF" in entidad_cruda):
                            leg["entidad"] = "Ciudad de México"

                        # Procesar edad, estatus de licencia, línea de tiempo y escolaridad
                        leg["edad"] = calcular_edad(leg.get("fecha_nacimiento"))
                        tipo_cargo = leg.get("tipo_cargo") or "Propietario"
                        leg["tiene_licencia"] = (
                            "Licencia" in tipo_cargo
                            or (leg.get("metadata", {}).get("estatus_licencia") == "Con Licencia")
                            or bool(leg.get("licencias"))
                        )
                        hist = procesar_trayectoria_y_estudios(leg)
                        leg["linea_tiempo"] = hist["linea_tiempo"]
                        leg["escolaridad_items"] = hist["escolaridad_items"]

                        # Auditoría académica (con escolaridad_items ya poblada)
                        leg["perfil_academico"] = education_service.clasificar_politico(leg)

                        # Cruzar asistencia real EXCLUSIVAMENTE para la LXVI Legislatura (actual)
                        asist_info = self.asistencias_nominales.get(slug) if leg_num == 66 else None
                        if asist_info and leg_num == 66:
                            pct = float(asist_info.get("porcentaje", 0))
                            leg["asistencia_kpi"] = {
                                "porcentaje": pct,
                                "asistencias": asist_info.get("asistencias", 0),
                                "faltas": asist_info.get("faltas", 0),
                                "justificadas": asist_info.get("justificadas", 0),
                                "total_sesiones": asist_info.get("total_sesiones", 0),
                                "semaforo": "verde" if pct >= 90 else ("amarillo" if pct >= 75 else "rojo")
                            }
                        else:
                            leg["asistencia_kpi"] = None

                        # Sanitizar suplente_nombre (eliminar \u00a0 y espacios duplicados)
                        suplente_raw = leg.get("suplente_nombre")
                        if suplente_raw and isinstance(suplente_raw, str):
                            suplente_clean = " ".join(suplente_raw.replace("\u00a0", " ").split()).strip()
                            leg["suplente_nombre"] = suplente_clean or None
                            leg["suplente_slug"] = slugify(suplente_clean) if suplente_clean else None

                        # No cruzar iniciativas locales en perfiles federales
                        leg["iniciativas"] = []

                        self.politicos_by_leg[leg_num].append(leg)
                        self.slug_index_by_leg[leg_num][slug] = leg

                        # Registrar mandato histórico
                        romano = self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("romano", str(leg_num))
                        periodo = self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("periodo", "")
                        mandato = {
                            "legislatura": leg_num,
                            "romano": romano,
                            "periodo": periodo,
                            "camara": camara,
                            "partido": leg.get("partido", "Sin Partido"),
                            "principio_eleccion": leg.get("principio_eleccion", "Mayoría Relativa"),
                            "entidad": leg.get("entidad", "")
                        }
                        self.mandatos_historicos.setdefault(slug, []).append(mandato)

                        # Enriquecer perfil maestro unificado
                        if slug not in self.politicos_master or leg_num == 66:
                            self.politicos_master[slug] = dict(leg)

                        # Indexar comisiones por legislatura y maestro global
                        for com in leg.get("comisiones", []):
                            c_nombre = (com.get("comision") or com.get("nombre") or "").strip()
                            if not c_nombre:
                                continue
                            c_slug = slugify(c_nombre)
                            com["slug"] = c_slug

                            puesto = com.get("puesto") or "Integrante"
                            member_rec = {
                                "nombre_completo": leg.get("nombre_completo"),
                                "slug": slug,
                                "referencia_sil": leg.get("referencia_sil", ""),
                                "partido": leg.get("partido", "Sin Partido"),
                                "foto_oficial_url": leg.get("foto_oficial_url"),
                                "foto_local_path": leg.get("foto_local_path"),
                                "puesto": puesto,
                                "camara": camara,
                                "entidad": leg.get("entidad", ""),
                                "legislatura": leg_num,
                                "romano": self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("romano", str(leg_num))
                            }

                            if c_slug not in self.comisiones_by_leg[leg_num]:
                                self.comisiones_by_leg[leg_num][c_slug] = {
                                    "nombre": c_nombre,
                                    "slug": c_slug,
                                    "camara": camara,
                                    "integrantes": []
                                }
                            self.comisiones_by_leg[leg_num][c_slug]["integrantes"].append(member_rec)

                            if c_slug not in self.comisiones_master:
                                self.comisiones_master[c_slug] = {
                                    "nombre": c_nombre,
                                    "slug": c_slug,
                                    "camara": camara,
                                    "integrantes": []
                                }
                            self.comisiones_master[c_slug]["integrantes"].append(member_rec)
                except Exception:
                    continue

        # Inyectar mandatos históricos ordenados en cada político
        for slug in self.mandatos_historicos:
            self.mandatos_historicos[slug].sort(key=lambda m: m["legislatura"], reverse=True)

        for slug, pol in self.politicos_master.items():
            pol["mandatos_historicos"] = self.mandatos_historicos.get(slug, [])

        # Cargar catálogo consolidado maestro de comisiones si existe
        coms_master_file = json_dir / "comisiones_master.json"
        if coms_master_file.exists():
            try:
                with open(coms_master_file, "rb") as f:
                    self.comisiones_master = orjson.loads(f.read())
            except Exception:
                pass

        # Enriquecer comisiones de cada legislatura con metadatos de presidencia y bancadas
        for leg_num in self.comisiones_by_leg:
            for c_slug, com in self.comisiones_by_leg[leg_num].items():
                self._enrich_comision_entry(com)

    def _enrich_comision_entry(self, com_dict: Dict[str, Any]) -> Dict[str, Any]:
        nombre = com_dict.get("nombre", "")
        cam = com_dict.get("camara", "")
        cam_upper = nombre.upper()
        if "DIPUTADOS" in cam_upper or cam == "Diputados":
            camara_tag = "Diputados"
        elif "SENAD" in cam_upper or cam == "Senadores":
            camara_tag = "Senadores"
        else:
            camara_tag = "Congreso"

        integrantes = com_dict.get("integrantes", [])
        presidencia = com_dict.get("presidente")
        if not presidencia:
            for it in integrantes:
                if "PRESID" in str(it.get("puesto", "")).upper():
                    presidencia = it
                    break

        foto_pres = "data:image/svg+xml;utf8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27 viewBox=%270 0 150 150%27%3E%3Crect width=%27150%27 height=%27150%27 fill=%27%23F1F5F9%27/%3E%3Ccircle cx=%2775%27 cy=%2758%27 r=%2726%27 fill=%27%2394A3B8%27/%3E%3Cpath d=%27M25 138 C25 103 125 103 125 138%27 fill=%27%2394A3B8%27/%3E%3C/svg%3E"
        if presidencia:
            ref_sil = str(presidencia.get("referencia_sil") or "")
            if ref_sil and self._has_raw_photo(f"{ref_sil}.jpg"):
                foto_pres = f"/photos/{ref_sil}.jpg"
            elif presidencia.get("foto_oficial_url"):
                foto_pres = presidencia["foto_oficial_url"]
            elif presidencia.get("nombre_completo"):
                nom = presidencia["nombre_completo"].replace(" ", "+")
                foto_pres = "data:image/svg+xml;utf8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27 viewBox=%270 0 150 150%27%3E%3Crect width=%27150%27 height=%27150%27 fill=%27%23F1F5F9%27/%3E%3Ccircle cx=%2775%27 cy=%2758%27 r=%2726%27 fill=%27%2394A3B8%27/%3E%3Cpath d=%27M25 138 C25 103 125 103 125 138%27 fill=%27%2394A3B8%27/%3E%3C/svg%3E"

        partidos_conteo: Dict[str, int] = {}
        for it in integrantes:
            p = (it.get("partido") or "Sin Partido").strip()
            if p:
                partidos_conteo[p] = partidos_conteo.get(p, 0) + 1

        partidos_sorted = sorted(partidos_conteo.items(), key=lambda x: x[1], reverse=True)
        top_partidos = [
            {"nombre": p_name, "count": p_count, "color": get_party_color(p_name)}
            for p_name, p_count in partidos_sorted[:4]
        ]

        com_dict["camara_tag"] = camara_tag
        com_dict["total_integrantes"] = len(integrantes)
        com_dict["presidente"] = presidencia
        com_dict["foto_presidencia"] = foto_pres
        com_dict["top_partidos"] = top_partidos
        com_dict["partidos_conteo"] = partidos_conteo
        return com_dict

    def get_all_politicos(self) -> List[Dict[str, Any]]:
        return list(self.politicos_master.values())

    def get_politicos(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return self.politicos_by_leg.get(legislatura, [])

    def get_directorio_politicos(self, legislatura: Optional[int] = None) -> List[Dict[str, Any]]:
        """
        Retorna el catálogo enriquecido de figuras públicas para el directorio,
        utilizando caché en memoria y el índice precompilado para velocidad instantánea.
        """
        cache_file = Path(settings.BASE_DIR) / "storage" / "cache" / "directorio_politicos_index.json"
        now_ts = time.time()
        if not hasattr(self, "_directorio_index_cache") or (now_ts - getattr(self, "_directorio_index_checked_at", 0) > 60):
            self._directorio_index_checked_at = now_ts
            cache_mtime = cache_file.stat().st_mtime if cache_file.exists() else 0
            if not hasattr(self, "_directorio_index_cache") or getattr(self, "_directorio_index_cache_mtime", 0) != cache_mtime:
                if cache_file.exists():
                    try:
                        with open(cache_file, "rb") as f:
                            loaded_cache = orjson.loads(f.read())
                        for item in loaded_cache:
                            if not item.get("entidad_slug"):
                                item["entidad_slug"] = normalizar_entidad(item.get("entidad") or "")
                            if int(item.get("legislatura") or 0) != 66:
                                item["asistencia_kpi"] = None
                            item["iniciativas"] = []
                            if not item.get("foto_url"):
                                ref_sil = str(item.get("referencia_sil") or "")
                                if item.get("tiene_foto_local") and ref_sil:
                                    item["foto_url"] = f"/photos/{ref_sil}.jpg"
                                elif item.get("foto_oficial_url"):
                                    item["foto_url"] = item.get("foto_oficial_url")
                                else:
                                    item["foto_url"] = ""
                        self._directorio_index_cache = loaded_cache
                        self._directorio_index_cache_mtime = cache_mtime
                    except Exception:
                        self._directorio_index_cache = None
                else:
                    self._directorio_index_cache = None

        if getattr(self, "_directorio_index_cache", None) is not None:
            if legislatura and legislatura > 0:
                return [p for p in self._directorio_index_cache if int(p.get("legislatura") or 0) == legislatura or legislatura in (p.get("legislaturas_lista") or [])]
            return self._directorio_index_cache

        if legislatura and legislatura > 0:
            base_pols = self.get_politicos(legislatura)
        else:
            base_pols = self.get_all_politicos()

        directorio = []
        for p in base_pols:
            slug = p.get("slug") or slugify(p.get("nombre_completo", ""))
            nombre = p.get("nombre_completo", "")
            if not slug or not nombre:
                continue

            camara_actual = p.get("camara", "Diputados")
            leg_num = int(p.get("legislatura") or 66)
            mandatos = self.mandatos_historicos.get(slug, [])
            legs_disponibles = sorted(list({m["legislatura"] for m in mandatos} | {leg_num}), reverse=True)
            if not legs_disponibles:
                legs_disponibles = [leg_num]

            # Analizar textos de trayectoria pública
            textos = []
            for lt in p.get("linea_tiempo", []):
                textos.append(lt.get("experiencia", ""))
            for tl in p.get("trayectoria_legislativa", []):
                textos.append(tl if isinstance(tl, str) else (tl.get("cargo") or tl.get("experiencia") or ""))
            for tp in p.get("trayectoria_politica", []):
                textos.append(tp if isinstance(tp, str) else (tp.get("cargo") or tp.get("experiencia") or ""))
            for ta in p.get("trayectoria_administrativa", []):
                textos.append(ta if isinstance(ta, str) else (ta.get("cargo") or ta.get("experiencia") or ""))
            corpus = " | ".join(textos).lower()

            # 1. Senador
            has_senador = (camara_actual == "Senadores") or any(m.get("camara") == "Senadores" for m in mandatos)
            if not has_senador and re.search(r"\b(senador|senadora|senaduria)\b", corpus, re.IGNORECASE):
                has_senador = True

            # 2. Diputado Federal
            has_dip_fed = (camara_actual == "Diputados") or any(m.get("camara") == "Diputados" for m in mandatos)
            if not has_dip_fed and re.search(r"\b(diputad[ao]\s+federal|congreso\s+de\s+la\s+union|camara\s+de\s+diputados)\b", corpus, re.IGNORECASE):
                has_dip_fed = True

            # 3. Diputado Local
            has_dip_local = bool(re.search(r"\b(diputad[ao]\s+local|congreso\s+del?\s+estado|legislatura\s+local|asamblea\s+legislativa)\b", corpus, re.IGNORECASE))

            # 4. Alcalde / Munícipe Titular y Ediles de Cabildo
            has_alcalde = False
            has_edil = False
            municipio_alcalde = None
            municipio_edil = None
            cargo_edil_label = None

            re_negativo = re.compile(r'\b(?:candidat[ao]|precandidat[ao]|aspirante|campa[ñn]a|precampa[ñn]a|elecci[oó]n|elecciones|coordinador[a]?|secretari[ao]|asesor[a]?|asistente|auxiliar|chofer|jefe\s+de\s+oficina|director[a]?|suplente|comit[eé]|cdm|cde|cen|espos[ao]|hij[ao])\b', re.IGNORECASE)

            for t in textos:
                t_trim = (t or "").strip()
                if not t_trim:
                    continue

                # 4.1 Presidente Municipal / Alcalde Titular
                m_alc = re.search(r'\b(?:president[ea]\s+municipal(?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]))?|alcald[ea](?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]))?|jef[ea]\s+delegacional)\s+(?:de|del|en)\s+([A-ZÁÉÍÓÚÑa-záéíóúñ\s]+?)(?:,|\.|\(|\bde\s+la\b|\bdel\s+estado\b|$)', t_trim, re.IGNORECASE)
                if m_alc:
                    pos = t_trim.lower().find(m_alc.group(0).lower())
                    prefijo = t_trim[:pos] if pos > 0 else ""
                    es_invalido = bool(re_negativo.search(prefijo)) or bool(re.search(r'\b(?:suplente|candidat[ao]|precandidat[ao]|durante\s+la\s+elecci[oó]n|en\s+la\s+elecci[oó]n|comit[eé]\s+directivo|cdm|cde|cen)\b', t_trim, re.IGNORECASE))
                    if not es_invalido:
                        cand = m_alc.group(1).strip()
                        cand = re.sub(r'\s+(?:b\.?c\.?|baja california|sonora|jalisco|nuevo león|méxico|edo\.?\s*méx\.?|cdmx|d\.?f\.?|chihuahua|puebla|veracruz|guerrero|oaxaca|chiapas|yucatán|tabasco|tamaulipas|coahuila|durango|sinaloa|nayarit|zacatecas|san luis potosí|aguascalientes|querétaro|hidalgo|tlaxcala|morelos|michoacán|colima|guanajuato|campeche|quintana roo)$', '', cand, flags=re.IGNORECASE).strip()
                        cand = re.sub(r'^(?:el|la|los|las)\s+', '', cand, flags=re.IGNORECASE).strip()
                        if cand and 2 < len(cand) < 32 and not re.search(r'\b(partido|comite|directivo|consejo|gobierno|dif|camara|administracion|estado|republica)\b', cand, re.IGNORECASE):
                            municipio_alcalde = cand.title()
                            has_alcalde = True
                            break

                # 4.2 Ediles de Cabildo (Regidor o Síndico)
                if not has_alcalde:
                    m_ed = re.search(r'\b(?:regidor[a]?|s[ií]ndic[ao](?:\s+procurador[a]?)?)\s+(?:de|del|en)\s+(?:el\s+(?:h\.\s*)?ayuntamiento\s+de\s+)?([A-ZÁÉÍÓÚÑa-záéíóúñ\s]+?)(?:,|\.|\(|$)', t_trim, re.IGNORECASE)
                    if m_ed:
                        pos_ed = t_trim.lower().find(m_ed.group(0).lower())
                        prefijo_ed = t_trim[:pos_ed] if pos_ed > 0 else ""
                        es_invalido_ed = bool(re_negativo.search(prefijo_ed)) or bool(re.search(r'\b(?:candidat[ao]|precandidat[ao]|suplente|durante\s+la\s+elecci[oó]n|comit[eé]\s+directivo|cdm)\b', t_trim, re.IGNORECASE))
                        if not es_invalido_ed:
                            cand = m_ed.group(1).strip()
                            cand = re.sub(r'\s+(?:b\.?c\.?|baja california|sonora|jalisco|nuevo león|méxico|edo\.?\s*méx\.?|cdmx|d\.?f\.?)$', '', cand, flags=re.IGNORECASE).strip()
                            cand = re.sub(r'^(?:el|la|los|las)\s+', '', cand, flags=re.IGNORECASE).strip()
                            if cand and 2 < len(cand) < 32 and not re.search(r'\b(partido|comite|directivo|consejo|gobierno|dif)\b', cand, re.IGNORECASE):
                                municipio_edil = cand.title()
                                has_edil = True
                                cargo_edil_label = "Síndico" if re.search(r'\bs[ií]ndic', m_ed.group(0), re.IGNORECASE) else "Regidor"

            # 5. Gobernador / Jefe de Gobierno titular (Poder Ejecutivo)
            re_gob_exclusiones = re.compile(
                r'\b(?:candidat[ao]|precandidat[ao]|aspirante|campa[ñn]a|precampa[ñn]a|elecci[oó]n|elecciones|'
                r'asesor[a]?|secretari[ao]|subsecretari[ao]|director[a]?|coordinador[a]?|chofer|auxiliar|asistente|'
                r'jefe\s+de\s+(?:oficina|departamento|unidad|gabinete)|jefatura\s+de\s+departamento|representante|'
                r'comisi[oó]n\s+de\s+gobierno|secretar[ií]a\s+(?:general\s+)?de\s+gobierno|gabinete|administraci[oó]n\s+del|'
                r'espos[ao]|hij[ao]|suplente|enlace|palacio\s+de\s+gobierno|casa\s+de\s+gobierno|informe\s+de\s+gobierno|'
                r'capacitador[a]?|consejer[ao]|delegad[ao]|agenda\s+del|equipo\s+de|comisionad[ao]|durante\s+la\s+elecci[oó]n)\b',
                re.IGNORECASE
            )
            re_gob_titular = re.compile(
                r'\b(?:gobernador[a]?\s+(?:constitucional|interin[ao]|sustitut[ao]|provisional|del?\s+estado|elect[ao])|'
                r'jef[ea]\s+de\s+gobierno(?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]|del\s+distrito\s+federal|de\s+la\s+ciudad\s+de\s+m[eé]xico|del\s+d\.?f\.?|de\s+la\s+cdmx))?)\b',
                re.IGNORECASE
            )

            has_gobernador = False
            for t_item in textos:
                t_str = (t_item or "").strip()
                if t_str and re_gob_titular.search(t_str) and not re_gob_exclusiones.search(t_str):
                    has_gobernador = True
                    break

            badges = []
            if has_senador:
                badges.append({
                    "label": "Senador",
                    "icono": "bi-bank",
                    "tipo": "senador",
                    "badge_class": "bg-info-subtle text-info-emphasis border border-info-subtle"
                })
            if has_dip_fed:
                badges.append({
                    "label": "Dip. Federal",
                    "icono": "bi-building",
                    "tipo": "diputado_federal",
                    "badge_class": "bg-primary-subtle text-primary border border-primary-subtle"
                })
            if has_dip_local:
                badges.append({
                    "label": "Dip. Local",
                    "icono": "bi-geo-alt",
                    "tipo": "diputado_local",
                    "badge_class": "bg-secondary-subtle text-secondary border border-secondary-subtle"
                })
            if has_alcalde:
                label_alcalde = f"Alcalde • {municipio_alcalde}" if municipio_alcalde else "Alcalde"
                badges.append({
                    "label": label_alcalde,
                    "icono": "bi-houses",
                    "tipo": "alcalde",
                    "badge_class": "bg-warning-subtle text-warning-emphasis border border-warning-subtle"
                })
            elif has_edil:
                label_edil = f"{cargo_edil_label} • {municipio_edil}" if municipio_edil else (cargo_edil_label or "Edil de Cabildo")
                badges.append({
                    "label": label_edil,
                    "icono": "bi-people",
                    "tipo": "edil",
                    "badge_class": "bg-secondary-subtle text-dark border border-secondary-subtle"
                })
            if has_gobernador:
                badges.append({
                    "label": "Gobernador",
                    "icono": "bi-shield-shaded",
                    "tipo": "gobernador",
                    "badge_class": "bg-danger-subtle text-danger border border-danger-subtle"
                })

            es_activo = (66 in legs_disponibles)
            leg_romano = self.LEGISLATURAS_CATALOG.get(leg_num, {}).get("romano", str(leg_num))
            cargo_tit = "Senador de la República" if camara_actual == "Senadores" else "Diputado Federal"
            cargo_resumen = f"{cargo_tit} en funciones • {leg_romano} Legislatura" if es_activo else f"Ex-{cargo_tit} • {leg_romano} Legislatura"

            # Resolver foto
            ref_sil = str(p.get("referencia_sil") or "")
            foto_local = p.get("foto_local_path")
            if ref_sil and self._has_raw_photo(f"{ref_sil}.jpg"):
                foto_url = f"/photos/{ref_sil}.jpg"
            elif foto_local:
                foto_url = f"/{foto_local.lstrip('/')}"
            else:
                foto_url = p.get("foto_oficial_url") or ""

            item = dict(p)
            item["slug"] = slug
            item["entidad_slug"] = p.get("entidad_slug") or normalizar_entidad(p.get("entidad") or "")
            item["nombre_natural"] = formatear_nombre_natural(nombre)
            item["foto_url"] = foto_url
            item["municipio_alcalde"] = municipio_alcalde or municipio_edil or ""
            item["roles_flags"] = {
                "senador": has_senador,
                "diputado_federal": has_dip_fed,
                "diputado_local": has_dip_local,
                "alcalde": has_alcalde,
                "edil": has_edil,
                "gobernador": has_gobernador
            }
            item["cargos_badges"] = badges
            item["es_activo"] = es_activo
            item["cargo_resumen"] = cargo_resumen
            item["municipio_alcalde"] = municipio_alcalde
            item["legislaturas_lista"] = legs_disponibles
            directorio.append(item)

        directorio.sort(key=lambda x: unicodedata.normalize("NFKD", x.get("nombre_completo", "")).encode("ascii", "ignore").lower())
        return directorio

    def get_directorio_estudios(self) -> List[Dict[str, Any]]:
        """
        Retorna el catálogo de figuras públicas del directorio con perfil académico
        clasificado y enriquecido para el observatorio educativo, utilizando caché en memoria.
        Incluye legisladores federales, locales y los 32 titulares del Poder Ejecutivo Estatal.
        """
        if getattr(self, "_directorio_estudios_cache", None) is not None:
            return self._directorio_estudios_cache

        base = self.get_directorio_politicos(None)
        enriquecidos = []

        # Índice auxiliar por referencia_sil para blindar el matching contra diacríticos
        master_by_ref = {}
        if hasattr(self, "politicos_master"):
            for k, v in self.politicos_master.items():
                r = v.get("referencia_sil")
                if r:
                    master_by_ref[str(r)] = v

        for p in base:
            item = dict(p)
            slug = item.get("slug")
            ref_sil = str(item.get("referencia_sil") or "")

            full = self.politicos_master.get(slug)
            if not full and ref_sil in master_by_ref:
                full = master_by_ref[ref_sil]
            if not full:
                full = item

            for fld in ("escolaridad_items", "escolaridad_detalle", "ultimo_grado_estudios", "preparacion_academica", "escolaridad", "trayectoria_academica", "distrito", "principio_eleccion"):
                if fld in full and fld not in item:
                    item[fld] = full[fld]

            analisis = education_service.clasificar_politico(item)
            item["analisis_academico"] = analisis
            item["categoria_grupo"] = education_service.determinar_categoria_agrupada(analisis.get("grado_maximo", "sin_registro"))

            roles_flags = item.get("roles_flags") or {}
            roles_lista = [rk for rk, rv in roles_flags.items() if rv]
            item["roles_lista"] = roles_lista
            item["roles_str"] = ",".join(roles_lista)

            nom = item.get("nombre_completo", "")
            nom_nat = item.get("nombre_natural", "")
            part = item.get("partido", "")
            ent = item.get("entidad", "")
            c_res = item.get("cargo_resumen", "")
            mun = item.get("municipio_alcalde", "")
            grado_lbl = analisis.get("grado_info", {}).get("label", "")
            tit = analisis.get("titulo_destacado", "")
            disc = analisis.get("disciplina_principal", "")
            alma = analisis.get("institucion_resumen", {}).get("alma_mater_destacada", "")
            item["search_corpus"] = f"{nom} {nom_nat} {part} {ent} {c_res} {mun} {grado_lbl} {tit} {disc} {alma}".lower()
            enriquecidos.append(item)

        # Cargar los 32 Gobernadores(as) en funciones con ficha académica verificada
        gob_file = settings.STORAGE_DATA_DIR / "gobernadores_vigentes_estudios.json"
        if gob_file.exists():
            try:
                with open(gob_file, "r", encoding="utf-8") as gf:
                    raw_gobs = json.load(gf).get("gobernadores", [])

                slugs_existentes = {it.get("slug") for it in enriquecidos}
                nombres_existentes = {unicodedata.normalize("NFKD", it.get("nombre_completo", "")).encode("ascii", "ignore").lower() for it in enriquecidos}

                for gob in raw_gobs:
                    g_nom_norm = unicodedata.normalize("NFKD", gob.get("nombre_completo", "")).encode("ascii", "ignore").lower()
                    if gob.get("slug") in slugs_existentes or g_nom_norm in nombres_existentes:
                        # Si ya existía por ser ex-legislador, actualizar su rol y asegurar sus estudios
                        for it in enriquecidos:
                            it_nom_norm = unicodedata.normalize("NFKD", it.get("nombre_completo", "")).encode("ascii", "ignore").lower()
                            if it.get("slug") == gob.get("slug") or it_nom_norm == g_nom_norm:
                                it["roles_flags"]["gobernador"] = True
                                it["es_activo"] = True
                                it["cargo_resumen"] = f"Gobernador(a) en funciones • {gob.get('entidad')}"
                                if not it.get("ultimo_grado_estudios") or str(it.get("ultimo_grado_estudios")).lower() in ("none", "null", "sin registro", ""):
                                    it["ultimo_grado_estudios"] = gob.get("ultimo_grado_estudios")
                                    it["preparacion_academica"] = gob.get("preparacion_academica")
                                    it["escolaridad_detalle"] = gob.get("escolaridad_detalle")
                                    analisis = education_service.clasificar_politico(it)
                                    it["analisis_academico"] = analisis
                                    it["categoria_grupo"] = education_service.determinar_categoria_agrupada(analisis.get("grado_maximo", "sin_registro"))
                                break
                    else:
                        # Gobernador titular nuevo
                        g_slug = slugify(gob.get("nombre_completo"))
                        analisis = education_service.clasificar_politico(gob)
                        g_item = {
                            "slug": g_slug,
                            "nombre_completo": gob.get("nombre_completo"),
                            "nombre_natural": formatear_nombre_natural(gob.get("nombre_completo")),
                            "partido": gob.get("partido"),
                            "partido_slug": slugify(gob.get("partido")),
                            "entidad": gob.get("entidad"),
                            "entidad_slug": normalizar_entidad(gob.get("entidad")),
                            "cargo_resumen": f"Gobernador(a) en funciones • {gob.get('entidad')}",
                            "roles_flags": {"gobernador": True, "senador": False, "diputado_federal": False, "alcalde": False, "diputado_local": False, "edil": False},
                            "roles_lista": ["gobernador"],
                            "roles_str": "gobernador",
                            "cargos_badges": [{
                                "label": f"Gobernador(a) • {gob.get('entidad')}",
                                "icono": "bi-shield-shaded",
                                "tipo": "gobernador",
                                "badge_class": "bg-danger-subtle text-danger border border-danger-subtle"
                            }],
                            "es_activo": True,
                            "foto_url": "",
                            "municipio_alcalde": "",
                            "ultimo_grado_estudios": gob.get("ultimo_grado_estudios"),
                            "preparacion_academica": gob.get("preparacion_academica"),
                            "escolaridad_detalle": gob.get("escolaridad_detalle"),
                            "analisis_academico": analisis,
                            "categoria_grupo": education_service.determinar_categoria_agrupada(analisis.get("grado_maximo", "sin_registro")),
                            "legislaturas_lista": [66]
                        }
                        grado_lbl = analisis.get("grado_info", {}).get("label", "")
                        tit = analisis.get("titulo_destacado", "")
                        disc = analisis.get("disciplina_principal", "")
                        alma = analisis.get("institucion_resumen", {}).get("alma_mater_destacada", "")
                        g_item["search_corpus"] = f"{gob.get('nombre_completo')} {gob.get('partido')} {gob.get('entidad')} Gobernador {grado_lbl} {tit} {disc} {alma}".lower()
                        enriquecidos.append(g_item)
            except Exception as e:
                print(f"[DataService] Error cargando gobernadores en estudios: {e}")

        def _prioridad_estudios_sort_key(it: Dict[str, Any]):
            roles = it.get("roles_flags") or {}
            es_activo = bool(it.get("es_activo", False))
            score = 0
            if es_activo:
                score += 1000000
                if roles.get("gobernador"): score += 500000
                if roles.get("senador"): score += 400000
                if roles.get("diputado_federal"): score += 300000
                if roles.get("alcalde"): score += 200000
                if roles.get("diputado_local"): score += 100000
            else:
                legs = it.get("legislaturas_lista") or []
                max_leg = max(legs) if legs else 0
                score += max_leg * 10000
                if roles.get("gobernador"): score += 5000
                if roles.get("senador"): score += 4000
                if roles.get("diputado_federal"): score += 3000
                if roles.get("alcalde"): score += 2000
                if roles.get("diputado_local"): score += 1000
            nombre_norm = unicodedata.normalize("NFKD", it.get("nombre_completo", "")).encode("ascii", "ignore").lower()
            return (-score, nombre_norm)

        enriquecidos.sort(key=_prioridad_estudios_sort_key)
        self._directorio_estudios_cache = enriquecidos
        return self._directorio_estudios_cache
    def find_politico_by_nombre(self, nombre_raw: str, legislatura: Optional[int] = None) -> Optional[Dict[str, Any]]:
        if not nombre_raw:
            return None
        s = slugify(nombre_raw)
        pool = self.slug_index_by_leg.get(legislatura, {}) if (legislatura and legislatura in self.slug_index_by_leg) else self.politicos_master
        if s in pool:
            return pool[s]
        if s in self.politicos_master:
            return self.politicos_master[s]

        tokens = [t for t in s.split("-") if t and len(t) > 1]
        if not tokens:
            return None

        # Permutaciones exactas (Nombre Apellido1 Apellido2 <-> Apellido1 Apellido2 Nombre) sin coincidencia difusa n-1
        candidatos = []
        if len(tokens) == 3:
            candidatos.append(f"{tokens[1]}-{tokens[2]}-{tokens[0]}")
            candidatos.append(f"{tokens[2]}-{tokens[0]}-{tokens[1]}")
        elif len(tokens) == 4:
            candidatos.append(f"{tokens[2]}-{tokens[3]}-{tokens[0]}-{tokens[1]}")
            candidatos.append(f"{tokens[1]}-{tokens[2]}-{tokens[3]}-{tokens[0]}")
            candidatos.append(f"{tokens[3]}-{tokens[0]}-{tokens[1]}-{tokens[2]}")
        elif len(tokens) == 2:
            candidatos.append(f"{tokens[1]}-{tokens[0]}")

        for cand in candidatos:
            if cand in pool:
                return pool[cand]
            if cand in self.politicos_master:
                return self.politicos_master[cand]

        return None

    def _build_perfil_from_catalogo_item(self, it: Dict[str, Any]) -> Dict[str, Any]:
        """Construye una ficha de político oficial completa para figuras ejecutivas o municipales a partir del catálogo unificado."""
        slug = it.get("slug") or slugify(it.get("nombre") or "")
        nombre_completo = it.get("nombre_completo") or it.get("nombre") or ""
        nombre_natural = it.get("nombre") or formatear_nombre_natural(nombre_completo)
        cargo = it.get("cargo") or "Servidor(a) Público(a)"
        partido = it.get("partido_actual") or "Sin Partido"
        partido_color = it.get("partido_actual_color") or get_party_color(partido)
        entidad = it.get("entidad") or "México"
        entidad_slug = it.get("entidad_slug") or normalizar_entidad(entidad)
        periodo = it.get("periodo") or "2024–2027"
        es_actual = bool(it.get("es_actual", True))
        foto = it.get("foto_url") or ""
        nivel = it.get("nivel") or "Estatal"

        mandatos = [{
            "legislatura": 66 if es_actual else 0,
            "romano": "Constitucional",
            "periodo": periodo,
            "camara": cargo,
            "partido": partido,
            "principio_eleccion": it.get("principio_eleccion") or "Elección Popular Directa",
            "entidad": entidad
        }]

        if it.get("linea_tiempo"):
            linea_tiempo = list(it["linea_tiempo"])
        else:
            mun_str = it.get("municipio")
            exp_text = f"{cargo} de {mun_str}, {entidad}" if (mun_str and nivel == "Municipal") else f"{cargo} de {entidad}"
            linea_tiempo = [{
                "experiencia": exp_text,
                "periodo": periodo,
                "partido": partido,
                "partido_color": partido_color,
                "es_cargo_actual": es_actual,
                "es_reciente": True
            }]

        grado = it.get("grado_academico") or "Sin Registro"
        disciplina = it.get("disciplina") or "-"
        if it.get("escolaridad_items"):
            escolaridad_items = list(it["escolaridad_items"])
        else:
            escolaridad_items = []
            if grado != "Sin Registro":
                disc_label = f" en {disciplina}" if disciplina and disciplina != "-" else ""
                escolaridad_items.append({"experiencia": f"{grado}{disc_label}", "estudio": f"{grado}{disc_label}"})

        perfil_base = {
            "slug": slug,
            "nombre_completo": nombre_completo,
            "nombre_natural": nombre_natural,
            "cargo": cargo,
            "camara": cargo,
            "tipo_cargo": "Titular Electo",
            "nivel": nivel,
            "partido": partido,
            "partido_actual_color": partido_color,
            "entidad": entidad,
            "entidad_slug": entidad_slug,
            "distrito": it.get("municipio") or ("Poder Ejecutivo Estatal" if nivel == "Estatal" else ""),
            "municipio": it.get("municipio") or "",
            "foto_oficial_url": it.get("foto_oficial_url") or foto,
            "referencia_sil": None,
            "periodo": periodo,
            "legislatura": 66 if es_actual else 0,
            "es_actual": es_actual,
            "tiene_licencia": bool(it.get("tiene_licencia")),
            "principio_eleccion": it.get("principio_eleccion") or "Elección Popular Directa",
            "ultimo_grado_estudios": grado if grado != "Sin Registro" else None,
            "preparacion_academica": disciplina if disciplina != "-" else None,
            "grado_academico": grado,
            "disciplina": disciplina,
            "escolaridad_items": escolaridad_items,
            "linea_tiempo": linea_tiempo,
            "mandatos_historicos": mandatos,
            "legislaturas_disponibles": [66] if es_actual else [],
            "asistencia_kpi": None,
            "iniciativas": [],
            "comisiones": [],
            "redes_sociales": it.get("redes_sociales") or [],
            "disciplina_partidaria_pct": 100.0,
            "fuente_url": it.get("fuente_url") or ""
        }
        perfil_base["perfil_academico"] = education_service.clasificar_politico(perfil_base)
        return perfil_base

    def _build_perfil_from_universal_item(self, it: Dict[str, Any]) -> Dict[str, Any]:
        """Construye un perfil oficial completo para figuras de Congresos Locales, Alcaldías o Gubernaturas registradas en el índice universal."""
        slug = it.get("slug") or slugify(it.get("nombre", ""))
        nombre = it.get("nombre", "")
        cargo = it.get("cargo") or "Servidor(a) Público(a)"
        camara = it.get("camara") or cargo
        partido = get_canonical_party_name(it.get("partido") or "Sin Partido")
        partido_color = get_party_color(partido)
        entidad = it.get("entidad") or "México"
        entidad_slug = normalizar_entidad(entidad)
        municipio = it.get("municipio") or ""
        ambito = it.get("ambito") or "Estatal"
        foto = it.get("foto_url") or ""

        # Enriquecer datos desde el índice en memoria del congreso local (sin lecturas de disco ni cruce entre estados)
        info_extra = {}
        if ambito == "Estatal" and "Leg." in cargo:
            m_leg = re.search(r"(\d+)\s*Leg", cargo)
            leg_num = m_leg.group(1) if m_leg else ""
            ent_norm = entidad_slug.replace("-", "")
            if leg_num and ent_norm:
                info_extra = (
                    self._local_congreso_index.get((ent_norm, leg_num, slug))
                    or self._local_congreso_index.get((ent_norm, leg_num, slugify(nombre)))
                    or {}
                )

        distrito = info_extra.get("distrito") or municipio
        principio = info_extra.get("principio_eleccion") or "Mayoría Relativa"
        periodo = info_extra.get("periodo") or it.get("periodo") or "Mandato Registrado"
        comisiones = info_extra.get("comisiones") or []
        ubicacion = info_extra.get("ubicacion_oficina") or (f"Edificio del Poder Legislativo de {entidad}" if ambito == "Estatal" else f"Palacio Municipal de {municipio}")

        m_leg_num = 0
        if ambito == "Estatal" and "Leg" in cargo:
            m_l = re.search(r"(\d+)\s*Leg", cargo)
            if m_l:
                m_leg_num = int(m_l.group(1))

        romano_label = cargo
        if m_leg_num > 0:
            romano_label = f"{m_leg_num} Legislatura"

        mandatos = [{
            "legislatura": m_leg_num,
            "romano": romano_label,
            "periodo": periodo,
            "camara": camara,
            "partido": partido,
            "principio_eleccion": principio,
            "entidad": entidad
        }]

        linea_tiempo = [{
            "experiencia": f"{cargo} por {entidad}" + (f" ({distrito})" if distrito else ""),
            "periodo": periodo,
            "partido": partido,
            "partido_color": partido_color,
            "es_cargo_actual": False,
            "es_reciente": True
        }]

        es_dip_local = (ambito == "Estatal" and "Diputad" in cargo)

        nombre_completo = info_extra.get("nombre_completo") or (f"Dip. {nombre}" if (es_dip_local and not nombre.startswith("Dip.")) else nombre)
        nombre_natural = formatear_nombre_natural(nombre_completo)

        perfil = {
            "slug": slug,
            "nombre_completo": nombre_completo,
            "nombre_natural": nombre_natural,
            "cargo": cargo,
            "camara": camara,
            "tipo_cargo": info_extra.get("tipo_cargo") or "Titular Electo",
            "nivel": ambito,
            "partido": partido,
            "partido_actual_color": partido_color,
            "entidad": entidad,
            "entidad_slug": entidad_slug,
            "distrito": distrito,
            "municipio": municipio,
            "foto_oficial_url": info_extra.get("foto_oficial_url") or foto,
            "referencia_sil": info_extra.get("referencia_sil"),
            "periodo": periodo,
            "legislatura": m_leg_num,
            "es_actual": False,
            "tiene_licencia": False,
            "principio_eleccion": principio,
            "ubicacion_oficina": ubicacion,
            "ultimo_grado_estudios": info_extra.get("ultimo_grado_estudios"),
            "preparacion_academica": info_extra.get("preparacion_academica"),
            "grado_academico": info_extra.get("ultimo_grado_estudios") or "Sin Registro",
            "disciplina": info_extra.get("preparacion_academica") or "-",
            "escolaridad_items": [],
            "linea_tiempo": linea_tiempo,
            "mandatos_historicos": mandatos,
            "legislaturas_disponibles": [],
            "asistencia_kpi": None,
            "iniciativas": [],
            "comisiones": comisiones,
            "redes_sociales": [],
            "disciplina_partidaria_pct": 100.0,
            "fuente_url": info_extra.get("fuente_url") or it.get("fuente_url") or "Archivo Histórico Parlamentario Oficial"
        }
        perfil["perfil_academico"] = education_service.clasificar_politico(perfil)
        return perfil

    def get_politico_by_slug(self, slug: str, legislatura: Optional[int] = None) -> Optional[Dict[str, Any]]:
        norm_slug = slugify(slug)

        base_p = None
        if legislatura and legislatura in self.slug_index_by_leg:
            base_p = (
                self.slug_index_by_leg[legislatura].get(norm_slug)
                or self.slug_index_by_leg[legislatura].get(slug)
            )
        if not base_p:
            base_p = (
                self.politicos_master.get(norm_slug)
                or self.politicos_master.get(slug)
            )
        if not base_p:
            base_p = self.find_politico_by_nombre(slug, legislatura=legislatura)

        # Si no se pidió una legislatura histórica específica y el registro federal es histórico (< 66) o nulo,
        # verificar si la persona ejerce actualmente una Gubernatura o Alcaldía activa (2024-2027 / 2024-2030)
        if legislatura is None and (not base_p or int(base_p.get("legislatura") or 0) < 66):
            self.get_explorador_catalogo()
            cat_idx = getattr(self, "_explorador_catalogo_index", {})
            cat_it = cat_idx.get(norm_slug) or cat_idx.get(slug)
            if not cat_it:
                tokens = [t for t in norm_slug.split("-") if t]
                cands_cat = []
                if len(tokens) == 3:
                    cands_cat = [f"{tokens[1]}-{tokens[2]}-{tokens[0]}", f"{tokens[2]}-{tokens[0]}-{tokens[1]}"]
                elif len(tokens) == 4:
                    cands_cat = [f"{tokens[2]}-{tokens[3]}-{tokens[0]}-{tokens[1]}", f"{tokens[1]}-{tokens[2]}-{tokens[3]}-{tokens[0]}"]
                for cc in cands_cat:
                    if cc in cat_idx:
                        cat_it = cat_idx[cc]
                        break
            if cat_it and (not base_p or (cat_it.get("es_actual") and cat_it.get("nivel") in ("Estatal", "Municipal"))):
                exec_p = self._build_perfil_from_catalogo_item(cat_it)
                if base_p:
                    # Conservar el historial parlamentario federal, estudios y pestañas de legislaturas previas
                    fed_slug = base_p.get("slug") or norm_slug
                    exec_p["linea_tiempo"] = list(exec_p.get("linea_tiempo", [])) + list(base_p.get("linea_tiempo", []))
                    if not exec_p.get("escolaridad_items") and base_p.get("escolaridad_items"):
                        exec_p["escolaridad_items"] = list(base_p["escolaridad_items"])
                    if (not exec_p.get("ultimo_grado_estudios")) and base_p.get("ultimo_grado_estudios"):
                        exec_p["ultimo_grado_estudios"] = base_p.get("ultimo_grado_estudios")
                        exec_p["preparacion_academica"] = base_p.get("preparacion_academica")
                        exec_p["perfil_academico"] = base_p.get("perfil_academico") or education_service.clasificar_politico(exec_p)
                    exec_p["referencia_sil"] = base_p.get("referencia_sil")
                    exec_p["mandatos_historicos"] = list(self.mandatos_historicos.get(fed_slug, base_p.get("mandatos_historicos", [])))
                base_p = exec_p

        if not base_p and hasattr(self, "_universal_by_slug"):
            uni_it = (
                self._universal_by_slug.get(norm_slug)
                or self._universal_by_slug.get(slug)
            )
            if uni_it:
                base_p = self._build_perfil_from_universal_item(uni_it)

        if not base_p:
            return None

        # Trabajar sobre copia superficial para no mutar el diccionario compartido ni crear ciclos de referencias
        p = dict(base_p)
        real_slug = p.get("slug") or norm_slug
        p_leg = int(legislatura if (legislatura and legislatura in self.slug_index_by_leg) else (p.get("legislatura") or 66))

        # Aislar estrictamente asistencias e iniciativas: solo LXVI muestra asistencia; histórico ninguna
        if p_leg != 66:
            p["asistencia_kpi"] = None
        p["iniciativas"] = []

        # Mandatos e histórico
        mandatos = list(self.mandatos_historicos.get(real_slug, p.get("mandatos_historicos", [])))
        p["mandatos_historicos"] = mandatos
        p["legislaturas_disponibles"] = [m["legislatura"] for m in mandatos if m.get("legislatura")]

        # Enlazar suplente con resumen ligero (sin referencias circulares)
        suplente_nom = p.get("suplente_nombre")
        if suplente_nom:
            suplente_pol = self.find_politico_by_nombre(suplente_nom, legislatura=p_leg)
            if suplente_pol and suplente_pol.get("slug") != real_slug:
                p["suplente_politico"] = {
                    "slug": suplente_pol.get("slug"),
                    "nombre_completo": suplente_pol.get("nombre_completo"),
                    "partido": suplente_pol.get("partido")
                }
                p["suplente_slug"] = suplente_pol.get("slug")
            else:
                p["suplente_politico"] = None
                p["suplente_slug"] = slugify(suplente_nom)

        # Enlazar titular con resumen ligero (sin referencias circulares)
        titular_nom = (p.get("metadata") or {}).get("titular_nombre") or p.get("titular_nombre")
        if titular_nom:
            titular_pol = self.find_politico_by_nombre(titular_nom, legislatura=p_leg)
            if titular_pol and titular_pol.get("slug") != real_slug:
                p["titular_politico"] = {
                    "slug": titular_pol.get("slug"),
                    "nombre_completo": titular_pol.get("nombre_completo"),
                    "partido": titular_pol.get("partido")
                }
                p["titular_slug"] = titular_pol.get("slug")
            else:
                p["titular_politico"] = None
                p["titular_slug"] = slugify(titular_nom)

        # Resolver foto local vs remota oficial usando índice en memoria
        ref_sil = str(p.get("referencia_sil") or "")
        foto_local = p.get("foto_local_path")
        tiene_foto_local = False
        if ref_sil and self._has_raw_photo(f"{ref_sil}.jpg"):
            foto_url = f"/photos/{ref_sil}.jpg"
            tiene_foto_local = True
        elif self._has_raw_photo(f"{real_slug}.jpg"):
            foto_url = f"/photos/{real_slug}.jpg"
            tiene_foto_local = True
        elif foto_local and (Path(settings.BASE_DIR) / str(foto_local).lstrip("/\\")).exists():
            foto_url = f"/{str(foto_local).lstrip('/')}"
            tiene_foto_local = True
        elif p.get("foto_oficial_url"):
            foto_url = p.get("foto_oficial_url")
        elif p.get("foto_url"):
            foto_url = p.get("foto_url")
        else:
            foto_url = ""

        p["foto_url"] = foto_url
        p["tiene_foto_local"] = tiene_foto_local

        return p

    def get_diputados(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return [p for p in self.get_politicos(legislatura) if p.get("camara") == "Diputados"]

    def get_senadores(self, legislatura: int = 66) -> List[Dict[str, Any]]:
        return [p for p in self.get_politicos(legislatura) if p.get("camara") == "Senadores"]

    def get_votaciones(self, tema: Optional[str] = None, resultado: Optional[str] = None, legislatura: int = 66) -> List[Dict[str, Any]]:
        # Exclusivamente disponible para la LXVI Legislatura (actual); sin releer archivos en cada request
        if legislatura != 66:
            return []
        res = self.votaciones
        if tema:
            res = [v for v in res if (v.get("tema_clave") or "").lower() == tema.lower()]
        if resultado:
            res = [v for v in res if (v.get("resultado") or "").lower() == resultado.lower()]
        return res

    def get_comisiones(self, legislatura: Optional[int] = 66) -> Dict[str, Dict[str, Any]]:
        if legislatura == 0:
            return self.comisiones_master
        target_leg = 66 if legislatura is None else legislatura
        if target_leg in self.comisiones_by_leg:
            return self.comisiones_by_leg[target_leg]
        return self.comisiones_by_leg.get(66, self.comisiones_master)

    def get_comision_by_slug(self, slug: str, legislatura: Optional[int] = None) -> Optional[Dict[str, Any]]:
        target_norm = slugify(slug)
        raw_com = None
        active_leg = legislatura

        # Detectar qué legislaturas tienen registrada esta comisión
        legs_disponibles = []
        for l_num in sorted(self.comisiones_by_leg.keys(), reverse=True):
            if target_norm in self.comisiones_by_leg[l_num]:
                legs_disponibles.append(l_num)

        if legislatura and legislatura > 0 and legislatura in self.comisiones_by_leg:
            raw_com = self.comisiones_by_leg[legislatura].get(target_norm)
            active_leg = legislatura
        elif legislatura == 0:
            raw_com = self.comisiones_master.get(target_norm)
            active_leg = 0
        else:
            # Si no se especificó legislatura, priorizar la actual (66) si existe
            if 66 in self.comisiones_by_leg and target_norm in self.comisiones_by_leg[66]:
                raw_com = self.comisiones_by_leg[66].get(target_norm)
                active_leg = 66
            elif legs_disponibles:
                # Tomar la legislatura más reciente en la que estuvo activa
                active_leg = legs_disponibles[0]
                raw_com = self.comisiones_by_leg[active_leg].get(target_norm)

        if not raw_com:
            raw_com = self.comisiones_master.get(target_norm)
            if not raw_com:
                for k, v in self.comisiones_master.items():
                    if target_norm in k or k in target_norm:
                        raw_com = v
                        break

        if not raw_com:
            return None

        # Build clean enriched representation
        todos_integrantes = list(raw_com.get("integrantes", []))
        if active_leg and active_leg > 0:
            integrantes = [m for m in todos_integrantes if int(m.get("legislatura") or 0) == active_leg]
            if not integrantes and todos_integrantes:
                integrantes = todos_integrantes
        else:
            integrantes = todos_integrantes

        # Deduplicar integrantes por (slug, legislatura, puesto)
        seen = set()
        dedup_integrantes = []
        for m in integrantes:
            k = (m.get("slug"), m.get("legislatura", 0), m.get("puesto"))
            if k not in seen:
                seen.add(k)
                dedup_integrantes.append(m)

        def rank_puesto(puesto_str: str) -> int:
            up = (puesto_str or "").upper()
            if "PRESID" in up:
                return 0
            if "SECRET" in up:
                return 1
            return 2

        dedup_integrantes.sort(key=lambda m: (
            rank_puesto(m.get("puesto", "")),
            -int(m.get("legislatura") or 0),
            m.get("nombre_completo", "")
        ))

        for m in dedup_integrantes:
            if not m.get("foto_url"):
                ref = str(m.get("referencia_sil") or "")
                if ref and (settings.STORAGE_RAW_PHOTOS / f"{ref}.jpg").exists():
                    m["foto_url"] = f"/photos/{ref}.jpg"
                elif m.get("foto_oficial_url"):
                    m["foto_url"] = m["foto_oficial_url"]
                else:
                    nom = m.get("nombre_completo") or "Legislador"
                    m["foto_url"] = "data:image/svg+xml;utf8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27 viewBox=%270 0 150 150%27%3E%3Crect width=%27150%27 height=%27150%27 fill=%27%23F1F5F9%27/%3E%3Ccircle cx=%2775%27 cy=%2758%27 r=%2726%27 fill=%27%2394A3B8%27/%3E%3Cpath d=%27M25 138 C25 103 125 103 125 138%27 fill=%27%2394A3B8%27/%3E%3C/svg%3E"

        presidencia = [m for m in dedup_integrantes if rank_puesto(m.get("puesto", "")) == 0]
        secretarias = [m for m in dedup_integrantes if rank_puesto(m.get("puesto", "")) == 1]
        resto = [m for m in dedup_integrantes if rank_puesto(m.get("puesto", "")) == 2]

        partidos_conteo = {}
        for m in dedup_integrantes:
            part = (m.get("partido") or "Sin Partido").strip()
            if not part:
                part = "Sin Partido"
            partidos_conteo[part] = partidos_conteo.get(part, 0) + 1
        partidos_conteo = dict(sorted(partidos_conteo.items(), key=lambda item: item[1], reverse=True))

        total_int = len(dedup_integrantes)
        partidos_stats = []
        for part, qty in partidos_conteo.items():
            pct = round((qty / total_int) * 100, 1) if total_int > 0 else 0.0
            partidos_stats.append({
                "partido": part,
                "cantidad": qty,
                "porcentaje": pct,
                "color": get_party_color(part)
            })

        return {
            "nombre": raw_com.get("nombre"),
            "slug": raw_com.get("slug"),
            "camara": raw_com.get("camara", "Congreso"),
            "total_integrantes": total_int,
            "integrantes": dedup_integrantes,
            "presidencia": presidencia,
            "secretarias": secretarias,
            "resto_integrantes": resto,
            "partidos_conteo": partidos_conteo,
            "partidos_stats": partidos_stats,
            "legislatura": active_leg if active_leg is not None else 66,
            "meta_legislatura": self.LEGISLATURAS_CATALOG.get(active_leg if active_leg else 66, {"romano": "Histórico", "periodo": "1997–2027"}),
            "legislaturas_disponibles": legs_disponibles,
        }

    def get_by_estado(self, estado_slug: str, legislatura: int = 66) -> List[Dict[str, Any]]:
        target_norm = normalizar_entidad(estado_slug)
        return [
            p for p in self.get_politicos(legislatura)
            if p.get("entidad_slug") == target_norm
        ]

    def get_estado_by_slug(self, slug: str, legislatura: int = 66) -> Optional[Dict[str, Any]]:
        target_norm = normalizar_entidad(slug)
        estados = self.get_estados_data(legislatura)
        estado = estados.get(target_norm)
        if not estado:
            return None

        estado_dict = dict(estado)
        pols = self.get_by_estado(target_norm, legislatura)
        estado_dict["diputados"] = [p for p in pols if p.get("camara") == "Diputados"]
        estado_dict["senadores"] = [p for p in pols if p.get("camara") == "Senadores"]
        estado_dict["todos"] = pols
        estado_dict["escolaridad_stats"] = education_service.generar_metricas(pols)
        return estado_dict

    def get_estados_data(self, legislatura: int = 66) -> Dict[str, Dict[str, Any]]:
        return self._map_service.get_estados_data(self, legislatura)

    def get_mapa_capas(self, legislatura: int = 66) -> Dict[str, Any]:
        return self._map_service.get_mapa_capas(self, legislatura)

    def get_resumen_360_estados(self, legislatura: int = 66) -> Dict[str, Dict[str, Any]]:
        return self._map_service.get_resumen_360_estados(self, legislatura)

    def get_estadisticas_escolaridad(self, legislatura: int = 66) -> Dict[str, Any]:
        pols = self.get_politicos(legislatura)
        return education_service.generar_metricas(pols)

    def buscar(self, query: str, legislatura: Optional[int] = None, limite: int = 20) -> List[Dict[str, Any]]:
        if not query or len(query.strip()) < 2:
            return []
        q_raw = query.strip()
        q = slugify(q_raw)
        palabras = [slugify(w) for w in q_raw.split() if len(w) >= 2]
        if not palabras:
            palabras = [q]

        resultados = []
        slugs_agregados = set()

        # 1. Búsqueda en el Índice Universal (Federales de todas las legislaturas, Locales, Gobernadores, Alcaldes)
        if hasattr(self, "indice_busqueda_universal") and self.indice_busqueda_universal:
            for item in self.indice_busqueda_universal:
                s_text = item.get("search", "")
                slug = item.get("slug")
                if not slug or slug in slugs_agregados:
                    continue

                # Coincidencia: si contiene el slug completo o todas las palabras buscadas
                if q in s_text or all(p in s_text for p in palabras):
                    slugs_agregados.add(slug)
                    cargo_fmt = item.get("cargo") or item.get("camara") or "Servidor Público"
                    resultados.append({
                        "nombre": item.get("nombre"),
                        "slug": slug,
                        "cargo": cargo_fmt,
                        "camara": cargo_fmt,
                        "partido": item.get("partido"),
                        "entidad": item.get("entidad"),
                        "ambito": item.get("ambito", "Público"),
                        "foto_url": item.get("foto_url") or "",
                        "url": item.get("url") or f"/politicos/{slug}"
                    })
                    if len(resultados) >= limite:
                        break
        else:
            for p in self.get_politicos(legislatura or 66):
                nombre_slug = slugify(p.get("nombre_completo", ""))
                partido_slug = slugify(p.get("partido", ""))
                entidad_slug = p.get("entidad_slug", "")
                if q in nombre_slug or q in partido_slug or q in entidad_slug:
                    resultados.append({
                        "nombre": p.get("nombre_completo"),
                        "slug": p.get("slug"),
                        "cargo": p.get("camara"),
                        "camara": p.get("camara"),
                        "partido": p.get("partido"),
                        "entidad": p.get("entidad"),
                        "ambito": "Federal",
                        "url": f"/politicos/{p.get('slug')}"
                    })
                    if len(resultados) >= limite:
                        break

        # 2. Búsqueda en Comisiones del Congreso si aún hay cupo
        if len(resultados) < limite:
            for c_slug, c in self.comisiones_master.items():
                if q in c_slug or q in slugify(c.get("nombre", "")):
                    resultados.append({
                        "nombre": c.get("nombre"),
                        "slug": c_slug,
                        "cargo": "Comisión Parlamentaria",
                        "camara": c.get("camara_tag", "Comisión"),
                        "partido": f"{c.get('total_integrantes', len(c.get('integrantes', [])))} integrantes",
                        "entidad": "Congreso de la Unión",
                        "ambito": "Federal",
                        "url": f"/comisiones/{c_slug}"
                    })
                    if len(resultados) >= limite:
                        break

        return resultados

    def _calcular_transicion_partidista(self, p: Dict[str, Any], slug: str) -> Dict[str, Any]:
        """Calcula cronológicamente los saltos y transiciones partidistas documentados de una figura pública."""
        partidos_cronologicos = []

        # 1. Mandatos históricos parlamentarios
        mandatos = self.mandatos_historicos.get(slug, [])
        for m in sorted(mandatos, key=lambda x: int(x.get("legislatura") or 0)):
            p_part = m.get("partido")
            if p_part:
                c_nom = get_canonical_party_name(p_part)
                if c_nom and c_nom not in ("Sin Partido", "Independiente"):
                    ano_aprox = 1997 + (int(m.get("legislatura") or 57) - 57) * 3
                    partidos_cronologicos.append((ano_aprox, c_nom))

        # 2. Trayectoria y línea de tiempo
        for lt in p.get("linea_tiempo", []):
            p_det = lt.get("partido") or detect_party_from_text(lt.get("experiencia", ""))
            if p_det:
                c_nom = get_canonical_party_name(p_det)
                del_ano = lt.get("del_ano")
                try:
                    ano_num = int(del_ano) if del_ano and str(del_ano).isdigit() else 2010
                except Exception:
                    ano_num = 2010
                partidos_cronologicos.append((ano_num, c_nom))

        # 3. Antecedentes multinivel
        hitos_multi = self.indice_multinivel.get(slug, [])
        for hm in hitos_multi:
            hm_part = hm.get("partido")
            if hm_part:
                c_nom = get_canonical_party_name(hm_part)
                del_ano = hm.get("del_ano")
                try:
                    ano_num = int(del_ano) if del_ano and str(del_ano).isdigit() else 2010
                except Exception:
                    ano_num = 2010
                partidos_cronologicos.append((ano_num, c_nom))

        # 4. Partido del cargo actual
        partido_actual = get_canonical_party_name(p.get("partido") or "Sin Partido")
        if partido_actual not in ("Sin Partido", "Independiente"):
            partidos_cronologicos.append((2024, partido_actual))

        partidos_cronologicos.sort(key=lambda x: x[0])

        dedup_lista = []
        for _, p_nom in partidos_cronologicos:
            if not dedup_lista or dedup_lista[-1] != p_nom:
                dedup_lista.append(p_nom)

        if not dedup_lista and partido_actual:
            dedup_lista = [partido_actual]

        tiene_transicion = len(dedup_lista) > 1
        texto = " ➔ ".join(dedup_lista) if tiene_transicion else (f"{dedup_lista[0]} (Fiel)" if dedup_lista else "Sin registro")
        badges = [{"nombre": p_n, "color": get_party_color(p_n)} for p_n in dedup_lista]

        return {
            "lista": dedup_lista,
            "tiene_transicion": tiene_transicion,
            "texto": texto,
            "badges": badges,
            "total_partidos": len(dedup_lista)
        }

    def get_explorador_catalogo(self) -> List[Dict[str, Any]]:
        return self._explorer_service.get_explorador_catalogo(self)

    def consultar_explorador(
        self,
        q: str = "",
        nivel: str = "todos",
        cargo: str = "todos",
        entidad: str = "todos",
        partido: str = "todos",
        transicion: str = "todos",
        principio: str = "todos",
        licencia: str = "todos",
        reeleccion: str = "todos",
        periodo: str = "todos",
        educacion: str = "todos",
        page: int = 1,
        limit: int = 50,
        **kwargs
    ) -> Dict[str, Any]:
        return self._explorer_service.consultar_explorador(
            self,
            q=q,
            nivel=nivel,
            cargo=cargo,
            entidad=entidad,
            partido=partido,
            transicion=transicion,
            principio=principio,
            licencia=licencia,
            reeleccion=reeleccion,
            periodo=periodo,
            educacion=educacion,
            page=page,
            limit=limit,
            **kwargs
        )


data_service = DataService()
