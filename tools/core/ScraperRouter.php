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

            case 'bcs':
            case 'bajacaliforniasur':
                return self::scrapeBajaCaliforniaSur($config, $params);

            case 'cam':
            case 'campeche':
            case 'congreso_campeche':
                return self::scrapeCampeche($config, $params);

            case 'yuc':
            case 'yucatan':
                return self::scrapeYucatan($config, $params);

            case 'qroo':
            case 'quintanaroo':
                return self::scrapeQuintanaRoo($config, $params);

            case 'chis':
            case 'chiapas':
                return self::scrapeChiapas($config, $params);

            case 'sinaloa':
            case 'congreso_sinaloa':
                return self::scrapeSinaloa($config, $params);

            case 'sonora':
            case 'congreso_sonora':
                return self::scrapeSonora($config, $params);

            case 'durango':
            case 'congreso_durango':
                return self::scrapeDurango($config, $params);

            case 'chihuahua':
            case 'congreso_chihuahua':
                return self::scrapeChihuahua($config, $params);

            case 'coahuila':
            case 'congreso_coahuila':
                return self::scrapeCoahuila($config, $params);

            case 'nuevoleon':
            case 'nuevo_leon':
            case 'congreso_nuevoleon':
                return self::scrapeNuevoLeon($config, $params);

            case 'ags':
            case 'aguascalientes':
            case 'congreso_aguascalientes':
                return self::scrapeEstadoGenerico('CongresoAguascalientesScraperService', 'ROUTER-AGS', $config, $params);

            case 'edomex':
            case 'estadodemexico':
            case 'congreso_edomex':
                return self::scrapeEstadoGenerico('CongresoEdomexScraperService', 'ROUTER-EDOMEX', $config, $params);

            case 'gto':
            case 'guanajuato':
            case 'congreso_guanajuato':
                return self::scrapeEstadoGenerico('CongresoGuanajuatoScraperService', 'ROUTER-GTO', $config, $params);

            case 'hgo':
            case 'hidalgo':
            case 'congreso_hidalgo':
                return self::scrapeEstadoGenerico('CongresoHidalgoScraperService', 'ROUTER-HGO', $config, $params);

            case 'jal':
            case 'jalisco':
            case 'congreso_jalisco':
                return self::scrapeEstadoGenerico('CongresoJaliscoScraperService', 'ROUTER-JAL', $config, $params);

            case 'mich':
            case 'michoacan':
            case 'congreso_michoacan':
                return self::scrapeEstadoGenerico('CongresoMichoacanScraperService', 'ROUTER-MICH', $config, $params);

            case 'nay':
            case 'nayarit':
            case 'congreso_nayarit':
                return self::scrapeEstadoGenerico('CongresoNayaritScraperService', 'ROUTER-NAY', $config, $params);

            case 'pue':
            case 'puebla':
            case 'congreso_puebla':
                return self::scrapeEstadoGenerico('CongresoPueblaScraperService', 'ROUTER-PUE', $config, $params);

            case 'qro':
            case 'queretaro':
            case 'congreso_queretaro':
                return self::scrapeEstadoGenerico('CongresoQueretaroScraperService', 'ROUTER-QRO', $config, $params);

            case 'slp':
            case 'sanluispotosi':
            case 'congreso_sanluispotosi':
                return self::scrapeEstadoGenerico('CongresoSanLuisPotosiScraperService', 'ROUTER-SLP', $config, $params);

            case 'zac':
            case 'zacatecas':
            case 'congreso_zacatecas':
                return self::scrapeEstadoGenerico('CongresoZacatecasScraperService', 'ROUTER-ZAC', $config, $params);

            case 'ejecutivo:estatal':
            case 'ejecutivo:aguascalientes':
            case 'ejecutivo:ags':
                return self::scrapeEjecutivoEstatal($config, $params, 'aguascalientes');

            default:
                return [
                    'ok' => false,
                    'error' => "Fuente de scraping '{$source}' no soportada.",
                    'available_sources' => [
                        'sil', 'votaciones', 'asistencias',
                        'bc', 'bcs', 'campeche', 'yucatan', 'quintanaroo', 'chiapas',
                        'sinaloa', 'sonora', 'durango', 'chihuahua', 'coahuila', 'nuevoleon',
                        'aguascalientes', 'edomex', 'guanajuato', 'hidalgo', 'jalisco',
                        'michoacan', 'nayarit', 'puebla', 'queretaro', 'sanluispotosi', 'zacatecas',
                        'ejecutivo:aguascalientes'
                    ],
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

    private static function scrapeBajaCaliforniaSur(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-BCS');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\CongresoBcsScraperService($httpClient, $logger, $config['paths']['storage']);

        $modo = strtolower((string)($params['modo'] ?? 'todo'));
        if ($modo === 'actual' || $modo === '17' || (!empty($params['legislatura']) && (int)$params['legislatura'] === 17)) {
            return $service->scrapeLegislatura17();
        }

        if ($modo === 'historico' || $modo === 'historicas') {
            return $service->scrapeTodasHistoricas();
        }

        return $service->scrapeTodo();
    }

    private static function scrapeCampeche(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-CAM');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\CongresoCampecheScraperService($httpClient, $logger, $config['paths']['storage']);

        $modo = strtolower((string)($params['modo'] ?? 'todo'));
        if ($modo === 'actual' || $modo === '65' || (!empty($params['legislatura']) && (int)$params['legislatura'] === 65)) {
            return $service->scrapeLegislatura65();
        }

        if ($modo === 'historico' || $modo === 'historicas') {
            return $service->scrapeTodasHistoricas();
        }

        if (!empty($params['legislatura'])) {
            $legNum = (int)$params['legislatura'];
            if ($legNum === 64) {
                return $service->scrapeLegislatura64();
            }
            if ($legNum === 63) {
                return $service->scrapeLegislatura63();
            }
            if ($legNum === 62) {
                return $service->scrapeLegislatura62();
            }
        }

        return $service->scrapeTodo();
    }

    private static function scrapeYucatan(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-YUC');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\CongresoYucatanScraperService($httpClient, $logger, $config['paths']['storage']);

        $modo = strtolower((string)($params['modo'] ?? 'todo'));
        if ($modo === 'actual' || $modo === '64' || (!empty($params['legislatura']) && (int)$params['legislatura'] === 64)) {
            return $service->scrapeLegislatura64();
        }

        if ($modo === 'historico' || $modo === 'historicas') {
            return $service->scrapeTodasHistoricas();
        }

        return $service->scrapeTodo();
    }

    private static function scrapeQuintanaRoo(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-QROO');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\CongresoQuintanaRooScraperService($httpClient, $logger, $config['paths']['storage']);

        $modo = strtolower((string)($params['modo'] ?? 'todo'));
        if ($modo === 'actual' || $modo === '18' || (!empty($params['legislatura']) && (int)$params['legislatura'] === 18)) {
            return $service->scrapeLegislatura18();
        }

        if ($modo === 'historico' || $modo === 'historicas') {
            return $service->scrapeTodasHistoricas();
        }

        return $service->scrapeTodo();
    }

    private static function scrapeChiapas(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-CHIS');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\CongresoChiapasScraperService($httpClient, $logger, $config['paths']['storage']);

        $modo = strtolower((string)($params['modo'] ?? 'todo'));
        if ($modo === 'actual' || $modo === '69' || (!empty($params['legislatura']) && (int)$params['legislatura'] === 69)) {
            return $service->scrapeLegislatura69();
        }

        if ($modo === 'historico' || $modo === 'historicas') {
            return $service->scrapeTodasHistoricas();
        }

        return $service->scrapeTodo();
    }

    private static function scrapeEstadoGenerico(string $className, string $logChannel, array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], $logChannel);
        $httpClient = new HttpClient($config['sil'], $logger);
        $fullClass = '\\PulsoCivico\\Services\\' . $className;
        /** @var mixed $service */
        $service = new $fullClass($httpClient, $logger, $config['paths']['storage']);

        if (!empty($params['legislatura']) && is_numeric($params['legislatura'])) {
            return $service->scrapeLegislatura((int)$params['legislatura']);
        }

        return $service->scrapeAll();
    }

    private static function scrapeSinaloa(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-SINALOA');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\SinaloaScraperService($config, $httpClient, $logger);

        $historico = !empty($params['historico']) || (isset($params['legislatura']) && $params['legislatura'] === 'all');
        if ($historico) {
            return $service->extraerTodas();
        }

        $legislatura = isset($params['legislatura']) ? (int)$params['legislatura'] : 65;
        return $service->extraer($legislatura);
    }

    private static function scrapeSonora(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-SONORA');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\SonoraScraperService($config, $httpClient, $logger);

        $historico = !empty($params['historico']) || (isset($params['legislatura']) && $params['legislatura'] === 'all');
        if ($historico) {
            return $service->extraerTodas();
        }

        $legislatura = isset($params['legislatura']) ? (int)$params['legislatura'] : 64;
        return $service->extraer($legislatura);
    }

    private static function scrapeDurango(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-DURANGO');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\DurangoScraperService($config, $httpClient, $logger);

        $historico = !empty($params['historico']) || (isset($params['legislatura']) && $params['legislatura'] === 'all');
        if ($historico) {
            return $service->extraerTodas();
        }

        $legislatura = isset($params['legislatura']) ? (int)$params['legislatura'] : 70;
        return $service->extraer($legislatura);
    }

    private static function scrapeChihuahua(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-CHIHUAHUA');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\ChihuahuaScraperService($config, $httpClient, $logger);

        $historico = !empty($params['historico']) || (isset($params['legislatura']) && $params['legislatura'] === 'all');
        if ($historico) {
            return $service->extraerTodas();
        }

        $legislatura = isset($params['legislatura']) ? (int)$params['legislatura'] : 68;
        return $service->extraer($legislatura);
    }

    private static function scrapeCoahuila(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'SCRAPER-COAHUILA');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\CoahuilaScraperService($config, $httpClient, $logger);

        $inicio = microtime(true);
        if (!empty($params['todas']) || (isset($params['historico']) && (string)$params['historico'] === '1')) {
            $res = $service->extraerTodas();
        } else {
            $legislatura = isset($params['legislatura']) ? (int)$params['legislatura'] : 63;
            $res = $service->extraer($legislatura);
        }
        $res['duracion_segundos'] = round(microtime(true) - $inicio, 2);
        return $res;
    }

    private static function scrapeNuevoLeon(array $config, array $params): array
    {
        $logger = new Logger($config['paths']['logs'], 'SCRAPER-NUEVOLEON');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\NuevoLeonScraperService($config, $httpClient, $logger);

        $inicio = microtime(true);
        if (!empty($params['todas']) || (isset($params['historico']) && (string)$params['historico'] === '1')) {
            $res = $service->extraerTodas();
        } else {
            $legislatura = isset($params['legislatura']) ? (int)$params['legislatura'] : 77;
            $res = $service->extraer($legislatura);
        }
        $res['duracion_segundos'] = round(microtime(true) - $inicio, 2);
        return $res;
    }

    private static function scrapeEjecutivoEstatal(array $config, array $params, string $entidad): array
    {
        $logger = new Logger($config['paths']['logs'], 'ROUTER-EJECUTIVO-ESTATAL');
        $httpClient = new HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\EjecutivoEstatalService($httpClient, $logger, $config['paths']['storage']);

        return $service->scrapeEntidad($entidad);
    }
}


