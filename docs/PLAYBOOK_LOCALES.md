# Guía de Extracción de Datos Locales con IA (Playbook para Claude, Codex y Agentes)

> **Documento Oficial de Contribución** — Dirigido a desarrolladores, investigadores y colaboradores que utilicen asistentes de IA (Claude Code, OpenAI Codex, Cursor, Gemini, ChatGPT) para extraer y estructurar datos de **Congresos Estatales** y **Alcaldías**.

---

## 1. Contexto y Principios Rectores

Pulso Cívico es una plataforma de transparencia ciudadana **100% neutral y basada en fuentes oficiales**.
1. **Neutralidad Absoluta:** Cero calificativos, opiniones o sesgos. Solo datos oficiales públicos.
2. **Trazabilidad Mandatoria:** Todo dataset debe incluir su `fuente_url` oficial directa y `fecha_extraccion`.
3. **Privacidad Estricta:** Solo información de cargos públicos (oficinas institucionales, correos de enlace oficial). Prohibido registrar domicilios o teléfonos particulares.
4. **Almacenamiento Low-Cost:** Sin bases de datos pesadas. Todo se almacena en archivos JSON estructurados y validados.

---

## 2. Convención de Almacenamiento y Rutas

Para evitar miles de archivos dispersos o megarchivos inmanejables, los datos locales se agrupan **por Estado**:

```text
storage/data/json/local/
└── {estado_slug}/
    ├── diputados-locales-leg-{numero}.json
    └── alcaldias-{periodo_inicio}-{periodo_fin}.json
```

### Reglas de nomenclatura:
- `{estado_slug}`: En minúsculas y kebab-case conforme al catálogo oficial (ej. `baja-california`, `cdmx`, `jalisco`, `nuevo-leon`, `oaxaca`).
- **Diputados Locales:** Un único archivo por legislatura que contenga a todos los diputados del estado.
  - Ejemplo: `storage/data/json/local/baja-california/diputados-locales-leg-25.json`
- **Alcaldías / Municipios:** Un único archivo por periodo que contenga a todas las alcaldías o presidencias municipales de la entidad.
  - Ejemplo: `storage/data/json/local/baja-california/alcaldias-2024-2027.json` (7 alcaldes)
  - Ejemplo: `storage/data/json/local/oaxaca/alcaldias-2024-2027.json` (todos los municipios en un solo array)

---

## 3. Contratos de Datos (JSON Schemas)

Todo archivo debe ser un **Array JSON válido en UTF-8**, indentado a 2 espacios. Los campos vacíos deben ser `null` (nunca cadenas como `"N/A"`, `"N/D"` o vacíos `""`).

### A. Diputados Locales (`diputados-locales-leg-{numero}.json`)

| Campo | Tipo | Req. | Descripción |
|---|---|---|---|
| `id` | string | ✅ | Identificador único (`{estado_slug}-{leg}-{slug_nombre}`) |
| `nombre_completo` | string | ✅ | Nombre oficial sin títulos ("Lic.", "Dr.", "Dip.") |
| `cargo` | string | ✅ | Fijo: `"Diputado Local"` |
| `entidad` | string | ✅ | Nombre oficial de la entidad federativa |
| `camara` | string | ✅ | Nombre del Congreso (ej. `"Congreso del Estado de Baja California"`) |
| `legislatura` | int | ✅ | Número arábigo de la legislatura (ej. `25` para la XXV) |
| `periodo` | string | ✅ | Formato `"YYYY-YYYY"` (ej. `"2024-2027"`) |
| `partido` | string | ✅ | Siglas oficiales (`MORENA`, `PAN`, `PRI`, `PVEM`, `PT`, `MC`, `IND`) |
| `principio_eleccion` | string | ✅ | `"Mayoría Relativa"` o `"Representación Proporcional"` |
| `distrito` | string\|null | | Distrito local que representa (ej. `"Distrito 1"`) o `null` si es plurinominal |
| `tipo_cargo` | string | ✅ | `"Propietario"` o `"Suplente"` |
| `suplente_nombre` | string\|null | | Nombre completo del suplente oficial |
| `comisiones` | array | | Lista de objetos: `[{"nombre": "Comisión de Hacienda", "cargo": "Presidente"}]` |
| `contacto` | object | | Objeto con datos oficiales de oficina institucional |
| `contacto.correo_institucional` | string\|null | | Correo oficial del dominio del congreso |
| `contacto.telefono_oficina` | string\|null | | Teléfono oficial de oficina/conmutador |
| `contacto.modulo_enlace` | string\|null | | Dirección pública de oficina parlamentaria o de enlace |
| `foto_oficial_url` | string\|null | | URL pública directa de la fotografía en el portal oficial |
| `redes_sociales` | object | | Enlaces a perfiles públicos oficiales (`twitter`, `facebook`, `instagram`) |
| `metadata` | object | ✅ | Objeto obligatorio de auditoría y trazabilidad |
| `metadata.fuente_url` | string | ✅ | URL pública exacta de donde se obtuvo la información |
| `metadata.fecha_extraccion` | string | ✅ | Fecha de captura en formato ISO `YYYY-MM-DD` |
| `metadata.colaborador` | string | | Usuario de GitHub o identificador del colaborador |

#### Ejemplo de registro:
```json
[
  {
    "id": "bc-25-juan-perez-lopez",
    "nombre_completo": "Juan Pérez López",
    "cargo": "Diputado Local",
    "entidad": "Baja California",
    "camara": "Congreso del Estado de Baja California",
    "legislatura": 25,
    "periodo": "2024-2027",
    "partido": "PAN",
    "principio_eleccion": "Mayoría Relativa",
    "distrito": "Distrito 2",
    "tipo_cargo": "Propietario",
    "suplente_nombre": "Carlos Soto Mendoza",
    "comisiones": [
      {
        "nombre": "Comisión de Hacienda y Presupuesto",
        "cargo": "Presidente"
      },
      {
        "nombre": "Comisión de Gobernación",
        "cargo": "Vocal"
      }
    ],
    "contacto": {
      "correo_institucional": "juan.perez@congresobc.gob.mx",
      "telefono_oficina": "686-559-5600",
      "modulo_enlace": "Av. Reforma #123, Mexicali, B.C."
    },
    "foto_oficial_url": "https://www.congresobc.gob.mx/fotos/dip25/jperez.jpg",
    "redes_sociales": {
      "twitter": "https://x.com/...",
      "facebook": "https://facebook.com/..."
    },
    "metadata": {
      "fuente_url": "https://www.congresobc.gob.mx/DirectorioLegislativo",
      "fecha_extraccion": "2024-10-12",
      "colaborador": "usuario_github"
    }
  }
]
```

---

### B. Alcaldías y Presidencias Municipales (`alcaldias-{periodo}.json`)

| Campo | Tipo | Req. | Descripción |
|---|---|---|---|
| `id` | string | ✅ | Identificador único (`{estado_slug}-{municipio_slug}-{periodo}`) |
| `municipio` | string | ✅ | Nombre oficial del municipio o alcaldía |
| `entidad` | string | ✅ | Entidad federativa |
| `clave_inegi` | string\|null | | Clave geoestadística INEGI si se conoce (ej. `"02004"`) |
| `nombre_alcalde` | string | ✅ | Nombre de la persona titular de la presidencia municipal |
| `cargo` | string | ✅ | `"Presidente Municipal"` o `"Alcalde"` |
| `periodo` | string | ✅ | Formato `"YYYY-YYYY"` (ej. `"2024-2027"`) |
| `partido` | string | ✅ | Siglas del partido postulado (`MORENA`, `PAN`, `PRI`, etc.) |
| `coalicion` | string\|null | | Nombre de la coalición si compitió en alianza |
| `sitio_web_ayuntamiento` | string\|null | | Enlace oficial del ayuntamiento |
| `contacto` | object | | Datos de contacto institucional (`correo_institucional`, `telefono_oficina`) |
| `foto_oficial_url` | string\|null | | Fotografía oficial del titular |
| `metadata` | object | ✅ | Objeto con `fuente_url` (portal electoral u oficial) y `fecha_extraccion` |

---

## 4. Prompt Maestro para Agentes (Claude Code / Codex / LLM)

Copia y pega este prompt al asistente o agente de IA junto con el contenido oficial (texto, PDF o tabla del sitio web institucional):

```text
Actúa como un extractor y validador de datasets cívicos para el proyecto open source Pulso Cívico.
Tu tarea es convertir la información de [DIPUTADOS LOCALES / ALCALDES] de [NOMBRE DEL ESTADO] al formato JSON estricto del proyecto.

REGLAS OBLIGATORIAS:
1. Responde ÚNICAMENTE con el bloque JSON válido en array (```json [ ... ] ```), sin texto introductorio ni explicaciones.
2. Cada campo debe coincidir con la especificación de docs/PLAYBOOK_LOCALES.md.
3. Si un valor no está disponible en la fuente oficial, asígnalo como null (nunca uses "", "N/A" ni inventes datos).
4. Limpia los nombres de títulos honoríficos o profesionales (elimina "Lic.", "Dr.", "Dip.", etc.).
5. Normaliza siglas de partidos: MORENA, PAN, PRI, PVEM, PT, MC, PRD, IND.
6. Registra obligatoriamente metadata.fuente_url y metadata.fecha_extraccion (YYYY-MM-DD).

INFORMACIÓN OFICIAL A PROCESAR:
[PEGA AQUÍ EL TEXTO, HTML O TABLA OFICIAL]
```

---

## 5. Validación y Flujo de Pull Request (PR)

Antes de abrir un Pull Request en GitHub:

1. **Colocar el archivo en la ruta correspondiente:**
   `storage/data/json/local/{estado_slug}/{archivo}.json`
2. **Validar sintaxis y salud del proyecto:**
   ```bash
   # En Windows PowerShell:
   powershell -ExecutionPolicy Bypass -File .\tools\run.ps1 health

   # En Linux / macOS / Bash:
   php tools/gateway.php health
   ```
3. **Hacer commit selectivo (nunca `git add .`):**
   ```bash
   git checkout -b data/{estado_slug}-locales
   git add storage/data/json/local/{estado_slug}/diputados-locales-leg-XX.json
   git commit -m "data: agregar diputados locales de {Estado} (Leg. {XX})"
   git push origin data/{estado_slug}-locales
   ```
4. **Abrir PR** indicando la fuente oficial consultada y el método de extracción utilizado.
