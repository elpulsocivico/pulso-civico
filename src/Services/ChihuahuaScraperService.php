<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\DTO\LegisladorDTO;

/**
 * ChihuahuaScraperService: Extractor oficial del H. Congreso del Estado de Chihuahua.
 * Extrae legisladores de la actual (LXVIII) e históricas registradas (LXII a LXVII).
 */
class ChihuahuaScraperService
{
    public const LEGISLATURAS = [
        68 => ['romano' => 'LXVIII', 'periodo' => '2024-2027', 'activa' => true],
        67 => ['romano' => 'LXVII', 'periodo' => '2021-2024', 'activa' => false],
        66 => ['romano' => 'LXVI', 'periodo' => '2018-2021', 'activa' => false],
        65 => ['romano' => 'LXV', 'periodo' => '2016-2018', 'activa' => false],
        64 => ['romano' => 'LXIV', 'periodo' => '2013-2016', 'activa' => false],
        63 => ['romano' => 'LXIII', 'periodo' => '2010-2013', 'activa' => false],
        62 => ['romano' => 'LXII', 'periodo' => '2007-2010', 'activa' => false],
    ];

    private const PARTIDOS_MAP = [
        '1' => 'PRI',
        '2' => 'PAN',
        '3' => 'PRD',
        '4' => 'PT',
        '5' => 'NUEVA ALIANZA',
        '6' => 'PVEM',
        '7' => 'CDP',
        '8' => 'PARM',
        '9' => 'MC',
        '10' => 'MORENA',
        '11' => 'PES',
        '12' => 'INDEPENDIENTE',
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
     * Extrae todas las legislaturas disponibles del Congreso de Chihuahua (62 a 68).
     */
    public function extraerTodas(): array
    {
        $resultados = [];
        $orden = [62, 63, 64, 65, 66, 67, 68];
        foreach ($orden as $leg) {
            $this->logger->info("Iniciando extracción Congreso Chihuahua", ['leg' => $leg]);
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
     * Extrae legisladores de Chihuahua para una legislatura específica y genera el dataset JSON.
     */
    public function extraer(int $legislatura = 68): array
    {
        if (!isset(self::LEGISLATURAS[$legislatura])) {
            throw new \InvalidArgumentException("Legislatura {$legislatura} no soportada para Chihuahua.");
        }

        $info = self::LEGISLATURAS[$legislatura];
        $rawDir = $this->config['paths']['storage'] . '/raw/chihuahua';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }

        $this->logger->info("Iniciando scraper Congreso de Chihuahua", [
            'legislatura' => $legislatura,
            'romano' => $info['romano'],
            'periodo' => $info['periodo'],
        ]);

        $fuenteUrl = $info['activa']
            ? 'https://www.congresochihuahua.gob.mx/diputados/index.php'
            : 'https://www.congresochihuahua.gob.mx/diputados/diputadosAnteriores.php';

        $cacheFile = "{$rawDir}/listado_leg_{$legislatura}.html";
        $html = '';

        if (file_exists($cacheFile) && filesize($cacheFile) > 3000) {
            $html = (string)file_get_contents($cacheFile);
        } else {
            $ch = curl_init($fuenteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            if (!$info['activa']) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "idlegislatura={$legislatura}&bandera=1");
            }
            $html = (string)curl_exec($ch);
            if (PHP_VERSION_ID < 80500) {
                @curl_close($ch);
            }
            if ($html !== '') {
                file_put_contents($cacheFile, $html);
            }
        }

        if (empty($html)) {
            throw new \RuntimeException("No fue posible cargar el listado del Congreso de Chihuahua (Leg {$legislatura}).");
        }

        $sha256 = EvidenceHasher::hashContent($html);
        $items = $info['activa']
            ? $this->parsearActualHtml($html, $sha256)
            : $this->parsearAnterioresHtml($html, $sha256);

        $legisladores = [];
        foreach ($items as $item) {
            $dto = new LegisladorDTO(
                referenciaSil: 'CHIH-' . $legislatura . '-' . $item['id'],
                nombreCompleto: $item['nombre_completo'],
                camara: 'Congreso Local',
                legislatura: $legislatura,
                partido: $item['partido'] ?? 'Sin Partido',
                principioEleccion: $item['principio_eleccion'] ?? 'Mayoría Relativa',
                entidad: 'Chihuahua',
                distrito: $item['distrito'] ?? null,
                tipoCargo: $item['tipo_cargo'] ?? 'Propietario',
                suplenteNombre: $item['suplente_de'] ?? null,
                fotoOficialUrl: $item['foto_url'] ?? null,
                metadata: [
                    'fuente_url' => $fuenteUrl,
                    'sha256_evidencia' => $sha256,
                    'fecha_extraccion' => date('Y-m-d H:i:s'),
                    'entidad_tipo' => 'Congreso Local',
                    'id_interno' => $item['id'],
                    'municipios' => $item['municipio'] ?? null,
                ]
            );
            $legisladores[] = $dto->toArray();
        }

        $dataDir = $this->config['paths']['data_json'];
        $archivoSalida = "{$dataDir}/congreso_chihuahua_leg_{$legislatura}.json";

        $resumen = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Chihuahua - {$info['romano']} Legislatura ({$info['periodo']})",
                'entidad' => 'Chihuahua',
                'camara' => 'Congreso Local',
                'legislatura' => $legislatura,
                'periodo' => $info['periodo'],
                'generado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'fuente_url' => $fuenteUrl,
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        file_put_contents(
            $archivoSalida,
            json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->logger->success("Dataset del Congreso de Chihuahua generado", [
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

    private function parsearActualHtml(string $html, string $sha256): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        $articles = $xpath->query('//article[contains(@class, "span6")]');

        $items = [];
        foreach ($articles as $art) {
            $linkNode = $xpath->query('.//a[contains(@href, "irDetalle")]', $art)->item(0);
            if (!$linkNode) {
                continue;
            }
            preg_match('/irDetalle\((\d+)\)/', $linkNode->getAttribute('href'), $m);
            $id = $m[1] ?? (string)count($items);

            $nombreNode = $xpath->query('.//h3//a[contains(@href, "irDetalle")]', $art)->item(0);
            $nombreRaw = $nombreNode ? trim($nombreNode->textContent) : '';
            $nombre = trim((string)preg_replace('/^Dip\.\s+/i', '', $nombreRaw));

            $imgNode = $xpath->query('.//img[contains(@class, "imgBorder")]', $art)->item(0);
            $foto = $imgNode ? $imgNode->getAttribute('src') : null;

            $pNodes = $xpath->query('.//p', $art);
            $distrito = null;
            $municipio = null;
            $partido = 'Sin Partido';

            foreach ($pNodes as $p) {
                $txt = trim($p->textContent);
                if (stripos($txt, 'Distrito') !== false) {
                    $distrito = $txt;
                    if (str_contains($txt, '.')) {
                        $parts = explode('.', $txt, 2);
                        $distrito = trim($parts[0]);
                        $municipio = trim($parts[1]);
                    }
                } elseif (stripos($txt, 'Representación') !== false) {
                    $distrito = 'Plurinominal';
                }

                $pImg = $xpath->query('.//img', $p)->item(0);
                if ($pImg) {
                    $src = $pImg->getAttribute('src');
                    $partido = $this->resolverPartido($src, $txt);
                }
            }

            $rep = ($distrito === 'Plurinominal' || stripos((string)$distrito, 'Proporcional') !== false)
                ? 'Representación Proporcional'
                : 'Mayoría Relativa';

            $items[] = [
                'id' => $id,
                'nombre_completo' => $nombre,
                'partido' => $partido,
                'principio_eleccion' => $rep,
                'distrito' => $distrito,
                'municipio' => $municipio,
                'tipo_cargo' => 'Propietario',
                'foto_url' => $foto,
                'sha256' => $sha256,
            ];
        }
        return $items;
    }

    private function parsearAnterioresHtml(string $html, string $sha256): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        $articles = $xpath->query('//article[contains(@class, "span6")]');

        $items = [];
        foreach ($articles as $art) {
            $linkNode = $xpath->query('.//a[contains(@href, "irDetalle")]', $art)->item(0);
            if (!$linkNode) {
                continue;
            }
            preg_match('/irDetalle\((\d+)\)/', $linkNode->getAttribute('href'), $m);
            $id = $m[1] ?? (string)count($items);

            $nombreNode = $xpath->query('.//h4//a[contains(@href, "irDetalle")]', $art)->item(0);
            $nombreRaw = $nombreNode ? trim($nombreNode->textContent) : '';
            $nombre = trim((string)preg_replace('/^Dip\.\s+/i', '', $nombreRaw));

            $imgNode = $xpath->query('.//img[contains(@class, "imgBorder")]', $art)->item(0);
            $foto = $imgNode ? $imgNode->getAttribute('src') : null;

            $partidoImgNode = $xpath->query('.//h4//img[contains(@class, "icono")]', $art)->item(0);
            $partido = 'Sin Partido';
            if ($partidoImgNode) {
                $partido = $this->resolverPartido($partidoImgNode->getAttribute('src'), '');
            }

            $pNode = $xpath->query('.//p', $art)->item(0);
            $infoTxt = $pNode ? trim($pNode->textContent) : '';

            $tipoCargo = 'Propietario';
            $suplenteDe = null;
            if (stripos($infoTxt, 'Suplente de') !== false) {
                $tipoCargo = 'Suplente';
                if (preg_match('/Suplente\s+de\s+(?:la\s+|el\s+)?(?:Dip\.\s+)?(.*)$/i', $infoTxt, $supM)) {
                    $suplenteDe = trim($supM[1]);
                }
            }

            $distrito = null;
            $municipio = null;
            if (preg_match('/Distrito\s+([IVXLCDM\d]+)(?:\.\s*([^<\n\r]+))?/i', $infoTxt, $distM)) {
                $distrito = 'Distrito ' . trim($distM[1]);
                if (!empty($distM[2])) {
                    $municipio = trim((string)preg_replace('/(Diputad[ao]|Suplente).*/i', '', $distM[2]));
                }
            } elseif (stripos($infoTxt, 'Representación Proporcional') !== false || stripos($infoTxt, 'Plurinominal') !== false) {
                $distrito = 'Plurinominal';
            }

            $rep = ($distrito === 'Plurinominal') ? 'Representación Proporcional' : 'Mayoría Relativa';

            $items[] = [
                'id' => $id,
                'nombre_completo' => $nombre,
                'partido' => $partido,
                'principio_eleccion' => $rep,
                'distrito' => $distrito,
                'municipio' => $municipio,
                'tipo_cargo' => $tipoCargo,
                'suplente_de' => $suplenteDe,
                'foto_url' => $foto,
                'sha256' => $sha256,
            ];
        }
        return $items;
    }

    private function resolverPartido(string $src, string $texto): string
    {
        if (preg_match('/partidos\/(\d+)\.gif/i', $src, $m)) {
            $num = $m[1];
            if (isset(self::PARTIDOS_MAP[$num])) {
                return self::PARTIDOS_MAP[$num];
            }
        }
        foreach (self::PARTIDOS_MAP as $partido) {
            if (stripos($texto, $partido) !== false) {
                return $partido;
            }
        }
        return 'Sin Partido';
    }
}
