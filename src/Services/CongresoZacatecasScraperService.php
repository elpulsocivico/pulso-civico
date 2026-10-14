<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use DOMDocument;
use DOMElement;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

class CongresoZacatecasScraperService
{
    private const LEGISLATURAS_META = [
        65 => ['romano' => 'LXV', 'periodo' => '2024-2027', 'inicio' => '2024-09-07', 'fin' => '2027-09-06'],
        64 => ['romano' => 'LXIV', 'periodo' => '2021-2024', 'inicio' => '2021-09-07', 'fin' => '2024-09-06'],
        63 => ['romano' => 'LXIII', 'periodo' => '2018-2021', 'inicio' => '2018-09-07', 'fin' => '2021-09-06'],
        62 => ['romano' => 'LXII', 'periodo' => '2016-2018', 'inicio' => '2016-09-07', 'fin' => '2018-09-06'],
        61 => ['romano' => 'LXI', 'periodo' => '2013-2016', 'inicio' => '2013-09-07', 'fin' => '2016-09-06'],
        60 => ['romano' => 'LX', 'periodo' => '2010-2013', 'inicio' => '2010-09-07', 'fin' => '2013-09-06'],
        59 => ['romano' => 'LIX', 'periodo' => '2007-2010', 'inicio' => '2007-09-07', 'fin' => '2010-09-06'],
        58 => ['romano' => 'LVIII', 'periodo' => '2004-2007', 'inicio' => '2004-09-07', 'fin' => '2007-09-06'],
        57 => ['romano' => 'LVII', 'periodo' => '2001-2004', 'inicio' => '2001-09-07', 'fin' => '2004-09-06'],
        56 => ['romano' => 'LVI', 'periodo' => '1998-2001', 'inicio' => '1998-09-07', 'fin' => '2001-09-06'],
        55 => ['romano' => 'LV', 'periodo' => '1995-1998', 'inicio' => '1995-09-07', 'fin' => '1998-09-06'],
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
            'entidad' => 'Zacatecas',
            'legislaturas_procesadas' => count($resultados),
            'total_general_legisladores' => $totalGeneral,
            'detalle' => $resultados,
        ];
    }

    public function scrapeLegislatura(int $leg): array
    {
        if (!isset(self::LEGISLATURAS_META[$leg])) {
            return [
                'ok' => false,
                'error' => "Legislatura {$leg} de Zacatecas no soportada (Rango válido: 55 a 65).",
            ];
        }

        $meta = self::LEGISLATURAS_META[$leg];
        $romano = $meta['romano'];
        $periodo = $meta['periodo'];

        $this->logger->info("Extrayendo H. Congreso de Zacatecas: Legislatura {$romano} ({$periodo})...");

        $url = "https://es.wikipedia.org/w/api.php?action=parse&page=Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Zacatecas&prop=text&format=json";
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
        $directorioOficial = ($leg === 65) ? $this->getDirectorioOficial65() : [];

        $legisladores = [];
        $fuenteUrl = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Zacatecas";
        $mrCount = 0;
        $rpCount = 0;

        foreach ($rawItems as $idx => $item) {
            $slugId = "zac_leg{$leg}_" . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $partidoNorm = $this->normalizeParty($item['partido']);
            $item['principio'] === 'Mayoría Relativa' ? $mrCount++ : $rpCount++;

            $nombre = $item['nombre'];
            $correo = null;
            $foto = null;

            // Enriquecer con portal oficial si es la 65
            if ($leg === 65 && !empty($directorioOficial)) {
                $match = $this->matchDirectorio($nombre, $directorioOficial);
                if ($match) {
                    $correo = $match['email'] ?? null;
                    $foto = $match['foto_url'] ?? null;
                }
            }

            $legisladores[] = $this->buildRegistro(
                $slugId,
                'Dip. ' . $nombre,
                $leg,
                $partidoNorm,
                $item['principio'],
                $item['distrito'],
                $item['suplente'],
                $meta['inicio'],
                $fuenteUrl,
                $correo,
                null,
                $foto
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

            if (strpos($headerText, 'distrito') !== false && (strpos($headerText, 'diputado') !== false || strpos($headerText, 'nombre') !== false)) {
                $hasCabecera = strpos($headerText, 'cabecera') !== false;
                $hasSuplente = strpos($headerText, 'suplente') !== false;

                for ($i = 1; $i < $rows->length; $i++) {
                    $cells = $this->extractRowCells($rows->item($i));
                    if (count($cells) < 2 || empty($cells[0])) continue;

                    $distrito = $cells[0];
                    if (strlen($distrito) > 15) continue;

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
            } elseif ((strpos($headerText, 'diputado') !== false || strpos($headerText, 'nombre') !== false) && strpos($headerText, 'partido') !== false && strpos($headerText, 'distrito') === false) {
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

    private function getDirectorioOficial65(): array
    {
        $ch = curl_init('https://www.congresozac.gob.mx/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = (string)curl_exec($ch);

        preg_match_all('/<a[^>]+href=[\"\x27]\/65\/perfil\/([a-z0-9]+)[\"\x27][^>]*>(.*?)<\/a>/iu', $res, $m);
        $slugs = $m[1] ?? [];
        $names = array_map(fn($n) => trim(strip_tags($n)), $m[2] ?? []);

        $directorio = [];
        for ($i = 0; $i < count($slugs); $i++) {
            $slug = $slugs[$i];
            $name = $names[$i];
            if (empty($name)) continue;

            $email = null;
            if (preg_match('/mailto:([a-zA-Z0-9_\-\.]+@congresozac\.gob\.mx)/i', substr($res, (int)strpos($res, "/65/perfil/{$slug}"), 800), $em)) {
                $email = $em[1];
            }
            $directorio[] = [
                'nombre' => $name,
                'slug' => $slug,
                'email' => $email,
                'foto_url' => "https://www.congresozac.gob.mx/coz7/images/Perfiles/{$slug}.jpg",
            ];
        }

        return $directorio;
    }

    private function matchDirectorio(string $nombre, array $directorio): ?array
    {
        $nParts = array_filter(explode(' ', mb_strtolower($nombre)), fn($w) => mb_strlen($w) > 3);
        foreach ($directorio as $dir) {
            $hits = 0;
            $dirLower = mb_strtolower($dir['nombre']);
            foreach ($nParts as $p) {
                if (str_contains($dirLower, $p)) $hits++;
            }
            if ($hits >= 2) return $dir;
        }
        return null;
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
            'camara' => 'Congreso del Estado de Zacatecas',
            'legislatura' => $leg,
            'partido' => $partido,
            'principio_eleccion' => $principio,
            'entidad' => 'Zacatecas',
            'distrito' => $distrito,
            'tipo_cargo' => 'Propietario',
            'suplente_nombre' => $suplente,
            'fecha_toma_protesta' => $inicio,
            'fecha_nacimiento' => null,
            'correo_institucional' => $correo,
            'telefono' => $tel,
            'ubicacion_oficina' => 'Sede del Poder Legislativo, Zacatecas, Zac.',
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
                'titulo' => "H. Congreso del Estado de Zacatecas - {$romano} Legislatura ({$periodo})",
                'entidad' => 'Zacatecas',
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

        $filePath = "{$targetDir}/congreso_zacatecas_leg_{$leg}.json";
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
