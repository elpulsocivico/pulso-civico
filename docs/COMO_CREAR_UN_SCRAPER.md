# Cómo crear un scraper (nueva legislatura o fuente)

> 📖 **Manual Completo de la Suite de Scrapers:** Consulta **[`scrapers/README.md`](../scrapers/README.md)** para ver la guía detallada de uso del motor CLI (`bin/console.php` y `tools/gateway.php`), el listado de los 32 congresos estatales, lineamientos éticos (`robots.txt` y `rate_limit_ms`) y contratos de salida con hash SHA-256.

## 1. Antes de programar
1. Revisa [FUENTES.md](FUENTES.md) y abre un Issue **"Nueva fuente"** para reservarla.
2. Revisa `robots.txt` del portal.
3. Guarda 2-3 páginas de ejemplo (HTML) para probar sin golpear el servidor.

## 2. Estructura
Los scrapers actuales viven en `src/Services/`:
- `SilIngestService.php` — legisladores (SIL-SEGOB).
- `ParliamentScraperService.php` — votaciones y asistencias (SITL).

Se registran en `tools/core/ScraperRouter.php` (fuentes `sil`, `votaciones`, `asistencias`).

Para una fuente nueva:
1. Crea `src/Services/<Fuente>ScraperService.php` (< 400 líneas; divide en helpers si crece).
2. Usa **siempre** `PulsoCivico\Core\HttpClient` (ya trae reintentos, rate limit y logs). No uses `file_get_contents` sobre URLs.
3. Mapea los resultados a los DTOs de `src/DTO/` (ver [DATA_SCHEMA.md](DATA_SCHEMA.md)).
4. Guarda el resultado en `storage/data/json/<camara>_leg_<numero>.json`.
5. Registra un nuevo `case` en `ScraperRouter::run()`.

## 3. Esqueleto
```php
<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

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
        $response = $this->http->request('GET', $url);
        // 1. Parsear HTML con DOMDocument + DOMXPath (nativo)
        // 2. Construir LegisladorDTO por cada registro
        // 3. metadata: ['fuente_url' => $url, 'fecha_extraccion' => date('Y-m-d')]
        return [];
    }
}
```

## 4. Checklist del PR
- [ ] `php tools/gateway.php lint "<archivos>"` sin errores.
- [ ] Cada registro tiene `metadata.fuente_url` y `metadata.fecha_extraccion`.
- [ ] Rate limit ≥ 1000 ms; sin peticiones en paralelo.
- [ ] Sin datos personales no públicos.
- [ ] Fila actualizada en `docs/FUENTES.md`.

## 5. Prompt sugerido para tu IA
```
Lee AGENTS.md, docs/DATA_SCHEMA.md y src/Services/SilIngestService.php.
Crea un scraper en src/Services/ para <URL del portal> que extraiga <datos>
de la legislatura <N>, usando HttpClient y LegisladorDTO, PHP 8.1 con
strict_types, sin dependencias externas, parseando con DOMDocument/DOMXPath.
Incluye metadata.fuente_url y fecha_extraccion. Archivo < 400 líneas.
Registra la fuente en tools/core/ScraperRouter.php.
```
