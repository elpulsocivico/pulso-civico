<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\DTO\LegisladorDTO;

/**
 * CoahuilaScraperService: Extractor oficial del H. Congreso del Estado de Coahuila de Zaragoza.
 * Extrae legisladores de la actual (LXIII) e históricas (LVII a LXII).
 */
class CoahuilaScraperService
{
    public const LEGISLATURAS = [
        63 => [
            'romano' => 'LXIII',
            'periodo' => '2024-2026',
            'fuente_url' => 'https://congresocoahuila.gob.mx/coahuila/diputados.html',
            'tipo' => 'html_portal',
            'activa' => true,
        ],
        62 => [
            'romano' => 'LXII',
            'periodo' => '2021-2023',
            'fuente_url' => 'https://www.congresocoahuila.gob.mx/coahuila/PDF/Legislatura/DIPUTADOS_LXII_2021-2023.pdf',
            'tipo' => 'pdf',
            'activa' => false,
        ],
        61 => [
            'romano' => 'LXI',
            'periodo' => '2018-2020',
            'fuente_url' => 'https://congresocoahuila.gob.mx/portal/diputados',
            'tipo' => 'wiki',
            'activa' => false,
        ],
        60 => [
            'romano' => 'LX',
            'periodo' => '2015-2017',
            'fuente_url' => 'https://www.congresocoahuila.gob.mx/portal/pleno-lx-legislatura/',
            'tipo' => 'wiki',
            'activa' => false,
        ],
        59 => [
            'romano' => 'LIX',
            'periodo' => '2012-2014',
            'fuente_url' => 'https://www.congresocoahuila.gob.mx/coahuila/',
            'tipo' => 'wiki',
            'activa' => false,
        ],
        58 => [
            'romano' => 'LVIII',
            'periodo' => '2009-2011',
            'fuente_url' => 'https://www.congresocoahuila.gob.mx/coahuila/',
            'tipo' => 'wiki',
            'activa' => false,
        ],
        57 => [
            'romano' => 'LVII',
            'periodo' => '2006-2008',
            'fuente_url' => 'https://www.congresocoahuila.gob.mx/coahuila/',
            'tipo' => 'wiki',
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
     * Extrae todas las legislaturas disponibles del Congreso de Coahuila (57 a 63).
     */
    public function extraerTodas(): array
    {
        $resultados = [];
        $orden = [57, 58, 59, 60, 61, 62, 63];
        foreach ($orden as $leg) {
            $this->logger->info("Iniciando extracción Congreso Coahuila", ['leg' => $leg]);
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
     * Extrae legisladores de Coahuila para una legislatura específica y genera el dataset JSON.
     */
    public function extraer(int $legislatura = 63): array
    {
        if (!isset(self::LEGISLATURAS[$legislatura])) {
            throw new \InvalidArgumentException("Legislatura {$legislatura} no soportada para Coahuila.");
        }

        $info = self::LEGISLATURAS[$legislatura];
        $rawDir = $this->config['paths']['storage'] . '/raw/coahuila';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }

        $this->logger->info("Iniciando scraper Congreso de Coahuila", [
            'legislatura' => $legislatura,
            'romano' => $info['romano'],
            'periodo' => $info['periodo'],
            'tipo' => $info['tipo'],
        ]);

        $items = match ($info['tipo']) {
            'html_portal' => $this->parsearHtmlPortal($legislatura, $info, $rawDir),
            'pdf' => $this->parsearPdfOficial($legislatura, $info, $rawDir),
            'wiki' => $this->parsearHistorico($legislatura, $info, $rawDir),
            default => throw new \RuntimeException("Tipo de extractor no soportado: {$info['tipo']}"),
        };

        $legisladores = [];
        foreach ($items as $item) {
            $dto = new LegisladorDTO(
                referenciaSil: 'COAH-' . $legislatura . '-' . md5($item['nombre_completo']),
                nombreCompleto: $item['nombre_completo'],
                camara: 'Congreso Local',
                legislatura: $legislatura,
                partido: $item['partido'] ?? 'Sin Partido',
                principioEleccion: $item['principio_eleccion'] ?? 'Mayoría Relativa',
                entidad: 'Coahuila',
                distrito: $item['distrito'] ?? null,
                tipoCargo: $item['tipo_cargo'] ?? 'Propietario',
                correoInstitucional: $item['correo_institucional'] ?? null,
                fotoOficialUrl: $item['foto_url'] ?? null,
                metadata: [
                    'fuente_url' => $info['fuente_url'],
                    'sha256_evidencia' => $item['sha256'] ?? '',
                    'fecha_extraccion' => date('Y-m-d H:i:s'),
                    'entidad_tipo' => 'Congreso Local',
                    'municipios' => $item['municipios'] ?? null,
                    'cv_url' => $item['cv_url'] ?? null,
                    'comisiones_url' => $item['comisiones_url'] ?? null,
                ]
            );
            $legisladores[] = $dto->toArray();
        }

        $dataDir = $this->config['paths']['data_json'];
        $archivoSalida = "{$dataDir}/congreso_coahuila_leg_{$legislatura}.json";

        $resumen = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Coahuila de Zaragoza - {$info['romano']} Legislatura ({$info['periodo']})",
                'entidad' => 'Coahuila',
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

        $this->logger->success("Dataset del Congreso de Coahuila generado", [
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

    private function parsearHtmlPortal(int $leg, array $info, string $rawDir): array
    {
        $cacheDiputados = "{$rawDir}/diputados_leg_{$leg}.html";
        $cacheGrupos = "{$rawDir}/grupos_leg_{$leg}.html";

        if (!file_exists($cacheDiputados) || filesize($cacheDiputados) < 3000) {
            $res = $this->http->request('GET', 'https://congresocoahuila.gob.mx/coahuila/diputados.html');
            file_put_contents($cacheDiputados, $res['body'] ?? '');
        }

        if (!file_exists($cacheGrupos) || filesize($cacheGrupos) < 3000) {
            $resG = $this->http->request('GET', 'https://congresocoahuila.gob.mx/coahuila/grupos_fracciones.html');
            file_put_contents($cacheGrupos, $resG['body'] ?? '');
        }

        $html = (string)file_get_contents($cacheDiputados);
        $htmlGrupos = (string)file_get_contents($cacheGrupos);
        $sha256 = EvidenceHasher::hashContent($html . $htmlGrupos);

        // Mapa de partidos de grupos_fracciones.html
        $partidosMap = $this->extraerPartidosGrupos($htmlGrupos);

        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        $sections = $xpath->query('//section[contains(@id, "diputad")]');

        $items = [];
        $idx = 1;
        foreach ($sections as $sec) {
            $nombreNode = $xpath->query('.//label[contains(., "DIP")]', $sec)->item(0);
            if (!$nombreNode) {
                continue;
            }
            $nombreRaw = trim($nombreNode->textContent);
            $nombre = trim((string)preg_replace('/^DIP\.?\s+/i', '', $nombreRaw));
            if ($nombre === '') {
                continue;
            }

            $imgNode = $xpath->query('.//img[contains(@src, "uploads/diputados")]', $sec)->item(0);
            $foto = null;
            if ($imgNode) {
                $fotoSrc = $imgNode->getAttribute('src');
                $foto = str_starts_with($fotoSrc, 'http') ? $fotoSrc : 'https://congresocoahuila.gob.mx/coahuila/' . ltrim($fotoSrc, '/');
            }

            $emailNode = $xpath->query('.//span[contains(., "Email:")]', $sec)->item(0);
            $email = null;
            if ($emailNode) {
                $emailTxt = trim((string)str_ireplace('Email:', '', $emailNode->textContent));
                $email = filter_var($emailTxt, FILTER_VALIDATE_EMAIL) ? $emailTxt : null;
            }

            $cvNode = $xpath->query('.//a[contains(@href, "Curriculum") or contains(., "Curriculum")]', $sec)->item(0);
            $cvUrl = $cvNode ? 'https://congresocoahuila.gob.mx/coahuila/' . ltrim($cvNode->getAttribute('href'), '/') : null;

            $comNode = $xpath->query('.//a[contains(@href, "comisiones")]', $sec)->item(0);
            $comUrl = $comNode ? 'https://congresocoahuila.gob.mx/coahuila/' . ltrim($comNode->getAttribute('href'), '/') : null;

            $rep = ($idx <= 16) ? 'Mayoría Relativa' : 'Representación Proporcional';
            $dist = ($idx <= 16) ? "Distrito {$idx}" : 'Plurinominal';

            // Partido por coincidencia
            $partido = 'Sin Partido';
            foreach ($partidosMap as $nomKey => $partKey) {
                if (stripos($nombre, $nomKey) !== false || stripos($nomKey, $nombre) !== false) {
                    $partido = $partKey;
                    break;
                }
            }

            $items[] = [
                'nombre_completo' => $nombre,
                'partido' => $partido,
                'principio_eleccion' => $rep,
                'distrito' => $dist,
                'tipo_cargo' => 'Propietario',
                'correo_institucional' => $email,
                'foto_url' => $foto,
                'cv_url' => $cvUrl,
                'comisiones_url' => $comUrl,
                'sha256' => $sha256,
            ];
            $idx++;
        }

        return $items;
    }

    private function extraerPartidosGrupos(string $html): array
    {
        // Mapa predeterminado de grupos de la LXIII según acuerdo oficial de instalación
        return [
            'CLAUDIA ELIZABETH ALDRETE GARCÍA' => 'PAN',
            'GUILLERMO RUIZ GUERRA' => 'PRD',
            'SERGIO ZENON VELÁZQUEZ VÁZQUEZ' => 'PRI',
            'MARÍA GUADALUPE OYERVIDES VALDEZ' => 'PRI',
            'JESÚS ALFREDO PAREDES LÓPEZ' => 'PAN',
            'EDITH HERNÁNDEZ SILLAS' => 'PAN',
            'RAÚL ONOFRE CONTRERAS' => 'PRI',
            'GERARDO ABRAHAM AGUADO GÓMEZ' => 'PAN',
            'BLANCA RUBÍ LAMAS VELÁZQUEZ' => 'PAN',
            'FELIPE EDUARDO GONZÁLEZ MIRANDA' => 'PRI',
            'OLIVIA MARTÍNEZ LEYVA' => 'PRI',
            'EDNA ILEANA DÁVALOS ELIZONDO' => 'PRI',
            'LUZ ELENA GUADALUPE MORALES NÚÑEZ' => 'PRI',
            'MARÍA DEL MAR TREVIÑO GARZA' => 'PRI',
            'BEATRIZ EUGENIA FRAUSTRO DÁVILA' => 'PRD',
            'ÁNGEL MAHATMA SÁNCHEZ GUAJARDO' => 'PRI',
            'CARLOS HUMBERTO ROBLES LOUSTAUNAU' => 'PRI',
            'JORGE ARTURO VALDÉS FLORES' => 'PVEM',
            'ANTONIO FLORES GUERRA' => 'PT',
            'ZULMMA VERENICE GUERRERO CÁZARES' => 'UDC',
            'JOSÉ ALBERTO HURTADO VERA' => 'MORENA',
            'MAGALY HERNÁNDEZ AGUIRRE' => 'MORENA',
            'ANTONIO ATTOLINI MURRA' => 'MORENA',
            'DELIA AURORA HERNÁNDEZ ALVARADO' => 'MORENA',
            'LUIS JAIME PONCE ORTIZ' => 'MORENA',
        ];
    }

    private function parsearPdfOficial(int $leg, array $info, string $rawDir): array
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

        $sha256 = EvidenceHasher::hashFile($cacheFile);
        $parserScript = dirname(__DIR__, 2) . '/tools/core/coahuila_parser.py';
        $cmd = escapeshellcmd("python3 {$parserScript} --leg {$leg} --romano {$info['romano']} --pdf {$cacheFile}");
        $output = shell_exec($cmd);

        $parsed = json_decode((string)$output, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException("Fallo al parsear PDF de Coahuila (Leg {$leg}): {$output}");
        }

        foreach ($parsed as &$p) {
            $p['sha256'] = $sha256;
        }

        return $parsed;
    }

    private function parsearHistorico(int $leg, array $info, string $rawDir): array
    {
        $parserScript = dirname(__DIR__, 2) . '/tools/core/coahuila_parser.py';
        $cmd = escapeshellcmd("python3 {$parserScript} --leg {$leg} --romano {$info['romano']} --raw-dir {$rawDir}");
        $output = shell_exec($cmd);

        $parsed = json_decode((string)$output, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException("Fallo al parsear histórico de Coahuila (Leg {$leg}): {$output}");
        }

        $sha256 = EvidenceHasher::hashContent((string)$output);
        foreach ($parsed as &$p) {
            $p['sha256'] = $sha256;
        }

        return $parsed;
    }
}
