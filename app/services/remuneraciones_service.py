import orjson
from decimal import Decimal, ROUND_HALF_EVEN
from pathlib import Path
from typing import Dict, Any, List, Optional, Union
from app.core.config import settings

TWOPLACES = Decimal("0.01")


def to_decimal(val: Union[str, int, float, Decimal, None], default: str = "0.00") -> Decimal:
    if val is None:
        return Decimal(default)
    if isinstance(val, Decimal):
        return val
    return Decimal(str(val))


class RemuneracionesService:
    def __init__(self):
        self.data_file: Path = settings.STORAGE_DATA_DIR / "tabuladores_cargos_oficiales.json"
        self._data: Optional[Dict[str, Any]] = None
        self._mtime: float = 0.0
        self._load_data()

    def _load_data(self):
        if self.data_file.exists():
            try:
                mtime = self.data_file.stat().st_mtime
                with open(self.data_file, "rb") as f:
                    self._data = orjson.loads(f.read())
                self._mtime = mtime
            except Exception as e:
                print(f"[RemuneracionesService] Error al cargar tabulador: {e}")
                self._data = {}
        else:
            self._data = {}

    @property
    def data(self) -> Dict[str, Any]:
        if self._data is None or not self._data:
            self._load_data()
        elif self.data_file.exists():
            try:
                if self.data_file.stat().st_mtime != self._mtime:
                    self._load_data()
            except Exception:
                pass
        return self._data or {}

    def get_totales_nacionales(self) -> Dict[str, Any]:
        return self.data.get("totales_nacionales", {})

    def get_metadata(self) -> Dict[str, Any]:
        return self.data.get("metadata", {})

    def get_cargos_federales(self) -> Dict[str, Any]:
        return self.data.get("cargos_federales", {})

    def get_gobernadores(self) -> List[Dict[str, Any]]:
        return self.data.get("gobernadores", [])

    def get_congresos_locales(self) -> List[Dict[str, Any]]:
        return self.data.get("congresos_locales", [])

    def get_alcaldias_categorias(self) -> Dict[str, Any]:
        return self.data.get("alcaldias_categorias", {})

    def get_bancadas_distribucion(self) -> Dict[str, Any]:
        return self.data.get("bancadas_distribucion", {})

    def get_costos_unitarios_oficiales(self) -> Dict[str, Decimal]:
        """
        Deriva los costos unitarios anuales exactamente del tabulador oficial cargado
        (bajo criterio de piso conservador auditable), utilizando aritmética Decimal
        para evitar pérdida de centavos y valores inventados.
        """
        fed = self.get_cargos_federales()
        desglose = self.get_totales_nacionales().get("desglose_por_orden", {})

        dip_fed = to_decimal(fed.get("diputado_federal", {}).get("costo_anual_puesto"), "909158.16")
        sen_rep = to_decimal(fed.get("senador_republica", {}).get("costo_anual_puesto"), "1521600.00")

        ejec = desglose.get("poderes_ejecutivos_estatales", {})
        gob_puestos = int(ejec.get("puestos") or 32)
        gob_unit = (to_decimal(ejec.get("costo_anual"), "26880000.00") / Decimal(gob_puestos)).quantize(TWOPLACES, rounding=ROUND_HALF_EVEN)

        cong = desglose.get("congresos_estatales_locales", {})
        cong_puestos = int(cong.get("puestos") or 1123)
        dip_loc_unit = (to_decimal(cong.get("costo_anual"), "606420000.00") / Decimal(cong_puestos)).quantize(TWOPLACES, rounding=ROUND_HALF_EVEN)

        muns = desglose.get("presidencias_municipales_alcaldias", {})
        mun_puestos = int(muns.get("puestos") or 2491)
        alc_unit = (to_decimal(muns.get("costo_anual"), "747300000.00") / Decimal(mun_puestos)).quantize(TWOPLACES, rounding=ROUND_HALF_EVEN)

        return {
            "diputado_federal": dip_fed.quantize(TWOPLACES, rounding=ROUND_HALF_EVEN),
            "senador_republica": sen_rep.quantize(TWOPLACES, rounding=ROUND_HALF_EVEN),
            "gobernador_promedio": gob_unit,
            "diputado_local_promedio": dip_loc_unit,
            "alcalde_promedio": alc_unit,
        }

    def get_estados_ranking(self, sort_by: str = "costo_total_anual") -> List[Dict[str, Any]]:
        ranking = list(self.data.get("estados_ranking", []))
        if sort_by == "costo_per_capita_anual":
            ranking.sort(key=lambda x: x.get("costo_per_capita_anual", 0), reverse=True)
        elif sort_by == "poblacion":
            ranking.sort(key=lambda x: x.get("poblacion", 0), reverse=True)
        elif sort_by == "nombre":
            ranking.sort(key=lambda x: x.get("nombre", ""))
        else:
            ranking.sort(key=lambda x: x.get("costo_total_anual", 0), reverse=True)
        return ranking

    def get_estado_detalle(self, slug: str) -> Optional[Dict[str, Any]]:
        for est in self.data.get("estados_ranking", []):
            if est.get("slug") == slug:
                gob = next((g for g in self.get_gobernadores() if g.get("slug") == slug), None)
                cong = next((c for c in self.get_congresos_locales() if c.get("slug") == slug), None)
                res = dict(est)
                res["detalle_gobernador"] = gob
                res["detalle_congreso"] = cong
                return res
        return None

    def calcular_equivalencias_ciudadanas(self, monto_anual: Union[Decimal, float]) -> Dict[str, Any]:
        """Calcula a qué equivale un monto institucional en bienes/servicios públicos con precisión Decimal."""
        monto_dec = to_decimal(monto_anual)
        salario_minimo_anual = Decimal("89615.00")
        beca_escolar_anual = Decimal("9200.00")
        patrulla_costo = Decimal("950000.00")
        aula_costo = Decimal("650000.00")
        canasta_costo = Decimal("1800.00")

        salarios = (monto_dec / salario_minimo_anual).quantize(Decimal("0.1"), rounding=ROUND_HALF_EVEN)
        return {
            "salarios_minimos_anuales": float(salarios),
            "becas_escolares_anuales": int(monto_dec // beca_escolar_anual),
            "patrullas_policiacas": int(monto_dec // patrulla_costo),
            "aulas_escolares": int(monto_dec // aula_costo),
            "canastas_basicas": int(monto_dec // canasta_costo),
        }

remuneraciones_service = RemuneracionesService()
