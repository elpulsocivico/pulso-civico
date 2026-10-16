<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso de la Ciudad de México y la
 * Asamblea Legislativa del Distrito Federal (10 legislaturas consecutivas, 1997–2027).
 */
class CongresoCdmxScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public const SEDE_DIRECCION = 'Donceles y Allende s/n, Centro Histórico, Alcaldía Cuauhtémoc, C.P. 06010, Ciudad de México.';
    public const TEL_INSTITUCIONAL = '(55) 5130-1900';
    public const SITIO_OFICIAL = 'https://www.congresocdmx.gob.mx/';

    /**
     * Catálogo de legislaturas de la CDMX y ALDF.
     * [clave, tipo, num, romano, inicio, fin, nombre_organo, rpMax]
     */
    private const LEGISLATURAS = [
        'aldf_1' => ['aldf', 1, 'I', '1997-09-15', '2000-09-14', 'Asamblea Legislativa del Distrito Federal', 26],
        'aldf_2' => ['aldf', 2, 'II', '2000-09-15', '2003-09-14', 'Asamblea Legislativa del Distrito Federal', 26],
        'aldf_3' => ['aldf', 3, 'III', '2003-09-15', '2006-09-14', 'Asamblea Legislativa del Distrito Federal', 26],
        'aldf_4' => ['aldf', 4, 'IV', '2006-09-15', '2009-09-14', 'Asamblea Legislativa del Distrito Federal', 26],
        'aldf_5' => ['aldf', 5, 'V', '2009-09-15', '2012-09-14', 'Asamblea Legislativa del Distrito Federal', 26],
        'aldf_6' => ['aldf', 6, 'VI', '2012-09-15', '2015-09-14', 'Asamblea Legislativa del Distrito Federal', 26],
        'aldf_7' => ['aldf', 7, 'VII', '2015-09-15', '2018-09-14', 'Asamblea Legislativa del Distrito Federal', 26],
        'cdmx_1' => ['cdmx', 1, 'I', '2018-09-15', '2021-09-14', 'Congreso de la Ciudad de México', 33],
        'cdmx_2' => ['cdmx', 2, 'II', '2021-09-15', '2024-09-14', 'Congreso de la Ciudad de México', 32],
        'cdmx_3' => ['cdmx', 3, 'III', '2024-09-15', '2027-09-14', 'Congreso de la Ciudad de México', 32],
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    /**
     * Procesa y genera los datasets JSON de todas las legislaturas de la Ciudad de México y ALDF.
     */
    public function scrapeAll(): array
    {
        $this->logger->info("Iniciando consolidación de la Ciudad de México (10 legislaturas ALDF y Congreso CDMX)...");
        $resultados = [];
        $totalGeneral = 0;

        foreach (array_keys(self::LEGISLATURAS) as $key) {
            $res = $this->scrapeLegislaturaKey($key);
            $resultados[$key] = $res;
            $totalGeneral += $res['total_legisladores'] ?? 0;
        }

        return [
            'ok' => true,
            'estado' => 'Ciudad de México',
            'legislaturas_procesadas' => count($resultados),
            'total_general_diputados' => $totalGeneral,
            'detalle' => $resultados,
        ];
    }

    /**
     * Procesa una legislatura por clave (ej: 'cdmx_3' o 'aldf_7').
     */
    public function scrapeLegislaturaKey(string $key): array
    {
        if (!isset(self::LEGISLATURAS[$key])) {
            throw new \InvalidArgumentException("Clave de legislatura '{$key}' no configurada para CDMX.");
        }

        [$tipo, $numero, $romano, $inicio, $fin, $camaraNom, $rpMax] = self::LEGISLATURAS[$key];
        $html = $this->getHtmlLegislatura($tipo, $numero, $romano);
        $fuente = $tipo === 'aldf'
            ? "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_de_la_Asamblea_Legislativa_del_Distrito_Federal"
            : "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_de_la_Ciudad_de_M%C3%A9xico";

        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($doc);

        $tables = $xpath->query('//table');
        $tMR = null;
        $tRP = null;

        foreach ($tables as $t) {
            $r0 = $this->cleanText($xpath->query('.//tr[1]', $t)->item(0)->textContent ?? '');
            if (str_contains($r0, 'Partido Diputados') || str_contains($r0, 'Partidos Diputados')
                || str_contains($r0, 'Control de autoridades') || str_contains($r0, 'Predecesor:')
                || str_contains($r0, 'Periodo Duración') || str_contains($r0, 'Localización')
                || str_contains($r0, 'Partido Coordinador') || str_contains($r0, 'Partido Diputado Posición')
                || str_contains($r0, 'III Legislatura del Congreso')) {
                continue;
            }
            if (str_contains($r0, 'Distrito') && $tMR === null) {
                $tMR = $t;
            } elseif ((str_contains($r0, 'Diputado') || str_contains($r0, 'Nombre')) && $tMR !== null && $tRP === null) {
                $tRP = $t;
            }
        }

        $fechaExtraccion = date('Y-m-d H:i:s');
        $fechaCorta = date('Y-m-d');
        $legisladores = [];
        $idx = 1;
        $distritosVistos = [];

        // Mayoría Relativa
        if ($tMR) {
            $rows = $xpath->query('.//tr', $tMR);
            $header = $this->cleanText($rows->item(0)->textContent ?? '');
            $hasCabecera = str_contains($header, 'Cabecera');

            for ($i = 1; $i < $rows->length; $i++) {
                $r = $rows->item($i);
                $cells = $xpath->query('.//th|.//td', $r);
                if ($cells->length < 2) continue;

                $distritoRaw = $this->cleanText($cells->item(0)->textContent ?? '');
                if (!$this->isValidDistrict($distritoRaw) || isset($distritosVistos[$distritoRaw])) {
                    continue;
                }

                $cabecera = $hasCabecera && $cells->length >= 3 ? $this->cleanText($cells->item(1)->textContent ?? '') : null;
                $diputado = $hasCabecera && $cells->length >= 3 ? $this->cleanText($cells->item(2)->textContent ?? '') : $this->cleanText($cells->item(1)->textContent ?? '');
                if (!$this->isValidDeputyName($diputado)) continue;

                $suplente = null;
                if (str_contains($header, 'Suplente') && $cells->length >= 4) {
                    $suplente = $this->cleanText($cells->item(3)->textContent ?? '');
                } elseif (preg_match('/\s+(?:suple a|sustituido por|suplido por)\s+(.+)$/ui', $diputado, $sm)) {
                    $suplente = trim($sm[1]);
                    $diputado = trim(preg_replace('/\s+(?:suple a|sustituido por|suplido por)\s+.+$/ui', '', $diputado), " ,;\t\n\r");
                }
                $diputado = trim(preg_replace('/\s*\(\d{4}[^)]*\)/', '', $diputado));

                $distritosVistos[$distritoRaw] = true;
                $partido = $this->detectParty($r, $xpath);
                $slugId = sprintf('cdmx_%s_leg%02d_%02d', $tipo, $numero, $idx);
                $hash = hash('sha256', "{$diputado}|{$partido}|{$distritoRaw}|{$key}|{$fechaCorta}");

                $legisladores[] = $this->buildRecord(
                    $slugId, $diputado, $camaraNom, $numero, $romano, $partido,
                    'Mayoría Relativa', $distritoRaw, $cabecera, $suplente,
                    $inicio, $fuente, $hash, $fechaExtraccion
                );
                $idx++;
            }
        }

        // Representación Proporcional
        if ($tRP) {
            $rows = $xpath->query('.//tr', $tRP);
            $rpNum = 1;

            for ($i = 1; $i < $rows->length; $i++) {
                if ($rpNum > $rpMax) break;
                $r = $rows->item($i);
                $cells = $xpath->query('.//th|.//td', $r);
                if ($cells->length < 2) continue;

                $diputado = $this->cleanText($cells->item(0)->textContent ?? '');
                if (!$this->isValidDeputyName($diputado)) continue;

                $suplente = null;
                if (preg_match('/\s+(?:suple a|sustituido por|suplido por)\s+(.+)$/ui', $diputado, $sm)) {
                    $suplente = trim($sm[1]);
                    $diputado = trim(preg_replace('/\s+(?:suple a|sustituido por|suplido por)\s+.+$/ui', '', $diputado), " ,;\t\n\r");
                }
                $diputado = trim(preg_replace('/\s*\(\d{4}[^)]*\)/', '', $diputado));

                $distrito = "RP {$rpNum}";
                $partido = $this->detectParty($r, $xpath);
                $slugId = sprintf('cdmx_%s_leg%02d_%02d', $tipo, $numero, $idx);
                $hash = hash('sha256', "{$diputado}|{$partido}|{$distrito}|{$key}|{$fechaCorta}");

                $legisladores[] = $this->buildRecord(
                    $slugId, $diputado, $camaraNom, $numero, $romano, $partido,
                    'Representación Proporcional', $distrito, null, $suplente,
                    $inicio, $fuente, $hash, $fechaExtraccion
                );
                $idx++;
                $rpNum++;
            }
        }

        $dataset = [
            'metadata' => [
                'titulo' => "{$camaraNom} - {$romano} Legislatura ({$inicio} a {$fin})",
                'entidad' => 'Ciudad de México',
                'camara' => $camaraNom,
                'organo_tipo' => $tipo === 'aldf' ? 'Asamblea Legislativa' : 'Congreso de la Ciudad de México',
                'legislatura' => $numero,
                'legislatura_romano' => $romano,
                'periodo' => substr($inicio, 0, 4) . '-' . substr($fin, 0, 4),
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'generado_en' => date('c'),
                'fuente_url' => $fuente,
                'sitio_oficial' => self::SITIO_OFICIAL,
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        $targetDir = $this->storagePath . '/data/json';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $fileName = $tipo === 'aldf' ? "congreso_cdmx_aldf_leg_{$numero}.json" : "congreso_cdmx_leg_{$numero}.json";
        $targetFile = "{$targetDir}/{$fileName}";
        file_put_contents($targetFile, json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->logger->info("Generado dataset {$camaraNom} {$romano}: {$targetFile} (" . count($legisladores) . " diputados)");

        return [
            'ok' => true,
            'key' => $key,
            'camara' => $camaraNom,
            'legislatura' => $numero,
            'romano' => $romano,
            'total_legisladores' => count($legisladores),
            'archivo' => $targetFile,
        ];
    }

    private function buildRecord(
        string $slugId, string $diputado, string $camaraNom, int $numero, string $romano,
        string $partido, string $principio, string $distrito, ?string $cabecera, ?string $suplente,
        string $inicio, string $fuente, string $hash, string $fechaExtraccion
    ): array {
        return [
            'referencia_sil' => $slugId,
            'nombre_completo' => $diputado,
            'camara' => $camaraNom,
            'legislatura' => $numero,
            'legislatura_romano' => $romano,
            'partido' => $partido,
            'principio_eleccion' => $principio,
            'entidad' => 'Ciudad de México',
            'distrito' => $distrito,
            'cabecera' => $cabecera,
            'tipo_cargo' => 'Diputado Local Propietario',
            'suplente_nombre' => $suplente,
            'fecha_toma_protesta' => $inicio,
            'fecha_nacimiento' => null,
            'correo_institucional' => null,
            'telefono' => self::TEL_INSTITUCIONAL,
            'ubicacion_oficina' => self::SEDE_DIRECCION,
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
                'fuente_url' => $fuente,
                'sha256_evidencia' => $hash,
                'fecha_extraccion' => $fechaExtraccion,
                'entidad_tipo' => 'Congreso Local',
            ],
        ];
    }

    private function getHtmlLegislatura(string $tipo, int $numero, string $romano): string
    {
        $rawDir = $this->storagePath . '/raw/cdmx';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0755, true);
        }
        $rawFile = "{$rawDir}/{$tipo}_leg_{$numero}.html";
        if (file_exists($rawFile) && filesize($rawFile) > 5000) {
            return (string)file_get_contents($rawFile);
        }

        $url = $tipo === 'aldf'
            ? "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_de_la_Asamblea_Legislativa_del_Distrito_Federal"
            : "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_de_la_Ciudad_de_M%C3%A9xico";

        $res = $this->http->request('GET', $url, [
            'headers' => ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'],
        ]);
        $html = (string)($res['body'] ?? '');
        if (strlen($html) > 5000) {
            file_put_contents($rawFile, $html);
        }
        return $html;
    }

    private function isValidDistrict(string $s): bool
    {
        return (bool)preg_match('/^\s*([IVXLCDM]+|\d{1,2})[°\.]?\s*$/i', $s);
    }

    private function isValidDeputyName(string $s): bool
    {
        if ($s === '') return false;
        if (preg_match('/^(Diputado|Nombre|Distrito|Cabecera|Partido|Sin partido|Propietario|Suplente)/ui', $s)) return false;
        if (preg_match('/^\d+/', $s)) return false;
        return (bool)preg_match('/[a-záéíóúñ]{3,}/ui', $s);
    }

    private function detectParty(DOMElement $tr, DOMXPath $xpath): string
    {
        $links = $xpath->query('.//a', $tr);
        foreach ($links as $l) {
            $t = (string)$l->getAttribute('title');
            $h = (string)$l->getAttribute('href');
            if (preg_match('/(Partido|Morena|Nueva Alianza|Movimiento Ciudadano|Verde|PRD|PAN|PRI|PT|Encuentro)/i', "{$t} {$h}")) {
                $cleaned = preg_replace('/\s*\([^)]*\)/', '', $t !== '' ? $t : $h) ?? '';
                return $this->normalizeParty($cleaned);
            }
        }
        $imgs = $xpath->query('.//img', $tr);
        foreach ($imgs as $img) {
            $src = $img->getAttribute('src') . ' ' . $img->getAttribute('resource');
            if (preg_match('/(PRI|PAN|PRD|PT|PVEM|Morena|PNA|PANAL|MC|PES)/i', $src, $im)) {
                return $this->normalizeParty($im[1]);
            }
        }
        $tds = $xpath->query('.//td', $tr);
        if ($tds->length > 0) {
            $txt = $this->cleanText($tds->item($tds->length - 1)->textContent ?? '');
            if ($txt !== '') {
                return $this->normalizeParty($txt);
            }
        }
        return 'Sin partido';
    }

    private function normalizeParty(string $raw): string
    {
        $r = mb_strtoupper($raw, 'UTF-8');
        if (str_contains($r, 'REGENERACIÓN') || str_contains($r, 'MORENA')) return 'MORENA';
        if (str_contains($r, 'ACCIÓN NACIONAL') || str_contains($r, 'PAN')) return 'PAN';
        if (str_contains($r, 'REVOLUCIONARIO INSTITUCIONAL') || str_contains($r, 'PRI')) return 'PRI';
        if (str_contains($r, 'REVOLUCIÓN DEMOCRÁTICA') || str_contains($r, 'PRD')) return 'PRD';
        if (str_contains($r, 'VERDE') || str_contains($r, 'PVEM')) return 'PVEM';
        if (str_contains($r, 'DEL TRABAJO') || preg_match('/\bPT\b/', $r)) return 'PT';
        if (str_contains($r, 'MOVIMIENTO CIUDADANO') || preg_match('/\bMC\b/', $r)) return 'MC';
        if (str_contains($r, 'NUEVA ALIANZA') || str_contains($r, 'PNA') || str_contains($r, 'PANAL')) return 'PNA';
        if (str_contains($r, 'ENCUENTRO') || str_contains($r, 'PES')) return 'PES';
        if (str_contains($r, 'REDES SOCIALES') || str_contains($r, 'RSP')) return 'RSP';
        if (str_contains($r, 'FUERZA') || str_contains($r, 'FXM')) return 'FXM';
        if (str_contains($r, 'ASOCIACIÓN PARLAMENTARIA')) return 'Asociación Parlamentaria';
        if (str_contains($r, 'INDEPENDIENTE') || str_contains($r, 'SIN PARTIDO')) return 'Sin partido';
        return $raw !== '' ? $raw : 'Sin partido';
    }

    private function cleanText(string $text): string
    {
        $text = (string)preg_replace('/\[[^\]]*\]/', '', $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string)preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
