<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador del H. Congreso del Estado de Jalisco.
 * Procesa 10 legislaturas consecutivas (LV a LXIV, 1998-2027).
 * Integra 391 diputaciones con trazabilidad SHA-256 y normalización de MR y RP.
 */
class CongresoJaliscoScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    private const PERIODOS = [
        55 => ['rom' => 'LV', 'inicio' => '1998-02-01', 'fin' => '2001-01-31', 'years' => '1998-2001'],
        56 => ['rom' => 'LVI', 'inicio' => '2001-02-01', 'fin' => '2004-01-31', 'years' => '2001-2004'],
        57 => ['rom' => 'LVII', 'inicio' => '2004-02-01', 'fin' => '2007-01-31', 'years' => '2004-2007'],
        58 => ['rom' => 'LVIII', 'inicio' => '2007-02-01', 'fin' => '2010-01-31', 'years' => '2007-2010'],
        59 => ['rom' => 'LIX', 'inicio' => '2010-02-01', 'fin' => '2012-10-31', 'years' => '2010-2012'],
        60 => ['rom' => 'LX', 'inicio' => '2012-11-01', 'fin' => '2015-10-31', 'years' => '2012-2015'],
        61 => ['rom' => 'LXI', 'inicio' => '2015-11-01', 'fin' => '2018-10-31', 'years' => '2015-2018'],
        62 => ['rom' => 'LXII', 'inicio' => '2018-11-01', 'fin' => '2021-10-31', 'years' => '2018-2021'],
        63 => ['rom' => 'LXIII', 'inicio' => '2021-11-01', 'fin' => '2024-10-31', 'years' => '2021-2024'],
        64 => ['rom' => 'LXIV', 'inicio' => '2024-11-01', 'fin' => '2027-10-31', 'years' => '2024-2027'],
    ];

    private const PARTIDOS_MAP = [
        'partido acción nacional' => 'PAN', 'partido revolucionario institucional' => 'PRI',
        'partido de la revolución democrática' => 'PRD', 'partido verde ecologista de méxico' => 'PVEM',
        'partido del trabajo' => 'PT', 'movimiento ciudadano' => 'MC',
        'movimiento regeneración nacional' => 'MORENA', 'nueva alianza' => 'PANAL',
        'futuro' => 'FUTURO', 'hagamos' => 'HAGAMOS',
        'candidato independiente' => 'IND', 'independiente' => 'IND', 'sin partido' => 'IND',
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    /**
     * Ejecuta el raspado y consolidación de todas las legislaturas (55 a 64).
     */
    public function scrapeAll(): array
    {
        $inicio = microtime(true);
        $resultados = [];
        $totalGeneral = 0;

        foreach (array_keys(self::PERIODOS) as $leg) {
            $res = $this->scrapeLegislatura((int)$leg);
            $totalLeg = count($res['legisladores'] ?? []);
            $totalGeneral += $totalLeg;
            $resultados["leg_{$leg}"] = [
                'legislatura' => $leg,
                'romano' => self::PERIODOS[$leg]['rom'],
                'total_legisladores' => $totalLeg,
                'archivo' => $res['archivo'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'source' => 'congreso_jalisco',
            'entidad' => 'Jalisco',
            'legislaturas_procesadas' => count($resultados),
            'total_legisladores' => $totalGeneral,
            'duracion_segundos' => round(microtime(true) - $inicio, 2),
            'detalle' => $resultados,
        ];
    }

    /**
     * Procesa y genera el dataset de una legislatura individual.
     */
    public function scrapeLegislatura(int $leg): array
    {
        if (!isset(self::PERIODOS[$leg])) {
            throw new \InvalidArgumentException("Legislatura {$leg} de Jalisco no soportada.");
        }

        $meta = self::PERIODOS[$leg];
        $html = $this->getHtmlLegislatura($leg);
        $fuenteUrl = "https://es.wikipedia.org/wiki/Anexo:{$meta['rom']}_Legislatura_del_Congreso_del_Estado_de_Jalisco";

        $legisladores = $this->parseTables($html, $leg, $meta, $fuenteUrl);

        $dataset = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Jalisco - {$meta['rom']} Legislatura ({$meta['years']})",
                'entidad' => 'Jalisco',
                'camara' => 'Congreso Local',
                'legislatura' => $leg,
                'periodo' => $meta['years'],
                'generado_en' => date('c'),
                'fuente_url' => $leg === 64 ? 'https://www.congresojal.gob.mx/' : $fuenteUrl,
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        $jsonDir = $this->storagePath . '/data/json';
        if (!is_dir($jsonDir)) {
            mkdir($jsonDir, 0777, true);
        }

        $jsonFile = "{$jsonDir}/congreso_jalisco_leg_{$leg}.json";
        file_put_contents($jsonFile, json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->logger->info("Dataset guardado: {$jsonFile} (" . count($legisladores) . " legisladores)");

        return [
            'legislatura' => $leg,
            'archivo' => $jsonFile,
            'legisladores' => $legisladores,
        ];
    }

    private function parseTables(string $html, int $leg, array $meta, string $fuenteUrl): array
    {
        preg_match_all("/<table[^>]*wikitable[^>]*>(.*?)<\/table>/si", $html, $matches);
        if (count($matches[0]) < 3) {
            $this->logger->warning("Tablas insuficientes en leg {$leg} de Jalisco.");
            return [];
        }

        $tableMR = $matches[0][1];
        $tableRP = $matches[0][2];
        $fechaCorta = date('Y-m-d');
        $legisladores = [];
        $correlativo = 1;

        // 1. Mayoría Relativa (20 distritos)
        preg_match_all("/<tr[^>]*>(.*?)<\/tr>/si", $tableMR, $rowsMR);
        if (!empty($rowsMR[0])) {
            $hMR = $this->getHeaderColumns($rowsMR[0][0]);
            $hasCabecera = in_array('cabecera', $hMR, true);
            $hasSuplente = in_array('suplente', $hMR, true);

            for ($i = 1; $i < count($rowsMR[0]); $i++) {
                $cells = $this->extractRowCells($rowsMR[0][$i]);
                if (count($cells) < 3) continue;

                $distritoRaw = $cells[0] ?? '';
                $cabecera = '';
                $suplente = null;

                if ($hasCabecera && $hasSuplente && count($cells) >= 5) {
                    $cabecera = $cells[1];
                    $nombre = $cells[2];
                    $suplente = $cells[3];
                    $partidoRaw = $cells[4];
                } elseif ($hasCabecera && !$hasSuplente && count($cells) >= 4) {
                    $cabecera = $cells[1];
                    $nombre = $cells[2];
                    $partidoRaw = $cells[3];
                } elseif (!$hasCabecera && $hasSuplente && count($cells) >= 4) {
                    $nombre = $cells[1];
                    $suplente = $cells[2];
                    $partidoRaw = $cells[3];
                } else {
                    $nombre = $cells[1];
                    $partidoRaw = $cells[2] ?? end($cells);
                }

                $distritoLabel = $this->formatDistrito($distritoRaw, $cabecera);
                $partido = $this->normalizarPartido($partidoRaw);
                $slug = 'jal_leg' . $leg . '_' . str_pad((string)$correlativo, 2, '0', STR_PAD_LEFT);
                $hash = hash('sha256', "{$nombre}|{$partido}|MR|{$distritoLabel}|{$leg}|{$fechaCorta}");

                $legisladores[] = $this->buildRecord(
                    $slug, $nombre, $leg, $partido, 'Mayoría Relativa',
                    $distritoLabel, $suplente, $meta['inicio'], $fuenteUrl, $hash
                );
                $correlativo++;
            }
        }

        // 2. Representación Proporcional
        preg_match_all("/<tr[^>]*>(.*?)<\/tr>/si", $tableRP, $rowsRP);
        if (!empty($rowsRP[0])) {
            $hRP = $this->getHeaderColumns($rowsRP[0][0]);
            $hasSuplenteRP = in_array('suplente', $hRP, true);

            for ($i = 1; $i < count($rowsRP[0]); $i++) {
                $cells = $this->extractRowCells($rowsRP[0][$i]);
                if (count($cells) < 2) continue;

                $suplente = null;
                if ($hasSuplenteRP && count($cells) >= 3) {
                    $nombre = $cells[0];
                    $suplente = $cells[1];
                    $partidoRaw = $cells[2];
                } else {
                    $nombre = $cells[0];
                    $partidoRaw = $cells[1];
                }

                $partido = $this->normalizarPartido($partidoRaw);
                $slug = 'jal_leg' . $leg . '_' . str_pad((string)$correlativo, 2, '0', STR_PAD_LEFT);
                $hash = hash('sha256', "{$nombre}|{$partido}|RP|{$leg}|{$fechaCorta}");

                $legisladores[] = $this->buildRecord(
                    $slug, $nombre, $leg, $partido, 'Representación Proporcional',
                    null, $suplente, $meta['inicio'], $fuenteUrl, $hash
                );
                $correlativo++;
            }
        }

        return $legisladores;
    }

    private function getHeaderColumns(string $rowHtml): array
    {
        preg_match_all("/<th[^>]*>(.*?)<\/th>/si", $rowHtml, $ths);
        $headers = [];
        foreach ($ths[1] as $th) {
            $clean = mb_strtolower($this->cleanText($th), 'UTF-8');
            if (str_contains($clean, 'distrito')) $headers[] = 'distrito';
            elseif (str_contains($clean, 'cabecera')) $headers[] = 'cabecera';
            elseif (str_contains($clean, 'suplente')) $headers[] = 'suplente';
            elseif (str_contains($clean, 'partido')) $headers[] = 'partido';
            elseif (str_contains($clean, 'diputad')) $headers[] = 'diputado';
        }
        return $headers;
    }

    private function extractRowCells(string $rowHtml): array
    {
        preg_match_all("/<td[^>]*>(.*?)<\/td>/si", $rowHtml, $tds);
        $cells = [];
        foreach ($tds[1] as $td) {
            $t = $this->cleanText($td);
            if ($t !== '') {
                $cells[] = $t;
            }
        }
        return $cells;
    }

    private function formatDistrito(string $distritoRaw, string $cabecera = ''): string
    {
        $d = trim(preg_replace('/^distrito\s*/i', '', $distritoRaw));
        $label = "Distrito {$d}";
        if ($cabecera !== '') {
            $label .= " ({$cabecera})";
        }
        return $label;
    }

    private function normalizarPartido(string $p): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $p));
        $low = mb_strtolower($clean, 'UTF-8');
        foreach (self::PARTIDOS_MAP as $needle => $sigla) {
            if (str_contains($low, $needle)) {
                return $sigla;
            }
        }
        return $clean;
    }

    private function cleanText(string $html): string
    {
        $t = preg_replace('/<sup[^>]*>.*?<\/sup>/si', '', $html);
        $t = strip_tags($t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    private function buildRecord(
        string $slug,
        string $nombre,
        int $leg,
        string $partido,
        string $principio,
        ?string $distrito,
        ?string $suplente,
        string $tomaProtesta,
        string $fuenteUrl,
        string $hashEvidencia
    ): array {
        return [
            'referencia_sil' => $slug,
            'nombre_completo' => 'Dip. ' . $nombre,
            'camara' => 'Congreso del Estado de Jalisco',
            'legislatura' => $leg,
            'partido' => $partido,
            'principio_eleccion' => $principio,
            'entidad' => 'Jalisco',
            'distrito' => $distrito,
            'tipo_cargo' => 'Propietario',
            'suplente_nombre' => $suplente,
            'fecha_toma_protesta' => $tomaProtesta,
            'fecha_nacimiento' => null,
            'correo_institucional' => null,
            'telefono' => $leg === 64 ? '(33) 3679-1500' : null,
            'ubicacion_oficina' => 'Palacio Legislativo, Av. Hidalgo No. 222, Centro, Guadalajara, Jal.',
            'ultimo_grado_estudios' => null,
            'preparacion_academica' => null,
            'experiencia_legislativa_resumen' => null,
            'foto_oficial_url' => null,
            'foto_local_path' => null,
            'total_comisiones' => 0,
            'comisiones' => [],
            'trayectoria_politica' => [],
            'trayectoria_legislativa' => [],
            'trayectoria_administrativa' => [],
            'escolaridad_detalle' => [],
            'licencias' => [],
            'iniciativas' => [],
            'puntos_acuerdo' => [],
            'redes_sociales' => [],
            'metadata' => [
                'fuente_url' => $fuenteUrl,
                'sha256_evidencia' => $hashEvidencia,
                'fecha_extraccion' => date('Y-m-d H:i:s'),
                'entidad_tipo' => 'Congreso Local',
            ],
        ];
    }

    private function getHtmlLegislatura(int $leg): string
    {
        $rawDir = $this->storagePath . '/raw/jalisco';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }

        $cacheFile = "{$rawDir}/leg_{$leg}.html";
        if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
            return (string)file_get_contents($cacheFile);
        }

        $meta = self::PERIODOS[$leg];
        $title = "Anexo:{$meta['rom']}_Legislatura_del_Congreso_del_Estado_de_Jalisco";
        $url = "https://es.wikipedia.org/w/api.php?action=parse&page=" . urlencode($title) . "&prop=text&format=json";

        $html = '';
        for ($i = 0; $i < 3; $i++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PulsoCivicoBot/1.0 (https://pulsocivico.org; contacto@pulsocivico.org)');
            $raw = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 200 && is_string($raw)) {
                $res = json_decode($raw, true);
                $html = $res['parse']['text']['*'] ?? '';
                if ($html !== '') {
                    file_put_contents($cacheFile, $html);
                    break;
                }
            }
            sleep(2);
        }

        return $html;
    }
}
