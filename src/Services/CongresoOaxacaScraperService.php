<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMNode;
use DOMXPath;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de Oaxaca.
 * Cobertura: 10 legislaturas consecutivas (LVII a LXVI, periodos 1998-2027, casi 30 años).
 */
class CongresoOaxacaScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public const PORTAL_OFICIAL = 'https://www.congresooaxaca.gob.mx/';
    public const SEDE_CONGRESO = 'Calle 14 Oriente No. 1, San Raymundo Jalpan, Oaxaca, C.P. 71248';
    public const TEL_INSTITUCIONAL = '(951) 502-0400';

    private const LEGISLATURAS = [
        57 => ['romano' => 'LVII', 'inicio' => '1998-11-15', 'fin' => '2001-11-14', 'periodo' => '1998-2001', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LVII_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        58 => ['romano' => 'LVIII', 'inicio' => '2001-11-15', 'fin' => '2004-11-14', 'periodo' => '2001-2004', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LVIII_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        59 => ['romano' => 'LIX', 'inicio' => '2004-11-15', 'fin' => '2007-11-14', 'periodo' => '2004-2007', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LIX_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        60 => ['romano' => 'LX', 'inicio' => '2007-11-15', 'fin' => '2010-11-14', 'periodo' => '2007-2010', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LX_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        61 => ['romano' => 'LXI', 'inicio' => '2010-11-15', 'fin' => '2013-11-14', 'periodo' => '2010-2013', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LXI_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        62 => ['romano' => 'LXII', 'inicio' => '2013-11-15', 'fin' => '2016-11-14', 'periodo' => '2013-2016', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LXII_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        63 => ['romano' => 'LXIII', 'inicio' => '2016-11-15', 'fin' => '2018-11-14', 'periodo' => '2016-2018', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LXIII_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        64 => ['romano' => 'LXIV', 'inicio' => '2018-11-15', 'fin' => '2021-11-14', 'periodo' => '2018-2021', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LXIV_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        65 => ['romano' => 'LXV', 'inicio' => '2021-11-15', 'fin' => '2024-11-14', 'periodo' => '2021-2024', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LXV_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
        66 => ['romano' => 'LXVI', 'inicio' => '2024-11-15', 'fin' => '2027-11-14', 'periodo' => '2024-2027', 'wiki' => 'https://es.wikipedia.org/wiki/Anexo:LXVI_Legislatura_del_Congreso_del_Estado_de_Oaxaca'],
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    public function scrapeAll(): array
    {
        $this->logger->info("Iniciando consolidación total del H. Congreso del Estado de Oaxaca (Leg LVII a LXVI)...");
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
            'entidad' => 'Oaxaca',
            'camara' => 'Congreso del Estado de Oaxaca',
            'legislaturas_procesadas' => count($resultados),
            'total_diputados' => $totalDiputados,
            'detalle' => $resultados,
        ];
    }

    public function scrapeLegislatura(int $leg): array
    {
        if (!isset(self::LEGISLATURAS[$leg])) {
            return ['ok' => false, 'error' => "Legislatura {$leg} no configurada para Oaxaca."];
        }

        $cfg = self::LEGISLATURAS[$leg];
        $this->logger->info("Extrayendo Oaxaca Leg {$leg} ({$cfg['romano']}) [{$cfg['periodo']}]...");

        $rawDir = $this->storagePath . '/raw/oaxaca';
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

        $diputados = $this->parseWikitables($xpath, $leg, $cfg);

        $targetDir = $this->storagePath . '/data/json';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $dataset = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Oaxaca - {$cfg['romano']} Legislatura ({$cfg['periodo']})",
                'entidad' => 'Oaxaca',
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

        $filePath = $targetDir . "/congreso_oaxaca_leg_{$leg}.json";
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

    private function parseWikitables(DOMXPath $xpath, int $leg, array $cfg): array
    {
        $tables = $xpath->query("//table[contains(@class, 'wikitable')]");
        if ($tables->length < 3) return [];

        $items = [];
        $seq = 1;

        // MR: Table 1
        $mr = $tables->item(1);
        $trsMR = $xpath->query(".//tr", $mr);

        foreach ($trsMR as $idx => $tr) {
            if ($idx === 0) continue;
            $tds = $xpath->query(".//td", $tr);
            if ($tds->length < 3) continue;

            $distritoRaw = $this->cleanText($tds->item(0)->textContent);
            $distritoNum = $this->romanToInt($distritoRaw);
            if ($distritoNum === null) continue;

            $cabecera = $this->cleanText($tds->item(1)->textContent);
            [$nombre, $suplente] = $this->cleanPersonName($tds->item(2)->textContent);

            if ($nombre === 'Vacante' || empty($nombre)) continue;

            $partido = $this->extractPartyFromNode($xpath, $tds->item($tds->length - 1));

            $items[] = $this->buildRecord($nombre, 'Mayoría Relativa', $distritoNum, $cabecera, $suplente, $partido, $leg, $cfg, $seq++);
        }

        // RP: Table 2
        $rp = $tables->item(2);
        $trsRP = $xpath->query(".//tr", $rp);

        foreach ($trsRP as $idx => $tr) {
            if ($idx === 0) continue;
            $tds = $xpath->query(".//td", $tr);
            if ($tds->length < 1) continue;

            [$nombre, $supl] = $this->cleanPersonName($tds->item(0)->textContent);
            if ($nombre === 'Propietario' || $nombre === 'Diputado' || empty($nombre)) continue;

            $suplente = $supl;
            if ($tds->length >= 3 && empty($suplente) && $leg === 62) {
                $suplente = $this->cleanText($tds->item(1)->textContent);
            }

            $partido = $this->extractPartyFromNode($xpath, $tds->item($tds->length - 1));

            $items[] = $this->buildRecord($nombre, 'Representación Proporcional', null, null, $suplente, $partido, $leg, $cfg, $seq++);
        }

        return $items;
    }

    private function buildRecord(string $nombre, string $principio, ?int $distrito, ?string $cabecera, ?string $suplente, string $partido, int $leg, array $cfg, int $seq): array
    {
        $slug = 'oax_leg' . $leg . '_' . str_pad((string)$seq, 2, '0', STR_PAD_LEFT);
        $fechaExt = date('Y-m-d H:i:s');
        $hash = hash('sha256', $nombre . '|' . $partido . '|' . ($distrito ?? 'RP') . '|' . $leg . '|' . $cfg['periodo']);

        return [
            'referencia_sil' => $slug,
            'nombre_completo' => $nombre,
            'camara' => 'Congreso del Estado de Oaxaca',
            'legislatura' => $leg,
            'partido' => $partido,
            'principio_eleccion' => $principio,
            'entidad' => 'Oaxaca',
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
            'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15, 'XVI' => 16, 'XVII' => 17, 'XVIII' => 18,
            'XIX' => 19, 'XX' => 20, 'XXI' => 21, 'XXII' => 22, 'XXIII' => 23, 'XXIV' => 24, 'XXV' => 25
        ];
        $clean = trim(strtoupper($roman));
        if (isset($map[$clean])) return $map[$clean];
        if (is_numeric($clean)) return (int)$clean;
        return null;
    }

    private function normalizeParty(string $raw): string
    {
        $raw = preg_replace('/\[[0-9a-zA-Z]+\]/', '', $raw);
        $raw = preg_replace('/\([0-9\-\s]+\)/', '', $raw);
        $raw = trim($raw);
        $upper = mb_strtoupper($raw, 'UTF-8');

        if (str_contains($upper, 'ACCION NACIONAL') || str_contains($upper, 'ACCIÓN NACIONAL') || str_contains($upper, 'PAN_PARTY') || $upper === 'PAN') return 'PAN';
        if (str_contains($upper, 'REVOLUCIONARIO INSTITUCIONAL') || str_contains($upper, 'PRI_PARTY') || $upper === 'PRI') return 'PRI';
        if (str_contains($upper, 'REVOLUCION DEMOCRATICA') || str_contains($upper, 'REVOLUCIÓN DEMOCRÁTICA') || str_contains($upper, 'PRD_PARTY') || $upper === 'PRD') return 'PRD';
        if (str_contains($upper, 'VERDE') || str_contains($upper, 'PVEM')) return 'PVEM';
        if (str_contains($upper, 'TRABAJO') || str_contains($upper, 'PT_PARTY') || $upper === 'PT') return 'PT';
        if (str_contains($upper, 'MOVIMIENTO CIUDADANO') || str_contains($upper, 'MC_PARTY') || $upper === 'MC') return 'MC';
        if (str_contains($upper, 'CONVERGENCIA')) return 'CONVERGENCIA';
        if (str_contains($upper, 'REGENERACION') || str_contains($upper, 'REGENERACIÓN') || str_contains($upper, 'MORENA')) return 'MORENA';
        if (str_contains($upper, 'ENCUENTRO SOCIAL') || str_contains($upper, 'ENCUENTRO SOLIDARIO') || $upper === 'PES') return 'PES';
        if (str_contains($upper, 'NUEVA ALIANZA') || $upper === 'PANAL' || $upper === 'NA') return 'PANAL';
        if (str_contains($upper, 'UNIDAD POPULAR') || $upper === 'PUP') return 'PUP';
        if (str_contains($upper, 'FUERZA POR MEXICO') || str_contains($upper, 'FUERZA POR MÉXICO') || $upper === 'FXM') return 'FXM';
        if (str_contains($upper, 'REDES SOCIALES PROGRESISTAS') || $upper === 'RSP') return 'RSP';
        if (str_contains($upper, 'PARLAMEMTARIO PLURAL') || str_contains($upper, 'PARLAMENTARIO PLURAL') || $upper === 'GPP') return 'GPP';
        if (str_contains($upper, 'SOCIEDAD NACIONALISTA') || $upper === 'PSN') return 'PSN';
        if (str_contains($upper, 'CENTRO DEMOCRATICO') || str_contains($upper, 'CENTRO DEMOCRÁTICO') || $upper === 'PCD') return 'PCD';
        if (str_contains($upper, 'SOCIALDEMOCRATA') || str_contains($upper, 'SOCIALDEMÓCRATA') || $upper === 'PSD') return 'PSD';
        if (str_contains($upper, 'SIN PARTIDO') || str_contains($upper, 'INDEPENDIENTE')) return 'IND';

        return $raw ?: 'SIN DATO';
    }

    private function extractPartyFromNode(DOMXPath $xpath, DOMNode $node): string
    {
        $text = trim($node->textContent);
        $cleanText = preg_replace('/\[[0-9a-zA-Z]+\]/', '', $text);
        $cleanText = trim($cleanText);

        if ($cleanText !== '') {
            $norm = $this->normalizeParty($cleanText);
            if ($norm !== 'SIN DATO' && strlen($norm) <= 20) return $norm;
        }

        foreach ($xpath->query(".//a|.//img", $node) as $elem) {
            $attrs = [
                $elem->getAttribute('title'),
                $elem->getAttribute('alt'),
                $elem->getAttribute('resource'),
            ];
            foreach ($attrs as $a) {
                if ($a && !str_starts_with($a, '#') && stripos($a, 'cite_note') === false && stripos($a, 'Emblema') === false) {
                    $norm = $this->normalizeParty($a);
                    if ($norm !== 'SIN DATO' && strlen($norm) <= 20) return $norm;
                }
            }
        }
        return 'SIN DATO';
    }
}
