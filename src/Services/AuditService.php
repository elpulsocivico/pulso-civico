<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * AuditService: Sistema de auditoría, control de reportes ciudadanos y mitigación de spam.
 * Implementa rate-limiting estricto (máx 5 peticiones/minuto) y baneo temporal de 24 horas.
 */
class AuditService
{
    private string $storageDir;
    private string $reportsFile;
    private string $bansFile;
    private string $rateLimitFile;

    public const MAX_REPORTS_PER_MINUTE = 5;
    public const BAN_DURATION_SECONDS = 86400; // 24 horas

    public function __construct(?string $storageDir = null)
    {
        $base = $storageDir ?? dirname(__DIR__, 2) . '/storage';
        $this->storageDir = $base . '/data/auditoria';
        $this->reportsFile = $this->storageDir . '/reportes.json';
        $this->bansFile = $this->storageDir . '/bans.json';
        $this->rateLimitFile = $this->storageDir . '/rate_limits.json';

        $this->ensureStorageExists();
    }

    private function ensureStorageExists(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0775, true);
        }

        // Crear archivos base vacíos si no existen
        if (!file_exists($this->reportsFile)) {
            file_put_contents($this->reportsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        if (!file_exists($this->bansFile)) {
            file_put_contents($this->bansFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        if (!file_exists($this->rateLimitFile)) {
            file_put_contents($this->rateLimitFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Obtiene la IP real del cliente considerando proxies y CDN.
     */
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', (string)$_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Revisa si una IP se encuentra actualmente baneada por 24 horas.
     * @return array{banned: bool, expires_at: ?int, reason: ?string, remaining_seconds: int}
     */
    public function checkBanStatus(string $ip): array
    {
        $bans = $this->readJsonFile($this->bansFile);
        $currentTime = time();

        if (isset($bans[$ip])) {
            $ban = $bans[$ip];
            $expiresAt = (int)($ban['expires_at'] ?? 0);

            if ($expiresAt > $currentTime) {
                return [
                    'banned' => true,
                    'expires_at' => $expiresAt,
                    'reason' => (string)($ban['reason'] ?? 'Exceso de solicitudes automatizadas'),
                    'remaining_seconds' => $expiresAt - $currentTime,
                ];
            }

            // Expiró el baneo: limpiar de la lista
            unset($bans[$ip]);
            $this->writeJsonFile($this->bansFile, $bans);
        }

        return [
            'banned' => false,
            'expires_at' => null,
            'reason' => null,
            'remaining_seconds' => 0,
        ];
    }

    /**
     * Aplica un baneo de 24 horas a una dirección IP.
     */
    public function banIp(string $ip, int $durationSeconds = self::BAN_DURATION_SECONDS, string $reason = 'Exceso de reportes (>5 en 1 minuto)'): void
    {
        $bans = $this->readJsonFile($this->bansFile);
        $currentTime = time();
        $expiresAt = $currentTime + $durationSeconds;

        $bans[$ip] = [
            'ip' => $ip,
            'banned_at' => date('Y-m-d H:i:s', $currentTime),
            'banned_timestamp' => $currentTime,
            'expires_at' => $expiresAt,
            'expires_date' => date('Y-m-d H:i:s', $expiresAt),
            'reason' => $reason,
        ];

        $this->writeJsonFile($this->bansFile, $bans);
    }

    /**
     * Evalúa el rate limit para una IP (máximo 5 peticiones por minuto).
     * Si supera el límite, banea automáticamente por 24 horas.
     * @return array{allowed: bool, banned: bool, remaining: int, error?: string, expires_at?: int}
     */
    public function evaluateRateLimit(string $ip): array
    {
        // 1. Revisar si ya está baneada
        $banStatus = $this->checkBanStatus($ip);
        if ($banStatus['banned']) {
            return [
                'allowed' => false,
                'banned' => true,
                'remaining' => 0,
                'expires_at' => $banStatus['expires_at'],
                'error' => 'Tu dirección IP se encuentra suspendida temporalmente por 24 horas.',
            ];
        }

        // 2. Control de ventana deslizante de 60 segundos
        $currentTime = time();
        $windowStart = $currentTime - 60;
        $limits = $this->readJsonFile($this->rateLimitFile);

        $timestamps = $limits[$ip] ?? [];
        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        // Filtrar intentos que ocurrieron en los últimos 60 segundos
        $recentTimestamps = array_filter($timestamps, fn(int $ts) => $ts > $windowStart);

        if (count($recentTimestamps) >= self::MAX_REPORTS_PER_MINUTE) {
            // Se excedió el límite: Baneo automático por 24 horas
            $this->banIp($ip, self::BAN_DURATION_SECONDS, 'Actividad no humana detectada (>5 reportes en menos de 60 segundos)');

            // Limpiar historial de rate limit
            unset($limits[$ip]);
            $this->writeJsonFile($this->rateLimitFile, $limits);

            return [
                'allowed' => false,
                'banned' => true,
                'remaining' => 0,
                'expires_at' => $currentTime + self::BAN_DURATION_SECONDS,
                'error' => 'Has superado el límite de 5 reportes por minuto. Tu IP ha sido suspendida por 24 horas.',
            ];
        }

        // Registrar este intento
        $recentTimestamps[] = $currentTime;
        $limits[$ip] = array_values($recentTimestamps);
        $this->writeJsonFile($this->rateLimitFile, $limits);

        $count = count($recentTimestamps);
        return [
            'allowed' => true,
            'banned' => false,
            'remaining' => max(0, self::MAX_REPORTS_PER_MINUTE - $count),
        ];
    }

    /**
     * Procesa y almacena un nuevo reporte ciudadano de anomalía.
     * @param array<string, mixed> $input
     * @return array{ok: bool, ticket_id?: string, error?: string, banned?: bool, redirect?: string}
     */
    public function submitReport(array $input, string $ip, string $userAgent): array
    {
        // 1. Verificación de Honeypot Anti-Spam (trampa para bots)
        $honeypot = trim((string)($input['website_url_check'] ?? ''));
        if ($honeypot !== '') {
            // El bot cayó en la trampa: banear inmediatamente y responder fingiendo éxito
            $this->banIp($ip, self::BAN_DURATION_SECONDS, 'Bot detectado vía honeypot');
            return [
                'ok' => false,
                'banned' => true,
                'redirect' => '/baneado',
            ];
        }

        // 2. Verificación de Rate Limit y Estado de Ban
        $rateCheck = $this->evaluateRateLimit($ip);
        if (!$rateCheck['allowed']) {
            return [
                'ok' => false,
                'banned' => true,
                'error' => $rateCheck['error'] ?? 'Solicitud no permitida.',
                'redirect' => '/baneado',
            ];
        }

        // 3. Sanitización y Validación de Campos
        $categoria = trim((string)($input['categoria'] ?? ''));
        $detalle = trim((string)($input['detalle'] ?? ''));

        if ($categoria === '' || mb_strlen($detalle) < 5) {
            return [
                'ok' => false,
                'banned' => false,
                'error' => 'Por favor selecciona la categoría de la anomalía y describe brevemente el hallazgo.',
            ];
        }

        $checkmarks = $input['checkmarks'] ?? [];
        if (!is_array($checkmarks)) {
            $checkmarks = [];
        }
        $cleanCheckmarks = array_map(fn($item) => strip_tags(trim((string)$item)), $checkmarks);

        $fuenteUrl = trim((string)($input['fuente_url'] ?? ''));
        if ($fuenteUrl !== '' && !filter_var($fuenteUrl, FILTER_VALIDATE_URL)) {
            $fuenteUrl = '';
        }

        $email = trim((string)($input['contacto_email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        // 4. Generación de Identificador Único de Ticket (Auditable)
        $randomSuffix = strtoupper(bin2hex(random_bytes(2)));
        $ticketId = 'REP-' . date('Ymd') . '-' . $randomSuffix;

        $reportRecord = [
            'ticket_id' => $ticketId,
            'fecha_iso' => date('c'),
            'fecha_humana' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'user_agent' => substr($userAgent, 0, 255),
            'origen' => [
                'tipo' => strip_tags(trim((string)($input['origen_tipo'] ?? 'politico'))),
                'id' => strip_tags(trim((string)($input['origen_id'] ?? ''))),
                'nombre' => strip_tags(trim((string)($input['origen_nombre'] ?? ''))),
                'url' => strip_tags(trim((string)($input['origen_url'] ?? ''))),
            ],
            'categoria' => strip_tags($categoria),
            'checkmarks' => array_values($cleanCheckmarks),
            'detalle' => strip_tags($detalle),
            'fuente_url' => $fuenteUrl,
            'contacto_email' => $email,
            'estado' => 'pendiente', // pendiente, en_revision, resuelto, descartado
            'notas_auditoria' => [],
        ];

        // 5. Almacenamiento Persistente en JSON
        $reports = $this->readJsonFile($this->reportsFile);
        $reports[] = $reportRecord;
        $this->writeJsonFile($this->reportsFile, $reports);

        // 6. Notificación Inmediata a Telegram (para la manada de colaboradores)
        $this->dispatchTelegramNotification($reportRecord);

        return [
            'ok' => true,
            'ticket_id' => $ticketId,
            'mensaje' => 'Reporte recibido exitosamente. Folio asignado: ' . $ticketId,
        ];
    }

    /**
     * Envía una alerta enriquecida con botones interactivos al grupo de Telegram.
     */
    public function dispatchTelegramNotification(array $report): bool
    {
        $token = $this->getEnvVar('TELEGRAM_BOT_TOKEN');
        $chatId = $this->getEnvVar('TELEGRAM_CHAT_ID');
        $topicId = $this->getEnvVar('TELEGRAM_TOPIC_ID');

        if ($token === '' || $chatId === '') {
            return false;
        }

        $ticketId = (string)($report['ticket_id'] ?? 'REP-0000');
        $nombre = htmlspecialchars((string)($report['origen']['nombre'] ?? 'Expediente General'), ENT_QUOTES, 'UTF-8');
        $origenUrl = (string)($report['origen']['url'] ?? '');
        $categoria = htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($report['categoria'] ?? 'anomalia'))), ENT_QUOTES, 'UTF-8');
        $detalle = htmlspecialchars((string)($report['detalle'] ?? ''), ENT_QUOTES, 'UTF-8');
        $fuenteUrl = (string)($report['fuente_url'] ?? '');
        $fecha = (string)($report['fecha_humana'] ?? date('Y-m-d H:i:s'));
        $ip = (string)($report['ip'] ?? '0.0.0.0');

        $text = "🚩 <b>NUEVO REPORTE DE ANOMALÍA</b>\n";
        $text .= "<b>Folio:</b> <code>{$ticketId}</code>\n\n";
        $text .= "👤 <b>Expediente:</b> {$nombre}\n";
        $text .= "🏷️ <b>Categoría:</b> {$categoria}\n";

        if (!empty($report['checkmarks']) && is_array($report['checkmarks'])) {
            $checksStr = htmlspecialchars(implode(', ', $report['checkmarks']), ENT_QUOTES, 'UTF-8');
            $text .= "📋 <b>Detalles:</b> <i>{$checksStr}</i>\n";
        }

        $text .= "\n💬 <b>Descripción del ciudadano:</b>\n<i>\"{$detalle}\"</i>\n";

        if ($fuenteUrl !== '') {
            $fuenteEsc = htmlspecialchars($fuenteUrl, ENT_QUOTES, 'UTF-8');
            $text .= "\n🔗 <b>Fuente aportada:</b> <a href=\"{$fuenteEsc}\">Enlace Oficial de Respaldo</a>\n";
        }

        $text .= "\n🕒 <b>Fecha:</b> {$fecha}\n";
        $text .= "🌐 <b>IP / Auditoría:</b> <code>{$ip}</code>";

        // Botones interactivos (Inline Keyboard). Nota: Telegram rechaza botones con 'localhost'
        $buttons = [];
        if ($origenUrl !== '' && filter_var($origenUrl, FILTER_VALIDATE_URL) && !str_contains($origenUrl, 'localhost') && !str_contains($origenUrl, '127.0.0.1')) {
            $buttons[] = ['text' => '🔍 Ver Ficha en Web', 'url' => $origenUrl];
        }
        if ($fuenteUrl !== '' && filter_var($fuenteUrl, FILTER_VALIDATE_URL) && !str_contains($fuenteUrl, 'localhost') && !str_contains($fuenteUrl, '127.0.0.1')) {
            $buttons[] = ['text' => '🔗 Fuente Oficial', 'url' => $fuenteUrl];
        }

        $postParams = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if (!empty($buttons)) {
            $postParams['reply_markup'] = json_encode(['inline_keyboard' => [$buttons]]);
        }
        if (!empty($topicId)) {
            $postParams['message_thread_id'] = (int)$topicId;
        }

        $apiUrl = "https://api.telegram.org/bot{$token}/sendMessage";
        $raw = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postParams));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($postParams),
                    'timeout' => 6,
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $raw = @file_get_contents($apiUrl, false, $ctx);
        }

        if ($raw === false || $raw === null) {
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

        $envFile = dirname(__DIR__, 2) . '/.env';
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

    /**
     * Obtiene el listado de reportes para auditoría en CLI / Gateway.
     * @return array<int, array>
     */
    public function getReports(?string $estado = null): array
    {
        $reports = $this->readJsonFile($this->reportsFile);
        if ($estado === null || $estado === '') {
            return $reports;
        }

        return array_values(array_filter($reports, fn(array $r) => ($r['estado'] ?? '') === $estado));
    }

    /**
     * Obtiene la lista actual de IPs baneadas.
     * @return array<string, array>
     */
    public function getActiveBans(): array
    {
        $bans = $this->readJsonFile($this->bansFile);
        $currentTime = time();
        $active = [];

        foreach ($bans as $ip => $data) {
            $expiresAt = (int)($data['expires_at'] ?? 0);
            if ($expiresAt > $currentTime) {
                $data['tiempo_restante_minutos'] = round(($expiresAt - $currentTime) / 60, 1);
                $active[$ip] = $data;
            }
        }

        return $active;
    }

    /**
     * Desbanea una IP manualmente (operación de soporte/administración).
     */
    public function unbanIp(string $ip): bool
    {
        $bans = $this->readJsonFile($this->bansFile);
        if (isset($bans[$ip])) {
            unset($bans[$ip]);
            $this->writeJsonFile($this->bansFile, $bans);
            return true;
        }
        return false;
    }

    /**
     * Actualiza el estado de un ticket de reporte.
     */
    public function updateReportStatus(string $ticketId, string $nuevoEstado): bool
    {
        $reports = $this->readJsonFile($this->reportsFile);
        $encontrado = false;

        foreach ($reports as &$rep) {
            if (($rep['ticket_id'] ?? '') === $ticketId) {
                $rep['estado'] = $nuevoEstado;
                $rep['actualizado_en'] = date('Y-m-d H:i:s');
                $encontrado = true;
                break;
            }
        }
        unset($rep);

        if ($encontrado) {
            $this->writeJsonFile($this->reportsFile, $reports);
        }

        return $encontrado;
    }

    private function readJsonFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }
        $content = (string)file_get_contents($filePath);
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writeJsonFile(string $filePath, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @file_put_contents($filePath, (string)$json, LOCK_EX);
    }
}
