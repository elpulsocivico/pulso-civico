# 🇲🇽 Pulso Cívico — Suite Oficial de Scrapers e Ingesta Gubernamental

> **Plataforma ciudadana, neutral y de código abierto para la transparencia política en México.**
> Documentación integral de los extractores oficiales, parsers legislativos y herramientas de verificación criptográfica de datos públicos.

<p align="center">
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.1%2B%20Strict%20Types-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.1+"></a>
  <a href="https://www.python.org/"><img src="https://img.shields.io/badge/Python-3.10%2B-3776AB?style=for-the-badge&logo=python&logoColor=white" alt="Python 3.10+"></a>
  <img src="https://img.shields.io/badge/Datos-JSON%20UTF--8%20%2B%20SHA--256-success?style=for-the-badge" alt="JSON UTF-8 + SHA-256">
  <a href="../LICENSE"><img src="https://img.shields.io/badge/Licencia-AGPL--3.0-blue.svg?style=for-the-badge" alt="Licencia AGPL-3.0"></a>
  <a href="../DATA_LICENSE.md"><img src="https://img.shields.io/badge/Datasets-CC%20BY%204.0-lightgrey.svg?style=for-the-badge" alt="CC BY 4.0"></a>
</p>

---

## 🧭 Propósito y Arquitectura de la Suite

El motor de extracción de **Pulso Cívico** está diseñado bajo el principio de **cero dependencias externas** (nativo con `DOMDocument`, `DOMXPath` y `cURL` en PHP 8.1+, junto con utilidades en Python 3.10+) para recolectar, estructurar y auditar información de portales gubernamentales (`.gob.mx`).

> 💡 **Rama Aislada (`scrapers`)**: Quienes deseen trabajar únicamente con los archivos de los extractores sin descargar los más de 3,200 datasets JSON ni la interfaz web pueden consultar o clonar la rama huérfana dedicada exclusivamente a la suite de scraping.

### Mapa del Código de Extracción

| Ruta | Responsabilidad |
|---|---|
| [`bin/console.php`](../bin/console.php) | Motor CLI para ingesta nacional o por entidad del Sistema de Información Legislativa (SIL-SEGOB). |
| [`tools/gateway.php`](../tools/gateway.php) | Gateway unificado para ejecutar scrapers federales, estatales y validaciones de sintaxis (`lint`). |
| [`tools/core/ScraperRouter.php`](../tools/core/ScraperRouter.php) | Enrutador parametrizado de fuentes (`sil`, `votaciones`, `asistencias` y las 32 entidades federativas). |
| [`tools/core/Linter.php`](../tools/core/Linter.php) | Validador de sintaxis PHP en lote previo a cada entrega. |
| [`tools/audit_encoding.py`](../tools/audit_encoding.py) | Auditoría estricta de codificación UTF-8 para detectar secuencias corruptas (*mojibake*). |
| [`src/Core/HttpClient.php`](../src/Core/HttpClient.php) | Cliente HTTP con reintentos exponenciales, jitter aleatorio y `rate_limit_ms` (1,250 ms por defecto). |
| [`src/Core/EvidenceHasher.php`](../src/Core/EvidenceHasher.php) | Cálculo de huellas criptográficas SHA-256 y Raíces de Merkle (`computeMerkleRoot`) para fe pública digital. |
| [`src/Core/Logger.php`](../src/Core/Logger.php) | Registro estructurado en formato JSONL (`storage/logs/`) de cada petición y evento de extracción. |
| [`src/DTO/`](../src/DTO/) | Contratos de datos tipados ([`LegisladorDTO.php`](../src/DTO/LegisladorDTO.php), [`VotacionDTO.php`](../src/DTO/VotacionDTO.php), [`SesionPlenoDTO.php`](../src/DTO/SesionPlenoDTO.php), [`AlcaldeDTO.php`](../src/DTO/AlcaldeDTO.php)). |
| [`src/Services/`](../src/Services/) | Extractores del Congreso de la Unión ([`SilIngestService.php`](../src/Services/SilIngestService.php), [`ParliamentScraperService.php`](../src/Services/ParliamentScraperService.php)), los 32 Congresos Locales (`Congreso*ScraperService.php`) e [`InafedScraperService.php`](../src/Services/InafedScraperService.php). |
| [`config/config.php`](../config/config.php) | Configuración central de endpoints gubernamentales, rutas de almacenamiento y variables de `.env`. |

---

## 🚀 Cómo Usar los Scrapers

### 1. Configuración del Entorno Local
Copia el archivo de plantilla de variables de entorno (nunca subas tu archivo `.env` al repositorio):
```bash
cp .env.example .env
```

### 2. Ingesta Federal del SIL (`bin/console.php`)
Permite extraer las fichas oficiales de diputados federales y senadores desde el Sistema de Información Legislativa (SEGOB):
```bash
# Ingestar las 32 entidades federativas completas (628 legisladores de la LXVI Legislatura)
php bin/console.php --nacional --legislatura=66

# Ingestar una sola entidad federativa (ejemplo: 14 = Jalisco, 2 = Baja California)
php bin/console.php --entidad=14 --legislatura=66

# Ingestar omitiendo la descarga de fotografías oficiales
php bin/console.php --entidad=2 --legislatura=66 --no-photos
```

### 3. Gateway Unificado (`tools/gateway.php`)
Punto de entrada para invocar cualquier extractor federal o estatal registrado en [`ScraperRouter.php`](../tools/core/ScraperRouter.php):
```bash
# Listar todas las fuentes y congresos estatales disponibles
php tools/gateway.php list

# Extraer padrón legislativo desde el SIL
php tools/gateway.php scrape sil "nacional=1,legislatura=66"

# Extraer votaciones nominales y asistencias del pleno de Diputados (SITL)
php tools/gateway.php scrape votaciones "periodo=1,legislatura=66"
php tools/gateway.php scrape asistencias "periodo=1,legislatura=66"

# Ejecutar scrapers de Congresos Locales (32 entidades soportadas)
php tools/gateway.php scrape cdmx
php tools/gateway.php scrape jalisco
php tools/gateway.php scrape nuevoleon
php tools/gateway.php scrape sonora
```

### 4. Parsers Auxiliares en Python (`tools/core/`)
Para el procesamiento de gacetas parlamentarias en PDF y tablas históricas estatales:
```bash
# Parseo de historial legislativo de Coahuila
python3 tools/core/coahuila_parser.py

# Extracción de gacetas en PDF del Congreso de Nuevo León
python3 tools/core/nl_pdf_parser.py
```

---

## 📜 Lineamientos Éticos, `robots.txt` y Respeto a Servidores Públicos

Los portales gubernamentales operan con frecuencia sobre infraestructura antigua protegida por WAF (*Web Application Firewall*). Todo scraper de **Pulso Cívico** debe cumplir sin excepción las siguientes reglas:

1. **Verificación de `robots.txt`**:
   - Antes de programar o ejecutar un extractor contra cualquier portal `.gob.mx`, inspecciona `https://<dominio>/robots.txt` y respeta estrictamente las rutas restringidas (`Disallow`) y tiempos de espera (`Crawl-delay`).
2. **Rate-Limiting Obligatorio (`HTTP_RATE_LIMIT_MS`)**:
   - Respeta una pausa mínima de **1,250 ms** (nunca menor a **1,000 ms**) entre peticiones consecutivas, sumando un jitter aleatorio de 100–250 ms.
   - **Prohibido** realizar peticiones en paralelo o concurrencia masiva.
   - Utiliza siempre [`PulsoCivico\Core\HttpClient`](../src/Core/HttpClient.php); nunca uses `file_get_contents()` directamente sobre URLs remotas.
3. **Caché Local de Evidencia Cruda (`storage/raw/html/`)**:
   - Durante el desarrollo, descarga 2–3 páginas HTML de ejemplo en `storage/raw/html/` para probar tu parser en local sin golpear repetidamente el servidor oficial.
4. **User-Agent Transparente**:
   - Declara un identificador claro y verificable (`PulsoCivicoBot/1.0 (+https://github.com/elpulsocivico/pulso-civico)`).
5. **Neutralidad Absoluta y Protección de Datos Personales**:
   - **Neutralidad fáctica**: Cero adjetivos calificativos, opiniones o sesgos partidistas en el código, comentarios o datos extraídos.
   - **Privacidad**: Recopila exclusivamente datos públicos e institucionales inherentes al cargo (`correo_institucional`, teléfono de oficina legislativa, `ubicacion_oficina`). **Prohibido** extraer o almacenar domicilios particulares, teléfonos privados o datos familiares.

---

## 📦 Contrato Estándar de Datos (`src/DTO/`)

Todo scraper debe producir archivos JSON codificados en **UTF-8 estricto (sin BOM)**, con claves en `snake_case` y fechas en formato ISO (`YYYY-MM-DD`). La fuente de verdad de la estructura son las clases en [`src/DTO/`](../src/DTO/) y la especificación en [`docs/DATA_SCHEMA.md`](../docs/DATA_SCHEMA.md).

### Reglas Generales del Esquema
* **Trazabilidad obligatoria**: Todo registro o dataset debe incluir el objeto `metadata` con `fuente_url` (URL oficial directa `.gob.mx`), `fecha_extraccion` (`YYYY-MM-DD`) y la huella criptográfica `sha256` generada con [`EvidenceHasher::hashContent()`](../src/Core/EvidenceHasher.php).
* **Valores desconocidos**: Utilizar siempre `null` (nunca cadenas arbitrarias como `"N/D"` o `"Sin dato"`).
* **Partidos políticos**: Siglas oficiales normalizadas (`MORENA`, `PAN`, `PRI`, `PVEM`, `PT`, `MC`, `PRD`).
* **Entidades federativas**: Nombre oficial del catálogo INEGI (`Ciudad de México`, `Estado de México`, `Michoacán de Ocampo`, etc.).

### Rutas y Nomenclatura de Salida en `storage/data/json/`
* **Congreso de la Unión:** `congreso_union_leg_<numero>.json` y `legisladores_entidad_<id>_leg_<numero>.json`.
* **Congresos Locales (32 Estados):** `congreso_<estado_slug>_leg_<numero>.json` (ej. `congreso_jalisco_leg_64.json`, `congreso_cdmx_leg_66.json`).
* **Votaciones y Asistencias:** `votaciones_leg_<numero>.json` y `asistencias_leg_<numero>.json`.
* **Municipios y Cabildos:** `storage/data/json/municipios/<cvegeo>.json`.

### Ejemplo Canónico de Salida (`LegisladorDTO`)
```json
{
  "referencia_sil": "9228115",
  "nombre_completo": "Nombre Apellido Ejemplo",
  "camara": "Diputados",
  "legislatura": 66,
  "partido": "MORENA",
  "principio_eleccion": "Mayoría Relativa",
  "entidad": "Jalisco",
  "distrito": "1",
  "tipo_cargo": "Propietario",
  "suplente_nombre": "Nombre Suplente Ejemplo",
  "correo_institucional": "ejemplo@diputados.gob.mx",
  "ultimo_grado_estudios": "Licenciatura",
  "comisiones": [],
  "trayectoria_politica": [],
  "metadata": {
    "fuente_url": "http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia=9228115",
    "fecha_extraccion": "2026-10-30",
    "sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
  }
}
```

---

## 🛠️ Cómo Crear un Nuevo Scraper y Enviar Pull Request a `main`

### 1. Esqueleto Estándar en PHP 8.1+
Todo nuevo extractor debe ubicarse en `src/Services/<Fuente>ScraperService.php`, declarar `strict_types=1`, mantenerse por debajo de las **400 líneas** y registrarse en [`tools/core/ScraperRouter.php`](../tools/core/ScraperRouter.php):

```php
<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\DTO\LegisladorDTO;

final class CongresoEjemploScraperService
{
    public function __construct(
        private readonly array $config,
        private readonly HttpClient $http,
        private readonly Logger $logger
    ) {}

    /** @return LegisladorDTO[] */
    public function ingest(int $legislatura): array
    {
        $url = 'https://congreso-ejemplo.gob.mx/diputados';
        $html = $this->http->get($url);
        $sha256 = EvidenceHasher::hashContent($html);

        // 1. Parsear HTML con DOMDocument + DOMXPath (nativo, sin librerías externas)
        // 2. Mapear cada registro a LegisladorDTO
        // 3. Incluir metadata: ['fuente_url' => $url, 'fecha_extraccion' => date('Y-m-d'), 'sha256' => $sha256]
        return [];
    }
}
```

### 2. Flujo de Trabajo y Envío a la Rama Principal (`main`)
1. **Revisa [`docs/FUENTES.md`](../docs/FUENTES.md) y abre un Issue** en **[`elpulsocivico/pulso-civico`](https://github.com/elpulsocivico/pulso-civico)** antes de trabajar para evitar duplicar esfuerzos.
2. Crea una rama descriptiva desde tu fork:
   - `scraper/congreso-<estado>`, `data/actualizacion-<fuente>`, `fix/...`
3. **Verifica la sintaxis y la codificación UTF-8** antes de confirmar tus cambios:
   ```bash
   # Validar sintaxis de archivos PHP modificados
   php tools/gateway.php lint "src/Services/CongresoEjemploScraperService.php,tools/core/ScraperRouter.php"

   # Validar sintaxis de todo el directorio src/
   php tools/gateway.php lint:dir src

   # Verificar que los JSON generados no contengan errores de codificación UTF-8
   python3 tools/audit_encoding.py
   ```
4. **Realiza commits convencionales** agregando archivo por archivo (nunca uses `git add .`):
   ```bash
   git add src/Services/CongresoEjemploScraperService.php tools/core/ScraperRouter.php
   git commit -m "scraper: agrega extractor oficial del Congreso de Ejemplo LXVI"
   ```
5. Abre un **Pull Request** hacia la rama `main` de **[`elpulsocivico/pulso-civico`](https://github.com/elpulsocivico/pulso-civico)**.

### 3. Checklist Obligatorio para el Pull Request
- [ ] `php tools/gateway.php lint "<archivos>"` ejecutado sin errores de sintaxis.
- [ ] `python3 tools/audit_encoding.py` sin anomalías de codificación UTF-8.
- [ ] Archivos individuales con longitud menor a **400 líneas** y con `declare(strict_types=1);`.
- [ ] La fuente es 100% oficial (`.gob.mx`, congreso estatal o periódico oficial).
- [ ] Cada registro incluye `metadata.fuente_url`, `metadata.fecha_extraccion` y hash `sha256`.
- [ ] `rate_limit_ms` ≥ 1,000 ms (1,250 ms recomendado) y sin peticiones en paralelo.
- [ ] Sin datos personales no públicos ni dependencias externas no aprobadas.
- [ ] Fila actualizada en [`docs/FUENTES.md`](../docs/FUENTES.md).

---

## 🗺️ Documentación de Referencia

* [Índice Principal de Pulso Cívico](../README.md)
* [Guía Rápida para Crear un Scraper](../docs/COMO_CREAR_UN_SCRAPER.md)
* [Esquema de Datos y Contratos DTO](../docs/DATA_SCHEMA.md)
* [Manual de Datos y Evidencia Criptográfica](../docs/MANUAL_DE_DATOS_Y_EVIDENCIA.md)
* [Catálogo de Fuentes Oficiales](../docs/FUENTES.md)
* [Guía de Contribución](../CONTRIBUTING.md) · [Reglas para Agentes de IA](../AGENTS.md)
