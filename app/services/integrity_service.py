import hashlib
import re
import unicodedata
from decimal import Decimal
from pathlib import Path
from typing import Dict, Any, List
import orjson
from app.core.config import settings
from app.services.data_service import data_service
from app.services.remuneraciones_service import remuneraciones_service, to_decimal


def _norm_ascii(text: str) -> str:
    if not text:
        return ""
    return unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode("utf-8").lower()


class IntegrityService:
    """
    Servicio Forense de Integridad Parlamentaria, Relacional y Contable.
    Audita determinísticamente votaciones de la LXVI Legislatura, asistencias,
    colisiones Propietario/Suplente, contratos de adquisiciones y hashes SHA-256.
    """

    def auditar_votaciones_lxvi(self) -> Dict[str, Any]:
        votaciones = data_service.get_votaciones(legislatura=66)
        anomalias: List[Dict[str, Any]] = []
        mapeo_bancada = {
            "favor": "a_favor",
            "contra": "en_contra",
            "abstencion": "abstencion",
            "asistencia_sin_voto": "solo_asistencia",
            "ausente": "ausente",
            "total": "total"
        }

        for v in votaciones:
            vid = v.get("id_votacion_origen")
            camara = v.get("camara", "Diputados")
            tope_constitucional = 500 if camara == "Diputados" else 128
            t = v.get("totales") or {}

            favor = int(t.get("favor", 0) or 0)
            contra = int(t.get("contra", 0) or 0)
            abstencion = int(t.get("abstencion", 0) or 0)
            sin_voto = int(t.get("asistencia_sin_voto", 0) or 0)
            ausente = int(t.get("ausente", 0) or 0)
            total_rep = int(t.get("total", 0) or 0)

            suma_partes = favor + contra + abstencion + sin_voto + ausente
            if suma_partes != total_rep:
                anomalias.append({
                    "id_votacion": vid,
                    "tipo": "descuadre_totales",
                    "detalle": f"Suma de componentes ({suma_partes}) != total reportado ({total_rep})"
                })

            if total_rep > tope_constitucional:
                anomalias.append({
                    "id_votacion": vid,
                    "tipo": "exceso_curules",
                    "detalle": f"Total ({total_rep}) excede tope constitucional de {camara} ({tope_constitucional})"
                })

            # Reconciliar suma de bancadas vs totales
            bancadas = v.get("bancadas") or {}
            for k_tot, k_banc in mapeo_bancada.items():
                suma_b = sum(int((b or {}).get(k_banc, 0) or 0) for b in bancadas.values())
                if suma_b != int(t.get(k_tot, 0) or 0):
                    anomalias.append({
                        "id_votacion": vid,
                        "tipo": f"descuadre_bancadas_{k_tot}",
                        "detalle": f"Suma bancadas ({suma_b}) != total.{k_tot} ({t.get(k_tot)})"
                    })

            # Quórum y mayorías (Simple >50% vs Calificada >= 2/3 en reformas constitucionales)
            presentes = favor + contra + abstencion + sin_voto
            quorum_min = (tope_constitucional // 2) + 1
            hay_quorum = presentes >= quorum_min
            titulo_upper = (v.get("titulo") or "").upper()
            es_constitucional = ("CONSTITUCI" in titulo_upper and "DECRETO" in titulo_upper)

            if es_constitucional:
                aprobado_calc = hay_quorum and (favor * 3 >= presentes * 2)
            else:
                aprobado_calc = hay_quorum and (favor > contra)

            res_esperado = "Aprobado" if aprobado_calc else "Desechado"
            if (v.get("resultado") or "") != res_esperado:
                anomalias.append({
                    "id_votacion": vid,
                    "tipo": "mayoria_inconsistente",
                    "detalle": f"Reportado '{v.get('resultado')}' vs calculado '{res_esperado}' (presentes={presentes}, favor={favor}, constitucional={es_constitucional})"
                })

        return {
            "legislatura_auditada": 66,
            "total_votaciones": len(votaciones),
            "anomalias_detectadas": len(anomalias),
            "anomalias": anomalias
        }

    def auditar_asistencias_y_suplencias_lxvi(self) -> Dict[str, Any]:
        pols_66 = data_service.get_politicos(66)
        anomalias_asist: List[Dict[str, Any]] = []
        colisiones_suplencia: List[Dict[str, Any]] = []

        propietarios_slugs = {
            p.get("slug"): p for p in pols_66
            if (p.get("tipo_cargo") or "Propietario") != "Suplente"
        }

        for p in pols_66:
            slug = p.get("slug", "")
            kpi = p.get("asistencia_kpi")
            if kpi:
                asist = int(kpi.get("asistencias", 0) or 0)
                faltas = int(kpi.get("faltas", 0) or 0)
                justif = int(kpi.get("justificadas", 0) or 0)
                ses = int(kpi.get("total_sesiones", 0) or 0)
                if ses > 0 and (asist + faltas + justif) > ses:
                    anomalias_asist.append({
                        "slug": slug,
                        "detalle": f"asistencias ({asist}) + faltas ({faltas}) + justificadas ({justif}) > total_sesiones ({ses})"
                    })

            sup_slug = p.get("suplente_slug")
            if sup_slug and sup_slug in propietarios_slugs and sup_slug != slug:
                colisiones_suplencia.append({
                    "titular_slug": slug,
                    "suplente_slug": sup_slug,
                    "camara": p.get("camara"),
                    "nota": "El suplente registrado aparece simultáneamente como propietario en la LXVI Legislatura"
                })

        # Verificar que ninguna legislatura histórica (57-65) exponga asistencias
        fugas_historicas = 0
        for leg_num in range(57, 66):
            for p in data_service.get_politicos(leg_num):
                if p.get("asistencia_kpi") is not None or p.get("iniciativas"):
                    fugas_historicas += 1

        return {
            "legislatura_activa": 66,
            "total_legisladores_lxvi": len(pols_66),
            "anomalias_aritmetica_asistencia": len(anomalias_asist),
            "fugas_en_legislaturas_historicas": fugas_historicas,
            "suplentes_con_doble_registro_propietario": colisiones_suplencia
        }

    def auditar_adquisiciones_y_hashes(self, max_hash_checks: int = 25) -> Dict[str, Any]:
        contratos_file = settings.BASE_DIR / "storage" / "data" / "adquisiciones" / "ensenada_contratos_auditoria.json"
        if not contratos_file.exists():
            return {"disponible": False}

        with open(contratos_file, "rb") as f:
            data = orjson.loads(f.read())

        meta = data.get("metadatos_auditoria") or {}
        resumen = data.get("resumen_estadistico") or {}
        contratos = data.get("contratos") or []

        cvegeo = str(meta.get("cvegeo") or "")
        cvegeo_valido = bool(re.fullmatch(r"\d{5}", cvegeo)) and (1 <= int(cvegeo[:2]) <= 32)

        # Reconciliar conteos reales vs resumen_estadistico
        adj_reales = 0
        lic_reales = 0
        inv_reales = 0
        adj_xxv = 0
        lic_xxv = 0
        for c in contratos:
            tp = _norm_ascii(c.get("tipo_procedimiento") or "")
            es_xxv = "xxv" in _norm_ascii(c.get("administracion") or "")
            if "adjudicacion directa" in tp:
                adj_reales += 1
                if es_xxv:
                    adj_xxv += 1
            elif "licitacion publica" in tp:
                lic_reales += 1
                if es_xxv:
                    lic_xxv += 1
            elif "invitacion" in tp:
                inv_reales += 1

        descuadres_resumen = []
        if resumen.get("adjudicaciones_directas") != adj_reales:
            descuadres_resumen.append(f"adjudicaciones_directas: resumen={resumen.get('adjudicaciones_directas')} vs real={adj_reales}")
        if resumen.get("licitaciones_publicas") != lic_reales:
            descuadres_resumen.append(f"licitaciones_publicas: resumen={resumen.get('licitaciones_publicas')} vs real={lic_reales}")
        if resumen.get("adjudicaciones_xxv_periodo_actual") != adj_xxv:
            descuadres_resumen.append(f"adjudicaciones_xxv_periodo_actual: resumen={resumen.get('adjudicaciones_xxv_periodo_actual')} vs real={adj_xxv}")
        if resumen.get("licitaciones_xxv_periodo_actual") != lic_xxv:
            descuadres_resumen.append(f"licitaciones_xxv_periodo_actual: resumen={resumen.get('licitaciones_xxv_periodo_actual')} vs real={lic_xxv}")

        # Validar formato SHA-256 en el 100% de documentos y muestrear archivos físicos
        docs = [d for c in contratos for d in (c.get("documentos_oficiales") or [])]
        hashes_formato_invalido = 0
        verificados_disco = 0
        hashes_mismatch = []

        for d in docs:
            h = str(d.get("sha256") or "").lower()
            if not re.fullmatch(r"[0-9a-f]{64}", h):
                hashes_formato_invalido += 1
                continue
            if verificados_disco < max_hash_checks:
                rel_path = str(d.get("ruta_local") or "").replace("\\", "/")
                full_path = settings.BASE_DIR / rel_path
                if full_path.exists():
                    digest = hashlib.sha256(full_path.read_bytes()).hexdigest()
                    verificados_disco += 1
                    if digest != h:
                        hashes_mismatch.append(d.get("archivo_nombre"))

        return {
            "disponible": True,
            "cvegeo": cvegeo,
            "cvegeo_formato_inegi_valido": cvegeo_valido,
            "total_contratos": len(contratos),
            "conteos_reales": {
                "adjudicaciones_directas": adj_reales,
                "invitaciones_cuando_menos_tres": inv_reales,
                "licitaciones_publicas": lic_reales,
                "adjudicaciones_xxv_periodo_actual": adj_xxv,
                "licitaciones_xxv_periodo_actual": lic_xxv,
            },
            "descuadres_resumen": descuadres_resumen,
            "total_documentos_oficiales": len(docs),
            "hashes_formato_invalido": hashes_formato_invalido,
            "hashes_verificados_en_disco": verificados_disco,
            "hashes_mismatch": hashes_mismatch,
        }

    def ejecutar_auditoria_integral(self) -> Dict[str, Any]:
        vot = self.auditar_votaciones_lxvi()
        asist = self.auditar_asistencias_y_suplencias_lxvi()
        adq = self.auditar_adquisiciones_y_hashes()
        costos = {k: str(v) for k, v in remuneraciones_service.get_costos_unitarios_oficiales().items()}

        ok = (
            vot["anomalias_detectadas"] == 0
            and asist["anomalias_aritmetica_asistencia"] == 0
            and asist["fugas_en_legislaturas_historicas"] == 0
            and len(adq.get("descuadres_resumen", [])) == 0
            and adq.get("hashes_formato_invalido", 0) == 0
            and len(adq.get("hashes_mismatch", [])) == 0
        )
        return {
            "ok": ok,
            "politica_activa": "Votaciones y asistencias restringidas exclusivamente a la LXVI Legislatura (actual); LVII–LXV operan como archivo histórico.",
            "votaciones_lxvi": vot,
            "asistencias_y_suplencias_lxvi": asist,
            "adquisiciones_y_sha256": adq,
            "costos_unitarios_decimal_mxn": costos,
        }


integrity_service = IntegrityService()
