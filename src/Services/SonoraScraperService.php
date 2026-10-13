<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\DTO\LegisladorDTO;

/**
 * SonoraScraperService: Extractor oficial del H. Congreso del Estado de Sonora.
 * Integra 10 legislaturas (LV a LXIV, 1997–2027) combinando la API REST oficial
 * de votación electrónica (votacion-electronica.api.congresoson.gob.mx) y los registros parlamentarios históricos.
 */
class SonoraScraperService
{
    public const LEGISLATURAS = [
        64 => ['romano' => 'LXIV', 'periodo' => '2024-2027', 'api_uuid' => '0c6f3d28-a382-4592-bd8a-81d1cef345a2'],
        63 => ['romano' => 'LXIII', 'periodo' => '2021-2024', 'api_uuid' => '2c68200d-b6b9-4bde-9654-8ebd1254433a'],
        62 => ['romano' => 'LXII', 'periodo' => '2018-2021', 'api_uuid' => null],
        61 => ['romano' => 'LXI', 'periodo' => '2015-2018', 'api_uuid' => null],
        60 => ['romano' => 'LX', 'periodo' => '2012-2015', 'api_uuid' => null],
        59 => ['romano' => 'LIX', 'periodo' => '2009-2012', 'api_uuid' => null],
        58 => ['romano' => 'LVIII', 'periodo' => '2006-2009', 'api_uuid' => null],
        57 => ['romano' => 'LVII', 'periodo' => '2003-2006', 'api_uuid' => null],
        56 => ['romano' => 'LVI', 'periodo' => '2000-2003', 'api_uuid' => null],
        55 => ['romano' => 'LV', 'periodo' => '1997-2000', 'api_uuid' => null],
    ];

    private const GRUPOS_PARLAMENTARIOS_UUID = [
        '51fb747f-3795-4998-9b9e-55f0ed3c377a' => 'MORENA',
        'fbed0b79-9d1b-406a-ae4c-ca88e4b960f7' => 'PT',
        'cb34f2b0-39c0-44c0-af77-8ecb6293e88f' => 'MC',
        'af3f88c8-053a-4590-bb1a-470ffe0c035e' => 'PAN',
        '7f4de55a-0b0f-42d9-8996-00a3d0254bbb' => 'NUEVA ALIANZA',
        '7bbb4fef-35bd-40ec-aa67-904c1de82d2c' => 'PRI',
        '5cfb56c6-11e5-4d3b-8ccd-f7341b9366a9' => 'PVEM',
        '7ca6cd66-427c-43ab-972f-632c671120d2' => 'PES',
        '87f80e25-a13f-4652-8dd8-892d51b1c80c' => 'PRD',
        '80df759d-f055-4054-9129-187edfe87447' => 'PS',
        'dc3b328f-14fe-4675-af12-79facfe14e41' => 'CES',
        'b35639d7-16dd-4d5f-888d-36b3943aa403' => 'Sin Partido',
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

    public function extraerTodas(): array
    {
        $resultados = [];
        $orden = [55, 56, 57, 58, 59, 60, 61, 62, 63, 64];
        foreach ($orden as $leg) {
            $this->logger->info("Iniciando extracción Congreso Sonora", ['leg' => $leg]);
            $resultados[$leg] = $this->extraer($leg);
            usleep(250000);
        }
        return [
            'ok' => true,
            'total_legislaturas' => count($resultados),
            'legislaturas' => array_keys($resultados),
            'detalles' => $resultados,
        ];
    }

    public function extraer(int $legislatura = 64): array
    {
        if (!isset(self::LEGISLATURAS[$legislatura])) {
            throw new \InvalidArgumentException("Legislatura {$legislatura} no soportada para Sonora.");
        }

        $info = self::LEGISLATURAS[$legislatura];
        $rawDir = $this->config['paths']['storage'] . '/raw/sonora';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }

        $this->logger->info("Iniciando scraper Congreso de Sonora", [
            'legislatura' => $legislatura,
            'romano' => $info['romano'],
            'periodo' => $info['periodo'],
        ]);

        if ($info['api_uuid'] !== null) {
            return $this->extraerDesdeApiOficial($rawDir, $info, $legislatura);
        }

        return $this->extraerHistoricaWiki($rawDir, $info, $legislatura);
    }

    private function extraerDesdeApiOficial(string $rawDir, array $info, int $legislatura): array
    {
        $cacheFile = "{$rawDir}/api_diputados_leg_{$legislatura}.json";
        $rawJson = '';

        if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
            $rawJson = (string)file_get_contents($cacheFile);
        } else {
            $apiUrl = "https://votacion-electronica.api.congresoson.gob.mx/publico/diputado?idLegislatura={$info['api_uuid']}&expand=distrito&limite=-1";
            $rawJson = $this->fetchUrl($apiUrl);
            if ($rawJson !== '') {
                file_put_contents($cacheFile, $rawJson);
            }
        }

        $sha256 = EvidenceHasher::hashContent($rawJson);
        $json = json_decode($rawJson, true);
        $diputadosApi = $json['resultado'] ?? [];

        $legisladores = [];
        $fuenteUrl = 'https://congresoson.gob.mx/diputados';

        foreach ($diputadosApi as $idx => $d) {
            $nombres = trim(($d['nombre'] ?? '') . ' ' . ($d['primerApellido'] ?? '') . ' ' . ($d['segundoApellido'] ?? ''));
            $nombres = SonoraTableParser::limpiarTexto($nombres);

            $idGrupo = $d['idGrupoParlamentario'] ?? '';
            $partido = self::GRUPOS_PARLAMENTARIOS_UUID[$idGrupo] ?? 'Sin Partido';

            $tipoElec = ($d['tipoEleccion'] ?? 'MR') === 'RP' ? 'Representación Proporcional' : 'Mayoría Relativa';

            $distrito = null;
            if ($tipoElec === 'Mayoría Relativa' && isset($d['distrito']['clave'])) {
                $claveDist = trim((string)$d['distrito']['clave']);
                if (is_numeric($claveDist)) {
                    $distrito = (string)(int)$claveDist;
                } elseif (preg_match('/^([IVXLCDM]+|\d+)$/i', $claveDist, $m)) {
                    $distrito = $m[1];
                }
            }

            $refId = 'SON-' . $legislatura . '-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $dto = new LegisladorDTO(
                referenciaSil: $refId,
                nombreCompleto: $nombres,
                camara: 'Congreso Local',
                legislatura: $legislatura,
                partido: $partido,
                principioEleccion: $tipoElec,
                entidad: 'Sonora',
                distrito: $distrito,
                tipoCargo: 'Propietario',
                suplenteNombre: !empty($d['suplente']) ? trim($d['suplente']) : null,
                fotoOficialUrl: !empty($d['foto']) ? $d['foto'] : null,
                metadata: [
                    'fuente_url' => $fuenteUrl,
                    'sha256_evidencia' => $sha256,
                    'fecha_extraccion' => date('Y-m-d H:i:s'),
                    'entidad_tipo' => 'Congreso Local',
                    'id_interno_api' => $d['id'] ?? null,
                    'email_oficial' => !empty($d['correo']) ? trim($d['correo']) : null,
                    'telefono' => !empty($d['telefono']) ? trim($d['telefono']) : null,
                ]
            );
            $legisladores[] = $dto->toArray();
        }

        return $this->guardarDataset($legislatura, $info, $fuenteUrl, $legisladores);
    }

    private function extraerHistoricaWiki(string $rawDir, array $info, int $legislatura): array
    {
        $anexoTitle = "Anexo:{$info['romano']}_Legislatura_del_Congreso_del_Estado_de_Sonora";
        $wikiUrl = "https://es.wikipedia.org/w/api.php?action=parse&page=" . urlencode($anexoTitle) . "&prop=text&format=json";

        $cacheFile = "{$rawDir}/anexo_leg_{$legislatura}.json";
        $rawContent = '';

        if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
            $rawContent = (string)file_get_contents($cacheFile);
        } else {
            $rawContent = $this->fetchUrl($wikiUrl);
            if ($rawContent !== '') {
                file_put_contents($cacheFile, $rawContent);
            }
        }

        if (empty($rawContent)) {
            throw new \RuntimeException("No fue posible cargar datos de Sonora (Leg {$legislatura}).");
        }

        $sha256 = EvidenceHasher::hashContent($rawContent);
        $json = json_decode($rawContent, true);
        $html = $json['parse']['text']['*'] ?? '';

        $items = SonoraTableParser::parseHtml($html);

        $legisladores = [];
        $fuenteUrl = "https://es.wikipedia.org/wiki/{$anexoTitle}";

        foreach ($items as $idx => $item) {
            $refId = 'SON-' . $legislatura . '-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $dto = new LegisladorDTO(
                referenciaSil: $refId,
                nombreCompleto: $item['nombre_completo'],
                camara: 'Congreso Local',
                legislatura: $legislatura,
                partido: $item['partido'] ?? 'Sin Partido',
                principioEleccion: $item['principio_eleccion'] ?? 'Mayoría Relativa',
                entidad: 'Sonora',
                distrito: $item['distrito'] !== null ? (string)$item['distrito'] : null,
                tipoCargo: 'Propietario',
                suplenteNombre: $item['suplente'] ?? null,
                fotoOficialUrl: null,
                metadata: [
                    'fuente_url' => $fuenteUrl,
                    'sha256_evidencia' => $sha256,
                    'fecha_extraccion' => date('Y-m-d H:i:s'),
                    'entidad_tipo' => 'Congreso Local',
                    'cabecera_distrital' => $item['cabecera'] ?? null,
                ]
            );
            $legisladores[] = $dto->toArray();
        }

        return $this->guardarDataset($legislatura, $info, $fuenteUrl, $legisladores);
    }

    private function guardarDataset(int $legislatura, array $info, string $fuenteUrl, array $legisladores): array
    {
        $dataDir = $this->config['paths']['data_json'];
        $archivoSalida = "{$dataDir}/congreso_sonora_leg_{$legislatura}.json";

        $resumen = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Sonora - {$info['romano']} Legislatura ({$info['periodo']})",
                'entidad' => 'Sonora',
                'camara' => 'Congreso Local',
                'legislatura' => $legislatura,
                'periodo' => $info['periodo'],
                'generado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'fuente_url' => $fuenteUrl,
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        file_put_contents($archivoSalida, json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->logger->info("Dataset del Congreso de Sonora generado", [
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

    private function fetchUrl(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PulsoCivicoBot/1.0 (contacto@pulsocivico.org)');
        $res = (string)curl_exec($ch);
        if (PHP_VERSION_ID < 80500) {
            @curl_close($ch);
        }
        return $res;
    }
}
