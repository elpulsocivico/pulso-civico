# Manual Maestro: Scraping de Demandas, Juicios y Sanciones Públicas
**Plataforma Pulso Cívico | Guía de Extracción Forense y Verificación Descentralizada (100 Personas)**

---

## 1. Visión y Arquitectura de la Ficha 360°

El objetivo del proyecto es alimentar el **Modal de Integridad y Situación Legal** de cada legislador en **Pulso Cívico**, sin redireccionar jamás a páginas externas comerciales ni depender de terceros.

Cada político tendrá una auditoría estructurada en 5 dimensiones públicas:

```
                            [FICHA DE INTEGRIDAD Y ANTECEDENTES]
                                              │
         ┌──────────────────┬─────────────────┼─────────────────┬──────────────────┐
         ▼                  ▼                 ▼                 ▼                  ▼
    1. LITIGIOS Y      2. SENTENCIAS     3. SITUACIÓN      4. CORRUPCIÓN Y    5. VERIFICACIÓN
       AMPAROS            ELECTORALES       FISCAL            FUNCIÓN PUB.       ACADÉMICA
    • Juzgados Fed.    • Sentencias      • SAT 69-B EFOS   • SFP Inhabilit.   • Cédulas SEP
    • Juzgados Locales   TEPJF (PES)     • SAT 69 Créditos • Multas Admin.    • Grados SIL
    • Amparos CJF      • Violencia INE   • Empresas vinc.  • Juicio Político  • Homonimia DGP
```

---

## 2. Mapa Quirúrgico de Fuentes: Dónde y Cómo Extraer

### Dimensión 1: Demandas, Amparos y Litigios Judiciales
En México no existe una base única de demandas, sino un ecosistema federal y 32 locales:

#### A. Nivel Federal: Poder Judicial de la Federación (CJF)
* **Portal Principal:** [Portal de Servicios en Línea CJF](https://www.serviciosenlinea.pjf.gob.mx) y Sistema Integral de Seguimiento de Expedientes (SISE).
* **Órganos Clave:**
  * **Juzgados de Distrito en Materia Penal:** Búsqueda de *juicios de amparo indirecto* contra órdenes de aprehensión, órdenes de comparecencia o aseguramiento de cuentas bancarias (UIF).
  * **Juzgados de Distrito en Materia Administrativa:** Amparos contra clausuras, auditorías del SAT o resoluciones de dependencias federales.
  * **Tribunales Colegiados de Circuito:** Recursos de revisión y quejas donde el político apela una negativa de amparo.
* **Criterio de Búsqueda:** `nombre_completo` en calidad de *Quejoso* (quien pide el amparo), *Tercero Interesado* o *Autoridad Responsable*.
* **Obstáculo Legal & Solución:** Muchos acuerdos aparecen con nombres testados por ley de privacidad. Si el nombre no aparece en el resumen público, el colaborador extrae el expediente con la carátula pública y el extracto de la materia.

#### B. Nivel Local: Tribunales Superiores de Justicia (Los 32 Estados)
* **Portal:** Boletines Judiciales oficiales de cada entidad (ej. TSJCDMX en CDMX, PJBC en Baja California, PJEDOMEX en Edomex, etc.).
* **Juzgados Específicos:**
  * **Juzgados Civiles:** Demandas por daño moral, controversias contractuales e indemnizaciones.
  * **Juzgados Mercantiles:** Juicios ejecutivos mercantiles por pagarés, créditos bancarios impagados y quiebras.
  * **Juzgados Familiares:** Padrón y controversias de pensión alimenticia (cruce directo con el Registro Nacional de Obligaciones Alimentarias).
  * **Juzgados Penales:** Autos de vinculación a proceso en delitos del fuero común (fraude, despojo, peculado local).
* **Particularidad en acuerdos:** En varios estados se publica como `[ACTOR] vs. SECRETO`. Cuando el político es el demandado original, el colaborador debe registrar el número de expediente radicado en el juzgado local y la fecha de publicación.

---

### Dimensión 2: Justicia y Sanciones Electorales

#### A. Tribunal Electoral del Poder Judicial de la Federación (TEPJF)
* **Portal:** [Buscador de Sentencias del TEPJF](https://www.te.gob.mx/BuscadorSentencias/) y Seguimiento de Medios de Impugnación.
* **Instancias:** Sala Superior y las 5 Salas Regionales (Guadalajara, Monterrey, Xalapa, CDMX, Toluca) + Sala Regional Especializada.
* **Tipos de Asuntos a Extraer:**
  * **PES (Procedimiento Especial Sancionador):** Sentencias por actos anticipados de campaña, uso indebido de recursos públicos, promoción personalizada ilegal y calumnia.
  * **RAP (Recurso de Apelación):** Sanciones y multas impuestas por el INE por irregularidades en informes de gastos de precampaña o campaña.
  * **JDC (Juicio para la Protección de los Derechos Político-Electorales):** Disputas internas por candidaturas y expulsiones partidistas.

#### B. Instituto Nacional Electoral (INE)
* **Portal:** [Registro Nacional de Personas Sancionadas en Materia de Violencia Política contra las Mujeres en Razón de Género (RNPVPEG)](https://www.ine.mx/actores-politicos/registro-nacional-personas-sancionadas/).
* **Datos a Extraer:** Si el político se encuentra inscrito en el padrón, fecha de inscripción, temporalidad de la sanción y pérdida del modo honesto de vivir (impedimento para ser candidato).

---

### Dimensión 3: Situación Fiscal y Empresas Fantasma (SAT)

* **Portal Oficial:** [Listas del Artículo 69 y 69-B del CFF](http://omawww.sat.gob.mx/cifras_sat/Paginas/datos/vinculacion.html).
* **Archivos a Descargar y Cruzar:**
  1. **Lista 69-B (EFOS):** Archivo `.csv` con personas físicas y morales clasificadas en *Presuntos*, *Desvirtuados*, *Definitivos* y *Favorables*. Permite auditar si el político o las razones sociales que declara en sus empresas están en el padrón definitivo de factureras.
  2. **Lista 69:** Personas con créditos fiscales firmes, condonados o no localizados.
* **Frecuencia:** Descarga quincenal automatizada.

---

### Dimensión 4: Corrupción y Función Pública (SFP)

* **Portal Oficial:** [Directorio de Servidores Públicos Sancionados (SFP / Datos Abiertos)](https://datos.gob.mx).
* **Catálogo:** Contiene el historial completo de resoluciones de la Secretaría de la Función Pública y órganos internos de control.
* **Campos Extraídos:**
  * Causa de la sanción (negligencia administrativa, cohecho, desvío).
  * Tipo de sanción: Inhabilitación temporal, destitución o sanción económica.
  * Periodo de vigencia de la inhabilitación.

---

### Dimensión 5: Verificación Curricular y Títulos (SEP)

* **Portal Oficial:** [Registro Nacional de Profesionistas (DGP - SEP)](https://www.gob.mx/cedulaprofesional).
* **Lógica de Auditoría:**
  1. Tomar el campo `ultimo_grado_estudios` y `escolaridad_detalle` que el legislador declaró en el Sistema de Información Legislativa (SIL).
  2. Consultar el nombre y apellidos completos en el padrón de la DGP.
  3. Resultado:
     * **Verificado:** Coincide carrera, año e institución educativa.
     * **Sin Registro Localizado:** Declaró grado académico pero no existe cédula expedida ante la SEP.
     * **Discrepancia:** Tiene cédula en una carrera totalmente distinta a la que ostenta en su ficha oficial.

---

## 3. División de los 100 Colaboradores y Dinámica de Trabajo

Para evitar que 100 personas choquen entre sí o repitan consultas, la estructura es modular y basada en squads:

```
                            SQUAD CENTRAL DE QA & AUDITORÍA (10)
                                            │
     ┌──────────────────┬───────────────────┼───────────────────┬──────────────────┐
     ▼                  ▼                   ▼                   ▼                  ▼
SQUAD FISCAL &     SQUAD ACADÉMICO     SQUAD ELECTORAL     SQUAD AMPAROS       SQUAD TRIBUNALES
SFP (15)           SEP (15)            TEPJF/INE (15)      FED. PJF (15)       ESTATALES (30)
• SAT 69 / 69-B    • Scraping DGP      • Sentencias Sala   • Juzgados Distrito • 1 persona por
• SFP Inhabil.     • Cruce contra SIL    Superior y Reg.   • Amparos Indirecto   cada estado
• Cruce masivo     • Detección títulos • Registro RNPVPEG  • Quejas / Rev.       (Boletines)
```

### Protocolo de Asignación por Político
Cada colaborador del Squad Judicial (Amparos y Tribunales Estatales) recibe un paquete de **15 a 20 legisladores específicos** (asignados por su `referencia_sil`) ordenados por la entidad federativa que representan, para concentrarse únicamente en los tribunales de esa jurisdicción.

---

## 4. Algoritmo Anti-Homonimia: Protocolo de Certeza Cívica

Para respetar el principio rector de **neutralidad** de Pulso Cívico y evitar difamaciones por personas que comparten nombre y apellidos:

| Nivel de Certeza | Requisitos de Acreditación | Despliegue en la Plataforma |
|---|---|---|
| **Nivel 1 (100% Certeza)** | Coincide RFC, CURP, Cédula Profesional, o el expediente judicial cita expresamente su cargo público/partido. | **Publicación Automática** con insignia verde en el modal. |
| **Nivel 2 (75% Alta Probabilidad)** | Mismo nombre completo + entidad federativa donde ejerció el cargo + fecha del juicio concurrente con su vida pública. | **Pasa a revisión del Squad de QA** antes de publicarse. |
| **Nivel 3 (Duda / Homónimo)** | Solo coincide el nombre, pero el expediente es en un estado ajeno o en materias particulares sin confirmación de identidad. | **No se publica como antecedente directo**. El modal indicará: *"Sin registros directos confirmados"*. |

---

## 5. Esquema de Salida JSON (`storage/data/json/antecedentes/`)

Cada archivo de antecedentes generado por un colaborador se nombrará:  
`storage/data/json/antecedentes/{referencia_sil}_antecedentes.json`

```json
{
  "referencia_sil": "9224861",
  "nombre_completo": "CASTRO TRENTI FERNANDO JORGE",
  "entidad": "Baja California",
  "cargo": "Diputado Federal",
  "resumen_integridad": {
    "total_expedientes_encontrados": 4,
    "sat_estatus": "limpio",
    "sfp_estatus": "limpio",
    "cedula_estatus": "sin_registro_exacto",
    "violencia_politica_ine": "sin_registro"
  },
  "secciones": {
    "verificacion_academica": {
      "grado_declarado_sil": "Licenciatura en Derecho",
      "institucion_declarada": "UNAM",
      "coincidencias_sep": [],
      "estatus": "sin_cedula_localizada",
      "observacion": "No se localizó registro de cédula profesional con el nombre exacto en el Registro Nacional de Profesionistas."
    },
    "situacion_fiscal_sat": {
      "lista_69_b_efos": "no_listado",
      "lista_69_creditos": "no_listado",
      "fecha_corte_sat": "2026-09-15"
    },
    "sanciones_sfp": {
      "inhabilitado": false,
      "sanciones_registradas": []
    },
    "tribunal_electoral_tepjf": [
      {
        "expediente": "SUP-RAP-045/2013",
        "sala": "Sala Superior",
        "tipo_asunto": "Recurso de Apelación",
        "materia": "Fiscalización de Campaña a la Gubernatura de Baja California",
        "sentido_resolucion": "Modifica resolución impugnada",
        "fecha_sentencia": "2013-06-20",
        "fuente_url": "https://www.te.gob.mx/sentencias/..."
      }
    ],
    "estrados_y_juicios": [
      {
        "instancia": "Poder Judicial del Estado de Baja California",
        "juzgado": "Juzgado Primero de lo Civil de Tijuana",
        "expediente": "01749/2022",
        "materia": "Jurisdicción Voluntaria",
        "calidad_parte": "familiar_mencionado",
        "resumen": "Acuerdo procesal en materia voluntaria civil.",
        "nivel_confianza": "nivel_2",
        "fuente_url": "https://tribunalelectronico.pjbc.gob.mx/..."
      }
    ]
  },
  "metadata": {
    "fecha_auditoria": "2026-10-12",
    "colaborador_id": "auditor_pjf_08",
    "revisor_qa": "qa_lead_02",
    "sha256": "4a7d1e89b25f93c09f3e582847d0e413ab8e0c3298a09f8c61e2376b5d921a9c"
  }
}
```

---

## 6. Integración con la Vista Web (Modal de Detalle)

Los datos generados se consumirán directamente en [**`views/politico.php`**](views/politico.php) sin redirecciones externas.

### El disparador en la ficha:
```html
<button type="button" class="btn btn-outline-dark w-100 rounded-pill shadow-sm py-2 my-2" data-bs-toggle="modal" data-bs-target="#modalIntegridadLegal">
    <i class="bi bi-shield-shaded text-primary me-2"></i>Historial Legal y Fiscal
</button>
```

### El modal de visualización:
El modal renderiza cada pestaña con sus badges de estado:
* **Verde (`bg-success-subtle`):** Verificado / Al corriente / Sin sanciones.
* **Amarillo (`bg-warning-subtle`):** En revisión / Procedimiento electoral ordinario / Sin cédula localizada.
* **Rojo (`bg-danger-subtle`):** Sanción firme / Inhabilitación activa / Listado en SAT 69-B.
* **Pie de página del modal:** Respaldo criptográfico utilizando el componente oficial [**`views/partials/badge_integridad.php`**](views/partials/badge_integridad.php).

---

## 7. Protocolo de Código y Calidad (Reglas Obligatorias)

1. **PHP 8.1 Vanilla Estricto:** Toda clase de ingesta en `src/Services/` debe incluir `declare(strict_types=1);` y tipado riguroso.
2. **Cero Dependencias Externas:** Usar `DOMDocument`, `DOMXPath`, `json_decode()` nativo y `PulsoCivico\Core\HttpClient`.
3. **Rate Limiting Preventivo:** `rate_limit_ms >= 1000`. Ningún scraper puede emitir ráfagas paralelas que comprometan portales gubernamentales.
4. **Auto-Check Pre-Entrega:** Antes de solicitar aprobación o commit:
   ```powershell
   powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 lint "src/Services/<TuServicio>Service.php"
   ```
