<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMElement;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

class CongresoNayaritScraperService
{
    private const LEGISLATURAS_META = [
        34 => ['romano' => 'XXXIV', 'periodo' => '2024-2027', 'inicio' => '2024-08-18', 'fin' => '2027-08-17'],
        33 => ['romano' => 'XXXIII', 'periodo' => '2021-2024', 'inicio' => '2021-08-18', 'fin' => '2024-08-17'],
        32 => ['romano' => 'XXXII', 'periodo' => '2017-2021', 'inicio' => '2017-08-18', 'fin' => '2021-08-17'],
        31 => ['romano' => 'XXXI', 'periodo' => '2014-2017', 'inicio' => '2014-08-18', 'fin' => '2017-08-17'],
        30 => ['romano' => 'XXX', 'periodo' => '2011-2014', 'inicio' => '2011-08-18', 'fin' => '2014-08-17'],
        29 => ['romano' => 'XXIX', 'periodo' => '2008-2011', 'inicio' => '2008-08-18', 'fin' => '2011-08-17'],
        28 => ['romano' => 'XXVIII', 'periodo' => '2005-2008', 'inicio' => '2005-08-18', 'fin' => '2008-08-17'],
        27 => ['romano' => 'XXVII', 'periodo' => '2002-2005', 'inicio' => '2002-08-18', 'fin' => '2005-08-17'],
        26 => ['romano' => 'XXVI', 'periodo' => '1999-2002', 'inicio' => '1999-08-18', 'fin' => '2002-08-17'],
    ];

    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    public function scrapeAll(): array
    {
        $resultados = [];
        $totalGeneral = 0;

        foreach (array_keys(self::LEGISLATURAS_META) as $leg) {
            $res = $this->scrapeLegislatura((int)$leg);
            if ($res['ok'] ?? false) {
                $totalGeneral += ($res['total_legisladores'] ?? 0);
            }
            $resultados[$leg] = $res;
            usleep(250000);
        }

        return [
            'ok' => true,
            'entidad' => 'Nayarit',
            'legislaturas_procesadas' => count($resultados),
            'total_general_legisladores' => $totalGeneral,
            'detalle' => $resultados,
        ];
    }

    public function scrapeLegislatura(int $leg): array
    {
        if ($leg === 34) {
            return $this->scrapeLegislatura34();
        }

        if (!isset(self::LEGISLATURAS_META[$leg])) {
            return [
                'ok' => false,
                'error' => "Legislatura {$leg} de Nayarit no soportada (Rango válido: 26 a 34).",
            ];
        }

        return $this->scrapeLegislaturaHistorica($leg);
    }

    public function scrapeLegislatura34(): array
    {
        $this->logger->info("Extrayendo XXXIV Legislatura de Nayarit vía API REST oficial...");
        $apiUrl = 'https://utics.congresonayarit.gob.mx/api/diputados/partidos';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PulsoCivico/1.0 (https://pulsocivico.mx)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);

        $partidos = json_decode((string)$res, true);
        if (!is_array($partidos) || empty($partidos)) {
            return ['ok' => false, 'error' => 'No se pudo obtener la respuesta de la API de Nayarit.'];
        }

        $legisladores = [];
        $mrCount = 0;
        $rpCount = 0;
        $idx = 1;

        foreach ($partidos as $partidoItem) {
            $partidoSiglas = $this->normalizeParty((string)($partidoItem['siglas'] ?? ''));
            $diputados = $partidoItem['diputados'] ?? [];

            foreach ($diputados as $dip) {
                $slugId = 'nay_leg34_' . str_pad((string)$idx++, 2, '0', STR_PAD_LEFT);
                $distritoVal = $dip['distrito'] ?? null;
                $esMR = !empty($distritoVal) && is_numeric($distritoVal);
                $principio = $esMR ? 'Mayoría Relativa' : 'Representación Proporcional';
                $distritoLabel = $esMR ? "Distrito {$distritoVal}" : 'RP';

                if ($esMR) {
                    $mrCount++;
                    if (!empty($dip['distrito_data']['municipios'])) {
                        $distritoLabel .= " ({$dip['distrito_data']['municipios']})";
                    }
                } else {
                    $rpCount++;
                }

                $nombreCompleto = $dip['fullnombre'] ?? ('Dip. ' . trim(($dip['nombre'] ?? '') . ' ' . ($dip['primer_apellido'] ?? '') . ' ' . ($dip['segundo_apellido'] ?? '')));
                $suplente = $dip['suplente']['fullnombre'] ?? null;
                $correo = $dip['contacto']['correo_electronico'] ?? null;
                $telefono = $dip['contacto']['telefono'] ?? null;
                $foto = $dip['imagen'] ?? null;

                $legisladores[] = $this->buildRegistro(
                    $slugId,
                    $nombreCompleto,
                    34,
                    $partidoSiglas,
                    $principio,
                    $distritoLabel,
                    $suplente,
                    '2024-08-18',
                    $apiUrl,
                    $correo,
                    $telefono,
                    $foto
                );
            }
        }

        return $this->guardarDataset(34, '2024-2027', $apiUrl, $legisladores, $mrCount, $rpCount);
    }

    public function scrapeLegislaturaHistorica(int $leg): array
    {
        $meta = self::LEGISLATURAS_META[$leg];
        $romano = $meta['romano'];
        $periodo = $meta['periodo'];

        $this->logger->info("Extrayendo histórico de Nayarit: Legislatura {$romano} ({$periodo})...");

        $url = "https://es.wikipedia.org/w/api.php?action=parse&page=Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Nayarit&prop=text&format=json";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PulsoCivico/1.0 (https://pulsocivico.mx)');
        $res = curl_exec($ch);

        $json = json_decode((string)$res, true);
        $html = $json['parse']['text']['*'] ?? '';
        if (empty($html)) {
            return ['ok' => false, 'error' => "No se pudo obtener el contenido para la Legislatura {$romano}."];
        }

        $rawItems = $this->parseHtmlTables($html);
        $legisladores = [];
        $fuenteUrl = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Nayarit";
        $mrCount = 0;
        $rpCount = 0;

        foreach ($rawItems as $idx => $item) {
            $slugId = "nay_leg{$leg}_" . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $partidoNorm = $this->normalizeParty($item['partido']);
            $item['principio'] === 'Mayoría Relativa' ? $mrCount++ : $rpCount++;

            $legisladores[] = $this->buildRegistro(
                $slugId,
                'Dip. ' . $item['nombre'],
                $leg,
                $partidoNorm,
                $item['principio'],
                $item['distrito'],
                $item['suplente'],
                $meta['inicio'],
                $fuenteUrl
            );
        }

        return $this->guardarDataset($leg, $periodo, $fuenteUrl, $legisladores, $mrCount, $rpCount);
    }

    private function parseHtmlTables(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $tables = $dom->getElementsByTagName('table');
        $legisladores = [];

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            if ($rows->length < 2) continue;

            $headerThs = $rows->item(0)->getElementsByTagName('th');
            $headerText = '';
            foreach ($headerThs as $th) {
                $headerText .= ' ' . strtolower(trim($th->textContent));
            }

            if (strpos($headerText, 'diputados') !== false || strpos($headerText, 'total') !== false) {
                continue;
            }

            if (strpos($headerText, 'distrito') !== false && strpos($headerText, 'diputado') !== false) {
                $hasCabecera = strpos($headerText, 'cabecera') !== false;
                $hasSuplente = strpos($headerText, 'suplente') !== false;

                for ($i = 1; $i < $rows->length; $i++) {
                    $cells = $this->extractRowCells($rows->item($i));
                    if (count($cells) < 2 || empty($cells[0])) continue;

                    $distrito = $cells[0];
                    $cabecera = $hasCabecera ? ($cells[1] ?? null) : null;
                    $colIdx = $hasCabecera ? 2 : 1;
                    $nombre = $cells[$colIdx++] ?? '';
                    $suplente = ($hasSuplente && count($cells) > $colIdx) ? ($cells[$colIdx++] ?? null) : null;
                    $partido = end($cells);

                    if (empty($nombre) || strlen($nombre) < 3) continue;

                    $legisladores[] = [
                        'principio' => 'Mayoría Relativa',
                        'distrito' => 'Distrito ' . $distrito . ($cabecera ? " ({$cabecera})" : ''),
                        'nombre' => $nombre,
                        'suplente' => $suplente,
                        'partido' => (string)$partido,
                    ];
                }
            } elseif (strpos($headerText, 'diputado') !== false && strpos($headerText, 'partido') !== false && strpos($headerText, 'distrito') === false) {
                $hasSuplente = strpos($headerText, 'suplente') !== false;
                for ($i = 1; $i < $rows->length; $i++) {
                    $cells = $this->extractRowCells($rows->item($i));
                    if (count($cells) < 2 || empty($cells[0])) continue;

                    $nombre = $cells[0];
                    if (strlen($nombre) < 3 || preg_match('/partido|total|\bfuente\b/iu', $nombre)) continue;

                    $suplente = ($hasSuplente && count($cells) >= 3) ? $cells[1] : null;
                    $legisladores[] = [
                        'principio' => 'Representación Proporcional',
                        'distrito' => 'RP',
                        'nombre' => $nombre,
                        'suplente' => $suplente,
                        'partido' => (string)end($cells),
                    ];
                }
            }
        }

        return $legisladores;
    }

    private function extractRowCells(DOMElement $tr): array
    {
        $cells = [];
        foreach ($tr->childNodes as $child) {
            if ($child->nodeName === 'td' || $child->nodeName === 'th') {
                $text = preg_replace('/\[\d+\]/', '', $child->textContent);
                $cells[] = trim((string)preg_replace('/\s+/', ' ', (string)$text));
            }
        }
        return $cells;
    }

    private function normalizeParty(string $p): string
    {
        $p = trim(preg_replace('/\([^\)]*\)|\[\d+\]/', '', $p));
        $map = [
            '/morena|regeneraci[oó]n/iu' => 'MORENA',
            '/acci[oó]n nacional|^pan$/iu' => 'PAN',
            '/revolucionario institucional|^pri$/iu' => 'PRI',
            '/revoluci[oó]n democr[aá]tica|^prd$/iu' => 'PRD',
            '/del trabajo|^pt$/iu' => 'PT',
            '/verde|^pvem$/iu' => 'PVEM',
            '/movimiento ciudadano|^mc$/iu' => 'MC',
            '/nueva alianza|^panal$/iu' => 'PANAL',
            '/revoluci[oó]n socialista|^prs$/iu' => 'PRS',
            '/lev[aá]ntate|^mln$/iu' => 'MLN',
            '/fuerza por m[eé]xico|^fxm/iu' => 'FXM',
            '/redes sociales|^rsp$/iu' => 'RSP',
            '/encuentro solidario|encuentro social|^pes/iu' => 'PES',
            '/sin partido|independiente/iu' => 'Independiente',
        ];
        foreach ($map as $pattern => $val) {
            if (preg_match($pattern, $p)) return $val;
        }
        return $p ?: 'S/P';
    }

    private function buildRegistro(
        string $slugId,
        string $nombre,
        int $leg,
        string $partido,
        string $principio,
        string $distrito,
        ?string $suplente,
        string $inicio,
        string $fuenteUrl,
        ?string $correo = null,
        ?string $tel = null,
        ?string $foto = null
    ): array {
        $hashEvidencia = hash('sha256', "{$nombre}|{$partido}|{$distrito}|{$leg}");
        return [
            'referencia_sil' => $slugId,
            'nombre_completo' => $nombre,
            'camara' => 'Congreso del Estado de Nayarit',
            'legislatura' => $leg,
            'partido' => $partido,
            'principio_eleccion' => $principio,
            'entidad' => 'Nayarit',
            'distrito' => $distrito,
            'tipo_cargo' => 'Propietario',
            'suplente_nombre' => $suplente,
            'fecha_toma_protesta' => $inicio,
            'fecha_nacimiento' => null,
            'correo_institucional' => $correo,
            'telefono' => $tel,
            'ubicacion_oficina' => 'H. Congreso del Estado de Nayarit, Tepic, Nayarit',
            'ultimo_grado_estudios' => null,
            'preparacion_academica' => null,
            'experiencia_legislativa_resumen' => null,
            'foto_oficial_url' => $foto,
            'foto_local_path' => null,
            'total_comisiones' => 0,
            'comisiones' => [],
            'trayectoria_politica' => [], 'trayectoria_legislativa' => [],
            'trayectoria_administrativa' => [], 'escolaridad_detalle' => [],
            'licencias' => [], 'iniciativas' => [], 'puntos_acuerdo' => [], 'redes_sociales' => [],
            'metadata' => [
                'fuente_url' => $fuenteUrl,
                'sha256_evidencia' => $hashEvidencia,
                'fecha_extraccion' => date('Y-m-d H:i:s'),
                'entidad_tipo' => 'Congreso Local',
            ],
        ];
    }

    private function guardarDataset(int $leg, string $periodo, string $fuente, array $legisladores, int $mr, int $rp): array
    {
        $meta = self::LEGISLATURAS_META[$leg];
        $romano = $meta['romano'];

        $dataset = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Nayarit - {$romano} Legislatura ({$periodo})",
                'entidad' => 'Nayarit',
                'camara' => 'Congreso Local',
                'legislatura' => $leg,
                'periodo' => $periodo,
                'generado_en' => date('c'),
                'fuente_url' => $fuente,
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        $targetDir = $this->storagePath . '/data/json';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = "{$targetDir}/congreso_nayarit_leg_{$leg}.json";
        $json = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filePath, $json);

        $this->logger->info("Dataset guardado: {$filePath} (" . count($legisladores) . " diputados)");

        return [
            'ok' => true,
            'legislatura' => $leg,
            'romano' => $romano,
            'archivo' => $filePath,
            'total_legisladores' => count($legisladores),
            'diputados_mayoria_relativa' => $mr,
            'diputados_representacion_proporcional' => $rp,
        ];
    }
}
