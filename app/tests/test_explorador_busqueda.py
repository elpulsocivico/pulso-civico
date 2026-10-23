import csv
import io
import unicodedata
import unittest
from unittest.mock import patch

from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.routers import api


def registro(nombre, entidad="San Luis Potosí", nivel="federal"):
    return {
        "nombre": nombre, "nombre_completo": nombre, "cargo": "Diputado",
        "entidad": entidad, "municipio": "", "partido_actual": "Prueba",
        "transicion_texto": "Sin cambio", "principio_eleccion": "Mayoría",
        "nivel_tipo": nivel, "cargo_tipo": "diputado_federal",
        "entidad_slug": "san-luis-potosi", "partido_slug": "prueba",
        "tiene_transicion": False, "principio_tipo": "mayoria",
        "licencia_tipo": "en_funciones", "es_reelecto": False,
        "es_actual": True, "grado_academico": "Licenciatura",
    }


class ExploradorBusquedaTests(unittest.TestCase):
    def setUp(self):
        self.catalogo = [registro("José Pérez"), registro("Ana García", nivel="estatal")]
        self.catalog_patch = patch.object(
            api.data_service, "get_explorador_catalogo", return_value=self.catalogo
        )
        self.catalog_patch.start()
        self.addCleanup(self.catalog_patch.stop)
        app = FastAPI()
        app.include_router(api.router, prefix="/api")
        self.client = self.enterContext(TestClient(app))

    def consultar(self, **params):
        response = self.client.get("/api/explorador", params=params)
        self.assertEqual(response.status_code, 200)
        return response.json()

    def test_nombre_con_y_sin_acentos(self):
        for q in ("José", "Jose", "JOSÉ", "  jose  ", unicodedata.normalize("NFD", "José")):
            with self.subTest(q=q):
                self.assertEqual([x["nombre"] for x in self.consultar(q=q)["items"]], ["José Pérez"])

    def test_consulta_acentuada_contra_nombre_sin_acento(self):
        self.catalogo[:] = [registro("Jose Perez")]
        self.assertEqual(self.consultar(q="José Pérez")["total_filtrados"], 1)

    def test_busqueda_por_entidad_y_principio(self):
        for q in ("Potosi", "POTOSÍ", "Mayoria", "Mayoría"):
            with self.subTest(q=q):
                self.assertEqual(self.consultar(q=q)["total_filtrados"], 2)

    def test_preserva_filtros_combinados(self):
        result = self.consultar(q="Potosi", nivel="federal", cargo="diputado_federal",
                                entidad="san-luis-potosi", partido="prueba",
                                principio="mayoria", licencia="en_funciones",
                                transicion="sin_cambio", reeleccion="primer_periodo",
                                periodo="actuales", educacion="licenciatura")
        self.assertEqual([x["nombre"] for x in result["items"]], ["José Pérez"])
        self.assertEqual(result["kpis"]["total"], 1)

    def test_paginacion_y_metricas(self):
        first = self.consultar(q="Potosi", limit=1)
        second = self.consultar(q="Potosi", limit=1, page=2)
        self.assertEqual(first["total_filtrados"], 2)
        self.assertTrue(first["has_more"])
        self.assertFalse(second["has_more"])
        self.assertEqual(first["items"][0]["nombre"], "José Pérez")
        self.assertEqual(second["items"][0]["nombre"], "Ana García")

    def test_vacio_y_sin_coincidencias(self):
        self.assertEqual(self.consultar(q="   ")["total_filtrados"], 2)
        self.assertEqual(self.consultar(q="zzzinexistente")["items"], [])
        self.assertEqual(self.consultar(q="!!!")["items"], [])

    def test_exportacion_comparte_busqueda_y_conserva_acentos(self):
        response = self.client.get("/api/explorador/exportar.csv",
                                   params={"q": "Jose", "columnas": "nombre"})
        self.assertEqual(response.status_code, 200)
        rows = list(csv.reader(io.StringIO(response.content.decode("utf-8-sig"))))
        self.assertEqual(rows, [["Político / Funcionario"], ["José Pérez"]])


if __name__ == "__main__":
    unittest.main()
