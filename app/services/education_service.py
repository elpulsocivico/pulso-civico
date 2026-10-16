import re
import unicodedata
from typing import List, Dict, Any, Optional

class EducationService:
    NIVEL_DOCTORADO = "doctorado"
    NIVEL_MAESTRIA = "maestria"
    NIVEL_LICENCIATURA = "licenciatura"
    NIVEL_TRUNCA = "trunca"
    NIVEL_PREPARATORIA = "preparatoria"
    NIVEL_BASICA = "basica"
    NIVEL_SIN_REGISTRO = "sin_registro"

    NIVELES_CONFIG = {
        NIVEL_DOCTORADO: {
            "codigo": NIVEL_DOCTORADO,
            "label": "Doctorado",
            "icono": "mortarboard-fill",
            "badge_class": "bg-purple-subtle text-purple border-purple-subtle",
            "color": "#6f42c1",
            "peso": 6,
        },
        NIVEL_MAESTRIA: {
            "codigo": NIVEL_MAESTRIA,
            "label": "Maestría / Especialidad",
            "icono": "award-fill",
            "badge_class": "bg-primary-subtle text-primary border-primary-subtle",
            "color": "#0d6efd",
            "peso": 5,
        },
        NIVEL_LICENCIATURA: {
            "codigo": NIVEL_LICENCIATURA,
            "label": "Licenciatura / Ingeniería",
            "icono": "book-half",
            "badge_class": "bg-success-subtle text-success border-success-subtle",
            "color": "#198754",
            "peso": 4,
        },
        NIVEL_TRUNCA: {
            "codigo": NIVEL_TRUNCA,
            "label": "Carrera Trunca / Pasante",
            "icono": "exclamation-triangle-fill",
            "badge_class": "bg-warning-subtle text-dark border-warning-subtle",
            "color": "#ffc107",
            "peso": 3,
        },
        NIVEL_PREPARATORIA: {
            "codigo": NIVEL_PREPARATORIA,
            "label": "Bachillerato / Preparatoria",
            "icono": "building",
            "badge_class": "bg-info-subtle text-info-emphasis border-info-subtle",
            "color": "#0dcaf0",
            "peso": 2,
        },
        NIVEL_BASICA: {
            "codigo": NIVEL_BASICA,
            "label": "Educación Básica",
            "icono": "journal-bookmark",
            "badge_class": "bg-secondary-subtle text-secondary border-secondary-subtle",
            "color": "#6c757d",
            "peso": 1,
        },
        NIVEL_SIN_REGISTRO: {
            "codigo": NIVEL_SIN_REGISTRO,
            "label": "No reportado / Sin registro",
            "icono": "question-circle",
            "badge_class": "bg-light text-muted border",
            "color": "#adb5bd",
            "peso": 0,
        },
    }

    TIPO_INSTITUCION_PUBLICA = "publica"
    TIPO_INSTITUCION_PRIVADA = "privada"
    TIPO_INSTITUCION_MIXTA = "mixta"
    TIPO_INSTITUCION_NO_DETERMINADA = "no_determinada"

    INSTITUCIONES_CONFIG = {
        TIPO_INSTITUCION_PUBLICA: {
            "codigo": TIPO_INSTITUCION_PUBLICA,
            "label": "Universidad Pública",
            "icono": "bank",
            "badge_class": "bg-primary-subtle text-primary border-primary-subtle",
            "color": "#0d6efd",
        },
        TIPO_INSTITUCION_PRIVADA: {
            "codigo": TIPO_INSTITUCION_PRIVADA,
            "label": "Universidad Privada",
            "icono": "mortarboard",
            "badge_class": "bg-info-subtle text-info-emphasis border-info-subtle",
            "color": "#0dcaf0",
        },
        TIPO_INSTITUCION_MIXTA: {
            "codigo": TIPO_INSTITUCION_MIXTA,
            "label": "Formación Mixta (Pública y Privada)",
            "icono": "shuffle",
            "badge_class": "bg-purple-subtle text-purple border-purple-subtle",
            "color": "#6f42c1",
        },
        TIPO_INSTITUCION_NO_DETERMINADA: {
            "codigo": TIPO_INSTITUCION_NO_DETERMINADA,
            "label": "No Especificada en Ficha",
            "icono": "question-circle",
            "badge_class": "bg-light text-muted border",
            "color": "#adb5bd",
        },
    }

    @staticmethod
    def _normalizar(texto: str) -> str:
        if not texto:
            return ""
        norm = unicodedata.normalize("NFKD", texto).encode("ascii", "ignore").decode("utf-8")
        return norm.lower().strip()

    def clasificar_politico(self, politico: Dict[str, Any]) -> Dict[str, Any]:
        """Clasifica el historial educativo completo de un legislador y retorna su grado máximo y detalle."""
        items: List[str] = []

        # Recolectar todas las fuentes posibles de formación en la ficha
        if politico.get("escolaridad_items") and isinstance(politico["escolaridad_items"], list):
            for item in politico["escolaridad_items"]:
                if isinstance(item, dict):
                    exp = (item.get("experiencia") or item.get("estudio") or item.get("grado") or "").strip()
                    if exp:
                        items.append(exp)
                elif isinstance(item, str) and item.strip():
                    items.append(item.strip())

        if politico.get("escolaridad_detalle") and isinstance(politico["escolaridad_detalle"], list):
            for item in politico["escolaridad_detalle"]:
                if isinstance(item, dict):
                    exp = (item.get("experiencia") or item.get("estudio") or item.get("grado") or "").strip()
                    if exp:
                        items.append(exp)
                elif isinstance(item, str) and item.strip():
                    items.append(item.strip())

        def es_valido(txt: str) -> bool:
            if not txt:
                return False
            t = txt.strip().lower()
            return t not in ("no disponible", "sin registro", "n/a", "ninguno", "no reportado", "sin datos")

        ultimo_grado = (politico.get("ultimo_grado_estudios") or "").strip()
        if es_valido(ultimo_grado):
            items.append(ultimo_grado)

        prep_acad = (politico.get("preparacion_academica") or "").strip()
        if es_valido(prep_acad):
            items.append(prep_acad)

        for extra_k in ["escolaridad", "grado_academico", "trayectoria_academica"]:
            val = politico.get(extra_k)
            if isinstance(val, str) and es_valido(val):
                items.append(val.strip())
            elif isinstance(val, list):
                for item in val:
                    if isinstance(item, dict):
                        exp = (item.get("experiencia") or item.get("estudio") or item.get("grado") or "").strip()
                        if es_valido(exp):
                            items.append(exp)
                    elif isinstance(item, str) and es_valido(item):
                        items.append(item.strip())

        # Deduplicar preservando orden
        seen = set()
        items_dedup = []
        for it in items:
            it_clean = it.strip()
            if it_clean and it_clean not in seen:
                seen.add(it_clean)
                items_dedup.append(it_clean)

        if not items_dedup:
            return {
                "grado_maximo": self.NIVEL_SIN_REGISTRO,
                "nivel_codigo": self.NIVEL_SIN_REGISTRO,
                "grado_info": self.NIVELES_CONFIG[self.NIVEL_SIN_REGISTRO],
                "titulo_destacado": "Sin registro académico reportado",
                "disciplina_principal": "No especificada",
                "tiene_trunca": False,
                "total_estudios": 0,
                "estudios_clasificados": [],
                "institucion_resumen": {
                    "tipo_predominante": self.TIPO_INSTITUCION_NO_DETERMINADA,
                    "info": self.INSTITUCIONES_CONFIG[self.TIPO_INSTITUCION_NO_DETERMINADA],
                    "tiene_publica": False,
                    "tiene_privada": False,
                    "instituciones": [],
                    "alma_mater_destacada": "No especificada en ficha",
                },
            }

        estudios_clasificados = []
        grado_maximo_key = self.NIVEL_SIN_REGISTRO
        max_peso = -1
        titulo_destacado = items_dedup[0]
        tiene_trunca = False
        disciplinas = []
        tiene_pub = False
        tiene_priv = False
        nombres_instituciones = []

        for estudio_str in items_dedup:
            clasificacion = self.analizar_texto_estudio(estudio_str)
            estudios_clasificados.append(clasificacion)

            if clasificacion["es_trunca"]:
                tiene_trunca = True

            if clasificacion["disciplina"] != "Otra / General":
                disciplinas.append(clasificacion["disciplina"])

            tipo_inst = clasificacion.get("institucion_tipo", self.TIPO_INSTITUCION_NO_DETERMINADA)
            if tipo_inst == self.TIPO_INSTITUCION_PUBLICA:
                tiene_pub = True
            elif tipo_inst == self.TIPO_INSTITUCION_PRIVADA:
                tiene_priv = True

            inst_nom = clasificacion.get("institucion_nombre", "")
            if inst_nom and inst_nom != "No especificada en ficha":
                nombres_instituciones.append(inst_nom)

            nivel_actual = clasificacion["nivel"]
            peso = self.NIVELES_CONFIG.get(nivel_actual, {}).get("peso", 0)

            if peso > max_peso:
                max_peso = peso
                grado_maximo_key = nivel_actual
                titulo_destacado = estudio_str

        disciplina_principal = disciplinas[0] if disciplinas else "General / Diversa"
        nombres_inst_dedup = list(dict.fromkeys(nombres_instituciones))

        if tiene_pub and tiene_priv:
            tipo_predominante = self.TIPO_INSTITUCION_MIXTA
        elif tiene_pub:
            tipo_predominante = self.TIPO_INSTITUCION_PUBLICA
        elif tiene_priv:
            tipo_predominante = self.TIPO_INSTITUCION_PRIVADA
        else:
            tipo_predominante = self.TIPO_INSTITUCION_NO_DETERMINADA

        return {
            "grado_maximo": grado_maximo_key,
            "nivel_codigo": grado_maximo_key,
            "grado_info": self.NIVELES_CONFIG.get(grado_maximo_key, self.NIVELES_CONFIG[self.NIVEL_SIN_REGISTRO]),
            "titulo_destacado": titulo_destacado,
            "disciplina_principal": disciplina_principal,
            "tiene_trunca": tiene_trunca,
            "total_estudios": len(items_dedup),
            "estudios_clasificados": estudios_clasificados,
            "institucion_resumen": {
                "tipo_predominante": tipo_predominante,
                "info": self.INSTITUCIONES_CONFIG.get(tipo_predominante, self.INSTITUCIONES_CONFIG[self.TIPO_INSTITUCION_NO_DETERMINADA]),
                "tiene_publica": tiene_pub,
                "tiene_privada": tiene_priv,
                "instituciones": nombres_inst_dedup,
                "alma_mater_destacada": nombres_inst_dedup[0] if nombres_inst_dedup else "No especificada en ficha",
            },
        }

    def determinar_categoria_agrupada(self, grado_maximo: str) -> str:
        if grado_maximo in (self.NIVEL_DOCTORADO, self.NIVEL_MAESTRIA):
            return "posgrados"
        if grado_maximo == self.NIVEL_LICENCIATURA:
            return "licenciatura"
        return "media-trunca"

    def analizar_texto_estudio(self, texto: str) -> Dict[str, Any]:
        norm = self._normalizar(texto)

        # 1. Detección de trunca / pasantía
        es_trunca = any(w in norm for w in [
            "trunca", "trunco", "pasante", "inconcluso",
            "creditos concluidos", "estudios no concluidos"
        ])

        # 2. Nivel jerárquico
        if "doctorado" in norm or "phd" in norm or "posdoctorado" in norm:
            nivel = self.NIVEL_TRUNCA if es_trunca else self.NIVEL_DOCTORADO
        elif any(w in norm for w in ["maestria", "master", "especialidad", "posgrado"]):
            nivel = self.NIVEL_TRUNCA if es_trunca else self.NIVEL_MAESTRIA
        elif any(w in norm for w in [
            "licenciatura", "ingenieria", "medico cirujano",
            "abogado", "abogada", "contador", "arquitecto",
            "profesor", "carrera tecnica superior"
        ]):
            nivel = self.NIVEL_TRUNCA if es_trunca else self.NIVEL_LICENCIATURA
        elif es_trunca:
            nivel = self.NIVEL_TRUNCA
        elif any(w in norm for w in [
            "bachillerato", "preparatoria", "vocacional",
            "medio superior", "tecnico", "diplomado"
        ]):
            nivel = self.NIVEL_PREPARATORIA
        elif any(w in norm for w in ["secundaria", "primaria", "basica"]):
            nivel = self.NIVEL_BASICA
        else:
            if "universidad" in norm or "facultad" in norm or "instituto tecnologico" in norm:
                nivel = self.NIVEL_TRUNCA if es_trunca else self.NIVEL_LICENCIATURA
            else:
                nivel = self.NIVEL_PREPARATORIA

        # 3. Disciplina / Campo de estudio
        disciplina = self.detectar_disciplina(norm)

        # 4. Alma máter / Institución educativa
        institucion = self.clasificar_institucion(texto)

        return {
            "texto_original": texto,
            "nivel": nivel,
            "nivel_info": self.NIVELES_CONFIG.get(nivel, self.NIVELES_CONFIG[self.NIVEL_SIN_REGISTRO]),
            "es_trunca": es_trunca,
            "disciplina": disciplina,
            "institucion_tipo": institucion["tipo"],
            "institucion_nombre": institucion["nombre"],
            "institucion_extranjera": institucion["es_extranjera"],
            "institucion_info": self.INSTITUCIONES_CONFIG.get(institucion["tipo"], self.INSTITUCIONES_CONFIG[self.TIPO_INSTITUCION_NO_DETERMINADA]),
        }

    def clasificar_institucion(self, texto: str) -> Dict[str, Any]:
        norm = self._normalizar(texto)
        norm = norm.replace("universdiad", "universidad")

        # Descartar textos sin mención de institución
        keywords = [
            "por ", "en ", "unam", "ipn", "itam", "itesm", "ibero", "anahuac",
            "universidad", "instituto", "facultad", "escuela", "colegio",
            "centro de estudios", "centro universitario"
        ]
        if not any(k in norm for k in keywords):
            return {
                "tipo": self.TIPO_INSTITUCION_NO_DETERMINADA,
                "nombre": "No especificada en ficha",
                "es_extranjera": False,
            }

        # 1. Privadas
        if any(w in norm for w in ["itesm", "tec de monterrey", "tecnologico de monterrey", "estudios superiores de monterrey"]):
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Tec de Monterrey (ITESM)", "es_extranjera": False}
        if any(w in norm for w in ["iberoamericana", "ibero", " uia"]):
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad Iberoamericana", "es_extranjera": False}
        if any(w in norm for w in ["itam", "autonomo de mexico"]):
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "ITAM", "es_extranjera": False}
        if "anahuac" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad Anáhuac", "es_extranjera": False}
        if "panamericana" in norm or "ipade" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad Panamericana / IPADE", "es_extranjera": False}
        if any(w in norm for w in ["la salle", "lasalle", "ulsa"]):
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad La Salle", "es_extranjera": False}
        if "valle de mexico" in norm or "uvm" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad del Valle de México (UVM)", "es_extranjera": False}
        if "unitec" in norm or "tecnologica de mexico" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "UNITEC", "es_extranjera": False}
        if "iteso" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "ITESO", "es_extranjera": False}
        if "udem" in norm or ("universidad de monterrey" in norm and "nuevo leon" not in norm):
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad de Monterrey (UDEM)", "es_extranjera": False}
        if "libre de derecho" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Escuela Libre de Derecho", "es_extranjera": False}
        if "ebc" in norm or "bancaria y comercial" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Escuela Bancaria y Comercial (EBC)", "es_extranjera": False}
        if "tecmilenio" in norm or "tec milenio" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad Tecmilenio", "es_extranjera": False}
        if "de las americas" in norm or "udlap" in norm or "udla" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "UDLAP / UDLA", "es_extranjera": False}
        if "valle de atemajac" in norm or "univa" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "UNIVA (Valle de Atemajac)", "es_extranjera": False}
        if "autonoma de guadalajara" in norm or " uag" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad Autónoma de Guadalajara (UAG)", "es_extranjera": False}
        if any(w in norm for w in [
            "universidad de tijuana", "xochicalco", "unifront", "universidad kino",
            "vasco de quiroga", "universidad univer", "valle del grijalva",
            "universidad montrer", "universidad ceuno", "universidad cnci",
            "universidad de santander", "leonardo de vinci", "instituto de ciencias juridicas",
            "instituto universitario", "centro de estudios universitarios", "centro universitario de tijuana"
        ]):
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad Privada Estatal / Regional", "es_extranjera": False}
        if any(w in norm for w in [
            "belgrano", "harvard", "columbia", "georgetown", "stanford",
            "george washington", "nebrija", "pacific western", "oberta de catalunya", "san pablo ceu"
        ]):
            return {"tipo": self.TIPO_INSTITUCION_PRIVADA, "nombre": "Universidad Privada Extranjera", "es_extranjera": True}

        # 2. Públicas
        if "unam" in norm or "nacional autonoma de mexico" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UNAM", "es_extranjera": False}
        if "ipn" in norm or "politecnico nacional" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "IPN", "es_extranjera": False}
        if "uam" in norm or "autonoma metropolitana" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAM", "es_extranjera": False}
        if any(w in norm for w in ["universidad de guadalajara", "udeg", "cucea", "cucsh", "cucs"]):
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad de Guadalajara (UdeG)", "es_extranjera": False}
        if "uanl" in norm or "autonoma de nuevo leon" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UANL", "es_extranjera": False}
        if "buap" in norm or "autonoma de puebla" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "BUAP", "es_extranjera": False}
        if "uabc" in norm or ("autonoma de baja california" in norm and "baja california sur" not in norm):
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UABC", "es_extranjera": False}
        if "uas" in norm or "autonoma de sinaloa" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAS", "es_extranjera": False}
        if "michoacana" in norm or "san nicolas de hidalgo" in norm or "umsnh" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UMSNH", "es_extranjera": False}
        if "universidad veracruzana" in norm or "normal veracruzana" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad Veracruzana", "es_extranjera": False}
        if "de sonora" in norm or "unison" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad de Sonora (UNISON)", "es_extranjera": False}
        if "del estado de mexico" in norm or "uaemex" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAEMéx", "es_extranjera": False}
        if "de coahuila" in norm or "uadec" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad Autónoma de Coahuila", "es_extranjera": False}
        if "de chihuahua" in norm or "uach" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UACH", "es_extranjera": False}
        if "de yucatan" in norm or "uady" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UADY", "es_extranjera": False}
        if "de tamaulipas" in norm or "uat" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAT", "es_extranjera": False}
        if "de san luis potosi" in norm or "uaslp" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UASLP", "es_extranjera": False}
        if "de queretaro" in norm or "uaq" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAQ", "es_extranjera": False}
        if "de zacatecas" in norm or "uaz" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAZ", "es_extranjera": False}
        if "de aguascalientes" in norm or "uaa" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAA", "es_extranjera": False}
        if "de chiapas" in norm or "unach" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UNACH", "es_extranjera": False}
        if "de guerrero" in norm or "uagro" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAGro", "es_extranjera": False}
        if "benito juarez de oaxaca" in norm or "uabjo" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UABJO", "es_extranjera": False}
        if "del estado de hidalgo" in norm or "uaeh" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAEH", "es_extranjera": False}
        if "del estado de morelos" in norm or "uaem" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAEM (Morelos)", "es_extranjera": False}
        if "de nayarit" in norm or "uan" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UAN", "es_extranjera": False}
        if "de campeche" in norm or "uacam" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UACAM", "es_extranjera": False}
        if "de tlaxcala" in norm or "uatx" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UATx", "es_extranjera": False}
        if "de baja california sur" in norm or "uabcs" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UABCS", "es_extranjera": False}
        if "juarez del estado de durango" in norm or "ujed" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UJED", "es_extranjera": False}
        if "juarez autonoma de tabasco" in norm or "ujat" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "UJAT", "es_extranjera": False}
        if "de colima" in norm or "ucol" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad de Colima", "es_extranjera": False}
        if "de guanajuato" in norm or "ugto" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad de Guanajuato", "es_extranjera": False}
        if "de quintana roo" in norm or "uqroo" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad de Quintana Roo", "es_extranjera": False}
        if any(w in norm for w in ["cide", "colegio de mexico", "colmex", "cinvestav", "ciesas", "colef"]):
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Centro Público de Investigación (CONAHCYT / Colmex / CIDE)", "es_extranjera": False}
        if any(w in norm for w in ["instituto tecnologico", "tecnologico regional", "tecnm"]):
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Tecnológico Nacional de México (Público)", "es_extranjera": False}
        if any(w in norm for w in ["normal superior", "escuela normal", "normalista"]):
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Escuela Normal Pública", "es_extranjera": False}
        if any(w in norm for w in ["colegio militar", "escuela militar", "medico militar"]):
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Colegio / Escuela Militar", "es_extranjera": False}
        if "pedagogica nacional" in norm or " upn" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad Pedagógica Nacional (UPN)", "es_extranjera": False}
        if "unadm" in norm or "abierta y a distancia" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad Abierta y a Distancia de México (UnADM)", "es_extranjera": False}
        if "autonoma" in norm:
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad Pública Estatal Autónoma", "es_extranjera": False}
        if any(w in norm for w in ["complutense", "salamanca", "buenos aires", "barcelona", "alcala", "university of texas"]):
            return {"tipo": self.TIPO_INSTITUCION_PUBLICA, "nombre": "Universidad Pública Extranjera", "es_extranjera": True}

        return {
            "tipo": self.TIPO_INSTITUCION_NO_DETERMINADA,
            "nombre": "No especificada en ficha",
            "es_extranjera": False,
        }

    def detectar_disciplina(self, norm: str) -> str:
        if any(w in norm for w in ["derecho", "leyes", "juridic", "penal"]):
            return "Derecho y Ciencias Jurídicas"
        if any(w in norm for w in [
            "politica", "administracion publica", "gobierno",
            "politicas publicas", "relaciones internacionales"
        ]):
            return "Ciencia Política y Gobierno"
        if any(w in norm for w in [
            "economia", "contadur", "finanza", "administracion de empresas",
            "comercio", "negocios", "mercadotecnia"
        ]):
            return "Economía, Finanzas y Negocios"
        if any(w in norm for w in ["medic", "medicin", "salud", "enfermer", "psicolog", "odontolog"]):
            return "Salud y Medicina"
        if any(w in norm for w in ["educacion", "pedagog", "normal", "docencia", "ensenanza"]):
            return "Educación y Pedagogía"
        if any(w in norm for w in [
            "ingenier", "sistemas", "computac", "tecnolog", "arquitect",
            "agronom", "civil", "quimic", "fisic"
        ]):
            return "Ingeniería, Tecnología y Arquitectura"
        if any(w in norm for w in [
            "comunicacion", "periodis", "letras", "sociolog",
            "filosof", "historia", "arte"
        ]):
            return "Ciencias Sociales y Comunicación"

        return "Otra / General"

    def generar_metricas(self, politicos: List[Dict[str, Any]]) -> Dict[str, Any]:
        total = len(politicos)
        if total == 0:
            return {
                "total_legisladores": 0,
                "niveles": {},
                "resumen_posgrados": {"total": 0, "porcentaje": 0.0},
                "resumen_licenciaturas": {"total": 0, "porcentaje": 0.0},
                "resumen_media_trunca": {"total": 0, "porcentaje": 0.0},
                "resumen_truncas": {"total": 0, "porcentaje": 0.0},
                "resumen_media_basica": {"total": 0, "porcentaje": 0.0},
                "por_camara": {},
                "por_partido": {},
                "top_disciplinas": {},
                "top_instituciones": {},
            }

        conteos = {
            k: {
                "codigo": k,
                "label": conf["label"],
                "icono": conf["icono"],
                "badge_class": conf["badge_class"],
                "color": conf["color"],
                "cantidad": 0,
                "porcentaje": 0.0,
            }
            for k, conf in self.NIVELES_CONFIG.items()
        }

        por_camara: Dict[str, Dict[str, int]] = {}
        por_partido: Dict[str, Dict[str, int]] = {}
        disciplina_conteos: Dict[str, int] = {}
        inst_frecuencia: Dict[str, int] = {}
        con_trunca_flag = 0

        for pol in politicos:
            # Si el político ya tiene perfil_academico preclasificado, usarlo; de lo contrario clasificar
            analisis = pol.get("perfil_academico")
            if not analisis or "grado_maximo" not in analisis:
                analisis = self.clasificar_politico(pol)

            nivel = analisis.get("grado_maximo", self.NIVEL_SIN_REGISTRO)
            if nivel not in conteos:
                nivel = self.NIVEL_SIN_REGISTRO

            conteos[nivel]["cantidad"] += 1
            if analisis.get("tiene_trunca"):
                con_trunca_flag += 1

            cam = pol.get("camara", "Diputados")
            part = pol.get("partido", "Sin Partido")

            por_camara.setdefault(cam, {k: 0 for k in self.NIVELES_CONFIG})[nivel] += 1
            por_partido.setdefault(part, {k: 0 for k in self.NIVELES_CONFIG})[nivel] += 1

            disc = analisis.get("disciplina_principal")
            if disc and disc not in ("Otra / General", "No especificada"):
                disciplina_conteos[disc] = disciplina_conteos.get(disc, 0) + 1

            for ec in analisis.get("estudios_clasificados", []):
                n_inst = ec.get("institucion_nombre")
                if n_inst and n_inst != "No especificada en ficha":
                    inst_frecuencia[n_inst] = inst_frecuencia.get(n_inst, 0) + 1

        for k, v in conteos.items():
            v["porcentaje"] = round((v["cantidad"] / total) * 100, 1)

        posgrados_tot = conteos[self.NIVEL_DOCTORADO]["cantidad"] + conteos[self.NIVEL_MAESTRIA]["cantidad"]
        lic_tot = conteos[self.NIVEL_LICENCIATURA]["cantidad"]
        trunca_tot = conteos[self.NIVEL_TRUNCA]["cantidad"]
        basica_tot = conteos[self.NIVEL_PREPARATORIA]["cantidad"] + conteos[self.NIVEL_BASICA]["cantidad"]
        sin_reg_tot = conteos[self.NIVEL_SIN_REGISTRO]["cantidad"]
        media_trunca_tot = trunca_tot + basica_tot + sin_reg_tot

        sorted_disc = dict(sorted(disciplina_conteos.items(), key=lambda x: x[1], reverse=True)[:5])
        sorted_inst = dict(sorted(inst_frecuencia.items(), key=lambda x: x[1], reverse=True)[:10])

        return {
            "total_legisladores": total,
            "niveles": conteos,
            "resumen_posgrados": {
                "total": posgrados_tot,
                "porcentaje": round((posgrados_tot / total) * 100, 1) if total > 0 else 0.0,
            },
            "resumen_licenciaturas": {
                "total": lic_tot,
                "porcentaje": round((lic_tot / total) * 100, 1) if total > 0 else 0.0,
            },
            "resumen_media_trunca": {
                "total": media_trunca_tot,
                "porcentaje": round((media_trunca_tot / total) * 100, 1) if total > 0 else 0.0,
                "truncas": trunca_tot,
                "preparatoria": conteos[self.NIVEL_PREPARATORIA]["cantidad"],
                "basica": conteos[self.NIVEL_BASICA]["cantidad"],
                "sin_registro": sin_reg_tot,
            },
            "resumen_truncas": {
                "total": trunca_tot,
                "porcentaje": round((trunca_tot / total) * 100, 1) if total > 0 else 0.0,
                "total_con_mencion_trunca": con_trunca_flag,
            },
            "resumen_media_basica": {
                "total": basica_tot,
                "porcentaje": round((basica_tot / total) * 100, 1) if total > 0 else 0.0,
            },
            "por_camara": por_camara,
            "por_partido": por_partido,
            "top_disciplinas": sorted_disc,
            "top_instituciones": sorted_inst,
        }

education_service = EducationService()
