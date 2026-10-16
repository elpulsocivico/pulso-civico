<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

class GitHubOps
{
    private const API_BASE = 'https://api.github.com';
    private const DEFAULT_REPO = 'elpulsocivico/pulso-civico';

    private static function getToken(): string
    {
        // 1. Variable de entorno
        $token = getenv('GITHUB_TOKEN');
        if ($token !== false && trim((string)$token) !== '') {
            return trim((string)$token);
        }

        // 2. Leer directamente de .env si no está en getenv()
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, '#')) continue;
                    if (str_starts_with($line, 'GITHUB_TOKEN=')) {
                        return trim(substr($line, strlen('GITHUB_TOKEN=')));
                    }
                }
            }
        }

        return '';
    }

    /**
     * Realiza una petición autenticada a la API REST de GitHub usando cURL nativo.
     */
    public static function api(string $method, string $endpoint, array $data = []): array
    {
        $token = self::getToken();
        if ($token === '') {
            return [
                'ok' => false,
                'status' => 401,
                'error' => 'GITHUB_TOKEN no configurado en .env',
            ];
        }

        $url = str_starts_with($endpoint, 'http') ? $endpoint : self::API_BASE . '/' . ltrim($endpoint, '/');
        $ch = curl_init();

        $headers = [
            'User-Agent: PulsoCivico-Agent/1.0',
            'Authorization: Bearer ' . $token,
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            return [
                'ok' => false,
                'status' => $httpCode,
                'error' => 'cURL error: ' . $curlError,
            ];
        }

        $decoded = json_decode((string)$responseBody, true);

        return [
            'ok' => ($httpCode >= 200 && $httpCode < 300),
            'status' => $httpCode,
            'data' => $decoded ?? $responseBody,
        ];
    }

    /**
     * Verifica la validez del token y los permisos del usuario.
     */
    public static function verifyAuth(): array
    {
        $res = self::api('GET', 'user');
        if ($res['ok'] && isset($res['data']['login'])) {
            return [
                'ok' => true,
                'user' => $res['data']['login'],
                'name' => $res['data']['name'] ?? '',
                'scopes' => $res['data']['scopes'] ?? 'repo',
            ];
        }
        return [
            'ok' => false,
            'error' => $res['data']['message'] ?? $res['error'] ?? 'Token inválido o sin permisos',
        ];
    }

    /**
     * Lista PRs abiertos.
     */
    public static function listPullRequests(string $repo = self::DEFAULT_REPO, string $state = 'open'): array
    {
        return self::api('GET', "repos/{$repo}/pulls?state={$state}&per_page=100");
    }

    /**
     * Crea un Pull Request.
     */
    public static function createPullRequest(
        string $title,
        string $head,
        string $base = 'main',
        string $body = '',
        string $repo = self::DEFAULT_REPO
    ): array {
        return self::api('POST', "repos/{$repo}/pulls", [
            'title' => $title,
            'head' => $head,
            'base' => $base,
            'body' => $body,
        ]);
    }

    /**
     * Fusiona un Pull Request.
     */
    public static function mergePullRequest(
        int $pullNumber,
        string $commitTitle = '',
        string $mergeMethod = 'merge',
        string $repo = self::DEFAULT_REPO
    ): array {
        $payload = [
            'merge_method' => $mergeMethod,
        ];
        if ($commitTitle !== '') {
            $payload['commit_title'] = $commitTitle;
        }

        return self::api('PUT', "repos/{$repo}/pulls/{$pullNumber}/merge", $payload);
    }

    /**
     * Crea y fusiona automáticamente una rama hacia main en una sola operación.
     */
    public static function createAndMerge(
        string $head,
        string $title,
        string $body = '',
        string $repo = self::DEFAULT_REPO
    ): array {
        // 1. Verificar si ya existe un PR abierto para esta rama
        $openPrs = self::listPullRequests($repo);
        $existingPrNumber = null;
        if ($openPrs['ok'] && is_array($openPrs['data'])) {
            foreach ($openPrs['data'] as $pr) {
                if (($pr['head']['ref'] ?? '') === $head) {
                    $existingPrNumber = (int)$pr['number'];
                    break;
                }
            }
        }

        // 2. Si no existe, crearlo
        if ($existingPrNumber === null) {
            $createRes = self::createPullRequest($title, $head, 'main', $body, $repo);
            if (!$createRes['ok']) {
                return [
                    'ok' => false,
                    'step' => 'create_pr',
                    'error' => $createRes['data']['message'] ?? 'No se pudo crear el PR',
                    'details' => $createRes['data'] ?? [],
                ];
            }
            $existingPrNumber = (int)$createRes['data']['number'];
        }

        // 3. Fusionar el PR
        $mergeRes = self::mergePullRequest($existingPrNumber, $title, 'merge', $repo);

        return [
            'ok' => $mergeRes['ok'],
            'pr_number' => $existingPrNumber,
            'merged' => $mergeRes['ok'],
            'merge_result' => $mergeRes['data'] ?? [],
            'error' => $mergeRes['ok'] ? null : ($mergeRes['data']['message'] ?? 'Error al fusionar'),
        ];
    }

    /**
     * Fusiona en lote todos los PRs abiertos que estén listos (mergeable=true).
     */
    public static function mergeAllReady(string $repo = self::DEFAULT_REPO): array

    {
        $prsRes = self::api('GET', "repos/{$repo}/pulls?state=open&per_page=100");
        if (!$prsRes['ok'] || !is_array($prsRes['data'])) {
            return [
                'ok' => false,
                'error' => 'No se pudieron listar los PRs',
                'details' => $prsRes,
            ];
        }

        $summary = [
            'total_open' => count($prsRes['data']),
            'merged' => [],
            'dirty_or_conflicted' => [],
            'failed' => [],
        ];

        foreach ($prsRes['data'] as $pr) {
            $num = (int)$pr['number'];
            $title = (string)$pr['title'];
            $head = (string)($pr['head']['ref'] ?? '');

            // Consultar detalle para verificar mergeable
            $detail = self::api('GET', "repos/{$repo}/pulls/{$num}");
            $isMergeable = $detail['data']['mergeable'] ?? null;
            $mergeableState = $detail['data']['mergeable_state'] ?? 'unknown';

            // Si está pendiente de cálculo por GitHub, esperar un segundo
            if ($isMergeable === null) {
                sleep(1);
                $detail = self::api('GET', "repos/{$repo}/pulls/{$num}");
                $isMergeable = $detail['data']['mergeable'] ?? null;
                $mergeableState = $detail['data']['mergeable_state'] ?? 'unknown';
            }

            if ($isMergeable === false || $mergeableState === 'dirty') {
                $summary['dirty_or_conflicted'][] = [
                    'number' => $num,
                    'title' => $title,
                    'head' => $head,
                    'state' => $mergeableState,
                ];
                continue;
            }

            // Intentar merge
            $merge = self::mergePullRequest($num, $title, 'merge', $repo);
            if ($merge['ok']) {
                $summary['merged'][] = [
                    'number' => $num,
                    'title' => $title,
                    'sha' => $merge['data']['sha'] ?? '',
                ];
            } else {
                $summary['failed'][] = [
                    'number' => $num,
                    'title' => $title,
                    'error' => $merge['data']['message'] ?? 'Error desconocido',
                ];
            }
        }

        $summary['ok'] = true;
        return $summary;
    }
}

