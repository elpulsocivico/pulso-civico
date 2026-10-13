<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\DTO\LegisladorDTO;

/**
 * SinaloaScraperService: Extractor oficial del H. Congreso del Estado de Sinaloa.
 * Integra 8 legislaturas oficiales (LVIII a LXV, 2004–2027) enriqueciendo con fotos oficiales
 * y perfiles institucionales de congresosinaloa.gob.mx.
 */
class SinaloaScraperService
{
    public const LEGISLATURAS = [
        65 => ['romano' => 'LXV', 'periodo' => '2024-2027', 'activa' => true],
        64 => ['romano' => 'LXIV', 'periodo' => '2021-2024', 'activa' => false],
        63 => ['romano' => 'LXIII', 'periodo' => '2018-2021', 'activa' => false],
        62 => ['romano' => 'LXII', 'periodo' => '2016-2018', 'activa' => false],
        61 => ['romano' => 'LXI', 'periodo' => '2013-2016', 'activa' => false],
        60 => ['romano' => 'LX', 'periodo' => '2010-2013', 'activa' => false],
        59 => ['romano' => 'LIX', 'periodo' => '2007-2010', 'activa' => false],
        58 => ['romano' => 'LVIII', 'periodo' => '2004-2007', 'activa' => false],
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
        $orden = [58, 59, 60, 61, 62, 63, 64, 65];
        foreach ($orden as $leg) {
            $this->logger->info("Iniciando extracción Congreso Sinaloa", ['leg' => $leg]);
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

    public function extraer(int $legislatura = 65): array
    {
        if (!isset(self::LEGISLATURAS[$legislatura])) {
            throw new \InvalidArgumentException("Legislatura {$legislatura} no soportada para Sinaloa.");
        }

        $info = self::LEGISLATURAS[$legislatura];
        $rawDir = $this->config['paths']['storage'] . '/raw/sinaloa';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }

        $this->logger->info("Iniciando scraper Congreso de Sinaloa", [
            'legislatura' => $legislatura,
            'romano' => $info['romano'],
            'periodo' => $info['periodo'],
        ]);

        $fotosOficiales = [];
        if ($legislatura === 65) {
            $fotosOficiales = $this->obtenerFotosOficiales65($rawDir);
        }

        $anexoTitle = "Anexo:{$info['romano']}_Legislatura_del_Congreso_del_Estado_de_Sinaloa";
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
            throw new \RuntimeException("No fue posible cargar datos de Sinaloa (Leg {$legislatura}).");
        }

        $sha256 = EvidenceHasher::hashContent($rawContent);
        $json = json_decode($rawContent, true);
        $html = $json['parse']['text']['*'] ?? '';

        $items = SinaloaTableParser::parseHtml($html, $fotosOficiales);

        $legisladores = [];
        $fuenteUrl = $info['activa']
            ? 'https://www.congresosinaloa.gob.mx/diputados-sinaloa/'
            : "https://es.wikipedia.org/wiki/{$anexoTitle}";

        foreach ($items as $idx => $item) {
            $refId = 'SIN-' . $legislatura . '-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $dto = new LegisladorDTO(
                referenciaSil: $refId,
                nombreCompleto: $item['nombre_completo'],
                camara: 'Congreso Local',
                legislatura: $legislatura,
                partido: $item['partido'] ?? 'Sin Partido',
                principioEleccion: $item['principio_eleccion'] ?? 'Mayoría Relativa',
                entidad: 'Sinaloa',
                distrito: $item['distrito'] !== null ? (string)$item['distrito'] : null,
                tipoCargo: 'Propietario',
                suplenteNombre: $item['suplente'] ?? null,
                fotoOficialUrl: $item['foto_url'] ?? null,
                metadata: [
                    'fuente_url' => $item['perfil_url'] ?? $fuenteUrl,
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

    private function obtenerFotosOficiales65(string $rawDir): array
    {
        $cacheFile = "{$rawDir}/portal_diputados_65.html";
        $html = '';

        if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
            $html = (string)file_get_contents($cacheFile);
        } else {
            $url = 'https://www.congresosinaloa.gob.mx/diputados-sinaloa/';
            $html = $this->fetchUrl($url);
            if ($html !== '') {
                file_put_contents($cacheFile, $html);
            }
        }

        if ($html === '') return [];

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//a[contains(@href, "/diputado65/")]');

        $map = [];
        $vistos = [];
        foreach ($links as $a) {
            $href = $a->getAttribute('href');
            if (isset($vistos[$href])) continue;
            $vistos[$href] = true;

            $slug = basename(rtrim($href, '/'));
            $nombreLimpio = trim(str_replace('-', ' ', $slug));
            $key = SinaloaTableParser::normalizarClave($nombreLimpio);

            $parent = $a->parentNode;
            while ($parent && !str_contains($parent->getAttribute('class'), 'col') && !str_contains($parent->getAttribute('class'), 'card') && $parent->parentNode) {
                $parent = $parent->parentNode;
            }

            $img = $parent ? $xpath->query('.//img', $parent) : null;
            $src = ($img && $img->length > 0) ? $img->item(0)->getAttribute('src') : null;
            if ($src && !str_starts_with($src, 'http')) {
                $src = 'https://www.congresosinaloa.gob.mx' . $src;
            }

            $map[$key] = [
                'foto_url' => $src,
                'perfil_url' => $href,
            ];
        }

        return $map;
    }

    private function guardarDataset(int $legislatura, array $info, string $fuenteUrl, array $legisladores): array
    {
        $dataDir = $this->config['paths']['data_json'];
        $archivoSalida = "{$dataDir}/congreso_sinaloa_leg_{$legislatura}.json";

        $resumen = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Sinaloa - {$info['romano']} Legislatura ({$info['periodo']})",
                'entidad' => 'Sinaloa',
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
        $this->logger->info("Dataset del Congreso de Sinaloa generado", [
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
