<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\Core\EvidenceHasher;
use PulsoCivico\DTO\LegisladorDTO;

/**
 * DurangoScraperService: Extractor oficial del H. Congreso del Estado de Durango.
 * Integra las 10 legislaturas históricas y vigentes (LXI a LXX, 1998–2027).
 */
class DurangoScraperService
{
    public const LEGISLATURAS = [
        70 => ['romano' => 'LXX', 'periodo' => '2024-2027', 'activa' => true, 'page_id' => 3492],
        69 => ['romano' => 'LXIX', 'periodo' => '2021-2024', 'activa' => false, 'page_id' => 1167],
        68 => ['romano' => 'LXVIII', 'periodo' => '2018-2021', 'activa' => false, 'page_id' => null],
        67 => ['romano' => 'LXVII', 'periodo' => '2016-2018', 'activa' => false, 'page_id' => null],
        66 => ['romano' => 'LXVI', 'periodo' => '2013-2016', 'activa' => false, 'page_id' => null],
        65 => ['romano' => 'LXV', 'periodo' => '2010-2013', 'activa' => false, 'page_id' => null],
        64 => ['romano' => 'LXIV', 'periodo' => '2007-2010', 'activa' => false, 'page_id' => null],
        63 => ['romano' => 'LXIII', 'periodo' => '2004-2007', 'activa' => false, 'page_id' => null],
        62 => ['romano' => 'LXII', 'periodo' => '2001-2004', 'activa' => false, 'page_id' => null],
        61 => ['romano' => 'LXI', 'periodo' => '1998-2001', 'activa' => false, 'page_id' => null],
    ];

    private const PARTIDOS_LXX = [
        'alejandromojicanarvaez' => ['partido' => 'PAN', 'distrito' => '1', 'tipo' => 'Mayoría Relativa'],
        'sugheyadrianatorresrodriguez' => ['partido' => 'PRI', 'distrito' => '2', 'tipo' => 'Mayoría Relativa'],
        'noelfernandezmaturino' => ['partido' => 'PRI', 'distrito' => '3', 'tipo' => 'Mayoría Relativa'],
        'celiadanielasotohernandez' => ['partido' => 'PRI', 'distrito' => '4', 'tipo' => 'Mayoría Relativa'],
        'fernandorochaamaro' => ['partido' => 'PAN', 'distrito' => '5', 'tipo' => 'Mayoría Relativa'],
        'gabrielavazquezchacon' => ['partido' => 'PAN', 'distrito' => '6', 'tipo' => 'Mayoría Relativa'],
        'hectorherreranunez' => ['partido' => 'PVEM', 'distrito' => '7', 'tipo' => 'Mayoría Relativa'],
        'sandraliliaamayarosales' => ['partido' => 'PVEM', 'distrito' => '8', 'tipo' => 'Mayoría Relativa'],
        'georginasoloriogarcia' => ['partido' => 'MORENA', 'distrito' => '9', 'tipo' => 'Mayoría Relativa'],
        'albertoalejandromatavaladez' => ['partido' => 'MORENA', 'distrito' => '10', 'tipo' => 'Mayoría Relativa'],
        'octavioulisesadamedelafuente' => ['partido' => 'MORENA', 'distrito' => '11', 'tipo' => 'Mayoría Relativa'],
        'nadiamonserratmilanramirez' => ['partido' => 'MORENA', 'distrito' => '12', 'tipo' => 'Mayoría Relativa'],
        'floraiselalealmendez' => ['partido' => 'MORENA', 'distrito' => '13', 'tipo' => 'Mayoría Relativa'],
        'joseosbaldosantillangomez' => ['partido' => 'PVEM', 'distrito' => '14', 'tipo' => 'Mayoría Relativa'],
        'ivansotomendia' => ['partido' => 'PRI', 'distrito' => '15', 'tipo' => 'Mayoría Relativa'],
        'veronicagonzalezolguin' => ['partido' => 'PAN', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'juliancesarrivasbnevarez' => ['partido' => 'PAN', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'mariadelrociorebollomendoza' => ['partido' => 'PRI', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'anamariaduronperez' => ['partido' => 'PVEM', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'ernestoabelalanisherrera' => ['partido' => 'PRI', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'martinvivancolira' => ['partido' => 'MC', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'otnielgarcianavarro' => ['partido' => 'MORENA', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'bernabeaguilarcarrillo' => ['partido' => 'MORENA', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'delialeticiaenriquezarriaga' => ['partido' => 'MORENA', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
        'cynthiamontserrathernandezquinones' => ['partido' => 'MORENA', 'distrito' => null, 'tipo' => 'Representación Proporcional'],
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
        $orden = [61, 62, 63, 64, 65, 66, 67, 68, 69, 70];
        foreach ($orden as $leg) {
            $this->logger->info("Iniciando extracción Congreso Durango", ['leg' => $leg]);
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

    public function extraer(int $legislatura = 70): array
    {
        if (!isset(self::LEGISLATURAS[$legislatura])) {
            throw new \InvalidArgumentException("Legislatura {$legislatura} no soportada para Durango.");
        }

        $info = self::LEGISLATURAS[$legislatura];
        $rawDir = $this->config['paths']['storage'] . '/raw/durango';
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }

        $this->logger->info("Iniciando scraper Congreso de Durango", [
            'legislatura' => $legislatura,
            'romano' => $info['romano'],
            'periodo' => $info['periodo'],
        ]);

        if ($legislatura === 70) {
            return $this->extraerLeg70Oficial($rawDir, $info);
        }

        return $this->extraerHistoricaWiki($rawDir, $info, $legislatura);
    }

    private function extraerLeg70Oficial(string $rawDir, array $info): array
    {
        $cacheFile = "{$rawDir}/directorio_lxx_3492.json";
        $rawJson = '';

        if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
            $rawJson = (string)file_get_contents($cacheFile);
        } else {
            $url = "https://congresodurango.gob.mx/wp-json/wp/v2/pages/{$info['page_id']}";
            $rawJson = $this->fetchUrl($url);
            if ($rawJson !== '') {
                file_put_contents($cacheFile, $rawJson);
            }
        }

        $sha256 = EvidenceHasher::hashContent($rawJson);
        $json = json_decode($rawJson, true);
        $html = $json['content']['rendered'] ?? '';

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);
        $sections = $xpath->query('//section[contains(@class, "elementor-top-section")]');

        $legisladores = [];
        $fuenteUrl = 'https://congresodurango.gob.mx/directorio-lxx/';
        $count = 0;

        foreach ($sections as $sec) {
            $h5 = $xpath->query('.//h5[contains(., "DIP.")]', $sec);
            if ($h5->length > 0) {
                $count++;
                $rawNombre = trim($h5->item(0)->textContent);
                $nombre = DurangoTableParser::limpiarNombre(preg_replace('/^DIP\.\s*/iu', '', $rawNombre));
                $key = DurangoTableParser::normalizarClave($nombre);

                $img = $xpath->query('.//img', $sec);
                $foto = $img->length > 0 ? $img->item(0)->getAttribute('src') : null;

                $emailNode = $xpath->query('.//a[starts-with(@href, "mailto:")]', $sec);
                $email = $emailNode->length > 0 ? str_replace('mailto:', '', $emailNode->item(0)->getAttribute('href')) : null;

                $distNode = $xpath->query('.//a[contains(@href, "distrito")]', $sec);
                $distUrl = $distNode->length > 0 ? $distNode->item(0)->getAttribute('href') : null;

                $metaPart = self::PARTIDOS_LXX[$key] ?? ['partido' => 'Sin Partido', 'distrito' => null, 'tipo' => 'Mayoría Relativa'];

                $refId = 'DGO-70-' . str_pad((string)$count, 2, '0', STR_PAD_LEFT);
                $dto = new LegisladorDTO(
                    referenciaSil: $refId,
                    nombreCompleto: $nombre,
                    camara: 'Congreso Local',
                    legislatura: 70,
                    partido: $metaPart['partido'],
                    principioEleccion: $metaPart['tipo'],
                    entidad: 'Durango',
                    distrito: $metaPart['distrito'],
                    tipoCargo: 'Propietario',
                    suplenteNombre: null,
                    fotoOficialUrl: $foto,
                    metadata: [
                        'fuente_url' => $distUrl ?? $fuenteUrl,
                        'sha256_evidencia' => $sha256,
                        'fecha_extraccion' => date('Y-m-d H:i:s'),
                        'entidad_tipo' => 'Congreso Local',
                        'email_oficial' => $email,
                    ]
                );
                $legisladores[] = $dto->toArray();
            }
        }

        return $this->guardarDataset(70, $info, $fuenteUrl, $legisladores);
    }

    private function extraerHistoricaWiki(string $rawDir, array $info, int $legislatura): array
    {
        $anexoTitle = "Anexo:{$info['romano']}_Legislatura_del_Congreso_del_Estado_de_Durango";
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
            throw new \RuntimeException("No fue posible cargar datos de Durango (Leg {$legislatura}).");
        }

        $sha256 = EvidenceHasher::hashContent($rawContent);
        $json = json_decode($rawContent, true);
        $html = $json['parse']['text']['*'] ?? '';

        $datosWeb = [];
        if ($info['page_id'] !== null) {
            $datosWeb = $this->obtenerDatosOficialesWeb($info['page_id'], $rawDir, $legislatura);
        }

        $items = DurangoTableParser::parseHtml($html, $datosWeb);

        $legisladores = [];
        $fuenteUrl = "https://es.wikipedia.org/wiki/{$anexoTitle}";

        foreach ($items as $idx => $item) {
            $refId = 'DGO-' . $legislatura . '-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $dto = new LegisladorDTO(
                referenciaSil: $refId,
                nombreCompleto: $item['nombre_completo'],
                camara: 'Congreso Local',
                legislatura: $legislatura,
                partido: $item['partido'] ?? 'Sin Partido',
                principioEleccion: $item['principio_eleccion'] ?? 'Mayoría Relativa',
                entidad: 'Durango',
                distrito: $item['distrito'] !== null ? (string)$item['distrito'] : null,
                tipoCargo: 'Propietario',
                suplenteNombre: $item['suplente'] ?? null,
                fotoOficialUrl: $item['foto_url'] ?? null,
                metadata: [
                    'fuente_url' => $item['fuente_url'] ?? $fuenteUrl,
                    'sha256_evidencia' => $sha256,
                    'fecha_extraccion' => date('Y-m-d H:i:s'),
                    'entidad_tipo' => 'Congreso Local',
                    'cabecera_distrital' => $item['cabecera'] ?? null,
                    'email_oficial' => $item['email'] ?? null,
                ]
            );
            $legisladores[] = $dto->toArray();
        }

        return $this->guardarDataset($legislatura, $info, $fuenteUrl, $legisladores);
    }

    private function obtenerDatosOficialesWeb(int $pageId, string $rawDir, int $legislatura): array
    {
        $cacheFile = "{$rawDir}/directorio_wp_{$legislatura}.json";
        $res = '';

        if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
            $res = (string)file_get_contents($cacheFile);
        } else {
            $url = "https://congresodurango.gob.mx/wp-json/wp/v2/pages/{$pageId}";
            $res = $this->fetchUrl($url);
            if ($res !== '') {
                file_put_contents($cacheFile, $res);
            }
        }

        $json = json_decode($res, true);
        $html = $json['content']['rendered'] ?? '';
        if ($html === '') return [];

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);
        $sections = $xpath->query('//section[contains(@class, "elementor-top-section")]');
        $map = [];

        foreach ($sections as $sec) {
            $h5 = $xpath->query('.//h5[contains(., "DIP.")]', $sec);
            if ($h5->length > 0) {
                $rawNombre = trim($h5->item(0)->textContent);
                $limpioNombre = DurangoTableParser::limpiarNombre(preg_replace('/^DIP\.\s*/iu', '', $rawNombre));
                $key = DurangoTableParser::normalizarClave($limpioNombre);

                $img = $xpath->query('.//img', $sec);
                $foto = $img->length > 0 ? $img->item(0)->getAttribute('src') : null;

                $emailNode = $xpath->query('.//a[starts-with(@href, "mailto:")]', $sec);
                $email = $emailNode->length > 0 ? str_replace('mailto:', '', $emailNode->item(0)->getAttribute('href')) : null;

                $distNode = $xpath->query('.//a[contains(@href, "distrito")]', $sec);
                $distUrl = $distNode->length > 0 ? $distNode->item(0)->getAttribute('href') : null;

                $map[$key] = [
                    'foto_url' => $foto,
                    'email' => $email,
                    'distrito_url' => $distUrl,
                ];
            }
        }

        return $map;
    }

    private function guardarDataset(int $legislatura, array $info, string $fuenteUrl, array $legisladores): array
    {
        $dataDir = $this->config['paths']['data_json'];
        $archivoSalida = "{$dataDir}/congreso_durango_leg_{$legislatura}.json";

        $resumen = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Durango - {$info['romano']} Legislatura ({$info['periodo']})",
                'entidad' => 'Durango',
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
        $this->logger->info("Dataset del Congreso de Durango generado", [
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
