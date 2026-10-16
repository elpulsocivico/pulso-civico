<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMXPath;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\DTO\AlcaldeDTO;

/**
 * Scraper especializado de la Enciclopedia de los Municipios y Delegaciones de México (INAFED / SEGOB).
 * Extrae la "Cronología de los Presidentes Municipales" para reconstruir el histórico profundo (1910 - 1988).
 */
class InafedScraperService
{
    private const BASE_URL = 'http://www.inafed.gob.mx/work/enciclopedia';
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public function __construct(
        HttpClient $http,
        Logger $logger,
        ?string $storagePath = null
    ) {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage';
    }

    /**
     * Extrae y procesa la cronología histórica de un municipio por su clave INEGI de 5 dígitos.
     *
     * @return array<string, mixed> Resultado del scraping
     */
    public function scrapeMunicipioHistorico(string $cvegeo): array
    {
        $cvegeo = sprintf('%05d', (int)$cvegeo);
        $cveEnt = (int)substr($cvegeo, 0, 2);
        $cveMun = (int)substr($cvegeo, 2, 3);
        $entidadStr = sprintf('%02d', $cveEnt);
        $munStr = sprintf('%03d', $cveMun);

        // URL estándar de las monografías municipales de la Enciclopedia INAFED
        $url = self::BASE_URL . "/EMM{$entidadStr}/municipios/{$entidadStr}{$munStr}a.html";

        $this->logger->info("Extrayendo histórico INAFED para municipio {$cvegeo}: {$url}");

        $response = $this->http->request('GET', $url);
        if ($response['status'] !== 200 || empty($response['body'])) {
            // Intentar formato alternativo de subcarpeta
            $altUrl = self::BASE_URL . "/EMM{$entidadStr}gob/municipios/{$entidadStr}{$munStr}a.html";
            $response = $this->http->request('GET', $altUrl);
            if ($response['status'] !== 200 || empty($response['body'])) {
                $this->logger->warning("No se localizó monografía INAFED para {$cvegeo} (HTTP {$response['status']})");
                return [
                    'ok' => false,
                    'cvegeo' => $cvegeo,
                    'error' => "HTTP {$response['status']} al consultar monografía INAFED",
                    'url' => $url,
                ];
            }
            $url = $altUrl;
        }

        $html = $response['body'];
        $cronologia = $this->parseCronologiaHtml($html, $cvegeo, $cveEnt, $cveMun, $url);

        if (empty($cronologia)) {
            return [
                'ok' => false,
                'cvegeo' => $cvegeo,
                'error' => 'No se detectó tabla de cronología de presidentes en la página',
                'url' => $url,
            ];
        }

        // Fusionar con el JSON histórico existente del municipio
        $mergeResult = $this->fusionarConHistoricoExistente($cvegeo, $cronologia);

        return [
            'ok' => true,
            'cvegeo' => $cvegeo,
            'total_alcaldes_inafed' => count($cronologia),
            'alcaldes_nuevos_incorporados' => $mergeResult['incorporados'],
            'total_historia_actualizada' => $mergeResult['total_acumulado'],
            'fuente_url' => $url,
        ];
    }

    /**
     * Parsea la tabla de cronología contenida en la monografía HTML.
     * @return array<AlcaldeDTO>
     */
    public function parseCronologiaHtml(
        string $html,
        string $cvegeo,
        int $cveEnt,
        int $cveMun,
        string $fuenteUrl
    ): array {
        $dom = new DOMDocument();
        // Suprimir warnings de HTML malformado clásico de portales de gobierno
        libxml_use_internal_errors(true);
        $encodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8, ISO-8859-1');
        @$dom->loadHTML($encodedHtml);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        // Buscar nodos de tabla cercanos al texto "Cronología de los Presidentes Municipales"
        $tablas = $xpath->query("//table[.//td[contains(translate(., 'CRONOLOGÍA', 'cronología'), 'presidente') or contains(translate(., 'PERÍODO', 'período'), 'periodo')]]");

        if ($tablas->length === 0) {
            // Búsqueda genérica por cualquier tabla con encabezados de presidentes
            $tablas = $xpath->query("//table[tr/td[contains(., 'Presidente') or contains(., 'Período')]]");
        }

        $alcaldes = [];
        if ($tablas->length === 0) {
            return $alcaldes;
        }

        /** @var \DOMElement $tabla */
        $tabla = $tablas->item(0);
        $filas = $xpath->query(".//tr", $tabla);

        foreach ($filas as $fila) {
            $celdas = $xpath->query(".//td", $fila);
            if ($celdas->length < 2) {
                continue;
            }

            $col0 = trim($celdas->item(0)->textContent);
            $col1 = trim($celdas->item(1)->textContent);
            $col2 = $celdas->length >= 3 ? trim($celdas->item(2)->textContent) : null;

            // Saltar fila de encabezado
            if (preg_match('/(presidente|nombre|autoridad)/ui', $col0) && preg_match('/(periodo|gesti[oó]n|año)/ui', $col1)) {
                continue;
            }

            // Normalizar cuál columna es el nombre y cuál es el periodo
            $nombre = $col0;
            $periodo = $col1;
            $partido = $col2;

            if (preg_match('/\d{4}/', $col0) && !preg_match('/\d{4}/', $col1)) {
                $periodo = $col0;
                $nombre = $col1;
            }

            // Extraer año inicio y fin
            preg_match_all('/\b(18\d{2}|19\d{2}|20\d{2})\b/', $periodo, $anosMatches);
            $anos = $anosMatches[0] ?? [];
            $anoInicio = !empty($anos[0]) ? (int)$anos[0] : 0;
            $anoFin = !empty($anos[1]) ? (int)$anos[1] : ($anoInicio > 0 ? $anoInicio + 3 : 0);

            if ($anoInicio === 0 || $nombre === '' || mb_strlen($nombre) < 3) {
                continue;
            }

            $alcaldes[] = new AlcaldeDTO(
                cvegeo: $cvegeo,
                entidad: '', // Se rellena en la fusión
                cveEnt: $cveEnt,
                municipio: '', // Se rellena en la fusión
                cveMun: $cveMun,
                nombreCompleto: mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8'),
                periodo: $periodo,
                anoInicio: $anoInicio,
                anoFin: $anoFin,
                partido: !empty($partido) ? strtoupper($partido) : null,
                esVigente: false,
                fuenteUrl: $fuenteUrl,
                fechaExtraccion: date('Y-m-d')
            );
        }

        return $alcaldes;
    }

    /**
     * Fusiona los registros antiguos scrapeados de INAFED con el archivo del municipio en disco.
     * @param array<AlcaldeDTO> $cronologiaInafed
     * @return array<string, int>
     */
    private function fusionarConHistoricoExistente(string $cvegeo, array $cronologiaInafed): array
    {
        $archivo = "{$this->storagePath}/data/json/municipios/{$cvegeo}.json";
        $existente = file_exists($archivo) ? json_decode((string)file_get_contents($archivo), true) : null;

        $cronologiaExistente = $existente['cronologia'] ?? [];
        $entidad = $existente['entidad'] ?? "Entidad " . substr($cvegeo, 0, 2);
        $municipio = $existente['municipio'] ?? "Municipio {$cvegeo}";

        // Mapear por año de inicio y nombre para no duplicar
        $mapExistentes = [];
        foreach ($cronologiaExistente as $item) {
            $key = ($item['ano_inicio'] ?? 0) . '_' . mb_strtolower(trim($item['nombre_completo'] ?? ''));
            $mapExistentes[$key] = true;
        }

        $incorporados = 0;
        $nuevos = [];
        foreach ($cronologiaInafed as $dto) {
            $key = $dto->anoInicio . '_' . mb_strtolower(trim($dto->nombreCompleto));
            if (!isset($mapExistentes[$key])) {
                $itemArray = $dto->toArray();
                $itemArray['entidad'] = $entidad;
                $itemArray['municipio'] = $municipio;
                $nuevos[] = $itemArray;
                $incorporados++;
            }
        }

        // Unir y ordenar cronológicamente
        $todos = array_merge($nuevos, $cronologiaExistente);
        usort($todos, static fn($a, $b) => ($a['ano_inicio'] ?? 0) <=> ($b['ano_inicio'] ?? 0));

        $alcaldeActual = $existente['alcalde_actual'] ?? (!empty($todos) ? end($todos) : null);

        $resultadoJson = [
            'cvegeo' => $cvegeo,
            'entidad' => $entidad,
            'cve_ent' => (int)substr($cvegeo, 0, 2),
            'municipio' => $municipio,
            'cve_mun' => (int)substr($cvegeo, 2, 3),
            'total_mandatos_registrados' => count($todos),
            'alcalde_actual' => $alcaldeActual,
            'cronologia' => $todos,
            'metadata' => [
                'fuentes' => ['INAFED Enciclopedia de Municipios', 'Bases Electorales Municipales / CIDAC / OPLEs'],
                'fecha_actualizacion' => date('Y-m-d H:i:s'),
            ],
        ];

        file_put_contents(
            $archivo,
            json_encode($resultadoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return [
            'incorporados' => $incorporados,
            'total_acumulado' => count($todos),
        ];
    }
}
