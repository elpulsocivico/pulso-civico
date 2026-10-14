<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de México.
 * Procesa y valida 12 legislaturas consecutivas (LI a LXII, periodos 1990–2027).
 */
class CongresoEdomexScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public const SEDE_DIRECCION = 'Palacio del Poder Legislativo, Plaza Hidalgo s/n, Col. Centro, C.P. 50000, Toluca de Lerdo, Estado de México.';
    public const TEL_INSTITUCIONAL = '(722) 279-6400';
    public const SITIO_OFICIAL = 'https://legislativoedomex.gob.mx/';

    /**
     * Catálogo de 12 legislaturas consecutivas (LI a LXII).
     * [número, romano, fecha_inicio, fecha_fin]
     */
    private const LEGISLATURAS = [
        51 => ['LI', '1990-09-05', '1993-09-04'],
        52 => ['LII', '1993-09-05', '1996-09-04'],
        53 => ['LIII', '1996-09-05', '2000-09-04'],
        54 => ['LIV', '2000-09-05', '2003-09-04'],
        55 => ['LV', '2003-09-05', '2006-09-04'],
        56 => ['LVI', '2006-09-05', '2009-09-04'],
        57 => ['LVII', '2009-09-05', '2012-09-04'],
        58 => ['LVIII', '2012-09-05', '2015-09-04'],
        59 => ['LIX', '2015-09-05', '2018-09-04'],
        60 => ['LX', '2018-09-05', '2021-09-04'],
        61 => ['LXI', '2021-09-05', '2024-09-04'],
        62 => ['LXII', '2024-09-05', '2027-09-04'],
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    /**
     * Procesa y genera los datasets JSON de todas las legislaturas del Estado de México.
     */
    public function scrapeAll(): array
    {
        $this->logger->info("Iniciando consolidación del Congreso del Estado de México (12 legislaturas LI a LXII)...");
        $resultados = [];
        $totalGeneral = 0;

        foreach (self::LEGISLATURAS as $num => [$romano, $inicio, $fin]) {
            $res = $this->scrapeLegislatura($num);
            $resultados[$romano] = $res;
            $totalGeneral += $res['total_legisladores'] ?? 0;
        }

        return [
            'ok' => true,
            'estado' => 'Estado de México',
            'legislaturas_procesadas' => count($resultados),
            'total_general_diputados' => $totalGeneral,
            'detalle' => $resultados,
        ];
    }

    /**
     * Procesa una legislatura individual del Estado de México.
     */
    public function scrapeLegislatura(int $numero): array
    {
        if (!isset(self::LEGISLATURAS[$numero])) {
            throw new \InvalidArgumentException("Legislatura {$numero} no configurada para el Estado de México.");
        }

        [$romano, $inicio, $fin] = self::LEGISLATURAS[$numero];
        $html = $this->getHtmlLegislatura($numero, $romano);
        $fuente = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_M%C3%A9xico";

        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($doc);

        $tables = $xpath->query('//table');
        $fechaExtraccion = date('Y-m-d H:i:s');
        $fechaCorta = date('Y-m-d');
        $legisladores = [];
        $idx = 1;

        // Table 1: Mayoría Relativa
        $tMR = $tables->item(1);
        if ($tMR) {
            $rows = $xpath->query('.//tr', $tMR);
            for ($i = 1; $i < $rows->length; $i++) {
                $r = $rows->item($i);
                $cells = $xpath->query('.//th|.//td', $r);
                if ($cells->length < 3) {
                    continue;
                }

                $distrito = $this->cleanText($cells->item(0)->textContent ?? '');
                $cabecera = $this->cleanText($cells->item(1)->textContent ?? '');
                $diputado = $this->cleanText($cells->item(2)->textContent ?? '');

                if ($diputado === '' || preg_match('/^(Diputado|Distrito|Cabecera|Municipio|Partido)$/ui', $diputado)) {
                    continue;
                }

                $suplente = null;
                if (preg_match('/\s+(?:sustituido por|suplido por|relevado por)\s+(.+)$/ui', $diputado, $sm)) {
                    $suplente = trim($sm[1]);
                    $diputado = trim(preg_replace('/\s+(?:sustituido por|suplido por|relevado por)\s+.+$/ui', '', $diputado), " ,;\t\n\r");
                }

                $partido = $this->detectParty($r, $xpath);
                $slugId = sprintf('edomex_leg%02d_%02d', $numero, $idx);
                $hash = hash('sha256', "{$diputado}|{$partido}|{$distrito}|{$numero}|{$fechaCorta}");

                $legisladores[] = [
                    'referencia_sil' => $slugId,
                    'nombre_completo' => $diputado,
                    'camara' => 'Congreso del Estado de México',
                    'legislatura' => $numero,
                    'partido' => $partido,
                    'principio_eleccion' => 'Mayoría Relativa',
                    'entidad' => 'Estado de México',
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
                $idx++;
            }
        }

        // Table 2: Representación Proporcional
        $tRP = $tables->item(2);
        if ($tRP) {
            $r0Text = $this->cleanText($xpath->query('.//tr[1]', $tRP)->item(0)->textContent ?? '');
            $hasFormula = str_contains($r0Text, 'Fórmula');
            $rows = $xpath->query('.//tr', $tRP);
            $rpNum = 1;

            for ($i = 1; $i < $rows->length; $i++) {
                $r = $rows->item($i);
                $cells = $xpath->query('.//th|.//td', $r);
                if ($cells->length < 2) {
                    continue;
                }

                $formula = $hasFormula ? $this->cleanText($cells->item(0)->textContent ?? '') : "RP {$rpNum}";
                $diputado = $hasFormula ? $this->cleanText($cells->item(1)->textContent ?? '') : $this->cleanText($cells->item(0)->textContent ?? '');

                if ($diputado === '' || preg_match('/^(Diputado|Fórmula|Nombre|Partido)$/ui', $diputado)) {
                    continue;
                }

                $suplente = null;
                // Si la tabla contiene columna Suplente explícita (ej. Leg 62)
                if (str_contains($r0Text, 'Suplente') && $cells->length >= 3) {
                    $suplente = $this->cleanText($cells->item(1)->textContent ?? '');
                } elseif (preg_match('/\s+(?:sustituido por|suplido por|relevado por)\s+(.+)$/ui', $diputado, $sm)) {
                    $suplente = trim($sm[1]);
                    $diputado = trim(preg_replace('/\s+(?:sustituido por|suplido por|relevado por)\s+.+$/ui', '', $diputado), " ,;\t\n\r");
                }

                $partido = $this->detectParty($r, $xpath);
                $slugId = sprintf('edomex_leg%02d_%02d', $numero, $idx);
                $hash = hash('sha256', "{$diputado}|{$partido}|{$formula}|{$numero}|{$fechaCorta}");

                $legisladores[] = [
                    'referencia_sil' => $slugId,
                    'nombre_completo' => $diputado,
                    'camara' => 'Congreso del Estado de México',
                    'legislatura' => $numero,
                    'partido' => $partido,
                    'principio_eleccion' => 'Representación Proporcional',
                    'entidad' => 'Estado de México',
                    'distrito' => $formula,
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
                $idx++;
                $rpNum++;
            }
        }

        $dataset = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de México - {$romano} Legislatura ({$inicio} a {$fin})",
                'entidad' => 'Estado de México',
                'camara' => 'Congreso Local',
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
        $targetFile = "{$targetDir}/congreso_edomex_leg_{$numero}.json";
        file_put_contents($targetFile, json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->logger->info("Generado dataset {$romano} Legislatura: {$targetFile} (" . count($legisladores) . " diputados)");

        return [
            'ok' => true,
            'legislatura' => $numero,
            'romano' => $romano,
            'total_legisladores' => count($legisladores),
            'archivo' => $targetFile,
        ];
    }

    private function getHtmlLegislatura(int $numero, string $romano): string
    {
        $rawDir = $this->storagePath . '/raw/edomex';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0755, true);
        }
        $rawFile = "{$rawDir}/leg_{$numero}.html";
        if (file_exists($rawFile) && filesize($rawFile) > 5000) {
            return (string)file_get_contents($rawFile);
        }

        $url = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_M%C3%A9xico";
        $res = $this->http->request('GET', $url, [
            'headers' => ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'],
        ]);
        $html = (string)($res['body'] ?? '');
        if (strlen($html) > 5000) {
            file_put_contents($rawFile, $html);
        }
        return $html;
    }

    private function detectParty(DOMElement $tr, DOMXPath $xpath): string
    {
        $links = $xpath->query('.//a', $tr);
        foreach ($links as $l) {
            $t = (string)$l->getAttribute('title');
            $h = (string)$l->getAttribute('href');
            if (preg_match('/(Partido|Morena|Nueva Alianza|Movimiento Ciudadano|Verde|PRD|PAN|PRI|PT)/i', "{$t} {$h}")) {
                $cleaned = preg_replace('/\s*\([^)]*\)/', '', $t !== '' ? $t : $h) ?? '';
                return $this->normalizeParty($cleaned);
            }
        }
        $imgs = $xpath->query('.//img', $tr);
        foreach ($imgs as $img) {
            $src = $img->getAttribute('src') . ' ' . $img->getAttribute('resource');
            if (preg_match('/(PRI|PAN|PRD|PT|PVEM|Morena|PNA|PANAL|MC)/i', $src, $im)) {
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
