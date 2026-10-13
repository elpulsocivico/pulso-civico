<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

use PulsoCivico\Core\Logger;
use PulsoCivico\Core\HttpClient;
use PulsoCivico\Services\SilIngestService;

class ScraperRouter
{
    /**
     * Enruta peticiones de scraping/ingesta de forma parametrizada y reusable.
     */
    public static function run(string $rootDir, string $source, array $params = []): array
    {
        // Cargar autoloader de src
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });

        $config = require $rootDir . '/config/config.php';

        switch (strtolower($source)) {
            case 'sil':
            case 'legisladores':
                return self::scrapeSil($config, $params);

            case 'votaciones':
                return self::scrapeVotaciones($config, $params);

            case 'asistencias':
                return self::scrapeAsistencias($config, $params);

            case 'bc':
            case 'bajacalifornia':
                return self::scrapeBajaCalifornia($config, $params);

            default:
                return [
                    'ok' => false,
                    'error' => "Fuente de scraping '{$source}' no soportada.",
                    'available_sources' => ['sil', 'votaciones', 'asistencias', 'bc'],
                ];
        }
    }

    private static function scrapeSil(array $config, array $params): array
    {
        $esNacional = !empty($params['nacional']) || (isset($params['entidad']) && (string)$params['entidad'] === 'all');
        $entidadId = isset($params['entidad']) && is_numeric($params['entidad']) ? (int)$params['entidad'] : 2;
        $legislatura = isset($params['legislatura']) ? (int)$params['legislatura'] : 66;
        $descargarFotos = !isset($params['no-photos']);

        $logger = new Logger($config['paths']['logs'], 'ROUTER-SCRAPER');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new SilIngestService($config, $httpClient, $logger);

        $inicio = microtime(true);
        if ($esNacional) {
            $resultados = $service->ingestNacional($legislatura, $descargarFotos);
            $duracionSeg = round(microtime(true) - $inicio, 2);

            return [
                'ok' => empty($resultados['errores']),
                'source' => 'sil',
                'alcance' => 'nacional',
                'legislatura' => $legislatura,
                'duration_seconds' => $duracionSeg,
                'entidades_procesadas' => $resultados['entidades_procesadas'] ?? 32,
                'total_diputados' => $resultados['total_diputados'] ?? 0,
                'total_senadores' => $resultados['total_senadores'] ?? 0,
                'total' => $resultados['total_general'] ?? 0,
                'errores' => $resultados['errores'] ?? [],
            ];
        }

        $resultados = $service->ingestByEntidad($entidadId, $legislatura, $descargarFotos);
        $duracionSeg = round(microtime(true) - $inicio, 2);

        return [
            'ok' => empty($resultados['errores']),
            'source' => 'sil',
            'entidad_id' => $entidadId,
            'legislatura' => $legislatura,
            'duration_seconds' => $duracionSeg,
            'diputados_count' => count($resultados['diputados'] ?? []),
            'senadores_count' => count($resultados['senadores'] ?? []),
            'total' => $resultados['total'] ?? 0,
            'errores' => $resultados['errores'] ?? [],
        ];
    }

    private static function scrapeVotaciones(array $config, array $params): array
    {
        $pert = isset($params['periodo']) ? (int)$params['periodo'] : 1;
        $limite = isset($params['limit']) ? (int)$params['limit'] : 5;

        $logger = new Logger($config['paths']['logs'], 'ROUTER-VOTACIONES');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\ParliamentScraperService($config, $httpClient, $logger);

        $inicio = microtime(true);
        $lista = $service->getVotacionesPorPeriodo($pert);

        $detalles = [];
        $contador = 0;
        foreach ($lista as $item) {
            if ($contador >= $limite) {
                break;
            }
            $det = $service->getDetalleVotacion($item['id_votacion_origen']);
            if ($det) {
                $detalles[] = $det;
                $contador++;
            }
        }

        if (!empty($detalles)) {
            $destFile = ($config['paths']['data_json'] ?? '') . "/votaciones_periodo_{$pert}_leg_66.json";
            file_put_contents($destFile, json_encode([
                'periodo_id' => $pert,
                'total_votaciones' => count($detalles),
                'votaciones' => $detalles,
                'fecha_extraccion' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return [
            'ok' => true,
            'source' => 'sitl_diputados',
            'modulo' => 'votaciones',
            'periodo_id' => $pert,
            'duracion_segundos' => round(microtime(true) - $inicio, 2),
            'votaciones_encontradas' => count($lista),
            'votaciones_procesadas' => count($detalles),
            'muestra' => array_slice($detalles, 0, 3),
        ];
    }

    private static function scrapeAsistencias(array $config, array $params): array
    {
        $pert = isset($params['periodo']) ? (int)$params['periodo'] : 1;
        $camara = strtolower((string)($params['camara'] ?? 'diputados'));
        $logger = new Logger($config['paths']['logs'], 'ROUTER-ASISTENCIAS');
        $sitlHttpConfig = array_merge($config['sil'], [
            'rate_limit_ms' => 100,
            'base_url' => \PulsoCivico\Services\ParliamentScraperService::BASE_SITL_DIPUTADOS,
        ]);
        $httpClient = new HttpClient($sitlHttpConfig, $logger);
        $service = new \PulsoCivico\Services\ParliamentScraperService($config, $httpClient, $logger);

        if ($camara === 'senadores' || $camara === 'senado') {
            return $service->scrapeAsistenciasSenadoresPeriodo($pert);
        }

        return $service->scrapeNominalAsistenciasPeriodo($pert);
    }

    private static function scrapeBajaCalifornia(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-BC');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\CongresoBajaCaliforniaScraperService($httpClient, $logger, $config['paths']['storage']);

        return $service->scrapeLegislatura25();
    }
}

