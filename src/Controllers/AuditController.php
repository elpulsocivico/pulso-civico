<?php
declare(strict_types=1);

namespace PulsoCivico\Controllers;

use PulsoCivico\Core\Router;
use PulsoCivico\Services\AuditService;

/**
 * AuditController: Controlador para la recepción de reportes ciudadanos y vista de suspensión por rate-limit.
 */
class AuditController
{
    private AuditService $auditService;

    public function __construct(?AuditService $auditService = null)
    {
        $this->auditService = $auditService ?? new AuditService();
    }

    /**
     * Endpoint API para recibir y procesar reportes de anomalías (POST /api/reportar-anomalia).
     */
    public function apiReportar(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $ip = AuditService::getClientIp();
        $userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');

        // Leer datos JSON o Form POST
        $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
        $data = [];

        if (str_contains($contentType, 'application/json')) {
            $raw = (string)file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        } else {
            $data = $_POST;
        }

        $result = $this->auditService->submitReport($data, $ip, $userAgent);

        if (!empty($result['banned'])) {
            http_response_code(429); // Too Many Requests
            $result['redirect'] = Router::url('/baneado');
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        if (empty($result['ok'])) {
            http_response_code(400); // Bad Request
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        http_response_code(200);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Vista de advertencia para usuarios con IP temporalmente suspendida (GET /baneado).
     */
    public function paginaBaneado(): void
    {
        $ip = AuditService::getClientIp();
        $banStatus = $this->auditService->checkBanStatus($ip);

        $seo = [
            'title' => 'Acceso Temporalmente Restringido — Pulso Cívico',
            'description' => 'Aviso de seguridad por límite de velocidad en solicitudes de reporte.',
            'canonical' => Router::url('/baneado'),
            'robots' => 'noindex, nofollow',
        ];

        require dirname(__DIR__, 2) . '/views/baneado.php';
    }
}
