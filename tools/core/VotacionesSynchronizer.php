<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;
use PulsoCivico\Services\ParliamentScraperService;

class VotacionesSynchronizer
{
    private string $rootDir;
    private array $config;
    private Logger $logger;
    private ParliamentScraperService $service;
    private string $telegramToken;
    private string $telegramChatId;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;

        // Autoloader para src/
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

        $this->config = require $rootDir . '/config/config.php';
        $this->logger = new Logger($this->config['paths']['logs'] ?? ($rootDir . '/storage/logs'), 'SYNC-VOTACIONES');

        $httpConfig = array_merge($this->config['sil'] ?? [], [
            'rate_limit_ms' => 150,
            'base_url' => ParliamentScraperService::BASE_SITL_DIPUTADOS,
        ]);
        $httpClient = new HttpClient($httpConfig, $this->logger);
        $this->service = new ParliamentScraperService($this->config, $httpClient, $this->logger);

        $this->telegramToken = $this->getEnvVar('TELEGRAM_BOT_TOKEN');
        $this->telegramChatId = $this->getEnvVar('TELEGRAM_CHAT_ID');
    }

    /**
     * Sincroniza todas las votaciones e iniciativas de la legislatura actual.
     * Detecta novedades, actualiza los datasets JSON y notifica a Telegram.
     */
    public function sync(int $legislatura = 66, bool $force = false, bool $notifyTelegram = true, int $maxVotaciones = 500): array
    {
        $inicio = microtime(true);
        $periodos = [1, 2, 3];
        $totalNuevas = 0;
        $totalAcumulado = 0;
        $periodosActualizados = [];
        $nuevasAprobadasNotificadas = [];

        $dataDir = $this->config['paths']['data_json'] ?? ($this->rootDir . '/storage/data/json');

        foreach ($periodos as $pert) {
            $archivoPeriodo = "{$dataDir}/votaciones_periodo_{$pert}_leg_{$legislatura}.json";
            
            // 1. Cargar datos existentes si existen
            $existentesMap = [];
            if (!$force && file_exists($archivoPeriodo)) {
                $raw = @file_get_contents($archivoPeriodo);
                if ($raw) {
                    $jsonDec = json_decode($raw, true);
                    if (is_array($jsonDec) && !empty($jsonDec['votaciones'])) {
                        foreach ($jsonDec['votaciones'] as $v) {
                            if (isset($v['id_votacion_origen'])) {
                                $existentesMap[(int)$v['id_votacion_origen']] = $v;
                            }
                        }
                    }
                }
            }

            // 2. Consultar lista actual del SITL
            $listaSitl = $this->service->getVotacionesPorPeriodo($pert);
            if (empty($listaSitl)) {
                continue;
            }

            $nuevasEnPeriodo = 0;
            $votacionesActualizadas = $existentesMap;

            foreach ($listaSitl as $itemSitl) {
                $vId = (int)$itemSitl['id_votacion_origen'];

                // Si ya la tenemos procesada y no estamos forzando, continuar
                if (isset($votacionesActualizadas[$vId]) && !$force) {
                    continue;
                }

                // Extraer detalle completo
                $this->logger->info("Extrayendo detalle de votación {$vId} (Periodo {$pert})...");
                $det = $this->service->getDetalleVotacion($vId);
                if ($det) {
                    $votacionesActualizadas[$vId] = $det;
                    $nuevasEnPeriodo++;
                    $totalNuevas++;

                    // Registrar para notificación si fue Aprobada
                    if (($det['resultado'] ?? '') === 'Aprobado') {
                        $nuevasAprobadasNotificadas[] = $det;
                    }
                }

                if ($totalNuevas >= $maxVotaciones) {
                    break;
                }
            }

            // Si hay datos para guardar
            if (!empty($votacionesActualizadas)) {
                // Ordenar por ID de votación ascendente
                ksort($votacionesActualizadas);
                $listaOrdenada = array_values($votacionesActualizadas);

                $payload = [
                    'periodo_id' => $pert,
                    'legislatura' => $legislatura,
                    'total_votaciones' => count($listaOrdenada),
                    'fecha_extraccion' => date('Y-m-d H:i:s'),
                    'votaciones' => $listaOrdenada,
                ];

                file_put_contents($archivoPeriodo, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $totalAcumulado += count($listaOrdenada);
                $periodosActualizados[$pert] = [
                    'total' => count($listaOrdenada),
                    'nuevas' => $nuevasEnPeriodo,
                ];
            }
        }

        $duracion = round(microtime(true) - $inicio, 2);

        // 3. Notificación a Telegram
        $telegramEnviado = false;
        if ($notifyTelegram && !empty($this->telegramToken) && !empty($this->telegramChatId)) {
            // Notificar las aprobadas más relevantes (hasta 4 para no saturar)
            $countNotif = 0;
            foreach ($nuevasAprobadasNotificadas as $vot) {
                if ($countNotif >= 4) {
                    break;
                }
                $this->enviarAlertaVotacionTelegram($vot);
                $countNotif++;
                usleep(500000); // 500ms pausa respetuosa con la API de Telegram
            }

            // Notificación resumen si hubo novedades o si es ejecución forzada
            if ($totalNuevas > 0) {
                $mensajeResumen = "🏛️ <b>PULSO CÍVICO: SINCRONIZACIÓN LEGISLATIVA</b>\n"
                    . "━━━━━━━━━━━━━━━━━━━━━━\n"
                    . "✅ Se incorporaron <b>{$totalNuevas}</b> nuevas votaciones nominales a la base de datos de la LXVI Legislatura.\n"
                    . "📊 <b>Total acumulado:</b> {$totalAcumulado} votaciones y dictámenes registrados.\n"
                    . "⏱️ <b>Tiempo de sincronización:</b> {$duracion}s\n"
                    . "🕒 <b>Fecha:</b> " . date('Y-m-d H:i:s') . "\n\n"
                    . "<i>Sistema de Auditoría Ciudadana en Pleno</i>";

                $telegramEnviado = $this->enviarMensajeTelegram($mensajeResumen);
            }
        }

        return [
            'ok' => true,
            'legislatura' => $legislatura,
            'nuevas_votaciones' => $totalNuevas,
            'total_acumulado' => $totalAcumulado,
            'periodos' => $periodosActualizados,
            'aprobadas_notificadas' => count($nuevasAprobadasNotificadas),
            'telegram_notificado' => $telegramEnviado,
            'duracion_segundos' => $duracion,
        ];
    }

    /**
     * Envía una alerta estructurada de una votación aprobada a Telegram.
     */
    private function enviarAlertaVotacionTelegram(array $votacion): bool
    {
        $titulo = htmlspecialchars($votacion['titulo'] ?? 'Dictamen Legislativo', ENT_QUOTES, 'UTF-8');
        // Truncar título largo para Telegram manteniendo la esencia
        if (mb_strlen($titulo) > 280) {
            $titulo = mb_substr($titulo, 0, 277) . '...';
        }

        $tema = htmlspecialchars($votacion['tema_clave'] ?? 'Legislación General', ENT_QUOTES, 'UTF-8');
        $fecha = htmlspecialchars($votacion['fecha'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8');
        $totales = $votacion['totales'] ?? [];
        $favor = (int)($totales['favor'] ?? 0);
        $contra = (int)($totales['contra'] ?? 0);
        $abst = (int)($totales['abstencion'] ?? 0);
        $ausente = (int)($totales['ausente'] ?? 0);

        // Desglose rápido por bancadas clave
        $bancadas = $votacion['bancadas'] ?? [];
        $desgloseBancadas = [];
        $partidosClave = ['MORENA', 'PAN', 'PRI', 'PVEM', 'PT', 'MC'];
        foreach ($partidosClave as $p) {
            if (isset($bancadas[$p])) {
                $b = $bancadas[$p];
                $desgloseBancadas[] = "• <b>{$p}:</b> {$b['a_favor']} fav / {$b['en_contra']} con / {$b['ausente']} aus";
            }
        }
        $textoBancadas = implode("\n", $desgloseBancadas);

        $urlSitl = htmlspecialchars($votacion['fuente_url'] ?? 'https://sitl.diputados.gob.mx', ENT_QUOTES, 'UTF-8');

        $html = "🗳️ <b>NUEVA INICIATIVA / REFORMA APROBADA</b>\n"
            . "🏛️ <i>Cámara de Diputados • LXVI Legislatura</i>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━\n"
            . "📜 <b>Asunto:</b>\n<i>\"{$titulo}\"</i>\n\n"
            . "🏷️ <b>Tema:</b> #{$tema}\n"
            . "📅 <b>Fecha de Sesión:</b> {$fecha}\n"
            . "📊 <b>Votación General:</b>\n"
            . "  🟢 A Favor: <b>{$favor}</b>\n"
            . "  🔴 En Contra: <b>{$contra}</b>\n"
            . "  ⚪ Abstención: <b>{$abst}</b>\n"
            . "  🚫 Ausentes: <b>{$ausente}</b>\n\n"
            . "👥 <b>Por Bancada:</b>\n{$textoBancadas}\n\n"
            . "🔗 <a href=\"{$urlSitl}\">Ficha Oficial del SITL</a>";

        $buttons = [
            ['text' => '🏛️ Ver Ficha Oficial SITL', 'url' => $votacion['fuente_url'] ?? 'https://sitl.diputados.gob.mx']
        ];

        return $this->enviarMensajeTelegram($html, $buttons);
    }

    /**
     * Envía un mensaje con formato HTML a Telegram usando el bot configurado.
     */
    public function enviarMensajeTelegram(string $htmlMessage, ?array $buttons = null): bool
    {
        if (empty($this->telegramToken) || empty($this->telegramChatId)) {
            $this->logger->warning('Telegram no configurado: faltan tokens en .env');
            return false;
        }

        $apiUrl = "https://api.telegram.org/bot{$this->telegramToken}/sendMessage";
        $postParams = [
            'chat_id' => $this->telegramChatId,
            'text' => $htmlMessage,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ];

        if (!empty($buttons)) {
            $postParams['reply_markup'] = json_encode(['inline_keyboard' => [$buttons]]);
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postParams));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($postParams),
                    'timeout' => 10,
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $raw = @file_get_contents($apiUrl, false, $ctx);
        }

        if (!$raw) {
            return false;
        }

        $decoded = json_decode((string)$raw, true);
        return !empty($decoded['ok']);
    }

    private function getEnvVar(string $key): string
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return (string)$val;
        }
        if (!empty($_ENV[$key])) {
            return (string)$_ENV[$key];
        }

        $envFile = $this->rootDir . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), $key . '=')) {
                    return trim(substr(trim($line), strlen($key . '=')));
                }
            }
        }
        return '';
    }
}
