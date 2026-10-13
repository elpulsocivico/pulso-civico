<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\DTO\LegisladorDTO;

/**
 * NuevoLeonScraperService: Extractor oficial del H. Congreso del Estado de Nuevo León.
 * Extrae legisladores de la actual (LXXVII) e históricas disponibles (LXXI a LXXVI).
 */
class NuevoLeonScraperService
{
    public const LEGISLATURAS = [
        77 => [
            'romano' => 'LXXVII',
            'periodo' => '2024-2027',
            'fuente_url' => 'https://www.hcnl.gob.mx/organizacion/distritos.php',
            'tipo' => 'html_distritos',
            'activa' => true,
        ],
        76 => [
            'romano' => 'LXXVI',
            'periodo' => '2021-2024',
            'fuente_url' => 'https://www.hcnl.gob.mx/organizacion/diputados-lxxvi.php',
            'tipo' => 'html_list',
            'activa' => false,
        ],
        75 => [
            'romano' => 'LXXV',
            'periodo' => '2018-2021',
            'fuente_url' => 'https://www.hcnl.gob.mx/organizacion/diputados-lxxv.php',
            'tipo' => 'html_list',
            'activa' => false,
        ],
        74 => [
            'romano' => 'LXXIV',
            'periodo' => '2015-2018',
            'fuente_url' => 'https://www.hcnl.gob.mx/organizacion/pdf/diputados_LXXIV.pdf',
            'tipo' => 'pdf',
            'activa' => false,
        ],
        73 => [
            'romano' => 'LXXIII',
            'periodo' => '2012-2015',
            'fuente_url' => 'https://www.hcnl.gob.mx/organizacion/pdf/diputados_LXXIII.pdf',
            'tipo' => 'pdf',
            'activa' => false,
        ],
        72 => [
            'romano' => 'LXXII',
            'periodo' => '2009-2012',
            'fuente_url' => 'https://www.hcnl.gob.mx/organizacion/pdf/diputados_LXXII.pdf',
            'tipo' => 'pdf',
            'activa' => false,
        ],
        71 => [
            'romano' => 'LXXI',
            'periodo' => '2006-2009',
            'fuente_url' => 'https://www.hcnl.gob.mx/organizacion/pdf/diputados_LXXI.pdf',
            'tipo' => 'pdf',
            'activa' => false,
        ],
    ];

    private array $config;
    private HttpClient $http;
    private Logger $logger;

    public function __construct(array $config, HttpClient $http, Logger $logger)
    {
        $this->config = $config;
        $this->http = $http;
        $this->logger = $logger;
    }

    /**
     * Extrae todas las legislaturas disponibles del Congreso de Nuevo León (71 a 77).
     */
    public function extraerTodas(): array
    {
        $resultados = [];
        $orden = [71, 72, 73, 74, 75, 76, 77];
        foreach ($orden as $leg) {
            $this->logger->info("Iniciando extracción Congreso Nuevo León", ['leg' => $leg]);
            $resultados[$leg] = $this->extraer($leg);
        }
        return [
            'ok' => true,
            'total_legislaturas' => count($resultados),
            'legislaturas' => array_keys($resultados),
            'detalles' => $resultados,
        ];
    }

    /**
     * Extrae legisladores de Nuevo León para una legislatura específica y genera el dataset JSON.
     */
    public function extraer(int $legislatura = 77): array
    {
        if (!isset(self::LEGISLATURAS[$legislatura])) {
            throw new \InvalidArgumentException("Legislatura {$legislatura} no soportada para Nuevo León.");
        }

        $info = self::LEGISLATURAS[$legislatura];
        $rawDir = $this->config['paths']['storage'] . '/raw/nuevoleon';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }

        $this->logger->info("Iniciando scraper Congreso de Nuevo León", [
            'legislatura' => $legislatura,
            'romano' => $info['romano'],
            'periodo' => $info['periodo'],
            'tipo' => $info['tipo'],
        ]);

        $items = match ($info['tipo']) {
            'html_distritos' => $this->parsearDistritosHtml($legislatura, $info, $rawDir),
            'html_list' => $this->parsearListaHtml($legislatura, $info, $rawDir),
            'pdf' => $this->parsearPdf($legislatura, $info, $rawDir),
            default => throw new \RuntimeException("Tipo de extractor no soportado: {$info['tipo']}"),
        };

        $legisladores = [];
        foreach ($items as $item) {
            $dto = new LegisladorDTO(
                referenciaSil: 'HCNL-' . $legislatura . '-' . md5($item['nombre_completo']),
                nombreCompleto: $item['nombre_completo'],
                camara: 'Congreso Local',
                legislatura: $legislatura,
                partido: $item['partido'] ?? 'Sin Partido',
                principioEleccion: $item['principio_eleccion'] ?? 'Mayoría Relativa',
                entidad: 'Nuevo León',
                distrito: $item['distrito'] ?? null,
                tipoCargo: $item['tipo_cargo'] ?? 'Propietario',
                suplenteNombre: $item['suplente_nombre'] ?? null,
                correoInstitucional: $item['correo_institucional'] ?? null,
                telefono: $item['telefono'] ?? null,
                fotoOficialUrl: $item['foto_url'] ?? null,
                metadata: [
                    'fuente_url' => $info['fuente_url'],
                    'sha256_evidencia' => $item['sha256'] ?? '',
                    'fecha_extraccion' => date('Y-m-d H:i:s'),
                    'entidad_tipo' => 'Congreso Local',
                    'municipios' => $item['municipios'] ?? null,
                    'perfil_url' => $item['perfil_url'] ?? null,
                ]
            );
            $legisladores[] = $dto->toArray();
        }

        $dataDir = $this->config['paths']['data_json'];
        $archivoSalida = "{$dataDir}/congreso_nuevoleon_leg_{$legislatura}.json";

        $resumen = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Nuevo León - {$info['romano']} Legislatura ({$info['periodo']})",
                'entidad' => 'Nuevo León',
                'camara' => 'Congreso Local',
                'legislatura' => $legislatura,
                'periodo' => $info['periodo'],
                'generado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'fuente_url' => $info['fuente_url'],
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        file_put_contents(
            $archivoSalida,
            json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->logger->success("Dataset del Congreso de Nuevo León generado con éxito", [
            'archivo' => $archivoSalida,
            'total' => count($legisladores),
        ]);

        return [
            'ok' => true,
            'legislatura' => $legislatura,
            'romano' => $info['romano'],
            'periodo' => $info['periodo'],
            'total_legisladores' => count($legisladores),
            'archivo' => $archivoSalida,
        ];
    }

    private function parsearDistritosHtml(int $leg, array $info, string $rawDir): array
    {
        $cacheFile = "{$rawDir}/distritos_leg_{$leg}.html";
        $html = '';
        if (file_exists($cacheFile) && filesize($cacheFile) > 5000) {
            $html = (string)file_get_contents($cacheFile);
        } else {
            $res = $this->http->request('GET', $info['fuente_url']);
            $html = $res['body'] ?? '';
            if ($html !== '') {
                file_put_contents($cacheFile, $html);
            }
        }

        if (empty($html)) {
            throw new \RuntimeException("No fue posible descargar HTML de distritos para Nuevo León (Leg {$leg}).");
        }

        $sha256 = EvidenceHasher::hashContent($html);
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        $rows = $xpath->query('//table[@id="Tabla"]/tbody/tr');

        $items = [];
        foreach ($rows as $row) {
            $tds = $xpath->query('./td', $row);
            if ($tds->length >= 8) {
                $fotoNode = $xpath->query('.//img', $tds->item(0))->item(0);
                $foto = $fotoNode ? $fotoNode->getAttribute('src') : null;
                $nombreNode = $xpath->query('.//h5', $tds->item(1))->item(0);
                $nombreRaw = $nombreNode ? trim($nombreNode->textContent) : '';
                $nombre = trim((string)preg_replace('/^DIP\.\s+/i', '', $nombreRaw));

                $perfilNode = $xpath->query('.//a', $tds->item(1))->item(0);
                $perfilUrl = $perfilNode ? $perfilNode->getAttribute('href') : null;

                $partidoNode = $xpath->query('.//img', $tds->item(2))->item(0);
                $partido = $partidoNode ? trim($partidoNode->getAttribute('alt')) : 'Sin Partido';
                if ($partido === 'PMC') {
                    $partido = 'MC';
                }

                $rep = trim($tds->item(3)->textContent);
                $distrito = trim($tds->item(4)->textContent);
                $municipios = trim($tds->item(5)->textContent);
                $telefono = trim($tds->item(6)->textContent);
                $correo = trim($tds->item(7)->textContent);

                $items[] = [
                    'nombre_completo' => $nombre,
                    'partido' => $partido !== '' ? $partido : 'Sin Partido',
                    'principio_eleccion' => $rep !== '' ? $rep : 'Mayoría Relativa',
                    'distrito' => $distrito !== '' ? $distrito : null,
                    'tipo_cargo' => 'Propietario',
                    'municipios' => $municipios !== '' ? $municipios : null,
                    'telefono' => $telefono !== '' ? $telefono : null,
                    'correo_institucional' => $correo !== '' ? $correo : null,
                    'foto_url' => $foto,
                    'perfil_url' => $perfilUrl,
                    'sha256' => $sha256,
                ];
            }
        }
        return $items;
    }

    private function parsearListaHtml(int $leg, array $info, string $rawDir): array
    {
        $cacheFile = "{$rawDir}/diputados_leg_{$leg}.html";
        $html = '';
        if (file_exists($cacheFile) && filesize($cacheFile) > 5000) {
            $html = (string)file_get_contents($cacheFile);
        } else {
            $res = $this->http->request('GET', $info['fuente_url']);
            $html = $res['body'] ?? '';
            if ($html !== '') {
                file_put_contents($cacheFile, $html);
            }
        }

        if (empty($html)) {
            throw new \RuntimeException("No fue posible descargar lista HTML de Nuevo León (Leg {$leg}).");
        }

        $sha256 = EvidenceHasher::hashContent($html);
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        $elements = $xpath->query('//div[contains(@class, "list-group-item")]');

        $items = [];
        foreach ($elements as $el) {
            $nombreNode = $xpath->query('.//h4[contains(@class, "media-heading")]', $el)->item(0);
            if (!$nombreNode) {
                continue;
            }
            $nombreRaw = trim($nombreNode->textContent);
            $nombre = trim((string)preg_replace('/^DIP\.\s+/i', '', $nombreRaw));

            $fotoNode = $xpath->query('.//img[contains(@class, "img-rounded")]', $el)->item(0);
            $foto = $fotoNode ? $fotoNode->getAttribute('src') : null;

            $emailNode = $xpath->query('.//a[starts-with(@href, "mailto:")]', $el)->item(0);
            $correo = $emailNode ? str_replace('mailto:', '', $emailNode->getAttribute('href')) : null;

            $perfilNode = $xpath->query('.//h4[contains(@class, "media-heading")]//a', $el)->item(0);
            $perfil = $perfilNode ? $perfilNode->getAttribute('href') : null;

            $cols = $xpath->query('.//div[contains(@class, "col-md-2")]', $el);
            $rep = 'Mayoría Relativa';
            $distrito = null;
            $partido = 'Sin Partido';

            foreach ($cols as $col) {
                $txt = trim($col->textContent);
                if (stripos($txt, 'Representación:') !== false) {
                    $rep = trim((string)str_ireplace('Representación:', '', $txt));
                } elseif (stripos($txt, 'Distrito Electoral:') !== false) {
                    $distrito = trim((string)str_ireplace('Distrito Electoral:', '', $txt));
                } elseif (stripos($txt, 'Grupo Legislativo:') !== false) {
                    $partido = trim((string)str_ireplace('Grupo Legislativo:', '', $txt));
                }
            }

            if ($partido === 'PMC') {
                $partido = 'MC';
            }

            $items[] = [
                'nombre_completo' => $nombre,
                'partido' => $partido !== '' ? $partido : 'Sin Partido',
                'principio_eleccion' => $rep !== '' ? $rep : 'Mayoría Relativa',
                'distrito' => $distrito !== '' ? $distrito : null,
                'tipo_cargo' => 'Propietario',
                'correo_institucional' => $correo,
                'foto_url' => $foto,
                'perfil_url' => $perfil,
                'sha256' => $sha256,
            ];
        }
        return $items;
    }

    private function parsearPdf(int $leg, array $info, string $rawDir): array
    {
        $cacheFile = "{$rawDir}/diputados_leg_{$leg}.pdf";
        if (!file_exists($cacheFile) || filesize($cacheFile) < 1000) {
            $ch = curl_init($info['fuente_url']);
            $fp = fopen($cacheFile, 'w+');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_exec($ch);
            if (PHP_VERSION_ID < 80500) {
                @curl_close($ch);
            }
            fclose($fp);
        }

        if (!file_exists($cacheFile) || filesize($cacheFile) < 1000) {
            throw new \RuntimeException("Error al descargar PDF de Nuevo León (Leg {$leg}).");
        }

        $sha256 = EvidenceHasher::hashFile($cacheFile);
        $parserScript = dirname(__DIR__, 2) . '/tools/core/nl_pdf_parser.py';
        $cmd = escapeshellcmd("python3 {$parserScript} --file {$cacheFile} --leg {$leg}");
        $output = shell_exec($cmd);

        $parsed = json_decode((string)$output, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException("Fallo al parsear PDF de Nuevo León (Leg {$leg}): {$output}");
        }

        foreach ($parsed as &$p) {
            $p['sha256'] = $sha256;
        }

        return $parsed;
    }
}
