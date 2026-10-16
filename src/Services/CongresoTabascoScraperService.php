<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de Tabasco.
 * Cobertura: 10 legislaturas consecutivas (LVI a LXV, periodos 1998 a 2027).
 * Sede: Calle Independencia 204, Col. Centro, C.P. 86000, Villahermosa, Tabasco.
 */
class CongresoTabascoScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;
    private string $rawDir;

    private const LEGISLATURAS = [
        56 => ['romano' => 'LVI',   'inicio' => '1998-01-01', 'fin' => '2000-12-31', 'periodo' => '1998-2000'],
        57 => ['romano' => 'LVII',  'inicio' => '2001-01-01', 'fin' => '2003-12-31', 'periodo' => '2001-2003'],
        58 => ['romano' => 'LVIII', 'inicio' => '2004-01-01', 'fin' => '2006-12-31', 'periodo' => '2004-2006'],
        59 => ['romano' => 'LIX',   'inicio' => '2007-01-01', 'fin' => '2009-12-31', 'periodo' => '2007-2009'],
        60 => ['romano' => 'LX',    'inicio' => '2010-01-01', 'fin' => '2012-12-31', 'periodo' => '2010-2012'],
        61 => ['romano' => 'LXI',   'inicio' => '2013-01-01', 'fin' => '2015-12-31', 'periodo' => '2013-2015'],
        62 => ['romano' => 'LXII',  'inicio' => '2016-01-01', 'fin' => '2018-09-04', 'periodo' => '2016-2018'],
        63 => ['romano' => 'LXIII', 'inicio' => '2018-09-05', 'fin' => '2021-09-04', 'periodo' => '2018-2021'],
        64 => ['romano' => 'LXIV',  'inicio' => '2021-09-05', 'fin' => '2024-09-04', 'periodo' => '2021-2024'],
        65 => ['romano' => 'LXV',   'inicio' => '2024-09-05', 'fin' => '2027-09-04', 'periodo' => '2024-2027'],
    ];

    private const PARTIDOS_MAP = [
        'Partido Revolucionario Institucional' => 'PRI',
        'Partido Acción Nacional' => 'PAN',
        'Partido de la Revolución Democrática' => 'PRD',
        'Partido del Trabajo' => 'PT',
        'Partido Verde Ecologista de México' => 'PVEM',
        'Partido Nueva Alianza' => 'PANAL',
        'Movimiento Ciudadano' => 'MC',
        'Movimiento Regeneración Nacional' => 'MORENA',
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
        $this->rawDir = $this->storagePath . '/raw/tabasco';
        if (!is_dir($this->rawDir)) {
            mkdir($this->rawDir, 0755, true);
        }
    }

    /**
     * Extrae y consolida todas las legislaturas disponibles (LVI a LXV).
     */
    public function scrapeAll(): array
    {
        $this->logger->info("Iniciando consolidación integral de Tabasco (10 legislaturas LVI-LXV)...");
        $resultados = [];
        foreach (array_keys(self::LEGISLATURAS) as $legNumero) {
            $resultados[$legNumero] = $this->scrapeLegislatura($legNumero);
        }
        return $resultados;
    }

    /**
     * Extrae una legislatura específica de Tabasco.
     */
    public function scrapeLegislatura(int $numero): array
    {
        if (!isset(self::LEGISLATURAS[$numero])) {
            throw new \InvalidArgumentException("Legislatura de Tabasco {$numero} fuera de rango catalogado (56 a 65).");
        }

        $metaLeg = self::LEGISLATURAS[$numero];
        $romano = $metaLeg['romano'];
        $urlWikipedia = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Tabasco";
        $rawFile = "{$this->rawDir}/leg_{$numero}.html";

        $html = '';
        if (file_exists($rawFile) && filesize($rawFile) > 5000) {
            $html = (string)file_get_contents($rawFile);
        } else {
            $this->logger->info("Descargando contenido para Tabasco Leg {$numero} ({$romano})...");
            $html = $this->http->get($urlWikipedia);
            if (!empty($html)) {
                file_put_contents($rawFile, $html);
            }
        }

        if (empty($html)) {
            $this->logger->error("No se pudo obtener el documento HTML de Tabasco {$romano}.");
            return [];
        }

        $diputados = $this->parseHtmlLegislatura($html, $numero, $metaLeg, $urlWikipedia);
        $this->guardarDataset($numero, $diputados, $metaLeg);

        $this->logger->info("Tabasco Leg {$numero} ({$romano}): " . count($diputados) . " diputados consolidados.");
        return $diputados;
    }

    private function parseHtmlLegislatura(string $html, int $numero, array $metaLeg, string $fuenteUrl): array
    {
        preg_match_all("/<table\b[^>]*class=[\x22\x27][^\x22\x27]*wikitable[^\x22\x27]*[\x22\x27][^>]*>(.*?)<\/table>/is", $html, $matches);
        $registros = [];
        $fechaExtraccion = date('Y-m-d H:i:s');
        $contador = 0;

        foreach ($matches[0] as $tbl) {
            if (stripos($tbl, 'Mayoría relativa') !== false && stripos($tbl, 'Plurinominales') !== false) {
                continue; // Resumen de bancadas
            }

            preg_match("/<tr[^>]*>(.*?)<\/tr>/is", $tbl, $firstRow);
            $header = $this->cleanText($firstRow[1] ?? '');

            $isMR = (stripos($header, 'Distrito') !== false && stripos($header, 'Cabecera') !== false && stripos($header, 'Diputad') !== false);
            $isRP = false;
            if (stripos($header, 'Circunscripción') !== false && stripos($header, 'Diputad') !== false) {
                $isRP = true;
            } elseif (stripos($header, 'Diputad') !== false && stripos($header, 'Partido') !== false) {
                if (stripos($header, 'Distrito') === false && stripos($header, 'Cabecera') === false &&
                    stripos($header, 'Cargo') === false && stripos($header, 'Fracción') === false &&
                    stripos($header, 'Escaños') === false && stripos($header, 'Plurinominales') === false &&
                    stripos($header, 'sustituyó') === false && stripos($header, 'Inicio') === false) {
                    $isRP = true;
                }
            }

            if (!$isMR && !$isRP) {
                continue;
            }

            $hasCirc = (stripos($header, 'Circunscripción') !== false);
            preg_match_all("/<tr[^>]*>(.*?)<\/tr>/is", $tbl, $rows);
            $currDistrito = '';
            $currCabecera = '';
            $currCircunscripcion = '';

            foreach (array_slice($rows[1], 1) as $r) {
                preg_match_all("/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is", $r, $cells);
                $rawCells = $cells[1];
                $cleanCells = array_map([$this, 'cleanText'], $rawCells);

                if (empty($cleanCells)) {
                    continue;
                }

                $nombre = '';
                $partidoRaw = '';
                $distrito = null;
                $cabecera = null;
                $circunscripcion = null;
                $principio = '';
                $tipoCargo = 'Propietario';

                if ($isMR) {
                    if (count($cleanCells) >= 4) {
                        $currDistrito = $cleanCells[0];
                        $currCabecera = $cleanCells[1];
                        $nombre = $cleanCells[2];
                        $partidoRaw = end($cleanCells);
                        $principio = 'Mayoría Relativa';
                    } elseif (count($cleanCells) >= 2 && !empty($currDistrito)) {
                        // Suplente que asume el cargo con rowspan
                        $sinSmall = preg_replace("/<small\b[^>]*>.*?<\/small>/is", "", $rawCells[0]);
                        $nombre = $this->cleanText($sinSmall);
                        $partidoRaw = end($cleanCells);
                        $principio = 'Mayoría Relativa (Suplente)';
                        $tipoCargo = 'Suplente en Funciones';
                    }
                    $distrito = $currDistrito;
                    $cabecera = $currCabecera;
                } elseif ($isRP) {
                    $principio = 'Representación Proporcional';
                    if ($hasCirc && count($cleanCells) >= 3) {
                        $currCircunscripcion = $cleanCells[0];
                        $nombre = $cleanCells[1];
                        $partidoRaw = end($cleanCells);
                    } elseif ($hasCirc && count($cleanCells) == 2) {
                        $nombre = $cleanCells[0];
                        $partidoRaw = $cleanCells[1];
                    } else {
                        // Sin columna Circunscripción
                        $nombre = $cleanCells[0];
                        $partidoRaw = end($cleanCells);
                    }
                    $circunscripcion = !empty($currCircunscripcion) ? $currCircunscripcion : 'Circunscripción Estatal';
                }

                $nombre = preg_replace("/\s+/", " ", trim($nombre));
                if (empty($nombre) || strlen($nombre) < 4 || stripos($nombre, 'vacante') !== false) {
                    continue;
                }

                $contador++;
                $partidoNorm = self::PARTIDOS_MAP[$partidoRaw] ?? $partidoRaw;
                $slugId = sprintf('tab_leg%d_%02d', $numero, $contador);
                $hashEvidencia = hash('sha256', "Tabasco|{$metaLeg['romano']}|{$nombre}|{$partidoNorm}|{$slugId}");

                $registros[] = [
                    'referencia_sil' => $slugId,
                    'nombre_completo' => $nombre,
                    'camara' => 'Congreso del Estado de Tabasco',
                    'legislatura' => $numero,
                    'legislatura_romano' => $metaLeg['romano'],
                    'periodo_constitucional' => $metaLeg['periodo'],
                    'partido' => $partidoNorm,
                    'partido_original' => $partidoRaw,
                    'principio_eleccion' => $principio,
                    'entidad' => 'Tabasco',
                    'distrito' => $distrito,
                    'cabecera' => $cabecera,
                    'circunscripcion' => $circunscripcion,
                    'tipo_cargo' => $tipoCargo,
                    'fecha_toma_protesta' => $metaLeg['inicio'],
                    'fecha_conclusion' => $metaLeg['fin'],
                    'ubicacion_oficina' => 'Calle Independencia 204, Col. Centro, C.P. 86000, Villahermosa, Tabasco',
                    'telefono' => '(993) 312-3212',
                    'correo_institucional' => null,
                    'comisiones' => [],
                    'metadata' => [
                        'fuente_url' => $fuenteUrl,
                        'sha256_evidencia' => $hashEvidencia,
                        'fecha_extraccion' => $fechaExtraccion,
                        'entidad_tipo' => 'Congreso Local',
                    ],
                ];
            }
        }

        return $registros;
    }

    private function guardarDataset(int $numero, array $diputados, array $metaLeg): void
    {
        $jsonDir = $this->storagePath . '/data/json';
        if (!is_dir($jsonDir)) {
            mkdir($jsonDir, 0755, true);
        }

        $outFile = "{$jsonDir}/congreso_tabasco_leg_{$numero}.json";
        $data = [
            'entidad' => 'Tabasco',
            'camara' => 'H. Congreso del Estado de Tabasco',
            'sede' => 'Calle Independencia 204, Col. Centro, C.P. 86000, Villahermosa, Tabasco',
            'portal_oficial' => 'https://congresotabasco.gob.mx',
            'legislatura' => $numero,
            'legislatura_romano' => $metaLeg['romano'],
            'periodo_constitucional' => $metaLeg['periodo'],
            'fecha_inicio' => $metaLeg['inicio'],
            'fecha_fin' => $metaLeg['fin'],
            'total_curules' => count($diputados),
            'generado_el' => date('Y-m-d H:i:s'),
            'diputados' => $diputados,
        ];

        file_put_contents($outFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace("/<sup\b[^>]*>.*?<\/sup>/is", "", $text);
        $text = strip_tags($text);
        $text = preg_replace("/\[\d+\]/", "", $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\s+/", " ", $text);
        return trim($text);
    }
}
