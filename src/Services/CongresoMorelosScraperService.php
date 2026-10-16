<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMNode;
use DOMXPath;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de Morelos.
 * Cobertura: 10 legislaturas consecutivas (XLVII a LVI, periodos 1997-2027, 30 años).
 */
class CongresoMorelosScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public const PORTAL_OFICIAL = 'https://congresomorelos.gob.mx/';
    public const SEDE_CONGRESO = 'Calle Guillermo Gándara No. 101, Col. Amatitlán, C.P. 62410, Cuernavaca, Morelos';
    public const TEL_INSTITUCIONAL = '(777) 362-1100';

    private const LEGISLATURAS = [
        47 => ['romano' => 'XLVII', 'inicio' => '1997-09-01', 'fin' => '2000-08-31', 'periodo' => '1997-2000', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:XLVII_Legislatura_del_Congreso_de_Morelos'],
        48 => ['romano' => 'XLVIII', 'inicio' => '2000-09-01', 'fin' => '2003-08-31', 'periodo' => '2000-2003', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:XLVIII_Legislatura_del_Congreso_de_Morelos'],
        49 => ['romano' => 'XLIX', 'inicio' => '2003-09-01', 'fin' => '2006-08-31', 'periodo' => '2003-2006', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:XLIX_Legislatura_del_Congreso_de_Morelos'],
        50 => ['romano' => 'L', 'inicio' => '2006-09-01', 'fin' => '2009-08-31', 'periodo' => '2006-2009', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:L_Legislatura_del_Congreso_de_Morelos'],
        51 => ['romano' => 'LI', 'inicio' => '2009-09-01', 'fin' => '2012-08-31', 'periodo' => '2009-2012', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LI_Legislatura_del_Congreso_de_Morelos'],
        52 => ['romano' => 'LII', 'inicio' => '2012-09-01', 'fin' => '2015-08-31', 'periodo' => '2012-2015', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LII_Legislatura_del_Congreso_de_Morelos'],
        53 => ['romano' => 'LIII', 'inicio' => '2015-09-01', 'fin' => '2018-08-31', 'periodo' => '2015-2018', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LIII_Legislatura_del_Congreso_de_Morelos'],
        54 => ['romano' => 'LIV', 'inicio' => '2018-09-01', 'fin' => '2021-08-31', 'periodo' => '2018-2021', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LIV_Legislatura_del_Congreso_de_Morelos'],
        55 => ['romano' => 'LV', 'inicio' => '2021-09-01', 'fin' => '2024-08-31', 'periodo' => '2021-2024', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LV_Legislatura_del_Congreso_del_Estado_de_Morelos'],
        56 => ['romano' => 'LVI', 'inicio' => '2024-09-01', 'fin' => '2027-08-31', 'periodo' => '2024-2027', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LVI_Legislatura_del_Congreso_del_Estado_de_Morelos'],
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    public function scrapeAll(): array
    {
        $this->logger->info("Iniciando consolidación total del H. Congreso del Estado de Morelos (Leg XLVII a LVI)...");
        $resultados = [];
        $totalDiputados = 0;

        foreach (array_keys(self::LEGISLATURAS) as $leg) {
            $res = $this->scrapeLegislatura($leg);
            $resultados[$leg] = $res;
            if (!empty($res['total_legisladores'])) {
                $totalDiputados += $res['total_legisladores'];
            }
        }

        return [
            'ok' => true,
            'entidad' => 'Morelos',
            'camara' => 'Congreso del Estado de Morelos',
            'legislaturas_procesadas' => count($resultados),
            'total_diputados' => $totalDiputados,
            'detalle' => $resultados,
        ];
    }

    public function scrapeLegislatura(int $leg): array
    {
        if (!isset(self::LEGISLATURAS[$leg])) {
            return ['ok' => false, 'error' => "Legislatura {$leg} no configurada para Morelos."];
        }

        $cfg = self::LEGISLATURAS[$leg];
        $this->logger->info("Extrayendo Morelos Leg {$leg} ({$cfg['romano']}) [{$cfg['periodo']}]...");

        $rawDir = $this->storagePath . '/raw/morelos';
        $rawFile = $rawDir . "/leg_{$leg}.html";
        if (file_exists($rawFile)) {
            $html = file_get_contents($rawFile);
        } else {
            $resp = $this->http->get($cfg['wiki']);
            if ($resp['status'] !== 200) {
                return ['ok' => false, 'error' => "Error HTTP {$resp['status']} descargando Leg {$leg}."];
            }
            $html = $resp['body'];
            if (!is_dir($rawDir)) {
                mkdir($rawDir, 0755, true);
            }
            file_put_contents($rawFile, $html);
        }

        $dom = new DOMDocument();
        @$dom->loadHTML("<?xml encoding=\"utf-8\"?>" . $html);
        $xpath = new DOMXPath($dom);

        $diputados = ($leg <= 53)
            ? $this->parseToccoloursTables($xpath, $leg, $cfg)
            : $this->parseWikitablesModernas($xpath, $leg, $cfg);

        $targetDir = $this->storagePath . '/data/json';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $dataset = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Morelos - {$cfg['romano']} Legislatura ({$cfg['periodo']})",
                'entidad' => 'Morelos',
                'camara' => 'Congreso Local',
                'legislatura' => $leg,
                'legislatura_romano' => $cfg['romano'],
                'periodo' => $cfg['periodo'],
                'fecha_inicio' => $cfg['inicio'],
                'fecha_fin' => $cfg['fin'],
                'sede' => self::SEDE_CONGRESO,
                'telefono' => self::TEL_INSTITUCIONAL,
                'portal_oficial' => self::PORTAL_OFICIAL,
                'fuente_url' => $cfg['wiki'],
                'generado_en' => date('c'),
                'total_legisladores' => count($diputados),
            ],
            'legisladores' => $diputados,
        ];

        $filePath = $targetDir . "/congreso_morelos_leg_{$leg}.json";
        file_put_contents($filePath, json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            'ok' => true,
            'legislatura' => $leg,
            'romano' => $cfg['romano'],
            'periodo' => $cfg['periodo'],
            'archivo' => $filePath,
            'total_legisladores' => count($diputados),
        ];
    }

    private function parseToccoloursTables(DOMXPath $xpath, int $leg, array $cfg): array
    {
        $tables = $xpath->query("//table[contains(@class, 'toccolours')]");
        if ($tables->length < 2) return [];

        $items = [];
        $seq = 1;

        // MR: Table 0
        $trsMR = $xpath->query(".//tr", $tables->item(0));
        foreach ($trsMR as $idx => $tr) {
            if ($idx < 2) continue;
            $tds = $xpath->query(".//td", $tr);
            if ($tds->length < 3) continue;

            $distritoRaw = $this->cleanText($tds->item(0)->textContent);
            $cabecera = $this->cleanText($tds->item(1)->textContent);
            [$nombre, $suplente] = $this->cleanPersonName($tds->item(2)->textContent);
            $partido = $this->extractPartyFromNode($xpath, $tds->item($tds->length - 1));

            if (!empty($nombre)) {
                $items[] = $this->buildRecord($nombre, 'Mayoría Relativa', $this->romanToInt($distritoRaw), $cabecera, $suplente, $partido, $leg, $cfg, $seq++);
            }
        }

        // RP: Table 1
        $trsRP = $xpath->query(".//tr", $tables->item(1));
        foreach ($trsRP as $idx => $tr) {
            if ($idx < 2) continue;
            $tds = $xpath->query(".//td", $tr);
            if ($tds->length < 2) continue;

            [$nombre, $suplente] = $this->cleanPersonName($tds->item(1)->textContent);
            $partido = $this->extractPartyFromNode($xpath, $tds->item($tds->length - 1));

            if (!empty($nombre) && strpos($nombre, 'Constitucional') === false) {
                $items[] = $this->buildRecord($nombre, 'Representación Proporcional', null, null, $suplente, $partido, $leg, $cfg, $seq++);
            }
        }

        return $items;
    }

    private function parseWikitablesModernas(DOMXPath $xpath, int $leg, array $cfg): array
    {
        $tables = $xpath->query("//table[contains(@class, 'wikitable')]");
        if ($tables->length < 3) return [];

        $items = [];
        $seq = 1;

        // MR: Table 1
        $trsMR = $xpath->query(".//tr", $tables->item(1));
        foreach ($trsMR as $idx => $tr) {
            if ($idx === 0) continue;
            $tds = $xpath->query(".//td", $tr);
            if ($tds->length < 3) continue;

            $distritoRaw = $this->cleanText($tds->item(0)->textContent);
            $cabecera = $this->cleanText($tds->item(1)->textContent);
            [$nombre, $supl] = $this->cleanPersonName($tds->item(2)->textContent);
            $suplente = $supl;
            if ($tds->length >= 5 && empty($suplente)) {
                $suplente = $this->cleanText($tds->item(3)->textContent);
            }
            $partido = $this->extractPartyFromNode($xpath, $tds->item($tds->length - 1));

            if (!empty($nombre)) {
                $items[] = $this->buildRecord($nombre, 'Mayoría Relativa', $this->romanToInt($distritoRaw), $cabecera, $suplente ?: null, $partido, $leg, $cfg, $seq++);
            }
        }

        // RP: Table 2
        $trsRP = $xpath->query(".//tr", $tables->item(2));
        $lastParty = 'MORELOS PROGRESA';
        foreach ($trsRP as $idx => $tr) {
            if ($idx === 0) continue;
            $tds = $xpath->query(".//td", $tr);
            if ($tds->length < 1) continue;

            [$nombre, $supl] = $this->cleanPersonName($tds->item(0)->textContent);
            $suplente = $supl;
            if ($tds->length >= 3 && empty($suplente)) {
                $suplente = $this->cleanText($tds->item(1)->textContent);
            }

            if ($tds->length > 1) {
                $candParty = $this->extractPartyFromNode($xpath, $tds->item($tds->length - 1));
                if ($candParty !== 'SIN DATO' && strlen($candParty) <= 20) {
                    $lastParty = $candParty;
                }
            }
            $partido = $lastParty;

            if (!empty($nombre)) {
                $items[] = $this->buildRecord($nombre, 'Representación Proporcional', null, null, $suplente ?: null, $partido, $leg, $cfg, $seq++);
            }
        }

        return $items;
    }

    private function buildRecord(string $nombre, string $principio, ?int $distrito, ?string $cabecera, ?string $suplente, string $partido, int $leg, array $cfg, int $seq): array
    {
        $slug = 'mor_leg' . $leg . '_' . str_pad((string)$seq, 2, '0', STR_PAD_LEFT);
        $fechaExt = date('Y-m-d H:i:s');
        $hash = hash('sha256', $nombre . '|' . $partido . '|' . ($distrito ?? 'RP') . '|' . $leg . '|' . $cfg['periodo']);

        return [
            'referencia_sil' => $slug,
            'nombre_completo' => $nombre,
            'camara' => 'Congreso del Estado de Morelos',
            'legislatura' => $leg,
            'partido' => $partido,
            'principio_eleccion' => $principio,
            'entidad' => 'Morelos',
            'distrito' => $distrito,
            'cabecera' => $cabecera,
            'tipo_cargo' => 'Propietario',
            'suplente_nombre' => $suplente,
            'fecha_toma_protesta' => $cfg['inicio'],
            'fecha_nacimiento' => null,
            'correo_institucional' => null,
            'telefono' => self::TEL_INSTITUCIONAL,
            'ubicacion_oficina' => self::SEDE_CONGRESO,
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
                'fuente_url' => $cfg['wiki'],
                'portal_oficial' => self::PORTAL_OFICIAL,
                'sha256_evidencia' => $hash,
                'fecha_extraccion' => $fechaExt,
                'entidad_tipo' => 'Congreso Local',
            ],
        ];
    }

    private function cleanText(string $s): string
    {
        $s = preg_replace('/\[[0-9a-zA-Z]+\]/', '', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function cleanPersonName(string $s): array
    {
        $s = preg_replace('/\[[0-9a-zA-Z]+\]/', '', $s);
        $s = preg_replace('/\([0-9\-\s]+\)/', '', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);
        $suplente = null;
        if (preg_match('/^(.*?)\s+sustituid[ao]\s+por\s+(.*)$/iu', $s, $m)) {
            $s = trim($m[1]);
            $suplente = trim($m[2]);
        }
        return [$s, $suplente];
    }

    private function romanToInt(string $roman): ?int
    {
        $map = [
            'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10,
            'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15, 'XVI' => 16, 'XVII' => 17, 'XVIII' => 18
        ];
        $clean = trim(strtoupper($roman));
        if (isset($map[$clean])) return $map[$clean];
        if (is_numeric($clean)) return (int)$clean;
        return null;
    }

    private function normalizeParty(string $raw): string
    {
        $raw = $this->cleanText($raw);
        $raw = preg_replace('/\([0-9\-\s]+\)/', '', $raw);
        $raw = trim($raw);
        $upper = mb_strtoupper($raw, 'UTF-8');

        if (str_contains($upper, 'ACCION NACIONAL') || str_contains($upper, 'ACCIÓN NACIONAL') || $upper === 'PAN') return 'PAN';
        if (str_contains($upper, 'REVOLUCIONARIO INSTITUCIONAL') || $upper === 'PRI') return 'PRI';
        if (str_contains($upper, 'REVOLUCION DEMOCRATICA') || str_contains($upper, 'REVOLUCIÓN DEMOCRÁTICA') || $upper === 'PRD') return 'PRD';
        if (str_contains($upper, 'VERDE') || $upper === 'PVEM') return 'PVEM';
        if (str_contains($upper, 'TRABAJO') || $upper === 'PT') return 'PT';
        if (str_contains($upper, 'MOVIMIENTO CIUDADANO') || $upper === 'MC') return 'MC';
        if (str_contains($upper, 'CONVERGENCIA')) return 'CONVERGENCIA';
        if (str_contains($upper, 'REGENERACION') || str_contains($upper, 'REGENERACIÓN') || str_contains($upper, 'MORENA')) return 'MORENA';
        if (str_contains($upper, 'ENCUENTRO SOCIAL') || str_contains($upper, 'ENCUENTRO SOLIDARIO') || $upper === 'PES') return 'PES';
        if (str_contains($upper, 'NUEVA ALIANZA') || $upper === 'PANAL' || $upper === 'NA') return 'PANAL';
        if (str_contains($upper, 'SOCIALDEMOCRATA') || str_contains($upper, 'SOCIALDEMÓCRATA') || $upper === 'PSD') return 'PSD';
        if (str_contains($upper, 'HUMANISTA')) return 'PH';
        if (str_contains($upper, 'MORELOS PROGRESA')) return 'MORELOS PROGRESA';
        if (str_contains($upper, 'REDES SOCIALES PROGRESISTAS') || $upper === 'RSP') return 'RSP';
        if (str_contains($upper, 'COMUNISTA') || $upper === 'PCM') return 'PCM';
        if (str_contains($upper, 'DEMOCRACIA SOCIAL') || $upper === 'PDS') return 'PDS';
        if (str_contains($upper, 'POR EL BIEN DE TODOS')) return 'PRD-PT-CONV';
        if (str_contains($upper, 'SIN PARTIDO') || str_contains($upper, 'INDEPENDIENTE')) return 'IND';

        return $raw ?: 'SIN DATO';
    }

    private function extractPartyFromNode(DOMXPath $xpath, DOMNode $node): string
    {
        $text = trim($node->textContent);
        foreach ($xpath->query(".//a|.//img", $node) as $elem) {
            $title = $elem->getAttribute('title') ?: $elem->getAttribute('alt');
            if ($title) {
                $norm = $this->normalizeParty($title);
                if ($norm !== 'SIN DATO' && strlen($norm) <= 20) return $norm;
            }
        }
        if ($text !== '') {
            return $this->normalizeParty($text);
        }
        return 'SIN DATO';
    }
}
