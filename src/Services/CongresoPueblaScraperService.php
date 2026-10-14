<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de Puebla.
 * Procesa y valida 12 legislaturas consecutivas (LI a LXII, periodos 1990–2027).
 */
class CongresoPueblaScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public const SEDE_DIRECCION = 'Av. 5 Poniente 128, Centro Histórico, C.P. 72000, Heroica Puebla de Zaragoza, Pue.';
    public const TEL_INSTITUCIONAL = '(222) 372-1100';
    public const SITIO_OFICIAL = 'https://www.congresopuebla.gob.mx/';

    /**
     * Catálogo de 12 legislaturas consecutivas (LI a LXII).
     * [número, romano, fecha_inicio, fecha_fin]
     */
    private const LEGISLATURAS = [
        51 => ['LI', '1990-01-15', '1993-01-14'],
        52 => ['LII', '1993-01-15', '1996-01-14'],
        53 => ['LIII', '1996-01-15', '1999-01-14'],
        54 => ['LIV', '1999-01-15', '2002-01-14'],
        55 => ['LV', '2002-01-15', '2005-01-14'],
        56 => ['LVI', '2005-01-15', '2008-01-14'],
        57 => ['LVII', '2008-01-15', '2011-01-14'],
        58 => ['LVIII', '2011-01-15', '2014-01-14'],
        59 => ['LIX', '2014-01-15', '2018-09-14'],
        60 => ['LX', '2018-09-15', '2021-09-14'],
        61 => ['LXI', '2021-09-15', '2024-09-14'],
        62 => ['LXII', '2024-09-15', '2027-09-14'],
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    /**
     * Procesa y genera los datasets JSON de todas las legislaturas de Puebla.
     */
    public function scrapeAll(): array
    {
        $this->logger->info("Iniciando consolidación del Congreso de Puebla (12 legislaturas LI a LXII)...");
        $resultados = [];
        $totalGeneral = 0;

        foreach (self::LEGISLATURAS as $num => [$romano, $inicio, $fin]) {
            $res = $this->scrapeLegislatura($num);
            $resultados[$romano] = $res;
            $totalGeneral += $res['total_legisladores'] ?? 0;
        }

        return [
            'ok' => true,
            'estado' => 'Puebla',
            'legislaturas_procesadas' => count($resultados),
            'total_general_diputados' => $totalGeneral,
            'detalle' => $resultados,
        ];
    }

    /**
     * Procesa una legislatura individual de Puebla.
     */
    public function scrapeLegislatura(int $numero): array
    {
        if (!isset(self::LEGISLATURAS[$numero])) {
            throw new \InvalidArgumentException("Legislatura {$numero} no configurada para Puebla.");
        }

        [$romano, $inicio, $fin] = self::LEGISLATURAS[$numero];
        $html = $this->getHtmlLegislatura($numero, $romano);
        $fuente = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Puebla";

        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($doc);

        $tables = $xpath->query('//table');
        $tMR = null;
        $tRP = null;

        foreach ($tables as $t) {
            $r0 = $this->cleanText($xpath->query('.//tr[1]', $t)->item(0)->textContent ?? '');
            if (str_contains($r0, 'Partido Diputados') || str_contains($r0, 'Control de autoridades') || str_contains($r0, 'Predecesor:')) {
                continue;
            }
            if (str_contains($r0, 'Distrito')) {
                $tMR = $t;
            } elseif ((str_contains($r0, 'Diputado') || str_contains($r0, 'Propietario')) && $tMR !== null && $tRP === null) {
                $tRP = $t;
            }
        }

        $fechaExtraccion = date('Y-m-d H:i:s');
        $fechaCorta = date('Y-m-d');
        $legisladores = [];
        $idx = 1;

        // Mayoría Relativa
        if ($tMR) {
            $rows = $xpath->query('.//tr', $tMR);
            $header = $this->cleanText($rows->item(0)->textContent ?? '');
            $hasCabecera = str_contains($header, 'Cabecera');

            for ($i = 1; $i < $rows->length; $i++) {
                $r = $rows->item($i);
                $cells = $xpath->query('.//th|.//td', $r);
                if ($cells->length < 2) {
                    continue;
                }

                $distritoRaw = $this->cleanText($cells->item(0)->textContent ?? '');
                if (!$this->isDistrict($distritoRaw)) {
                    continue;
                }

                $cabecera = $hasCabecera && $cells->length >= 3 ? $this->cleanText($cells->item(1)->textContent ?? '') : null;
                $diputado = $hasCabecera && $cells->length >= 3 ? $this->cleanText($cells->item(2)->textContent ?? '') : $this->cleanText($cells->item(1)->textContent ?? '');

                if ($diputado === '' || preg_match('/^(Diputado|Distrito|Cabecera|Partido|Propietario)$/ui', $diputado)) {
                    continue;
                }

                $suplente = null;
                if ($cells->length >= 4 && str_contains($header, 'Suplente') && !$hasCabecera) {
                    $suplente = $this->cleanText($cells->item(2)->textContent ?? '');
                } elseif (preg_match('/\s+(?:sustituido por|suplido por|relevado por)\s+(.+)$/ui', $diputado, $sm)) {
                    $suplente = trim($sm[1]);
                    $diputado = trim(preg_replace('/\s+(?:sustituido por|suplido por|relevado por)\s+.+$/ui', '', $diputado), " ,;\t\n\r");
                }
                // Limpiar años o periodos entre paréntesis en el nombre
                $diputado = trim(preg_replace('/\s*\(\d{4}[^)]*\)/', '', $diputado));

                $partido = $this->detectParty($r, $xpath);
                $slugId = sprintf('pue_leg%02d_%02d', $numero, $idx);
                $hash = hash('sha256', "{$diputado}|{$partido}|{$distritoRaw}|{$numero}|{$fechaCorta}");

                $legisladores[] = [
                    'referencia_sil' => $slugId,
                    'nombre_completo' => $diputado,
                    'camara' => 'Congreso del Estado de Puebla',
                    'legislatura' => $numero,
                    'partido' => $partido,
                    'principio_eleccion' => 'Mayoría Relativa',
                    'entidad' => 'Puebla',
                    'distrito' => $distritoRaw,
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

        // Representación Proporcional
        if ($tRP) {
            $rows = $xpath->query('.//tr', $tRP);
            $rpNum = 1;

            for ($i = 1; $i < $rows->length; $i++) {
                $r = $rows->item($i);
                $cells = $xpath->query('.//th|.//td', $r);
                if ($cells->length < 2) {
                    continue;
                }

                $diputado = $this->cleanText($cells->item(0)->textContent ?? '');
                if ($diputado === '' || preg_match('/^(Diputado|Partido|Propietario)$/ui', $diputado)) {
                    continue;
                }

                $suplente = null;
                if ($cells->length >= 3 && str_contains($this->cleanText($rows->item(0)->textContent ?? ''), 'Suplente')) {
                    $suplente = $this->cleanText($cells->item(1)->textContent ?? '');
                } elseif (preg_match('/\s+(?:sustituido por|suplido por|relevado por)\s+(.+)$/ui', $diputado, $sm)) {
                    $suplente = trim($sm[1]);
                    $diputado = trim(preg_replace('/\s+(?:sustituido por|suplido por|relevado por)\s+.+$/ui', '', $diputado), " ,;\t\n\r");
                }
                $diputado = trim(preg_replace('/\s*\(\d{4}[^)]*\)/', '', $diputado));

                $distrito = "RP {$rpNum}";
                $partido = $this->detectParty($r, $xpath);
                $slugId = sprintf('pue_leg%02d_%02d', $numero, $idx);
                $hash = hash('sha256', "{$diputado}|{$partido}|{$distrito}|{$numero}|{$fechaCorta}");

                $legisladores[] = [
                    'referencia_sil' => $slugId,
                    'nombre_completo' => $diputado,
                    'camara' => 'Congreso del Estado de Puebla',
                    'legislatura' => $numero,
                    'partido' => $partido,
                    'principio_eleccion' => 'Representación Proporcional',
                    'entidad' => 'Puebla',
                    'distrito' => $distrito,
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
                'titulo' => "H. Congreso del Estado de Puebla - {$romano} Legislatura ({$inicio} a {$fin})",
                'entidad' => 'Puebla',
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
        $targetFile = "{$targetDir}/congreso_puebla_leg_{$numero}.json";
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
        $rawDir = $this->storagePath . '/raw/puebla';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0755, true);
        }
        $rawFile = "{$rawDir}/leg_{$numero}.html";
        if (file_exists($rawFile) && filesize($rawFile) > 5000) {
            return (string)file_get_contents($rawFile);
        }

        $url = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Puebla";
        $res = $this->http->request('GET', $url, [
            'headers' => ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'],
        ]);
        $html = (string)($res['body'] ?? '');
        if (strlen($html) > 5000) {
            file_put_contents($rawFile, $html);
        }
        return $html;
    }

    private function isDistrict(string $s): bool
    {
        return (bool)preg_match('/^(?:Distrito\s*)?([IVXLCDM]+|\d{1,2})[°\.]?(?:\s+Distrito.*)?$/ui', $s);
    }

    private function detectParty(DOMElement $tr, DOMXPath $xpath): string
    {
        $links = $xpath->query('.//a', $tr);
        foreach ($links as $l) {
            $t = (string)$l->getAttribute('title');
            $h = (string)$l->getAttribute('href');
            if (preg_match('/(Partido|Morena|Nueva Alianza|Movimiento Ciudadano|Verde|PRD|PAN|PRI|PT|PSI)/i', "{$t} {$h}")) {
                $cleaned = preg_replace('/\s*\([^)]*\)/', '', $t !== '' ? $t : $h) ?? '';
                return $this->normalizeParty($cleaned);
            }
        }
        $imgs = $xpath->query('.//img', $tr);
        foreach ($imgs as $img) {
            $src = $img->getAttribute('src') . ' ' . $img->getAttribute('resource');
            if (preg_match('/(PRI|PAN|PRD|PT|PVEM|Morena|PNA|PANAL|MC|PSI)/i', $src, $im)) {
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
        if (str_contains($r, 'PACTO SOCIAL') || str_contains($r, 'PSI')) return 'PSI';
        if (str_contains($r, 'COMPROMISO POR PUEBLA') || str_contains($r, 'CPP')) return 'CPP';
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
