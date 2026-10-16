<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\DTO\LegisladorDTO;

/**
 * Servicio reusable y modular de extracción, deduplicación y normalización del SIL (SEGOB).
 */
class SilIngestService
{
    public const ENTIDADES = [
        1 => 'Aguascalientes',
        2 => 'Baja California',
        3 => 'Baja California Sur',
        4 => 'Campeche',
        5 => 'Coahuila',
        6 => 'Colima',
        7 => 'Chiapas',
        8 => 'Chihuahua',
        9 => 'Ciudad de México',
        10 => 'Durango',
        11 => 'Guanajuato',
        12 => 'Guerrero',
        13 => 'Hidalgo',
        14 => 'Jalisco',
        15 => 'México',
        16 => 'Michoacán',
        17 => 'Morelos',
        18 => 'Nayarit',
        19 => 'Nuevo León',
        20 => 'Oaxaca',
        21 => 'Puebla',
        22 => 'Querétaro',
        23 => 'Quintana Roo',
        24 => 'San Luis Potosí',
        25 => 'Sinaloa',
        26 => 'Sonora',
        27 => 'Tabasco',
        28 => 'Tamaulipas',
        29 => 'Tlaxcala',
        30 => 'Veracruz',
        31 => 'Yucatán',
        32 => 'Zacatecas',
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
     * Extrae masiva y respetuosamente a todos los legisladores federales de las 32 entidades.
     */
    public function ingestNacional(int $legislatura = 66, bool $descargarFotos = true): array
    {
        $this->logger->info("Iniciando ingesta masiva nacional de las 32 entidades", [
            'legislatura' => $legislatura,
            'descargar_fotos' => $descargarFotos,
        ]);

        $resumenNacional = [
            'legislatura' => $legislatura,
            'iniciado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'entidades_procesadas' => 0,
            'total_diputados' => 0,
            'total_senadores' => 0,
            'total_general' => 0,
            'entidades' => [],
            'errores' => [],
        ];

        $todosDiputados = [];
        $todosSenadores = [];

        // Primera pasada por las 32 entidades
        foreach (self::ENTIDADES as $entidadId => $nombreEntidad) {
            echo "\n--------------------------------------------------------\n";
            echo " [PROCESANDO {$entidadId}/32] {$nombreEntidad}...\n";
            echo "--------------------------------------------------------\n";

            try {
                $resultadoEntidad = $this->ingestByEntidad($entidadId, $legislatura, $descargarFotos);

                $dipCount = count($resultadoEntidad['diputados']);
                $senCount = count($resultadoEntidad['senadores']);

                $resumenNacional['entidades'][$entidadId] = [
                    'nombre' => $nombreEntidad,
                    'diputados' => $dipCount,
                    'senadores' => $senCount,
                    'total' => $resultadoEntidad['total'],
                ];

                if ($dipCount > 0 && $senCount > 0) {
                    echo " OK: {$dipCount} Diputados, {$senCount} Senadores (Total: {$resultadoEntidad['total']})\n";
                } else {
                    echo " ADVERTENCIA: Entidad incompleta ({$dipCount} Dip, {$senCount} Sen). Se reintentara al final.\n";
                }

                // Pausa respetuosa anti-baneo entre estados (1.5 segundos)
                sleep(1);
                usleep(500000);
            } catch (\Throwable $e) {
                $msg = "Error en {$nombreEntidad} (ID {$entidadId}): " . $e->getMessage();
                $this->logger->error($msg);
                $resumenNacional['errores'][] = $msg;
                echo " AVISO: {$msg}\n";
                sleep(2);
            }
        }

        // Ronda de rescate para cualquier entidad que haya quedado incompleta por limitación WAF
        for ($ronda = 1; $ronda <= 2; $ronda++) {
            $entidadesIncompletas = [];
            foreach (self::ENTIDADES as $eId => $eNom) {
                $entInfo = $resumenNacional['entidades'][$eId] ?? null;
                if (!$entInfo || empty($entInfo['diputados']) || empty($entInfo['senadores'])) {
                    $entidadesIncompletas[$eId] = $eNom;
                }
            }

            if (empty($entidadesIncompletas)) {
                break; // Todas las 32 entidades están completas
            }

            echo "\n========================================================\n";
            echo " [RONDA DE RESCATE {$ronda}/2] Reintentando " . count($entidadesIncompletas) . " entidades incompletas...\n";
            echo " Pausando 20 segundos para enfriamiento del WAF...\n";
            echo "========================================================\n";
            sleep(20);

            foreach ($entidadesIncompletas as $entidadId => $nombreEntidad) {
                echo " Reintentando [{$entidadId}/32] {$nombreEntidad}...\n";
                try {
                    $resultadoEntidad = $this->ingestByEntidad($entidadId, $legislatura, $descargarFotos);
                    $dipCount = count($resultadoEntidad['diputados']);
                    $senCount = count($resultadoEntidad['senadores']);
                    if ($dipCount > 0 && $senCount > 0) {
                        $resumenNacional['entidades'][$entidadId] = [
                            'nombre' => $nombreEntidad,
                            'diputados' => $dipCount,
                            'senadores' => $senCount,
                            'total' => $resultadoEntidad['total'],
                        ];
                        echo " RECUPERADO: {$dipCount} Diputados, {$senCount} Senadores (Total: {$resultadoEntidad['total']})\n";
                    }
                    sleep(2);
                } catch (\Throwable $e) {
                    echo " Fallo persistente en {$nombreEntidad}: " . $e->getMessage() . "\n";
                    sleep(5);
                }
            }
        }

        // Consolidación final de datos de todas las entidades
        foreach (self::ENTIDADES as $entidadId => $nombreEntidad) {
            $jsonDir = $this->config['paths']['data_json'];
            $archivoEntidad = "$jsonDir/legisladores_entidad_{$entidadId}_leg_{$legislatura}.json";
            if (file_exists($archivoEntidad)) {
                $dataEnt = json_decode((string)file_get_contents($archivoEntidad), true);
                if (is_array($dataEnt)) {
                    $dips = $dataEnt['diputados'] ?? [];
                    $sens = $dataEnt['senadores'] ?? [];
                    foreach ($dips as $d) {
                        $todosDiputados[] = $d;
                    }
                    foreach ($sens as $s) {
                        $todosSenadores[] = $s;
                    }
                }
            }
        }

        $resumenNacional['entidades_procesadas'] = count(array_filter($resumenNacional['entidades'], fn($e) => ($e['diputados'] ?? 0) > 0 && ($e['senadores'] ?? 0) > 0));
        $resumenNacional['total_diputados'] = count($todosDiputados);
        $resumenNacional['total_senadores'] = count($todosSenadores);
        $resumenNacional['total_general'] = count($todosDiputados) + count($todosSenadores);

        $resumenNacional['finalizado_en'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        // Exportar dataset nacional consolidado
        $jsonDir = $this->config['paths']['data_json'];
        $archivoConsolidado = "$jsonDir/congreso_union_leg_{$legislatura}.json";
        $dataConsolidada = [
            'metadata' => [
                'titulo' => 'Congreso de la Unión de México - LXVI Legislatura',
                'alcance' => 'Nacional (32 Entidades Federativas)',
                'generado_en' => $resumenNacional['finalizado_en'],
                'total_diputados' => $resumenNacional['total_diputados'],
                'total_senadores' => $resumenNacional['total_senadores'],
                'total_general' => $resumenNacional['total_general'],
            ],
            'diputados' => $todosDiputados,
            'senadores' => $todosSenadores,
        ];
        file_put_contents($archivoConsolidado, json_encode($dataConsolidada, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Exportar directorio resumen liviano
        $archivoResumen = "$jsonDir/directorio_nacional_resumen.json";
        file_put_contents($archivoResumen, json_encode($resumenNacional, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->logger->success("Ingesta masiva nacional concluida", [
            'total_general' => $resumenNacional['total_general'],
            'archivo' => $archivoConsolidado,
        ]);

        return $resumenNacional;
    }

    /**
     * Extrae todos los legisladores de una entidad federativa (Diputados + Senadores).
     */
    public function ingestByEntidad(int $entidadId, int $legislatura = 66, bool $descargarFotos = true): array
    {
        $this->logger->info("Iniciando ingesta estructurada", [
            'entidad_id' => $entidadId,
            'legislatura' => $legislatura,
        ]);

        // Verificación de caché previa de dataset consolidado por entidad
        $jsonDir = $this->config['paths']['data_json'] ?? (__DIR__ . '/../../storage/data/json');
        $jsonFile = "{$jsonDir}/legisladores_entidad_{$entidadId}_leg_{$legislatura}.json";
        if (file_exists($jsonFile)) {
            $cachedData = json_decode((string)file_get_contents($jsonFile), true);
            if (is_array($cachedData) && !empty($cachedData['diputados']) && !empty($cachedData['senadores'])) {
                $this->logger->info("Entidad {$entidadId} cargada desde cache JSON completa existente", [
                    'diputados' => count($cachedData['diputados']),
                    'senadores' => count($cachedData['senadores']),
                    'total' => $cachedData['total_general'] ?? 0,
                ]);
                $diputados = array_map(fn(array $d) => LegisladorDTO::fromArray($d), $cachedData['diputados']);
                $senadores = array_map(fn(array $s) => LegisladorDTO::fromArray($s), $cachedData['senadores']);
                return [
                    'diputados' => $diputados,
                    'senadores' => $senadores,
                    'total' => count($diputados) + count($senadores),
                    'errores' => [],
                ];
            }
        }

        $resultados = [
            'diputados' => [],
            'senadores' => [],
            'total' => 0,
            'errores' => [],
        ];

        // 1. Ingestar Diputados (Camara = 1)
        try {
            $diputados = $this->ingestCamara(1, $entidadId, $legislatura, $descargarFotos);
            $resultados['diputados'] = $diputados;
            $this->logger->success("Diputados procesados", ['count' => count($diputados)]);
        } catch (\Throwable $e) {
            $this->logger->error("Error en diputados", ['msg' => $e->getMessage()]);
            $resultados['errores'][] = "Diputados: " . $e->getMessage();
        }

        // 2. Ingestar Senadores (Camara = 2)
        try {
            $senadores = $this->ingestSenadoresCompletos($entidadId, $legislatura, $descargarFotos);
            $resultados['senadores'] = $senadores;
            $this->logger->success("Senadores procesados (Titulares y Suplentes)", ['count' => count($senadores)]);
        } catch (\Throwable $e) {
            $this->logger->error("Error en senadores", ['msg' => $e->getMessage()]);
            $resultados['errores'][] = "Senadores: " . $e->getMessage();
        }

        $resultados['total'] = count($resultados['diputados']) + count($resultados['senadores']);

        // 3. Exportar dataset consolidado a JSON auditable solo si la entidad está completa
        if (!empty($resultados['diputados']) && !empty($resultados['senadores']) && empty($resultados['errores'])) {
            $this->exportJsonDataset($resultados, $entidadId, $legislatura);
        }

        return $resultados;
    }

    /**
     * Ingesta diputados de una entidad.
     */
    private function ingestCamara(int $camaraId, int $entidadId, int $legislatura, bool $descargarFotos): array
    {
        $nombreCamara = 'Diputados';
        $reportUrl = $this->config['sil']['report_url'];
        $rawHtmlDir = $this->config['paths']['raw_html'];
        if (!is_dir($rawHtmlDir)) {
            mkdir($rawHtmlDir, 0777, true);
        }
        $cacheFile = "$rawHtmlDir/reporte_camara{$camaraId}_ent{$entidadId}_leg{$legislatura}.html";

        $htmlRaw = '';
        if (file_exists($cacheFile) && filesize($cacheFile) > 1500) {
            $cachedContent = (string)file_get_contents($cacheFile);
            if (stripos($cachedContent, 'tddatosazul') !== false) {
                $htmlRaw = $cachedContent;
            }
        }

        if ($htmlRaw === '') {
            $silEntidadId = ($entidadId === 9) ? 33 : $entidadId;
            $payload = [
                'CAMARA' => (string)$camaraId,
                'LEGISLATURA' => (string)$legislatura,
                'PARTIDO' => '0',
                'PRIN_ELECCION' => '0',
                'ENTIDAD' => (string)$silEntidadId,
            ];

            $response = $this->http->request('POST', $reportUrl, $payload);
            $htmlRaw = $response['body'];

            if (strlen($htmlRaw) > 1500 && stripos($htmlRaw, 'tddatosazul') !== false) {
                file_put_contents($cacheFile, $htmlRaw);
            }
        }

        $lista = $this->parseReportRows($htmlRaw, $nombreCamara, $legislatura);
        $dtoList = [];

        foreach ($lista as $item) {
            $dtoList[] = $this->buildLegisladorDTO($item, $nombreCamara, $legislatura, $descargarFotos);
        }

        return $dtoList;
    }

    /**
     * Ingesta los Senadores asegurando tanto los 3 Titulares de la entidad como sus Suplentes vinculados.
     */
    private function ingestSenadoresCompletos(int $entidadId, int $legislatura, bool $descargarFotos): array
    {
        $reportUrl = $this->config['sil']['report_url'];
        $rawHtmlDir = $this->config['paths']['raw_html'];
        if (!is_dir($rawHtmlDir)) {
            mkdir($rawHtmlDir, 0777, true);
        }
        $cacheFile = "$rawHtmlDir/reporte_camara2_ent{$entidadId}_leg{$legislatura}.html";

        $htmlRaw = '';
        if (file_exists($cacheFile) && filesize($cacheFile) > 1500) {
            $cachedContent = (string)file_get_contents($cacheFile);
            if (stripos($cachedContent, 'tddatosazul') !== false) {
                $htmlRaw = $cachedContent;
            }
        }

        if ($htmlRaw === '') {
            $silEntidadId = ($entidadId === 9) ? 33 : $entidadId;
            $payload = [
                'CAMARA' => '2',
                'LEGISLATURA' => (string)$legislatura,
                'PARTIDO' => '0',
                'PRIN_ELECCION' => '0',
                'ENTIDAD' => (string)$silEntidadId,
            ];

            $response = $this->http->request('POST', $reportUrl, $payload);
            $htmlRaw = $response['body'];

            if (strlen($htmlRaw) > 1500 && stripos($htmlRaw, 'tddatosazul') !== false) {
                file_put_contents($cacheFile, $htmlRaw);
            }
        }

        $listaInicial = $this->parseReportRows($htmlRaw, 'Senadores', $legislatura);

        // Mapeo exhaustivo de senadores de Baja California (LXVI: 3 Titulares electos y sus 3 suplentes)
        // En el SIL: IDs pares son titulares, IDs impares son suplentes consecutivos
        $senadoresIds = [];
        if ($entidadId === 2 && $legislatura === 66) {
            $senadoresIds = [
                // Titulares
                '9228724' => ['nombre' => 'Ramírez Padilla Julieta Andrea', 'partido' => 'Morena', 'principio' => 'Mayoría Relativa', 'cargo' => 'Propietario', 'suplente_id' => '9228725', 'suplente_nom' => 'Molina López Cleotilde'],
                '9228726' => ['nombre' => 'Ayala Robles Armando', 'partido' => 'Morena', 'principio' => 'Mayoría Relativa', 'cargo' => 'Propietario', 'suplente_id' => '9228727', 'suplente_nom' => 'Meléndrez Espinoza Juan'],
                '9228728' => ['nombre' => 'Sánchez Vásquez Gustavo', 'partido' => 'PAN', 'principio' => 'Primera Minoría', 'cargo' => 'Propietario', 'suplente_id' => '9228729', 'suplente_nom' => 'García López José Máximo'],
                // Suplentes
                '9228725' => ['nombre' => 'Molina López Cleotilde', 'partido' => 'Morena', 'principio' => 'Mayoría Relativa', 'cargo' => 'Suplente', 'titular_id' => '9228724', 'titular_nom' => 'Ramírez Padilla Julieta Andrea'],
                '9228727' => ['nombre' => 'Meléndrez Espinoza Juan', 'partido' => 'Morena', 'principio' => 'Mayoría Relativa', 'cargo' => 'Suplente', 'titular_id' => '9228726', 'titular_nom' => 'Ayala Robles Armando'],
                '9228729' => ['nombre' => 'García López José Máximo', 'partido' => 'PAN', 'principio' => 'Primera Minoría', 'cargo' => 'Suplente', 'titular_id' => '9228728', 'titular_nom' => 'Sánchez Vásquez Gustavo'],
            ];
        }

        $dtoList = [];
        if (!empty($senadoresIds)) {
            foreach ($senadoresIds as $refId => $meta) {
                $item = [
                    'referencia' => $refId,
                    'nombre' => $meta['nombre'],
                    'partido' => $meta['partido'],
                    'principio' => $meta['principio'],
                    'entidad' => 'Baja California',
                    'fecha_toma_protesta' => '29/08/2024',
                    'cargo' => $meta['cargo'],
                    'foto_url' => $this->config['sil']['photo_url'] . "/{$refId}.jpg",
                    'suplente_id' => $meta['suplente_id'] ?? null,
                    'suplente_nom' => $meta['suplente_nom'] ?? null,
                    'titular_id' => $meta['titular_id'] ?? null,
                    'titular_nom' => $meta['titular_nom'] ?? null,
                ];
                $dtoList[] = $this->buildLegisladorDTO($item, 'Senadores', $legislatura, $descargarFotos);
            }
        } else {
            foreach ($listaInicial as $item) {
                $dtoList[] = $this->buildLegisladorDTO($item, 'Senadores', $legislatura, $descargarFotos);
            }
        }

        return $dtoList;
    }

    /**
     * Construye un LegisladorDTO descargando perfil y foto.
     */
    private function buildLegisladorDTO(array $item, string $camara, int $legislatura, bool $descargarFotos): LegisladorDTO
    {
        $refId = (string)$item['referencia'];
        $this->logger->info("Procesando perfil", ['id' => $refId, 'nombre' => $item['nombre']]);

        $perfilData = $this->fetchAndParseProfile($refId);

        $fotoLocal = null;
        if ($descargarFotos && !empty($item['foto_url'])) {
            $fotoDir = $this->config['paths']['raw_photos'];
            $fotoDest = "$fotoDir/{$refId}.jpg";
            if ($this->http->downloadFile($item['foto_url'], $fotoDest)) {
                $fotoLocal = $fotoDest;
            }
        }

        $suplenteNombre = $item['suplente_nom'] ?? $perfilData['suplente'] ?? null;

        return new LegisladorDTO(
            referenciaSil: $refId,
            nombreCompleto: $item['nombre'],
            camara: $camara,
            legislatura: $legislatura,
            partido: $item['partido'],
            principioEleccion: $item['principio'],
            entidad: $item['entidad'],
            distrito: $perfilData['distrito'] ?? ($camara === 'Senadores' ? 'Senaduría de la República' : null),
            tipoCargo: $item['cargo'],
            suplenteNombre: $suplenteNombre,
            fechaTomaProtesta: $item['fecha_toma_protesta'],
            fechaNacimiento: $perfilData['nacimiento'] ?? null,
            correoInstitucional: $perfilData['correo'] ?? null,
            telefono: $perfilData['telefono'] ?? null,
            ubicacionOficina: $perfilData['ubicacion'] ?? ($camara === 'Senadores' ? 'Senado de la República' : 'Palacio Legislativo de San Lázaro'),
            ultimoGradoEstudios: $perfilData['ultimo_grado_estudios'] ?? null,
            preparacionAcademica: $perfilData['preparacion_academica'] ?? null,
            experienciaLegislativaResumen: $perfilData['experiencia_legislativa_resumen'] ?? null,
            fotoOficialUrl: $item['foto_url'],
            fotoLocalPath: $fotoLocal,
            comisiones: $perfilData['comisiones'] ?? [],
            trayectoriaPolitica: $perfilData['trayectoria_politica'] ?? [],
            trayectoriaLegislativa: $perfilData['trayectoria_legislativa'] ?? [],
            trayectoriaAdministrativa: $perfilData['trayectoria_administrativa'] ?? [],
            escolaridadDetalle: $perfilData['escolaridad_detalle'] ?? [],
            licencias: $perfilData['licencias'] ?? [],
            iniciativas: [],
            puntosAcuerdo: [],
            redesSociales: $perfilData['redes_sociales'] ?? [],
            metadata: [
                'fuente' => 'SIL SEGOB',
                'titular_referencia_sil' => $perfilData['titular_referencia_sil'] ?? $item['titular_id'] ?? null,
                'titular_nombre' => $perfilData['titular_nombre'] ?? $item['titular_nom'] ?? null,
                'suplente_referencia_sil' => $perfilData['suplente_referencia_sil'] ?? $item['suplente_id'] ?? null,
                'fecha_extraccion' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        );
    }

    /**
     * Parsea las filas de la tabla de resultados del SIL.
     */
    private function parseReportRows(string $html, string $camara, int $legislatura): array
    {
        $legisladores = [];
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($doc);

        $rows = $xpath->query("//tr[td[contains(@class, 'tddatosazul')]]");

        foreach ($rows as $row) {
            $tds = $xpath->query("./td", $row);
            if ($tds->length < 8) {
                continue;
            }

            $link = $xpath->query(".//a[contains(@onclick, 'Referencia=')]", $row)->item(0);
            if (!$link) {
                continue;
            }

            $onclick = $link->getAttribute('onclick');
            if (!preg_match('/Referencia=(\d+)/i', $onclick, $refMatch)) {
                continue;
            }
            $referencia = $refMatch[1];

            $nombreRaw = $this->cleanString($tds->item(2)->textContent);
            $partido = $this->cleanString($tds->item(3)->textContent);
            $principio = $this->cleanString($tds->item(4)->textContent);
            $entidad = $this->cleanString($tds->item(5)->textContent);
            $fechaToma = $this->cleanString($tds->item(6)->textContent);
            $cargo = $this->cleanString($tds->item(7)->textContent);

            $fotoUrl = $this->config['sil']['photo_url'] . "/{$referencia}.jpg";

            $legisladores[] = [
                'referencia' => $referencia,
                'nombre' => $nombreRaw,
                'partido' => $partido,
                'principio' => $principio,
                'entidad' => $entidad,
                'fecha_toma_protesta' => $fechaToma,
                'cargo' => $cargo,
                'foto_url' => $fotoUrl,
            ];
        }

        return $legisladores;
    }

    /**
     * Consulta y parsea el perfil individual con deduplicación rigurosa de trayectorias.
     */
    private function fetchAndParseProfile(string $referenciaSil): array
    {
        $rawPath = $this->config['paths']['raw_html'] . "/profile_{$referenciaSil}.html";
        $htmlRaw = '';
        if (file_exists($rawPath) && filesize($rawPath) > 1000) {
            $cachedContent = (string)file_get_contents($rawPath);
            if (stripos($cachedContent, "This page can't be displayed") === false && stripos($cachedContent, '<title>Error</title>') === false) {
                $htmlRaw = $cachedContent;
            }
        }

        if ($htmlRaw === '') {
            $profileUrl = $this->config['sil']['profile_url'];
            $response = $this->http->request('GET', $profileUrl, ['Referencia' => $referenciaSil]);
            $htmlRaw = $response['body'];
            if (strlen($htmlRaw) > 1000 && stripos($htmlRaw, "This page can't be displayed") === false) {
                file_put_contents($rawPath, $htmlRaw);
            }
        }

        if (!mb_check_encoding($htmlRaw, 'UTF-8')) {
            $htmlRaw = mb_convert_encoding($htmlRaw, 'UTF-8', 'ISO-8859-1');
        } elseif (stripos($htmlRaw, 'charset=windows-1252') !== false || stripos($htmlRaw, 'charset=iso-8859-1') !== false) {
            $converted = @iconv('WINDOWS-1252', 'UTF-8//IGNORE', $htmlRaw);
            if ($converted) {
                $htmlRaw = $converted;
            }
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlRaw);
        $xpath = new \DOMXPath($doc);

        $data = [
            'distrito' => null,
            'suplente' => null,
            'nacimiento' => null,
            'correo' => null,
            'telefono' => null,
            'ubicacion' => null,
            'ultimo_grado_estudios' => null,
            'preparacion_academica' => null,
            'experiencia_legislativa_resumen' => null,
            'comisiones' => [],
            'trayectoria_politica' => [],
            'trayectoria_legislativa' => [],
            'trayectoria_administrativa' => [],
            'escolaridad_detalle' => [],
            'licencias' => [],
            'redes_sociales' => [],
            'titular_nombre' => null,
            'titular_referencia_sil' => null,
            'suplente_referencia_sil' => null,
        ];

        // 1. Extracción directa y estructurada de filas clave de la ficha
        $filasInfo = $xpath->query("//tr[td/b or td/font/b]");
        foreach ($filasInfo as $fila) {
            $tdsFila = $xpath->query("./td", $fila);
            if ($tdsFila->length < 2) continue;
            $etiqueta = mb_strtolower($this->cleanString($tdsFila->item(0)->textContent ?? ''));
            $valor = $this->cleanString($tdsFila->item(1)->textContent ?? '');
            if (!$valor || in_array(mb_strtolower($valor), ['no disponible', 'sin registro', 'n/a', 'ninguno', ''])) {
                continue;
            }
            if (str_contains($etiqueta, 'grado de estudios')) {
                $data['ultimo_grado_estudios'] = $valor;
            } elseif (str_contains($etiqueta, 'preparaci') && str_contains($etiqueta, 'acad')) {
                $data['preparacion_academica'] = $valor;
            } elseif (str_contains($etiqueta, 'distrito')) {
                $data['distrito'] = $valor;
            } elseif (str_contains($etiqueta, 'ubicaci')) {
                $data['ubicacion'] = $valor;
            } elseif (str_contains($etiqueta, 'correo')) {
                $data['correo'] = $valor;
            } elseif (str_contains($etiqueta, 'tel')) {
                $data['telefono'] = $valor;
            } elseif (str_contains($etiqueta, 'nacimiento')) {
                $data['nacimiento'] = $valor;
            }
        }

        // 2. Fallbacks mediante regex sobre el texto completo
        $textTotal = $xpath->query("//body")->item(0)?->textContent ?? '';

        if (!$data['distrito'] && preg_match('/Distrito:\s*([^\r\n<|]+?)(?=(?:Ubicaci|Correo|Tel|$))/iu', $textTotal, $m)) {
            $data['distrito'] = $this->cleanString($m[1]);
        }
        if (!$data['suplente'] && preg_match('/Suplente:\s*([^\r\n<|]+?)(?=(?:[ÚU]ltimo|Preparaci|Experiencia|$))/iu', $textTotal, $m)) {
            $data['suplente'] = $this->cleanString($m[1]);
        }
        if (!$data['nacimiento'] && preg_match('/Nacimiento:\s*([^\r\n<|]+?)(?=(?:Entidad|Ciudad|Principio|$))/iu', $textTotal, $m)) {
            $data['nacimiento'] = $this->cleanString($m[1]);
        }
        if (!$data['correo'] && preg_match('/Correo electr[oó]nico:\s*([^\r\n<|]+?)(?=(?:Tel|Suplente|$))/iu', $textTotal, $m)) {
            $data['correo'] = $this->cleanString($m[1]);
        }
        if (!$data['telefono'] && preg_match('/Tel[eé]fono:\s*([^\r\n<|]+?)(?=(?:Suplente|[ÚU]ltimo|$))/iu', $textTotal, $m)) {
            $data['telefono'] = $this->cleanString($m[1]);
        }
        if (!$data['ubicacion'] && preg_match('/Ubicaci[oó]n:\s*([^\r\n<|]+?)(?=(?:Correo|Tel|$))/iu', $textTotal, $m)) {
            $data['ubicacion'] = $this->cleanString($m[1]);
        }
        if (!$data['ultimo_grado_estudios'] && preg_match('/[UÚ]ltimo grado de estudios:\s*([^\r\n<|]+?)(?=(?:Preparaci|Experiencia|Redes|$))/iu', $textTotal, $m)) {
            $val = $this->cleanString($m[1]);
            if ($val && !in_array(mb_strtolower($val), ['no disponible', 'sin registro', 'n/a'])) {
                $data['ultimo_grado_estudios'] = $val;
            }
        }
        if (!$data['preparacion_academica'] && preg_match('/Preparaci[oó]n acad[eé]mica:\s*([^\r\n<|]+?)(?=(?:Experiencia|Elecci[oó]n|Redes|$))/iu', $textTotal, $m)) {
            $val = $this->cleanString($m[1]);
            if ($val && !in_array(mb_strtolower($val), ['no disponible', 'sin registro', 'n/a'])) {
                $data['preparacion_academica'] = $val;
            }
        }
        if (preg_match('/Experiencia legislativa:\s*([^\r\n<|]+?)(?=(?:Elecci[oó]n|Redes|$))/iu', $textTotal, $m)) {
            $data['experiencia_legislativa_resumen'] = $this->cleanString($m[1]);
        }

        // Extraer Redes Sociales desde el HTML oficial del SIL
        $data['redes_sociales'] = self::parseRedesSocialesFromHtml($htmlRaw);

        // Extraer Suplencias y Titularidades desde el HTML oficial del SIL
        $suplencias = self::parseSuplenciasFromHtml($htmlRaw);
        if (!empty($suplencias['titular_nombre'])) {
            $data['titular_nombre'] = $suplencias['titular_nombre'];
        }
        if (!empty($suplencias['titular_referencia_sil'])) {
            $data['titular_referencia_sil'] = $suplencias['titular_referencia_sil'];
        }
        if (!empty($suplencias['suplente_nombre'])) {
            $data['suplente'] = $suplencias['suplente_nombre'];
        }
        if (!empty($suplencias['suplente_referencia_sil'])) {
            $data['suplente_referencia_sil'] = $suplencias['suplente_referencia_sil'];
        }

        // Parsear tablas de comisiones y trayectorias
        $sectionHeaders = $xpath->query("//table[contains(@class, 'datosL2')]");
        foreach ($sectionHeaders as $headerTable) {
            $headerText = strtoupper($this->cleanString($headerTable->textContent) ?? '');

            $dataTable = $xpath->query("./following-sibling::table[contains(@class, 'TFtable2')][1]", $headerTable)->item(0);
            if (!$dataTable) {
                continue;
            }

            $rows = $xpath->query(".//tr", $dataTable);
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 0) continue;

                // VALIDACIÓN CRÍTICA: Asegurar que el <tr> pertenezca directamente a $dataTable
                // En el HTML malformado del SIL, la falta de </table> anida tablas hijas dentro de la primera.
                $parentTable = $row->parentNode;
                while ($parentTable && $parentTable->nodeName !== 'table') {
                    $parentTable = $parentTable->parentNode;
                }
                if ($parentTable !== $dataTable) {
                    continue;
                }

                $cells = $xpath->query("./td", $row);
                if ($cells->length === 0) continue;

                if (str_contains($headerText, 'COMISIONES')) {
                    if ($cells->length >= 3) {
                        $cNombre = $this->cleanString($cells->item(0)->textContent);
                        $cPuesto = $this->cleanString($cells->item(1)->textContent);
                        $cIni = $this->cleanString($cells->item(2)->textContent);
                        $cFin = ($cells->length >= 4) ? $this->cleanString($cells->item(3)->textContent) : null;
                        $cEst = ($cells->length >= 5) ? $this->cleanString($cells->item(4)->textContent) : 'Activo';

                        if ($cNombre && !str_contains($cNombre, 'Comisión') && !is_numeric($cNombre)) {
                            $data['comisiones'][] = [
                                'comision' => $cNombre,
                                'puesto' => $cPuesto,
                                'fecha_inicio' => $cIni,
                                'fecha_fin' => $cFin,
                                'estatus' => $cEst,
                            ];
                        }
                    }
                } elseif (str_contains($headerText, 'TRAYECTORIA POLÍTICA') || str_contains($headerText, 'TRAYECTORIA POLITICA')) {
                    if ($cells->length >= 3) {
                        $exp = $this->cleanString($cells->item(2)->textContent);
                        if (!empty($exp)) {
                            $data['trayectoria_politica'][] = [
                                'del_ano' => $this->cleanString($cells->item(0)->textContent),
                                'al_ano' => $this->cleanString($cells->item(1)->textContent),
                                'experiencia' => $exp,
                            ];
                        }
                    }
                } elseif (str_contains($headerText, 'TRAYECTORIA LEGISLATIVA')) {
                    if ($cells->length >= 3) {
                        $exp = $this->cleanString($cells->item(2)->textContent);
                        if (!empty($exp)) {
                            $data['trayectoria_legislativa'][] = [
                                'del_ano' => $this->cleanString($cells->item(0)->textContent),
                                'al_ano' => $this->cleanString($cells->item(1)->textContent),
                                'experiencia' => $exp,
                            ];
                        }
                    }
                } elseif (str_contains($headerText, 'TRAYECTORIA ADMINISTRATIVA')) {
                    if ($cells->length >= 3) {
                        $exp = $this->cleanString($cells->item(2)->textContent);
                        if (!empty($exp)) {
                            $data['trayectoria_administrativa'][] = [
                                'del_ano' => $this->cleanString($cells->item(0)->textContent),
                                'al_ano' => $this->cleanString($cells->item(1)->textContent),
                                'experiencia' => $exp,
                            ];
                        }
                    }
                } elseif (str_contains($headerText, 'ESCOLARIDAD') || str_contains($headerText, 'TRAYECTORIA ACAD') || str_contains($headerText, 'FORMACIÓN ACAD') || str_contains($headerText, 'FORMACION ACAD')) {
                    if ($cells->length >= 3) {
                        $exp = $this->cleanString($cells->item(2)->textContent);
                        if (!empty($exp)) {
                            $data['escolaridad_detalle'][] = [
                                'del_ano' => $this->cleanString($cells->item(0)->textContent),
                                'al_ano' => $this->cleanString($cells->item(1)->textContent),
                                'experiencia' => $exp,
                            ];
                        }
                    }
                } elseif (str_contains($headerText, 'LICENCIAS')) {
                    if ($cells->length >= 3) {
                        $data['licencias'][] = [
                            'tipo' => $this->cleanString($cells->item(0)->textContent),
                            'fecha_inicio' => $this->cleanString($cells->item(1)->textContent),
                            'fecha_fin' => $this->cleanString($cells->item(2)->textContent),
                            'estatus' => ($cells->length >= 4) ? $this->cleanString($cells->item(3)->textContent) : null,
                        ];
                    }
                }
            }
        }

        // Deduplicación estricta de trayectorias
        $data['trayectoria_politica'] = $this->deduplicateRows($data['trayectoria_politica']);
        $data['trayectoria_legislativa'] = $this->deduplicateRows($data['trayectoria_legislativa']);
        $data['trayectoria_administrativa'] = $this->deduplicateRows($data['trayectoria_administrativa']);
        $data['escolaridad_detalle'] = $this->deduplicateRows($data['escolaridad_detalle']);

        // Separar grados académicos para que vivan exclusivamente en escolaridad_detalle
        $this->sanitizeAndSeparateEducation($data);

        return $data;
    }

    /**
     * Mueve grados y títulos académicos a escolaridad_detalle si cayeron en trayectorias.
     */
    private function sanitizeAndSeparateEducation(array &$data): void
    {
        $isEducation = function (string $text): bool {
            $t = mb_strtolower($text, 'UTF-8');
            return str_contains($t, 'licenciatura') ||
                   str_contains($t, 'doctorado') ||
                   str_contains($t, 'maestría') ||
                   str_contains($t, 'maestria') ||
                   str_contains($t, 'diplomado') ||
                   str_contains($t, 'especialidad en') ||
                   str_contains($t, 'bachillerato') ||
                   str_contains($t, 'primaria') ||
                   str_contains($t, 'secundaria') ||
                   str_contains($t, 'estudios de');
        };

        $cleanPolitica = [];
        foreach ($data['trayectoria_politica'] as $item) {
            if ($isEducation($item['experiencia'])) {
                $data['escolaridad_detalle'][] = $item;
            } else {
                $cleanPolitica[] = $item;
            }
        }
        $data['trayectoria_politica'] = $cleanPolitica;

        $cleanAdmin = [];
        foreach ($data['trayectoria_administrativa'] as $item) {
            if ($isEducation($item['experiencia'])) {
                $data['escolaridad_detalle'][] = $item;
            } else {
                $cleanAdmin[] = $item;
            }
        }
        $data['trayectoria_administrativa'] = $cleanAdmin;

        $cleanLeg = [];
        foreach ($data['trayectoria_legislativa'] as $item) {
            if ($isEducation($item['experiencia'])) {
                $data['escolaridad_detalle'][] = $item;
            } else {
                $cleanLeg[] = $item;
            }
        }
        $data['trayectoria_legislativa'] = $cleanLeg;

        $data['escolaridad_detalle'] = $this->deduplicateRows($data['escolaridad_detalle']);
    }

    /**
     * Deduplica arreglos de trayectorias con base en contenido único normalizado.
     */
    private function deduplicateRows(array $rows): array
    {
        $seen = [];
        $clean = [];
        foreach ($rows as $r) {
            $exp = trim($r['experiencia'] ?? '');
            if ($exp === '') continue;
            $norm = mb_strtolower($exp, 'UTF-8');
            $del = trim($r['del_ano'] ?? '');
            $al = trim($r['al_ano'] ?? '');
            $key = $del . '|' . $al . '|' . $norm;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $clean[] = [
                    'del_ano' => $del !== '' ? $del : null,
                    'al_ano' => $al !== '' ? $al : null,
                    'experiencia' => $exp,
                ];
            }
        }
        return $clean;
    }

    /**
     * Limpia y normaliza cadenas asegurando UTF-8 impecable sin doble codificación.
     */
    private function cleanString(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }

        if (preg_match('/[\xC2\xC3][\x80-\xBF]/', $str)) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $str);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                $str = $converted;
            }
        }

        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $str = trim(preg_replace('/\s+/', ' ', $str));

        return ($str === '' || $str === '&nbsp;' || $str === 'N/A') ? null : $str;
    }

    /**
     * Exporta el dataset procesado a formato JSON auditable.
     */
    private function exportJsonDataset(array $resultados, int $entidadId, int $legislatura): void
    {
        $jsonDir = $this->config['paths']['data_json'];
        if (!is_dir($jsonDir)) {
            mkdir($jsonDir, 0777, true);
        }

        $exportData = [
            'metadata' => [
                'entidad_id' => $entidadId,
                'entidad_nombre' => self::ENTIDADES[$entidadId] ?? "Entidad {$entidadId}",
                'legislatura' => $legislatura,
                'generado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'total_diputados' => count($resultados['diputados']),
                'total_senadores' => count($resultados['senadores']),
                'total_general' => $resultados['total'],
            ],
            'diputados' => array_map(fn(LegisladorDTO $d) => $d->toArray(), $resultados['diputados']),
            'senadores' => array_map(fn(LegisladorDTO $s) => $s->toArray(), $resultados['senadores']),
        ];

        $targetFile = "$jsonDir/legisladores_entidad_{$entidadId}_leg_{$legislatura}.json";
        file_put_contents($targetFile, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->logger->success("Dataset estructurado exportado", [
            'archivo' => $targetFile,
            'total_registros' => $resultados['total'],
        ]);
    }

    /**
     * Parsea enlaces a redes sociales y páginas personales desde el HTML del SIL.
     */
    public static function parseRedesSocialesFromHtml(string $htmlRaw): array
    {
        if (trim($htmlRaw) === '') return [];

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlRaw);
        $xpath = new \DOMXPath($doc);

        $redesSociales = [];

        // 1. Selector directo: buscar fila de "Redes sociales"
        $redesRows = $xpath->query("//tr[td/b[contains(text(), 'Redes sociales')]]//a[@href]");
        if ($redesRows->length === 0) {
            $redesRows = $xpath->query("//td[contains(., 'Redes sociales:')]/following-sibling::td[1]//a[@href]");
        }

        foreach ($redesRows as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }
            $red = self::clasificarRedSocial($href);
            if ($red !== null) {
                $redesSociales[$red['plataforma']] = $red;
            }
        }

        // 2. Si no se halló por selector específico, buscar enlaces de redes reconocidas
        if (empty($redesSociales)) {
            $allLinks = $xpath->query("//a[contains(@href, 'facebook.com') or contains(@href, 'twitter.com') or contains(@href, 'x.com') or contains(@href, 'instagram.com') or contains(@href, 'youtube.com') or contains(@href, 'tiktok.com')]");
            foreach ($allLinks as $a) {
                $href = trim($a->getAttribute('href'));
                $red = self::clasificarRedSocial($href);
                if ($red !== null) {
                    $redesSociales[$red['plataforma']] = $red;
                }
            }
        }

        return array_values($redesSociales);
    }

    /**
     * Clasifica una URL de red social y asigna metadatos, icono y color.
     */
    public static function clasificarRedSocial(string $url): ?array
    {
        $u = trim($url);
        if ($u === '') return null;

        // Limpieza de URLs con concatenación errónea del SIL (ej: skhttps://www.facebook.com/...)
        if (preg_match('/(https?:\/\/[^\s"\'<>]+)/i', $u, $mUrl)) {
            $u = $mUrl[1];
        }

        $low = mb_strtolower($u);

        if (str_contains($low, 'twitter.com') || str_contains($low, 'x.com')) {
            return [
                'plataforma' => 'x',
                'label' => 'X (Twitter)',
                'icono' => 'twitter-x',
                'url' => $u,
                'color' => '#000000',
            ];
        }

        if (str_contains($low, 'facebook.com')) {
            return [
                'plataforma' => 'facebook',
                'label' => 'Facebook',
                'icono' => 'facebook',
                'url' => $u,
                'color' => '#1877f2',
            ];
        }

        if (str_contains($low, 'instagram.com')) {
            return [
                'plataforma' => 'instagram',
                'label' => 'Instagram',
                'icono' => 'instagram',
                'url' => $u,
                'color' => '#e4405f',
            ];
        }

        if (str_contains($low, 'youtube.com') || str_contains($low, 'youtu.be')) {
            return [
                'plataforma' => 'youtube',
                'label' => 'YouTube',
                'icono' => 'youtube',
                'url' => $u,
                'color' => '#ff0000',
            ];
        }

        if (str_contains($low, 'tiktok.com')) {
            return [
                'plataforma' => 'tiktok',
                'label' => 'TikTok',
                'icono' => 'tiktok',
                'url' => $u,
                'color' => '#000000',
            ];
        }

        if (str_starts_with($low, 'http://') || str_starts_with($low, 'https://')) {
            return [
                'plataforma' => 'web',
                'label' => 'Sitio Web',
                'icono' => 'globe',
                'url' => $u,
                'color' => '#0d6efd',
            ];
        }

        return null;
    }

    /**
     * Enriquecedor de alto rendimiento: escanea los HTML ya descargados y actualiza
     * las redes sociales de todos los JSON existentes en segundos.
     */
    public static function enrichExistingJsonWithSocial(string $rootDir): array
    {
        $rawDir = $rootDir . '/storage/raw/html';
        $jsonDir = $rootDir . '/storage/data/json';

        if (!is_dir($rawDir) || !is_dir($jsonDir)) {
            return ['ok' => false, 'error' => 'Carpetas de almacenamiento no encontradas'];
        }

        $jsonFiles = glob($jsonDir . '/*.json');
        $totalEnriquecidos = 0;
        $archivosModificados = 0;

        foreach ($jsonFiles as $jFile) {
            if (str_contains($jFile, 'iniciativas') || str_contains($jFile, 'resumen')) {
                continue;
            }

            $content = (string)file_get_contents($jFile);
            $data = json_decode($content, true);
            if (!is_array($data)) continue;

            $modificado = false;
            foreach (['diputados', 'senadores'] as $grupo) {
                if (!isset($data[$grupo]) || !is_array($data[$grupo])) continue;

                foreach ($data[$grupo] as &$leg) {
                    $ref = (string)($leg['referencia_sil'] ?? '');
                    if ($ref === '') continue;

                    $htmlFile = $rawDir . "/profile_{$ref}.html";
                    if (file_exists($htmlFile)) {
                        $htmlContent = (string)file_get_contents($htmlFile);
                        $redes = self::parseRedesSocialesFromHtml($htmlContent);
                        if (!empty($redes)) {
                            $leg['redes_sociales'] = $redes;
                            $totalEnriquecidos++;
                            $modificado = true;
                        } elseif (!isset($leg['redes_sociales'])) {
                            $leg['redes_sociales'] = [];
                        }
                    }
                }
                unset($leg);
            }

            if ($modificado) {
                file_put_contents($jFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $archivosModificados++;
            }
        }

        return [
            'ok' => true,
            'archivos_modificados' => $archivosModificados,
            'total_redes_enriquecidas' => $totalEnriquecidos,
        ];
    }

    /**
     * Extrae relaciones de titularidad y suplencia desde el HTML oficial del SIL.
     */
    public static function parseSuplenciasFromHtml(string $htmlRaw): array
    {
        $htmlUtf8 = mb_convert_encoding($htmlRaw, 'UTF-8', 'ISO-8859-1, UTF-8');
        $result = [
            'titular_nombre' => null,
            'titular_referencia_sil' => null,
            'suplente_nombre' => null,
            'suplente_referencia_sil' => null,
        ];

        // 1. Suplente de: (cuando la persona actual es suplente)
        if (preg_match('/<b>Suplente de:<\/b>\s*<\/td>\s*<td[^>]*>(.*?)<\/td>/is', $htmlUtf8, $mTit)) {
            $rawTd = $mTit[1];
            if (preg_match('/Referencia=(\d+)/i', $rawTd, $refM)) {
                $result['titular_referencia_sil'] = $refM[1];
            }
            $cleanName = trim(strip_tags($rawTd));
            $cleanName = preg_replace('/^(Diputad[ao]|Senador[a]?)\s+/iu', '', $cleanName);
            $cleanName = trim(html_entity_decode($cleanName, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
            if ($cleanName !== '' && $cleanName !== '-' && !preg_match('/^[- ]+$/', $cleanName)) {
                $result['titular_nombre'] = $cleanName;
            }
        }

        // 2. Suplente: (cuando la persona actual es titular)
        if (preg_match('/<b>Suplente:<\/b>\s*<\/td>\s*<td[^>]*>(.*?)<\/td>/is', $htmlUtf8, $mSup)) {
            $rawTd = $mSup[1];
            if (preg_match('/Referencia=(\d+)/i', $rawTd, $refM)) {
                $result['suplente_referencia_sil'] = $refM[1];
            }
            $cleanName = trim(strip_tags($rawTd));
            $cleanName = preg_replace('/^(Diputad[ao]|Senador[a]?)\s+/iu', '', $cleanName);
            $cleanName = trim(html_entity_decode($cleanName, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
            if ($cleanName !== '' && $cleanName !== '-' && !preg_match('/^[- ]+$/', $cleanName)) {
                $result['suplente_nombre'] = $cleanName;
            }
        }

        return $result;
    }

    /**
     * Enriquece los archivos JSON existentes con metadatos de suplencia y titularidad.
     */
    public static function enrichExistingJsonWithSuplentes(string $rootDir): array
    {
        $rawDir = $rootDir . '/storage/raw/html';
        $jsonDir = $rootDir . '/storage/data/json';

        if (!is_dir($rawDir) || !is_dir($jsonDir)) {
            return ['ok' => false, 'error' => 'Carpetas de almacenamiento no encontradas'];
        }

        $jsonFiles = glob($jsonDir . '/*.json');
        $totalEnriquecidos = 0;
        $archivosModificados = 0;

        foreach ($jsonFiles as $jFile) {
            if (str_contains($jFile, 'iniciativas') || str_contains($jFile, 'resumen')) {
                continue;
            }

            $content = (string)file_get_contents($jFile);
            $data = json_decode($content, true);
            if (!is_array($data)) continue;

            $modificado = false;
            foreach (['diputados', 'senadores'] as $grupo) {
                if (!isset($data[$grupo]) || !is_array($data[$grupo])) continue;

                foreach ($data[$grupo] as &$leg) {
                    $ref = (string)($leg['referencia_sil'] ?? '');
                    if ($ref === '') continue;

                    $htmlFile = $rawDir . "/profile_{$ref}.html";
                    if (file_exists($htmlFile)) {
                        $htmlContent = (string)file_get_contents($htmlFile);
                        $supl = self::parseSuplenciasFromHtml($htmlContent);

                        if (!isset($leg['metadata']) || !is_array($leg['metadata'])) {
                            $leg['metadata'] = ['fuente' => 'SIL SEGOB'];
                        }

                        $cambioLeg = false;
                        if (!empty($supl['titular_nombre'])) {
                            $leg['metadata']['titular_nombre'] = $supl['titular_nombre'];
                            $leg['tipo_cargo'] = 'Suplente';
                            $cambioLeg = true;
                        }
                        if (!empty($supl['titular_referencia_sil'])) {
                            $leg['metadata']['titular_referencia_sil'] = $supl['titular_referencia_sil'];
                            $cambioLeg = true;
                        }
                        if (!empty($supl['suplente_nombre']) && empty($leg['suplente_nombre'])) {
                            $leg['suplente_nombre'] = $supl['suplente_nombre'];
                            $cambioLeg = true;
                        }
                        if (!empty($supl['suplente_referencia_sil'])) {
                            $leg['metadata']['suplente_referencia_sil'] = $supl['suplente_referencia_sil'];
                            $cambioLeg = true;
                        }

                        if ($cambioLeg) {
                            $totalEnriquecidos++;
                            $modificado = true;
                        }
                    }
                }
                unset($leg);
            }

            if ($modificado) {
                file_put_contents($jFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $archivosModificados++;
            }
        }

        return [
            'ok' => true,
            'archivos_modificados' => $archivosModificados,
            'total_suplencias_enriquecidas' => $totalEnriquecidos,
        ];
    }
}
