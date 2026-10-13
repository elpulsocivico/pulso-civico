<?php
declare(strict_types=1);

require_once __DIR__ . '/core/Linter.php';
require_once __DIR__ . '/core/GitOps.php';
require_once __DIR__ . '/core/Inspector.php';
require_once __DIR__ . '/core/ScraperRouter.php';

use PulsoCivico\Tools\Linter;
use PulsoCivico\Tools\GitOps;
use PulsoCivico\Tools\Inspector;
use PulsoCivico\Tools\ScraperRouter;

$rootDir = dirname(__DIR__);
$action = $argv[1] ?? 'help';
$target = $argv[2] ?? '';
$extra  = $argv[3] ?? '';

$response = [
    'action' => $action,
    'timestamp' => date('Y-m-d H:i:s'),
];

switch ($action) {
    case 'lint':
        // target puede ser una lista de archivos separados por coma o un solo archivo
        if ($target === '') {
            $response['error'] = 'Debe especificar al menos un archivo para lint.';
            break;
        }
        $files = explode(',', $target);
        // Limpiar rutas relativas o absolutas
        $resolvedFiles = array_map(function ($f) use ($rootDir) {
            $f = trim($f);
            if (!preg_match('/^[a-zA-Z]:\\\\|^\//', $f)) {
                return $rootDir . '/' . ltrim($f, '/\\');
            }
            return $f;
        }, $files);

        $result = Linter::checkFiles($resolvedFiles);
        $response = array_merge($response, $result);
        break;

    case 'lint:dir':
        $dir = $target !== '' ? $target : $rootDir . '/src';
        if (!preg_match('/^[a-zA-Z]:\\\\|^\//', $dir)) {
            $dir = $rootDir . '/' . ltrim($dir, '/\\');
        }
        $result = Linter::checkDirectory($dir);
        $response = array_merge($response, $result);
        break;

    case 'git:status':
        $result = GitOps::status();
        $response = array_merge($response, $result);
        break;

    case 'git:diff':
        $result = GitOps::diffSummary();
        $response = array_merge($response, $result);
        break;

    case 'git:cmd':
        $fullCmd = $target . ($extra !== '' ? " {$extra}" : '');
        $result = GitOps::execGit($fullCmd);
        $response = array_merge($response, $result);
        break;

    case 'gh:cmd':
        $fullCmd = $target . ($extra !== '' ? " {$extra}" : '');
        $result = GitOps::execGh($fullCmd);
        $response = array_merge($response, $result);
        break;

    case 'git:commit':
        $msg = $target !== '' ? $target : 'data: incorporar dataset del Congreso de Baja California (resolves #12)';
        $result = GitOps::execGit('commit -m ' . escapeshellarg($msg));
        $response = array_merge($response, $result);
        break;

    case 'git:push':
        $branch = $target !== '' ? $target : 'data/congreso-baja-california';
        $result = GitOps::execGit("push -u origin {$branch}");
        $response = array_merge($response, $result);
        break;

    case 'git:preflight':
        $result = GitOps::preflightCheck($rootDir);
        $response = array_merge($response, $result);
        break;

    case 'push:clean':
    case 'ship':
        $pre = GitOps::preflightCheck($rootDir);
        if (!$pre['ready_for_main']) {
            $response['ok'] = false;
            $response['error'] = 'Preflight falló. Errores de sintaxis o no hay archivos limpios para commitear.';
            $response['preflight'] = $pre;
            break;
        }

        $commitMsg = $target !== '' ? $target : 'feat: actualización validada';
        // 1. Agregar quirúrgicamente solo los archivos aprobados
        $added = [];
        foreach ($pre['clean_files_to_commit'] as $f) {
            GitOps::execGit('add ' . escapeshellarg($f));
            $added[] = $f;
        }

        // 2. Commit
        $cRes = GitOps::execGit('commit -m ' . escapeshellarg($commitMsg));
        $response['commit_result'] = $cRes;
        $response['files_staged'] = $added;
        $response['files_ignored'] = $pre['test_or_temp_files_detected'];
        $response['ok'] = ($cRes['code'] === 0);
        break;

    case 'telegram:check':
    case 'telegram:updates':
    case 'telegram:send':
    case 'telegram:post':
    case 'telegram:poll':
        $script = ($action === 'telegram:poll') ? $rootDir . '/tools/private/telegram_poll.php' : $rootDir . '/tools/private/telegram_bot.php';
        if (file_exists($script)) {
            $phpExec = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
            $cmd = escapeshellarg($phpExec) . " " . escapeshellarg($script) . " " . escapeshellarg(str_replace('telegram:', '', $action)) . " " . escapeshellarg($target) . " " . escapeshellarg($extra);
            $output = [];
            exec($cmd, $output);
            $response['bot_output'] = json_decode(implode("\n", $output), true) ?: implode("\n", $output);
        } else {
            $response['error'] = 'Herramienta privada de bot no instalada en local.';
        }
        break;

    case 'project:report':
    case 'project:status':
    case 'project:post':
    case 'project:start':
    case 'project:answer':
        $assistant = $rootDir . '/tools/private/project_assistant.php';
        if (file_exists($assistant)) {
            $phpExec = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
            $modeMap = [
                'project:post' => 'post',
                'project:start' => 'start',
                'project:answer' => 'answer',
            ];
            $mode = $modeMap[$action] ?? 'report';
            $arg1 = ($action === 'project:answer') ? $target : $target;
            $arg2 = ($action === 'project:answer') ? $extra : $extra;
            if ($action === 'project:answer' && is_numeric(trim($target, "'\""))) {
                // Si el primer arg es numérico (chat_id), invertirlos para compatibilidad
                $arg1 = $extra;
                $arg2 = $target;
            }
            $cmd = escapeshellarg($phpExec) . " " . escapeshellarg($assistant) . " " . escapeshellarg($mode) . " " . escapeshellarg($arg1) . " " . escapeshellarg($arg2);
            $output = [];
            exec($cmd, $output);
            $rawOut = implode("\n", $output);
            $response['report'] = json_decode($rawOut, true) ?: $rawOut;
        } else {
            $response['error'] = 'Script de asistente no encontrado en tools/private/';
        }
        break;

    case 'audit:pr':
    case 'audit:ref':
        $ref = $target !== '' ? $target : 'origin/data/leg-65';
        $base = $extra !== '' ? $extra : 'origin/main';
        $result = Inspector::auditGitRef($ref, $base);
        $response = array_merge($response, $result);
        break;

    case 'audit:summary':
        $prs = [
            'PR #22' => ['branch' => 'origin/data/congreso-bajacalifornia-historico', 'name' => 'Baja California XVI-XXV'],
            'PR #21' => ['branch' => 'origin/data/congreso-sinaloa', 'name' => 'Sinaloa LVIII-LXV'],
            'PR #20' => ['branch' => 'origin/data/congreso-sonora', 'name' => 'Sonora LV-LXIV'],
            'PR #19' => ['branch' => 'origin/data/congreso-durango', 'name' => 'Durango LXI-LXX'],
            'PR #18' => ['branch' => 'origin/data/congreso-chihuahua', 'name' => 'Chihuahua LXII-LXVIII'],
            'PR #17' => ['branch' => 'origin/data/congreso-coahuila', 'name' => 'Coahuila LVII-LXIII'],
            'PR #16' => ['branch' => 'origin/data/congreso-nuevoleon', 'name' => 'Nuevo León LXXI-LXXVII'],
        ];
        $summary = [];
        foreach ($prs as $prId => $info) {
            $branch = $info['branch'];
            $files = [];
            exec("git --no-pager diff --name-only origin/main...{$branch} 2>&1", $files);
            
            $jsonFiles = [];
            $otherFiles = [];
            $dangerHits = [];
            $legislatorCount = 0;
            $domains = [];
            
            foreach ($files as $f) {
                $f = trim($f);
                if ($f === '') continue;
                if (!str_starts_with($f, 'storage/data/json/')) {
                    $otherFiles[] = $f;
                } else {
                    $jsonFiles[] = $f;
                }
                
                // Inspeccionar contenido con git show
                $lines = [];
                exec("git --no-pager show {$branch}:" . escapeshellarg($f) . " 2>&1", $lines);
                $content = implode("\n", $lines);
                
                // Detección de backdoors y código malicioso
                if (str_ends_with($f, '.json')) {
                    if (preg_match_all('/\b(eval|assert|passthru|shell_exec|system|exec|base64_decode|gzinflate|unserialize)\s*\(|<\?php|<\/script>|<script|javascript:/i', $content, $m)) {
                        $dangerHits[$f] = array_unique($m[0]);
                    }
                } else {
                    if (preg_match_all('/\b(eval|assert|passthru|base64_decode|gzinflate|unserialize)\s*\(|<\/script>|<script|\b(nc\s+-e|\/bin\/sh|\/bin\/bash)\b/i', $content, $m)) {
                        $dangerHits[$f] = array_unique($m[0]);
                    }
                }
                
                // Si es json, contar legisladores y dominios
                if (str_ends_with($f, '.json')) {
                    $data = json_decode($content, true);
                    if ($data !== null) {
                        $legs = $data['legisladores'] ?? $data['diputados'] ?? [];
                        $legislatorCount += count($legs);
                    }
                }
                
                if (preg_match_all('/https?:\/\/([a-zA-Z0-9\-\._]+)/i', $content, $dm)) {
                    foreach ($dm[1] as $d) {
                        $d = strtolower($d);
                        $domains[$d] = ($domains[$d] ?? 0) + 1;
                    }
                }
            }
            
            $summary[$prId] = [
                'name' => $info['name'],
                'branch' => $branch,
                'total_files' => count($files),
                'json_files_count' => count($jsonFiles),
                'non_json_or_outside_storage' => $otherFiles,
                'total_legisladores' => $legislatorCount,
                'security_status' => empty($dangerHits) ? 'CLEAN_VERIFIED' : 'SECURITY_ALERT',
                'malicious_matches' => $dangerHits,
                'sources_top' => array_slice($domains, 0, 5, true),
            ];
        }
        $response['prs_summary'] = $summary;
        break;

    case 'audit:prs':
    case 'audit:all':
        $prs = [
            'PR #22 (Congreso Baja California XVI-XXV)' => 'origin/data/congreso-bajacalifornia-historico',
            'PR #21 (Congreso Sinaloa LVIII-LXV)'       => 'origin/data/congreso-sinaloa',
            'PR #20 (Congreso Sonora LV-LXIV)'          => 'origin/data/congreso-sonora',
            'PR #19 (Congreso Durango LXI-LXX)'         => 'origin/data/congreso-durango',
            'PR #18 (Congreso Chihuahua LXII-LXVIII)'   => 'origin/data/congreso-chihuahua',
            'PR #17 (Congreso Coahuila LVII-LXIII)'     => 'origin/data/congreso-coahuila',
            'PR #16 (Congreso Nuevo León LXXI-LXXVII)'  => 'origin/data/congreso-nuevoleon',
        ];
        $allAudits = [];
        foreach ($prs as $prLabel => $branch) {
            $allAudits[$prLabel] = Inspector::auditGitRef($branch, 'origin/main');
        }
        $response['audits'] = $allAudits;
        break;

    case 'analyze:tamaulipas':
        $result = Inspector::analyzeTamaulipasDatasets($rootDir);
        $response = array_merge($response, $result);
        break;

    case 'router:inspect':
        $prs = [
            'BC' => 'origin/data/congreso-bajacalifornia-historico',
            'Sinaloa' => 'origin/data/congreso-sinaloa',
            'Sonora' => 'origin/data/congreso-sonora',
            'Durango' => 'origin/data/congreso-durango',
            'Chihuahua' => 'origin/data/congreso-chihuahua',
            'Coahuila' => 'origin/data/congreso-coahuila',
            'NL' => 'origin/data/congreso-nuevoleon',
        ];
        $diffs = [];
        foreach ($prs as $state => $branch) {
            $lines = [];
            exec("git --no-pager diff origin/main...{$branch} -- tools/core/ScraperRouter.php 2>&1", $lines);
            $diffs[$state] = implode("\n", $lines);
        }
        $response['diffs'] = $diffs;
        break;

    case 'integrate:prs':
        $prs = [
            'PR #22 (Congreso Baja California XVI-XXV)' => 'origin/data/congreso-bajacalifornia-historico',
            'PR #21 (Congreso Sinaloa LVIII-LXV)'       => 'origin/data/congreso-sinaloa',
            'PR #20 (Congreso Sonora LV-LXIV)'          => 'origin/data/congreso-sonora',
            'PR #19 (Congreso Durango LXI-LXX)'         => 'origin/data/congreso-durango',
            'PR #18 (Congreso Chihuahua LXII-LXVIII)'   => 'origin/data/congreso-chihuahua',
            'PR #17 (Congreso Coahuila LVII-LXIII)'     => 'origin/data/congreso-coahuila',
            'PR #16 (Congreso Nuevo León LXXI-LXXVII)'  => 'origin/data/congreso-nuevoleon',
        ];
        $integrated = [];
        $allExtractedFiles = [];
        foreach ($prs as $prLabel => $branch) {
            $files = [];
            exec("git --no-pager diff --name-only origin/main...{$branch} 2>&1", $files);
            $restored = [];
            foreach ($files as $f) {
                $f = trim($f);
                if ($f === '' || $f === '.gitignore' || $f === 'tools/core/ScraperRouter.php' || $f === 'tools/gateway.php' || $f === 'tools/core/Inspector.php') {
                    continue;
                }
                if (str_starts_with($f, 'storage/data/json/congreso_') || 
                    str_starts_with($f, 'src/Services/') || 
                    str_ends_with($f, '.py') || 
                    $f === 'docs/ESTADOS_COBERTURA.md') {
                    
                    $outCo = [];
                    $code = 0;
                    exec("git --no-pager checkout {$branch} -- " . escapeshellarg($f) . " 2>&1", $outCo, $code);
                    if ($code === 0) {
                        $restored[] = $f;
                        $allExtractedFiles[] = $f;
                    }
                }
            }
            $integrated[$prLabel] = [
                'branch' => $branch,
                'files_restored_count' => count($restored),
                'files' => $restored,
            ];
        }
        $response['integrated'] = $integrated;
        $response['total_files_extracted'] = count($allExtractedFiles);
        $response['all_files'] = array_values(array_unique($allExtractedFiles));
        break;

    case 'ship:prs':
        $filesToStage = [
            'docs/ESTADOS_COBERTURA.md',
            'tools/core/ScraperRouter.php',
            'tools/gateway.php',
            'tools/core/coahuila_parser.py',
            'tools/core/nl_pdf_parser.py',
            'src/Services/Catalog/BajaCaliforniaCatalogXXV.php',
            'src/Services/CongresoBajaCaliforniaScraperService.php',
            'src/Services/SinaloaScraperService.php',
            'src/Services/SinaloaTableParser.php',
            'src/Services/SonoraScraperService.php',
            'src/Services/SonoraTableParser.php',
            'src/Services/DurangoScraperService.php',
            'src/Services/DurangoTableParser.php',
            'src/Services/ChihuahuaScraperService.php',
            'src/Services/CoahuilaScraperService.php',
            'src/Services/NuevoLeonScraperService.php',
        ];

        // Agregar todos los json de congresos estatales
        $jsonDir = $rootDir . '/storage/data/json';
        $stateJsonFiles = glob($jsonDir . '/congreso_*.json') ?: [];
        foreach ($stateJsonFiles as $sj) {
            $filesToStage[] = 'storage/data/json/' . basename($sj);
        }

        $staged = [];
        foreach (array_unique($filesToStage) as $f) {
            $fullPath = $rootDir . '/' . $f;
            if (file_exists($fullPath)) {
                GitOps::execGit('add ' . escapeshellarg($f));
                $staged[] = $f;
            }
        }

        $commitMsg = $target !== '' ? $target : 'feat(data): incorporar datasets historicos y scrapers de 7 congresos estatales (resolves #16, #17, #18, #19, #20, #21, #22)';
        $cRes = GitOps::execGit('commit -m ' . escapeshellarg($commitMsg));
        $response['commit_result'] = $cRes;
        $response['files_staged_count'] = count($staged);
        $response['files_staged'] = $staged;

        if ($cRes['code'] === 0) {
            $pRes = GitOps::execGit('push origin main');
            $response['push_result'] = $pRes;
            $response['ok'] = ($pRes['code'] === 0);
        } else {
            $response['ok'] = false;
        }
        break;

    case 'health':
    case 'check':

        $result = Inspector::checkHealth($rootDir);
        $response = array_merge($response, $result);
        break;

    case 'search':
    case 'find':
        $result = Inspector::searchCode($rootDir, $target, $extra);
        $response = array_merge($response, $result);
        break;

    case 'scrape':
    case 'ingest':
        // Target puede ser 'sil', 'nacional' o un ID numérico de entidad
        // Target puede ser 'sil', 'nacional', un ID de entidad o cadena key=val
        $source = 'sil';
        $params = [];
        $rawInputs = [$target, $extra];
        foreach ($rawInputs as $inp) {
            $inp = trim($inp);
            if ($inp === '') continue;
            if ($inp === 'nacional' || $inp === 'all') {
                $params['nacional'] = true;
            } elseif (is_numeric($inp)) {
                $params['entidad'] = (int)$inp;
            } elseif (str_contains($inp, '=') || str_contains($inp, '&')) {
                // Separar por coma o por &
                $pairs = preg_split('/[,&]/', $inp);
                foreach ($pairs as $pair) {
                    $pParts = explode('=', trim($pair), 2);
                    if (count($pParts) === 2) {
                        $k = trim($pParts[0]);
                        $v = trim($pParts[1]);
                        $params[$k] = is_numeric($v) ? (int)$v : $v;
                    }
                }
            } elseif (in_array(strtolower($inp), ['sil', 'votaciones', 'asistencias', 'bc', 'bajacalifornia', 'bcs', 'bajacaliforniasur', 'yuc', 'yucatan', 'qroo', 'quintanaroo'])) {
                $source = strtolower($inp);
            }
        }
        $response['parsed_params'] = $params;
        $result = ScraperRouter::run($rootDir, $source, $params);
        $response = array_merge($response, $result);
        break;

    case 'probe:sil':
        $leg = $target !== '' ? (int)$target : 61;
        $cam = $extra !== '' ? (int)$extra : 1;
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $config = require $rootDir . '/config/config.php';
        $logger = new \PulsoCivico\Core\Logger($config['paths']['logs'], 'PROBE');
        $http = new \PulsoCivico\Core\HttpClient($config['sil'], $logger);
        
        // Petición al formulario de reporte para ver opciones de entidad
        $reportUrl = $config['sil']['report_url'];
        // Probar entidades para ver en cuál están los senadores de la leg especificada
        $probes = [];
        $found = [];
        for ($ent = 1; $ent <= 35; $ent++) {
            $res = $http->request('POST', $reportUrl, [
                'CAMARA' => (string)$cam,
                'LEGISLATURA' => (string)$leg,
                'PARTIDO' => '0',
                'PRIN_ELECCION' => '0',
                'ENTIDAD' => (string)$ent,
            ]);
            $body = $res['body'] ?? '';
            $hasData = stripos($body, 'tddatosazul') !== false;
            $countRows = substr_count($body, 'tddatosazul');
            if ($hasData) {
                $found[$ent] = $countRows;
            }
            usleep(200000); // 200ms
        }
        $response['legislatura'] = $leg;
        $response['camara'] = $cam;
        $response['entidades_con_datos'] = $found;
        break;

    case 'list:legislaturas':
    case 'legs':
        $jsonDir = $rootDir . '/storage/data/json';
        $files = glob($jsonDir . '/*.json') ?: [];
        $legsFound = [];
        $stateFiles = [];
        $nationalFiles = [];
        $otherFiles = [];

        foreach ($files as $fPath) {
            $base = basename($fPath);
            if (preg_match('/_leg_(\d+)\.json$/', $base, $m)) {
                $legNum = (int)$m[1];
                $legsFound[$legNum] = ($legsFound[$legNum] ?? 0) + 1;
                if (str_starts_with($base, 'congreso_union_leg_')) {
                    $nationalFiles[$legNum] = $base;
                }
            } elseif (preg_match('/congreso_tamaulipas/i', $base)) {
                $stateFiles[] = $base;
            } else {
                $otherFiles[] = $base;
            }
        }
        ksort($legsFound);

        $response['legislaturas_en_json'] = $legsFound;
        $byLegFiles = [];
        foreach ($files as $fPath) {
            $base = basename($fPath);
            if (preg_match('/_leg_(\d+)\.json$/', $base, $m)) {
                $l = (int)$m[1];
                if (!isset($byLegFiles[$l])) $byLegFiles[$l] = [];
                $byLegFiles[$l][] = $base;
            }
        }
        $response['archivos_por_legislatura'] = [
            '57' => $byLegFiles[57] ?? [],
            '58' => $byLegFiles[58] ?? [],
            '59' => $byLegFiles[59] ?? [],
            '60' => $byLegFiles[60] ?? [],
            '61' => count($byLegFiles[61] ?? []) . ' archivos',
            '62' => count($byLegFiles[62] ?? []) . ' archivos',
            '63' => count($byLegFiles[63] ?? []) . ' archivos',
            '64' => count($byLegFiles[64] ?? []) . ' archivos',
            '65' => count($byLegFiles[65] ?? []) . ' archivos',
            '66' => count($byLegFiles[66] ?? []) . ' archivos',
        ];
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $disponiblesWeb = $ds->getLegislaturasDisponibles();
        $webConteo = [];
        foreach ($disponiblesWeb as $lNum) {
            $dataLeg = $ds->getLegislaturaData($lNum);
            $webConteo[$lNum] = [
                'romano' => $dataLeg['meta']['romano'] ?? (string)$lNum,
                'periodo' => $dataLeg['meta']['periodo'] ?? '',
                'total_legisladores' => $dataLeg['total_legisladores'] ?? 0,
                'total_diputados' => $dataLeg['total_diputados'] ?? 0,
                'total_senadores' => $dataLeg['total_senadores'] ?? 0,
                'bancadas_diputados_count' => count($dataLeg['bancadas_diputados'] ?? []),
            ];
        }

        $response['web_ready_legislaturas'] = $webConteo;
        $response['nacionales_consolidados'] = $nationalFiles;
        $response['total_json_files'] = count($files);
        break;

    case 'inspect:parser':
        $leg = $target !== '' ? (int)$target : 61;
        $cam = $extra !== '' ? (int)$extra : 1;
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $config = require $rootDir . '/config/config.php';
        $logger = new \PulsoCivico\Core\Logger($config['paths']['logs'], 'INSPECT-PARSER');
        $http = new \PulsoCivico\Core\HttpClient($config['sil'], $logger);
        $res = $http->request('POST', $config['sil']['report_url'], [
            'CAMARA' => (string)$cam,
            'LEGISLATURA' => (string)$leg,
            'PARTIDO' => '0',
            'PRIN_ELECCION' => '0',
            'ENTIDAD' => '9',
        ]);
        $html = $res['body'] ?? '';
        
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($doc);
        $rows = $xpath->query("//tr[td[contains(@class, 'tddatosazul')]]");
        $sampleRows = [];
        foreach ($rows as $i => $row) {
            if ($i >= 5) break;
            $tds = $xpath->query("./td", $row);
            $cols = [];
            foreach ($tds as $td) {
                $cols[] = trim($td->textContent);
            }
            $link = $xpath->query(".//a[contains(@onclick, 'Referencia=')]", $row)->item(0);
            $onclick = $link ? $link->getAttribute('onclick') : 'no-link';
            $sampleRows[] = [
                'cols_count' => $tds->length,
                'onclick' => $onclick,
                'cols' => $cols,
            ];
        }
        $response['total_rows_found'] = $rows->length;
        $response['sample_rows'] = $sampleRows;
        break;

    case 'extract:df:fast':
        $leg = $target !== '' ? (int)$target : 61;
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $config = require $rootDir . '/config/config.php';
        $logger = new \PulsoCivico\Core\Logger($config['paths']['logs'], 'FAST-EXTRACT');
        $http = new \PulsoCivico\Core\HttpClient($config['sil'], $logger);
        
        $camaras = [
            1 => 'Diputados',
            2 => 'Senadores',
        ];
        $allRecords = ['diputados' => [], 'senadores' => []];
        foreach ($camaras as $camId => $camName) {
            $res = $http->request('POST', $config['sil']['report_url'], [
                'CAMARA' => (string)$camId,
                'LEGISLATURA' => (string)$leg,
                'PARTIDO' => '0',
                'PRIN_ELECCION' => '0',
                'ENTIDAD' => '9',
            ]);
            $html = $res['body'] ?? '';
            $doc = new \DOMDocument();
            @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($doc);
            $rows = $xpath->query("//tr[td[contains(@class, 'tddatosazul')]]");
            
            foreach ($rows as $row) {
                $tds = $xpath->query("./td", $row);
                if ($tds->length < 8) continue;
                $link = $xpath->query(".//a[contains(@onclick, 'Referencia=')]", $row)->item(0);
                if (!$link) continue;
                $onclick = $link->getAttribute('onclick');
                if (!preg_match('/Referencia=(\d+)/i', $onclick, $refMatch)) continue;
                $refId = $refMatch[1];
                
                $dto = new \PulsoCivico\DTO\LegisladorDTO(
                    referenciaSil: $refId,
                    nombreCompleto: trim($tds->item(2)->textContent),
                    camara: $camName,
                    legislatura: $leg,
                    partido: trim($tds->item(3)->textContent),
                    principioEleccion: trim($tds->item(4)->textContent),
                    entidad: 'Distrito Federal',
                    distrito: null,
                    tipoCargo: trim($tds->item(7)->textContent),
                    suplenteNombre: null,
                    fechaTomaProtesta: trim($tds->item(6)->textContent),
                    fechaNacimiento: null,
                    correoInstitucional: null,
                    telefono: null,
                    ubicacionOficina: $camName === 'Senadores' ? 'Senado de la República' : 'Palacio Legislativo de San Lázaro',
                    ultimoGradoEstudios: null,
                    preparacionAcademica: null,
                    experienciaLegislativaResumen: null,
                    fotoOficialUrl: "http://sil.gobernacion.gob.mx/Archivos/Fotos/{$refId}.jpg",
                    fotoLocalPath: null,
                    comisiones: [],
                    trayectoriaPolitica: [],
                    trayectoriaLegislativa: [],
                    trayectoriaAdministrativa: [],
                    escolaridadDetalle: [],
                    licencias: [],
                    iniciativas: [],
                    puntosAcuerdo: [],
                    redesSociales: [],
                    metadata: [
                        'fuente' => 'SIL SEGOB',
                        'fuente_url' => "http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php",
                        'titular_referencia_sil' => null,
                        'titular_nombre' => null,
                        'suplente_referencia_sil' => null,
                        'fecha_extraccion' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                    ]
                );
                $key = strtolower($camName);
                $allRecords[$key][] = $dto->toArray();
            }
            sleep(1);
        }
        
        $outFile = $rootDir . "/storage/data/json/legisladores_entidad_9_leg_{$leg}.json";
        $dataToSave = [
            'metadata' => [
                'entidad_id' => 9,
                'entidad_nombre' => 'Ciudad de México',
                'legislatura' => $leg,
                'generado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'total_diputados' => count($allRecords['diputados']),
                'total_senadores' => count($allRecords['senadores']),
                'total_general' => count($allRecords['diputados']) + count($allRecords['senadores']),
            ],
            'diputados' => $allRecords['diputados'],
            'senadores' => $allRecords['senadores'],
        ];
        file_put_contents($outFile, json_encode($dataToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Re-consolidar archivo nacional de la legislatura si existe
        $consolidadoFile = $rootDir . "/storage/data/json/congreso_union_leg_{$leg}.json";
        $totalNacionalDips = 0;
        $totalNacionalSens = 0;
        if (file_exists($consolidadoFile)) {
            $todosDips = [];
            $todosSens = [];
            for ($e = 1; $e <= 32; $e++) {
                $eFile = $rootDir . "/storage/data/json/legisladores_entidad_{$e}_leg_{$leg}.json";
                if (file_exists($eFile)) {
                    $eData = json_decode((string)file_get_contents($eFile), true);
                    if (is_array($eData)) {
                        foreach ($eData['diputados'] ?? [] as $d) $todosDips[] = $d;
                        foreach ($eData['senadores'] ?? [] as $s) $todosSens[] = $s;
                    }
                }
            }
            $dataConsolidada = [
                'metadata' => [
                    'titulo' => "Congreso de la Unión de México - Legislatura {$leg}",
                    'alcance' => 'Nacional (32 Entidades Federativas)',
                    'generado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                    'total_diputados' => count($todosDips),
                    'total_senadores' => count($todosSens),
                    'total_general' => count($todosDips) + count($todosSens),
                ],
                'diputados' => $todosDips,
                'senadores' => $todosSens,
            ];
            file_put_contents($consolidadoFile, json_encode($dataConsolidada, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $totalNacionalDips = count($todosDips);
            $totalNacionalSens = count($todosSens);
        }
        
        $response['out_file'] = $outFile;
        $response['total_diputados'] = count($allRecords['diputados']);
        $response['total_senadores'] = count($allRecords['senadores']);
        $response['total'] = $dataToSave['metadata']['total_general'];
        $response['consolidado_actualizado'] = file_exists($consolidadoFile);
        $response['consolidado_total_diputados'] = $totalNacionalDips;
        $response['consolidado_total_senadores'] = $totalNacionalSens;
        break;

    case 'stats:educacion':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $targetEstado = $target !== '' ? $target : null;
        $response['target'] = $targetEstado;
        $response['estadisticas'] = $ds->getEstadisticasEscolaridad(null, $targetEstado);
        break;

    case 'stats:instituciones':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $stats = $ds->getEstadisticasEscolaridad();
        $response['resumen_tipo_institucion'] = $stats['resumen_tipo_institucion'] ?? [];
        $response['por_camara_institucion'] = $stats['por_camara_institucion'] ?? [];
        $response['por_partido_institucion'] = $stats['por_partido_institucion'] ?? [];
        $response['top_instituciones_publicas'] = $stats['top_instituciones_publicas'] ?? [];
        $response['top_instituciones_privadas'] = $stats['top_instituciones_privadas'] ?? [];
        $response['top_instituciones'] = $stats['top_instituciones'] ?? [];
        break;




    case 'sync:paths':
        // Fuente: argumento (run.ps1 sync:paths "<ruta>") o variable de entorno PULSO_PATHS_SOURCE
        $sourcePaths = $target !== '' ? $target : (string) (getenv('PULSO_PATHS_SOURCE') ?: '');
        $destPaths = $rootDir . '/storage/data/mexico_paths.json';
        if (file_exists($sourcePaths)) {
            @mkdir(dirname($destPaths), 0777, true);
            copy($sourcePaths, $destPaths);
            $response['synced'] = true;
            $response['dest'] = $destPaths;
        } else {
            $response['synced'] = false;
            $response['error'] = 'Source paths not found';
        }
        break;

    case 'politico':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $targetSlug = $target !== '' ? $target : 'ayala-robles-armando';
        $pol = $ds->getPoliticoBySlug($targetSlug);
        $response['politico'] = [
            'nombre' => $pol['nombre_completo'] ?? null,
            'slug' => $pol['slug'] ?? null,
            'suplente_nombre' => $pol['suplente_nombre'] ?? null,
            'tipo_cargo' => $pol['tipo_cargo'] ?? null,
            'entidad' => $pol['entidad'] ?? null,
            'distrito' => $pol['distrito'] ?? null,
            'principio' => $pol['principio_eleccion'] ?? null,
            'titular_referencia' => $pol['metadata']['titular_referencia_sil'] ?? null,
            'titular_nombre' => $pol['metadata']['titular_nombre'] ?? null,
            'titular_encontrado' => ($ds->getPoliticoByReferencia($pol['metadata']['titular_referencia_sil'] ?? '') ?? $ds->getPoliticoByNombre($pol['metadata']['titular_nombre'] ?? ''))['slug'] ?? null,
            'suplente_encontrado' => ($ds->getPoliticoByReferencia($pol['metadata']['suplente_referencia_sil'] ?? '') ?? $ds->getPoliticoByNombre($pol['suplente_nombre'] ?? ''))['slug'] ?? null,
            'asistencia_kpi' => $pol['asistencia_kpi'] ?? null,
            'disciplina_partidaria_pct' => $pol['disciplina_partidaria_pct'] ?? null,
            'votaciones_destacadas' => $pol['votaciones_destacadas'] ?? [],
            'metadata' => $pol['metadata'] ?? [],
            'linea_tiempo' => $pol['linea_tiempo'] ?? [],
        ];
        $ref = $pol['referencia_sil'] ?? '9228516';
        $rawPath = $rootDir . "/storage/raw/html/profile_{$ref}.html";
        if (file_exists($rawPath)) {
            $html = (string)file_get_contents($rawPath);
            $htmlUtf8 = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1, UTF-8');
            preg_match_all('/<tr[^>]*>.*?<\/tr>/is', $htmlUtf8, $rows);
            $foundRows = [];
            foreach ($rows[0] as $r) {
                if (stripos($r, 'Suplente de:') !== false) {
                    $foundRows[] = $r;
                }
            }
        }
        break;

    case 'check:trunca':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $ed = $ds->getEducationService();
        $casos = [];
        foreach ($ds->getPoliticos() as $p) {
            $analisis = $ed->clasificarPolitico($p);
            // Casos donde tiene_trunca es true pero el grado_maximo es licenciatura o posgrado
            // O donde dice Licenciatura pero también trunca
            if ($analisis['tiene_trunca']) {
                $casos[] = [
                    'nombre' => $p['nombre_completo'],
                    'slug' => $p['slug'],
                    'grado_maximo' => $analisis['grado_maximo'],
                    'categoria_grupo' => $ed->determinarCategoriaAgrupada($analisis['grado_maximo']),
                    'titulo_destacado' => $analisis['titulo_destacado'],
                    'estudios_clasificados' => $analisis['estudios_clasificados'],
                ];
            }
        }
        $response['total_casos_trunca'] = count($casos);
        $response['casos'] = array_slice($casos, 0, 15);
        break;

    case 'votaciones':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $response['votaciones'] = $ds->getVotaciones(20);
        break;

    case 'sesiones':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $response['sesiones'] = $ds->getSesiones(50);
        break;

    case 'check:suplentes':
        $htmlDir = $rootDir . '/storage/raw/html';
        $files = glob($htmlDir . '/profile_*.html');
        $suplentesDe = [];
        $suplentes = [];
        foreach ($files as $file) {
            $ref = basename($file, '.html');
            $ref = str_replace('profile_', '', $ref);
            $content = (string)file_get_contents($file);
            $contentUtf8 = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, UTF-8');
            if (preg_match('/<b>Suplente de:<\/b>\s*<\/td>\s*<td>(.*?)<\/td>/is', $contentUtf8, $m)) {
                $rawTd = $m[1];
                $linkRef = null;
                if (preg_match('/Referencia=(\d+)/i', $rawTd, $refM)) {
                    $linkRef = $refM[1];
                }
                $cleanName = trim(strip_tags($rawTd));
                $cleanName = preg_replace('/^(Diputad[ao]|Senador[a]?)\s+/iu', '', $cleanName);
                $cleanName = trim(html_entity_decode($cleanName));
                $suplentesDe[$ref] = [
                    'titular_nombre' => $cleanName,
                    'titular_referencia' => $linkRef,
                ];
            }
            if (preg_match('/<b>Suplente:<\/b>\s*<\/td>\s*<td>(.*?)<\/td>/is', $contentUtf8, $m)) {
                $rawTd = $m[1];
                $linkRef = null;
                if (preg_match('/Referencia=(\d+)/i', $rawTd, $refM)) {
                    $linkRef = $refM[1];
                }
                $cleanName = trim(strip_tags($rawTd));
                $cleanName = preg_replace('/^(Diputad[ao]|Senador[a]?)\s+/iu', '', $cleanName);
                $cleanName = trim(html_entity_decode($cleanName));
                if ($linkRef !== null || $cleanName !== '') {
                    $suplentes[$ref] = [
                        'suplente_nombre' => $cleanName,
                        'suplente_referencia' => $linkRef,
                    ];
                }
            }
        }
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $ds = new \PulsoCivico\Services\DataService();
        $titularesEstado = [];
        foreach ($suplentesDe as $sRef => $tData) {
            $nom = $tData['titular_nombre'];
            $tRef = $tData['titular_referencia'];
            $foundByNom = $ds->getPoliticoByNombre($nom);
            $foundByRef = $tRef ? $ds->getPoliticoByReferencia($tRef) : null;
            $titularesEstado[$sRef] = [
                'suplente_ref' => $sRef,
                'titular_nombre' => $nom,
                'titular_referencia' => $tRef,
                'encontrado_en_db' => ($foundByRef !== null || $foundByNom !== null),
                'slug' => $foundByRef['slug'] ?? $foundByNom['slug'] ?? null,
            ];
        }
        $response['total_suplentes_de'] = count($suplentesDe);
        $response['analisis_titulares'] = $titularesEstado;
        break;

    case 'ingest:titular':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });

        $titularRef = $target !== '' ? $target : '9228515';
        $logger = new \PulsoCivico\Core\Logger($rootDir . '/storage/logs');
        $client = new \PulsoCivico\Core\HttpClient(['timeout' => 15], $logger);

        // 1. Descargar HTML de perfil
        $rawPath = $rootDir . "/storage/raw/html/profile_{$titularRef}.html";
        if (!file_exists($rawPath) || filesize($rawPath) < 1000) {
            $url = "http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia={$titularRef}";
            $res = $client->request('GET', $url);
            if ($res['status'] === 200 && strlen($res['body']) > 1000) {
                file_put_contents($rawPath, $res['body']);
            }
        }

        // 2. Descargar foto
        $photoDir = $rootDir . '/storage/raw/photos';
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);
        $photoDest = "$photoDir/{$titularRef}.jpg";
        $fotoUrl = "http://sil.gobernacion.gob.mx/Archivos/Fotos/{$titularRef}.jpg";
        if (!file_exists($photoDest)) {
            $client->downloadFile($fotoUrl, $photoDest);
        }

        // 3. Parsear perfil
        $cfg = require $rootDir . '/config/config.php';
        $silService = new \PulsoCivico\Services\SilIngestService($cfg, $client, $logger);

        // Usar reflection para llamar a fetchAndParseProfile
        $refMethod = new \ReflectionMethod($silService, 'fetchAndParseProfile');
        $refMethod->setAccessible(true);
        $perfilData = $refMethod->invoke($silService, $titularRef);

        $htmlRaw = (string)file_get_contents($rawPath);
        $htmlUtf8 = mb_convert_encoding($htmlRaw, 'UTF-8', 'ISO-8859-1, UTF-8');

        // Nombre
        $nombre = null;
        if (preg_match('/<b>(?:Diputad[ao]|Senador[a]?)\s+(.*?)<\/b>/iu', $htmlUtf8, $nm)) {
            $nombre = trim(strip_tags($nm[1]));
        }
        $nombre = html_entity_decode($nombre ?? "Legislador {$titularRef}", ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre));

        // Invertir a Apellidos Nombres si viene Nombres Apellidos
        // Si no tiene coma y son 3 o 4 palabras: "Betzabé Martínez Arango" -> "Martínez Arango Betzabé"
        $partes = preg_split('/\s+/', $nombre);
        if (count($partes) === 3) {
            $nombreOrdenado = $partes[1] . ' ' . $partes[2] . ' ' . $partes[0];
        } elseif (count($partes) === 4) {
            $nombreOrdenado = $partes[2] . ' ' . $partes[3] . ' ' . $partes[0] . ' ' . $partes[1];
        } else {
            $nombreOrdenado = $nombre;
        }

        // Partido
        $partido = 'Morena';
        if (preg_match('/Partido:<\/b>\s*<\/td>\s*<td[^>]*>(.*?)<\/td>/iu', $htmlUtf8, $pm)) {
            $partido = trim(strip_tags($pm[1]));
        }

        // Entidad
        $entidad = 'Durango';
        if (preg_match('/Entidad:<\/b>\s*<\/td>\s*<td[^>]*>(.*?)<\/td>/iu', $htmlUtf8, $em)) {
            $entidad = trim(strip_tags($em[1]));
        }

        // Principio
        $principio = 'Mayoría Relativa';
        if (preg_match('/Principio de elecci[oó]n:<\/b>\s*<\/td>\s*<td[^>]*>(.*?)<\/td>/iu', $htmlUtf8, $prm)) {
            $principio = trim(strip_tags($prm[1]));
        }

        $camara = (preg_match('/<b>(?:Senador|Senadora)\b/iu', $htmlUtf8) || stripos($htmlUtf8, 'Cámara de Senadores') !== false && !preg_match('/<b>(?:Diputad[ao])\b/iu', $htmlUtf8)) ? 'Senadores' : 'Diputados';

        $suplenteNombre = $perfilData['suplente'] ?? null;
        $suplenteRef = $perfilData['suplente_referencia_sil'] ?? null;

        $dto = new \PulsoCivico\DTO\LegisladorDTO(
            referenciaSil: $titularRef,
            nombreCompleto: $nombreOrdenado,
            camara: $camara,
            legislatura: 66,
            partido: $partido,
            principioEleccion: $principio,
            entidad: $entidad,
            distrito: $perfilData['distrito'] ?? null,
            tipoCargo: 'Titular (Con Licencia)',
            suplenteNombre: $suplenteNombre,
            fechaTomaProtesta: '29/08/2024',
            fechaNacimiento: $perfilData['nacimiento'] ?? null,
            correoInstitucional: $perfilData['correo'] ?? null,
            telefono: $perfilData['telefono'] ?? null,
            ubicacionOficina: $perfilData['ubicacion'] ?? 'Palacio Legislativo de San Lázaro',
            ultimoGradoEstudios: $perfilData['ultimo_grado_estudios'] ?? null,
            preparacionAcademica: $perfilData['preparacion_academica'] ?? null,
            experienciaLegislativaResumen: $perfilData['experiencia_legislativa_resumen'] ?? null,
            fotoOficialUrl: $fotoUrl,
            fotoLocalPath: file_exists($photoDest) ? $photoDest : null,
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
                'titular_referencia_sil' => null,
                'titular_nombre' => null,
                'suplente_referencia_sil' => $suplenteRef,
                'estatus_licencia' => 'Con Licencia',
                'fecha_extraccion' => date('c'),
            ]
        );

        // 4. Agregar a congreso_union_leg_66.json
        $congresoFile = $rootDir . '/storage/data/json/congreso_union_leg_66.json';
        $cData = json_decode((string)file_get_contents($congresoFile), true);
        $grupo = ($camara === 'Senadores') ? 'senadores' : 'diputados';

        // Evitar duplicados
        $yaExiste = false;
        foreach ($cData[$grupo] as $idx => $existente) {
            if (($existente['referencia_sil'] ?? '') === $titularRef) {
                $cData[$grupo][$idx] = $dto->toArray();
                $yaExiste = true;
                break;
            }
        }
        if (!$yaExiste) {
            $cData[$grupo][] = $dto->toArray();
            if ($grupo === 'diputados') {
                $cData['metadata']['total_diputados']++;
            } else {
                $cData['metadata']['total_senadores']++;
            }
            $cData['metadata']['total_general']++;
        }

        file_put_contents($congresoFile, json_encode($cData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response['ok'] = true;
        $response['legislador_ingestado'] = [
            'nombre' => $dto->nombreCompleto,
            'slug' => \PulsoCivico\Services\DataService::slugify($dto->nombreCompleto),
            'cargo' => $dto->tipoCargo,
            'suplente' => $dto->suplenteNombre,
            'redes' => count($dto->redesSociales),
            'foto' => file_exists($photoDest),
        ];
        break;

    case 'enrich:social':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $result = \PulsoCivico\Services\SilIngestService::enrichExistingJsonWithSocial($rootDir);
        $response = array_merge($response, $result);
        break;

    case 'enrich:suplentes':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $result = \PulsoCivico\Services\SilIngestService::enrichExistingJsonWithSuplentes($rootDir);
        $response = array_merge($response, $result);
        break;

    case 'status:avance':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $entidades = \PulsoCivico\Services\SilIngestService::ENTIDADES;
        $jsonDir = $rootDir . '/storage/data/json';
        $completados = [];
        $pendientes = [];
        $totalLegisladores = 0;
        foreach ($entidades as $id => $nombre) {
            $f = "$jsonDir/legisladores_entidad_{$id}_leg_66.json";
            if (file_exists($f) && filesize($f) > 500) {
                $d = json_decode((string)file_get_contents($f), true);
                $dips = count($d['diputados'] ?? []);
                $sens = count($d['senadores'] ?? []);
                $tot = $dips + $sens;
                $totalLegisladores += $tot;
                $completados[$id] = [
                    'nombre' => $nombre,
                    'diputados' => $dips,
                    'senadores' => $sens,
                    'total' => $tot,
                ];
            } else {
                $pendientes[$id] = $nombre;
            }
        }
        $response['resumen'] = [
            'completados_count' => count($completados),
            'pendientes_count' => count($pendientes),
            'total_estados' => 32,
            'porcentaje' => round((count($completados) / 32) * 100, 1) . '%',
            'total_legisladores' => $totalLegisladores,
        ];
        $response['completados'] = $completados;
        $response['pendientes'] = $pendientes;
        break;

    case 'scrape:pendientes':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $config = require $rootDir . '/config/config.php';
        $logger = new \PulsoCivico\Core\Logger($config['paths']['logs'], 'ROUTER-SCRAPER');
        $httpClient = new \PulsoCivico\Core\HttpClient($config['sil'], $logger);
        $service = new \PulsoCivico\Services\SilIngestService($config, $httpClient, $logger);

        $entidades = \PulsoCivico\Services\SilIngestService::ENTIDADES;
        $jsonDir = $rootDir . '/storage/data/json';
        $pendientes = [];
        foreach ($entidades as $id => $nombre) {
            $f = "$jsonDir/legisladores_entidad_{$id}_leg_66.json";
            if (!file_exists($f) || filesize($f) < 500) {
                $pendientes[$id] = $nombre;
            } else {
                $d = json_decode((string)file_get_contents($f), true);
                if (empty($d['diputados']) || empty($d['senadores'])) {
                    $pendientes[$id] = $nombre;
                }
            }
        }

        $procesados = [];
        $errores = [];
        foreach ($pendientes as $pId => $pNombre) {
            echo "--> Iniciando [{$pId}/32] {$pNombre}...\n";
            try {
                $res = $service->ingestByEntidad($pId, 66, true);
                $procesados[$pId] = [
                    'nombre' => $pNombre,
                    'diputados' => count($res['diputados'] ?? []),
                    'senadores' => count($res['senadores'] ?? []),
                    'total' => $res['total'] ?? 0,
                ];
                echo "--> OK [{$pId}/32] {$pNombre}: " . ($res['total'] ?? 0) . " legisladores.\n";
                // Pausa sigilosa de 3 segundos entre estados
                sleep(3);
            } catch (\Throwable $e) {
                $errores[$pId] = $e->getMessage();
                echo "--> ERROR en [{$pId}/32] {$pNombre}: " . $e->getMessage() . "\n";
                sleep(5);
            }
        }

        $response['procesados'] = $procesados;
        $response['errores'] = $errores;
        $response['total_procesados'] = count($procesados);
        break;

    case 'consolidate:nacional':
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) require_once $file;
        });
        $entidades = \PulsoCivico\Services\SilIngestService::ENTIDADES;
        $jsonDir = $rootDir . '/storage/data/json';
        $todosDiputados = [];
        $todosSenadores = [];
        $entidadesResumen = [];

        foreach ($entidades as $id => $nombre) {
            $f = "$jsonDir/legisladores_entidad_{$id}_leg_66.json";
            if (file_exists($f)) {
                $d = json_decode((string)file_get_contents($f), true);
                if (is_array($d)) {
                    $dips = $d['diputados'] ?? [];
                    $sens = $d['senadores'] ?? [];
                    foreach ($dips as $dip) {
                        $todosDiputados[] = $dip;
                    }
                    foreach ($sens as $sen) {
                        $todosSenadores[] = $sen;
                    }
                    $entidadesResumen[$id] = [
                        'nombre' => $nombre,
                        'diputados' => count($dips),
                        'senadores' => count($sens),
                        'total' => count($dips) + count($sens),
                    ];
                }
            }
        }

        $resumenNacional = [
            'titulo' => 'Directorio Consolidado Nacional - LXVI Legislatura',
            'alcance' => '32 Entidades Federativas (100% Cobertura Nacional)',
            'generado_en' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'entidades_procesadas' => count($entidadesResumen),
            'total_diputados' => count($todosDiputados),
            'total_senadores' => count($todosSenadores),
            'total_general' => count($todosDiputados) + count($todosSenadores),
            'entidades' => $entidadesResumen,
        ];

        $archivoConsolidado = "$jsonDir/congreso_union_leg_66.json";
        $dataConsolidada = [
            'metadata' => [
                'titulo' => 'Congreso de la Unión de México - LXVI Legislatura',
                'alcance' => 'Nacional (32 Entidades Federativas)',
                'generado_en' => $resumenNacional['generado_en'],
                'total_diputados' => count($todosDiputados),
                'total_senadores' => count($todosSenadores),
                'total_general' => count($todosDiputados) + count($todosSenadores),
            ],
            'diputados' => $todosDiputados,
            'senadores' => $todosSenadores,
        ];
        file_put_contents($archivoConsolidado, json_encode($dataConsolidada, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $archivoResumen = "$jsonDir/directorio_nacional_resumen.json";
        file_put_contents($archivoResumen, json_encode($resumenNacional, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response['resumen_nacional'] = $resumenNacional;
        $response['archivo_consolidado'] = $archivoConsolidado;
        $response['archivo_resumen'] = $archivoResumen;
        break;

    case 'help':
    default:
        $response['message'] = "CLI Gateway Pulso Civico";
        $response['commands'] = [
            'lint <file1,file2...>'  => 'Valida sintaxis PHP de uno o varios archivos',
            'lint:dir [dir]'         => 'Valida sintaxis PHP de todo un directorio recursivamente',
            'git:status'             => 'Estado limpio y estructurado de git',
            'git:diff'               => 'Resumen de cambios de git',
            'health'                 => 'Chequeo completo de salud del proyecto (sintaxis y carpetas)',
            'search <term> [dir]'    => 'Búsqueda de código interna',
            'scrape <fuente> [json]' => 'Ejecuta ingesta de datos por fuente (ej: sil)',
        ];
        break;
}

$json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false) {
    echo json_encode(['error' => 'json_encode_failed', 'msg' => json_last_error_msg()]) . "\n";
} else {
    echo $json . "\n";
}

