# 📖 MANUAL OFICIAL DE SCRAPERS E INGESTA
## Pulso Cívico — Cómo extraer datos del Congreso de la Unión
**Versión:** 1.0 | **Público:** Desarrolladores y Analistas de Datos

Este manual detalla paso a paso cómo funcionan nuestros extractores, qué URLs exactas de gobierno consultan, cómo se envían los parámetros para extraer cualquier legislatura y cómo generar los archivos JSON con evidencia.

---

## 1. LAS FUENTES OFICIALES Y SUS ENDPOINTS

Actualmente extraemos de dos portales del Estado mexicano:

### A. SIL (Sistema de Información Legislativa - SEGOB)
* **Base URL:** `http://sil.gobernacion.gob.mx`
* **Reporte de Integración (Listado):**  
  `http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php`
* **Ficha Individual Completa:**  
  `http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia={ID_SIL}`
* **Fotos Institucionales en Alta Resolución:**  
  `http://sil.gobernacion.gob.mx/Archivos/Fotos/{ID_SIL}.jpg`

### B. SITL (Cámara de Diputados)
* **Base URL:** `https://sitl.diputados.gob.mx/LXVI_leg/`
* **Votaciones Nominales del Pleno:**  
  `https://sitl.diputados.gob.mx/LXVI_leg/votaciones_por_periodonplxxvi.php`
* **Asistencias por Sesión:**  
  `https://sitl.diputados.gob.mx/LXVI_leg/asistencias_por_periodonplxxvi.php`

---

## 2. CÓMO EXTRAER CUALQUIER LEGISLATURA DEL SIL (Paso a Paso)

El SIL responde a peticiones **HTTP POST**. Para obtener la lista de legisladores de cualquier periodo (ej. LXVI = 66, LXV = 65, LXIV = 64):

### Parámetros POST requeridos:
```http
POST /Reportes/Integracion/HCongreso/ResultIntegHCongreso.php HTTP/1.1
Host: sil.gobernacion.gob.mx
Content-Type: application/x-www-form-urlencoded

CAMARA=1&LEGISLATURA=65&PARTIDO=0&PRIN_ELECCION=0&ENTIDAD=0
```

* `CAMARA`: `1` para Diputados, `2` para Senadores.
* `LEGISLATURA`: Número arábigo (`66`, `65`, `64`, `63`, hasta la `57`).
* `PARTIDO`: `0` para traer todos los partidos.
* `PRIN_ELECCION`: `0` para todos (Mayoría Relativa y Plurinominales).
* `ENTIDAD`: `0` para todo el país, o del `1` al `32` para un estado específico *(nota: para CDMX en SIL el ID es `33`)*.

---

## 3. CÓMO EJECUTAR EL SCRAPER NATIVO DEL PROYECTO

Ya no necesitas programar un extractor desde cero si usas nuestro motor en PHP. Puedes invocar el runner directamente desde tu consola:

### A. Extraer una Legislatura Completa a nivel Nacional:
```bash
# Extrae los 500 diputados y 128 senadores de la Legislatura 65 (2021–2024)
php bin/console.php ingest:nacional --legislatura=65

# O vía el gateway universal:
php tools/gateway.php scrape sil "nacional=1,legislatura=65"
```

### B. Extraer un solo Estado de una Legislatura específica:
```bash
# Extrae solo Jalisco (Entidad 14) de la Legislatura 64
php bin/console.php ingest:entidad 14 --legislatura=64
```

### C. Extraer Votaciones Nominales de la Cámara de Diputados:
```bash
php tools/gateway.php scrape votaciones "periodo=1,legislatura=66"
```

---

## 4. ESTRUCTURA DE CÓDIGO INTERNO

El proyecto tiene la lógica organizada en capas modulares:

```text
src/
├── Core/
│   ├── HttpClient.php       # cURL con reintentos exponenciales, jitter y rate-limit (1250ms)
│   ├── Logger.php           # Registro en JSONL de cada petición
│   └── EvidenceHasher.php   # Genera hash SHA-256 de cada HTML descargado
└── Services/
    ├── SilIngestService.php         # Parseo de tablas del SIL a DTOs tipados
    └── ParliamentScraperService.php # Parseo de actas de votaciones del SITL
```

---

## 5. REGLAS OBLIGATORIAS DE RESPETO A LOS SERVIDORES (Rate-Limiting)

Los servidores de gobierno (`sil.gobernacion.gob.mx`) corren sobre infraestructura antigua y cuentan con WAF (Web Application Firewall). 

Todo scraper que colabore en el proyecto **DEBE cumplir**:
1. **Pausa obligatoria entre peticiones**: Mínimo **1,250 ms** + jitter aleatorio de 100-250 ms.
2. **Caché local de HTML**: El motor primero guarda el HTML descargado en `storage/raw/html/`. Si el archivo ya existe en disco, **no vuelve a golpear el servidor de gobierno**.
3. **User-Agent identificable**: No ocultar el bot; declarar cabeceras estándar.
4. **Respaldo de evidencia**: Calcular `sha256` del HTML y guardar la `fuente_url` en la metadata del registro.

---

## 6. PLANTILLA PARA NUEVOS EXTRACTORES (Otros Portales / Congresos Locales)

Si vas a crear un extractor para un congreso local o portal nuevo:

```php
<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\DTO\LegisladorDTO;

class CongresoLocalScraperService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $storageDir
    ) {}

    public function extraer(): array
    {
        $url = 'https://congreso-local.gob.mx/diputados';
        $res = $this->http->request('GET', $url);
        
        $sha256 = EvidenceHasher::hashContent($res['body']);
        
        // Parsear con DOMDocument nativo (cero librerías externas)
        $dom = new \DOMDocument();
        @$dom->loadHTML($res['body']);
        $xpath = new \DOMXPath($dom);

        // Mapear cada diputado a LegisladorDTO y retornar
        return [];
    }
}
```

El resultado final se exporta en:  
`storage/data/json/congreso_{estado}_leg_{numero}.json`.
