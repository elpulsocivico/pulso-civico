<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

class Inspector
{
    /**
     * Inspecciona integridad básica del proyecto:
     * - Chequeo de sintaxis en src/, bin/, config/, database/, tools/
     * - Verificación de carpetas esenciales de storage
     */
    public static function checkHealth(string $rootDir): array
    {
        $directoriesToCheck = ['src', 'bin', 'config', 'tools'];
        $syntaxResults = [];
        $totalChecked = 0;
        $totalErrors = 0;

        foreach ($directoriesToCheck as $dir) {
            $fullPath = $rootDir . '/' . $dir;
            if (is_dir($fullPath)) {
                $res = Linter::checkDirectory($fullPath);
                $totalChecked += $res['checked'];
                if (!$res['ok']) {
                    $totalErrors += count($res['errors']);
                    $syntaxResults[$dir] = $res['errors'];
                }
            }
        }

        // Verificar storage
        $requiredStorage = [
            'storage/logs',
            'storage/data',
            'storage/data/json',
            'storage/raw/html',
            'storage/raw/photos',
        ];
        $storageStatus = [];
        foreach ($requiredStorage as $relPath) {
            $path = $rootDir . '/' . $relPath;
            $storageStatus[$relPath] = [
                'exists' => is_dir($path),
                'writable' => is_writable($path),
            ];
        }

        // Verificar datasets JSON existentes (integridad de fuentes)
        $dataAudit = [
            'total_json_files' => 0,
            'total_records_checked' => 0,
            'missing_sources' => 0,
            'non_gov_sources' => 0,
            'alerts' => [],
        ];
        $jsonDir = $rootDir . '/storage/data/json';
        if (is_dir($jsonDir)) {
            $jsonFiles = glob($jsonDir . '/*.json');
            $dataAudit['total_json_files'] = count($jsonFiles);
            foreach ($jsonFiles as $jf) {
                $content = @file_get_contents($jf);
                $decoded = json_decode($content ?: '', true);
                if (!is_array($decoded)) continue;

                $records = [];
                if (isset($decoded['diputados']) && is_array($decoded['diputados'])) $records = array_merge($records, $decoded['diputados']);
                if (isset($decoded['senadores']) && is_array($decoded['senadores'])) $records = array_merge($records, $decoded['senadores']);
                if (isset($decoded['legisladores']) && is_array($decoded['legisladores'])) $records = array_merge($records, $decoded['legisladores']);

                $relFile = str_replace([$rootDir . DIRECTORY_SEPARATOR, $rootDir . '/'], '', $jf);
                foreach ($records as $idx => $r) {
                    if (!is_array($r)) continue;
                    $dataAudit['total_records_checked']++;
                    $fuente = $r['metadata']['fuente_url'] ?? $r['fuente_url'] ?? null;
                    if (empty($fuente)) {
                        $dataAudit['missing_sources']++;
                        if (count($dataAudit['alerts']) < 10) {
                            $dataAudit['alerts'][] = "Sin fuente_url: {$relFile} [registro #{$idx}]";
                        }
                    } else {
                        $host = parse_url($fuente, PHP_URL_HOST);
                        if (!$host || (!preg_match('/\.gob\.mx$|\.ine\.mx$|\.inegi\.org\.mx$/i', $host) && !in_array(strtolower($host), ['sil.gobernacion.gob.mx', 'sitl.diputados.gob.mx']))) {
                            $dataAudit['non_gov_sources']++;
                            if (count($dataAudit['alerts']) < 10) {
                                $dataAudit['alerts'][] = "Fuente no oficial ({$host}): {$relFile} [registro #{$idx}]";
                            }
                        }
                    }
                }
            }
        }

        return [
            'healthy' => $totalErrors === 0 && $dataAudit['missing_sources'] === 0,
            'php_files_checked' => $totalChecked,
            'syntax_errors_count' => $totalErrors,
            'syntax_errors' => $syntaxResults,
            'storage_status' => $storageStatus,
            'dataset_source_audit' => $dataAudit,
        ];
    }

    public static function searchCode(string $rootDir, string $term, string $dir = ''): array
    {
        $targetDir = $dir !== '' ? $rootDir . '/' . ltrim($dir, '/\\') : $rootDir;
        $matches = [];
        if (!is_dir($targetDir)) {
            return ['error' => "Directory not found: $targetDir"];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($targetDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $path = $file->getPathname();
            // Skip vendor or storage/raw or storage/logs
            if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) ||
                str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) ||
                str_contains($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'raw') ||
                str_contains($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs')) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($ext, ['php', 'js', 'html', 'json', 'css', 'sql'])) continue;

            $content = @file_get_contents($path);
            if ($content === false) continue;

            if (stripos($content, $term) !== false) {
                // Find matching line numbers
                $lines = explode("\n", $content);
                $fileMatches = [];
                foreach ($lines as $idx => $line) {
                    if (stripos($line, $term) !== false) {
                        $fileMatches[] = [
                            'line' => $idx + 1,
                            'text' => trim(mb_substr($line, 0, 140))
                        ];
                        if (count($fileMatches) >= 5) break;
                    }
                }
                $relPath = str_replace([$rootDir . DIRECTORY_SEPARATOR, $rootDir . '/'], '', $path);
                $matches[$relPath] = $fileMatches;
                if (count($matches) >= 20) break;
            }
        }

        return ['term' => $term, 'matches_count' => count($matches), 'matches' => $matches];
    }

    /**
     * Auditoría forense y de seguridad profunda sobre una referencia de git (commit o rama).
     */
    public static function auditGitRef(string $ref, string $baseRef = 'origin/main'): array
    {
        ini_set('memory_limit', '1024M');
        $filesOutput = [];
        exec("git diff --name-only {$baseRef}..{$ref} 2>&1", $filesOutput);

        $results = [
            'ref' => $ref,
            'base' => $baseRef,
            'total_files' => count($filesOutput),
            'files' => $filesOutput,
            'non_data_files' => [],
            'security_alerts' => [],
            'unexpected_keys' => [],
            'domains_found' => [],
            'missing_entities' => [],
            'entities_found' => [],
            'stats' => [
                'total_diputados' => 0,
                'total_senadores' => 0,
            ],
            'base64_candidates' => [],
        ];

        $validLegisladorKeys = [
            'referencia_sil', 'nombre_completo', 'camara', 'legislatura', 'partido',
            'principio_eleccion', 'entidad', 'distrito', 'tipo_cargo', 'suplente_nombre',
            'fecha_toma_protesta', 'fecha_nacimiento', 'correo_institucional', 'telefono',
            'ubicacion_oficina', 'ultimo_grado_estudios', 'preparacion_academica',
            'experiencia_legislativa_resumen', 'foto_oficial_url', 'foto_local_path',
            'comisiones', 'trayectoria_politica', 'trayectoria_legislativa',
            'trayectoria_administrativa', 'escolaridad_detalle', 'licencias',
            'iniciativas', 'puntos_acuerdo', 'redes_sociales', 'metadata',
            'slug', 'id', 'fecha_extraccion', 'asistencia_kpi', 'disciplina_partidaria_pct',
            'votaciones_destacadas', 'linea_tiempo', 'grado_maximo', 'categoria_grado',
            'cargo', 'tipo', 'comision', 'rol', 'periodo', 'descripcion', 'titulo',
            'institucion', 'grado', 'estatus', 'ano_inicio', 'ano_fin', 'url',
            'fuente', 'fecha_inicio', 'fecha_fin', 'motivo', 'titular_referencia_sil',
            'titular_nombre', 'suplente_referencia_sil', 'estatus_licencia',
            'documento_url', 'sinopsis', 'tramite', 'asunto', 'total_comisiones',
            'puesto', 'del_ano', 'al_ano', 'experiencia'
        ];
        $validRootKeys = [
            'metadata', 'diputados', 'senadores', 'entidad_id', 'entidad_nombre',
            'legislatura', 'total_diputados', 'total_senadores', 'total_general',
            'titulo', 'alcance', 'generado_en', 'entidades_procesadas', 'entidades',
            'iniciativas', 'puntos_acuerdo', 'votaciones', 'asistencias'
        ];

        $allKeysFound = [];
        $uniqueDomains = [];
        $entitiesPresent = [];

        foreach ($filesOutput as $relFile) {
            $relFile = trim($relFile);
            if ($relFile === '') continue;

            // Verificar si hay archivos fuera de storage/data o storage/raw
            if (!str_starts_with($relFile, 'storage/data/') && !str_starts_with($relFile, 'storage/raw/')) {
                $results['non_data_files'][] = $relFile;
                $results['security_alerts'][] = [
                    'file' => $relFile,
                    'type' => 'archivo_fuera_de_storage',
                    'snippet' => "Archivo modificado fuera de storage: {$relFile}",
                ];
            }

            // Extraer contenido de git
            $contentLines = [];
            $spec = "{$ref}:{$relFile}";
            exec("git show " . escapeshellarg($spec) . " 2>&1", $contentLines);
            $rawContent = implode("\n", $contentLines);

            // 1. Escaneo estático de firmas maliciosas
            $maliciousSignatures = [
                'php_tag' => '/<\?(php|=)/i',
                'script_tag' => '/<script\b/i',
                'iframe_tag' => '/<iframe\b/i',
                'event_handler' => '/\bon(load|error|click|mouse|key)\s*=/i',
                'js_protocol' => '/javascript:\s*/i',
                'php_code_exec' => '/\b(eval|assert|passthru|shell_exec|system|exec|popen|proc_open|create_function|base64_decode|gzinflate|unserialize)\s*\(/i',
                'shell_keywords' => '/\b(powershell|cmd\.exe|\/bin\/sh|\/bin\/bash|nc\s+-e|bash\s+-i)\b/i',
                'ip_direct_url' => '/https?:\/\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i',
                'suspicious_schemes' => '/(data:text\/html|vbscript:)/i',
            ];

            foreach ($maliciousSignatures as $sigName => $pattern) {
                if (preg_match_all($pattern, $rawContent, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $offset = $match[1];
                        $snippet = substr($rawContent, max(0, $offset - 40), 100);
                        if (count($results['security_alerts']) < 50) {
                            $results['security_alerts'][] = [
                                'file' => $relFile,
                                'type' => $sigName,
                                'matched' => $match[0],
                                'snippet' => preg_replace('/\s+/', ' ', trim($snippet)),
                            ];
                        }
                    }
                }
            }

            // 2. Extraer URLs y dominios generales
            if (preg_match_all('/https?:\/\/([a-zA-Z0-9\-\._]+)/i', $rawContent, $urlMatches)) {
                foreach ($urlMatches[1] as $dom) {
                    $uniqueDomains[strtolower($dom)] = ($uniqueDomains[strtolower($dom)] ?? 0) + 1;
                }
            }

            // 3. Inspección JSON profunda y verificación de integridad de datos
            if (str_ends_with(strtolower($relFile), '.json')) {
                $jsonData = json_decode($rawContent, true);
                if ($jsonData === null && !empty($rawContent)) {
                    $results['security_alerts'][] = [
                        'file' => $relFile,
                        'type' => 'malformed_json',
                        'error' => json_last_error_msg(),
                    ];
                    continue;
                }

                // 3.1 Validación estricta de fuentes de datos y dominios oficiales
                $trustedGovPatterns = [
                    '/\.gob\.mx$/i',
                    '/\.ine\.mx$/i',
                    '/\.inegi\.org\.mx$/i',
                    '/\.congreso[a-z0-9\-]*\.gob\.mx$/i',
                ];

                $checkRecordSource = function (array $record, string $context) use (&$results, $relFile, $trustedGovPatterns) {
                    $fuenteUrl = $record['metadata']['fuente_url'] ?? $record['fuente_url'] ?? null;
                    if (empty($fuenteUrl) || !is_string($fuenteUrl)) {
                        $results['security_alerts'][] = [
                            'file' => $relFile,
                            'type' => 'dato_sin_fuente_oficial',
                            'snippet' => "Registro en {$context} no tiene 'metadata.fuente_url' válida",
                        ];
                        return;
                    }

                    $parsedHost = parse_url($fuenteUrl, PHP_URL_HOST);
                    if (!$parsedHost) {
                        $results['security_alerts'][] = [
                            'file' => $relFile,
                            'type' => 'fuente_url_invalida',
                            'snippet' => "URL inválida en {$context}: {$fuenteUrl}",
                        ];
                        return;
                    }

                    // Validar si el host es de gobierno o fuente oficial reconocida
                    $isGov = false;
                    foreach ($trustedGovPatterns as $pattern) {
                        if (preg_match($pattern, $parsedHost)) {
                            $isGov = true;
                            break;
                        }
                    }

                    if (!$isGov && !in_array(strtolower($parsedHost), ['sil.gobernacion.gob.mx', 'sitl.diputados.gob.mx', 'inegi.org.mx', 'ine.mx'])) {
                        $results['security_alerts'][] = [
                            'file' => $relFile,
                            'type' => 'fuente_no_gubernamental',
                            'snippet' => "Fuente en {$context} no pertenece a dominio oficial reconocido: {$parsedHost} ({$fuenteUrl})",
                        ];
                    }
                };

                if (isset($jsonData['diputados']) && is_array($jsonData['diputados'])) {
                    foreach ($jsonData['diputados'] as $idx => $dip) {
                        if (is_array($dip)) $checkRecordSource($dip, "diputados[{$idx}]");
                    }
                }
                if (isset($jsonData['senadores']) && is_array($jsonData['senadores'])) {
                    foreach ($jsonData['senadores'] as $idx => $sen) {
                        if (is_array($sen)) $checkRecordSource($sen, "senadores[{$idx}]");
                    }
                }
                if (isset($jsonData['legisladores']) && is_array($jsonData['legisladores'])) {
                    foreach ($jsonData['legisladores'] as $idx => $leg) {
                        if (is_array($leg)) $checkRecordSource($leg, "legisladores[{$idx}]");
                    }
                }

                if (preg_match('/legisladores_entidad_(\d+)_leg_/i', $relFile, $entM)) {
                    $entitiesPresent[(int)$entM[1]] = true;
                }

                // Conteos estadísticos si es consolidado nacional
                if (str_contains($relFile, 'congreso_union_leg_')) {
                    $results['stats']['total_diputados'] += count($jsonData['diputados'] ?? []);
                    $results['stats']['total_senadores'] += count($jsonData['senadores'] ?? []);
                }

                // Recorrido de árbol
                $walk = function ($data, string $path = '') use (&$walk, &$allKeysFound, &$results, $relFile, $validLegisladorKeys, $validRootKeys) {
                    if (!is_array($data)) {
                        if (is_string($data)) {
                            $trimmed = trim($data);
                            if (strlen($trimmed) > 80 && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $trimmed)) {
                                $decoded = @base64_decode($trimmed, true);
                                if ($decoded !== false && preg_match('/[^\x20-\x7E\t\r\n]/', $decoded) === 0 && strlen($decoded) > 20) {
                                    if (count($results['base64_candidates']) < 20) {
                                        $results['base64_candidates'][] = [
                                            'file' => $relFile,
                                            'path' => $path,
                                            'preview_decoded' => mb_substr($decoded, 0, 100, 'UTF-8'),
                                        ];
                                    }
                                }
                            }
                        }
                        return;
                    }

                    foreach ($data as $k => $v) {
                        $currentPath = $path === '' ? (string)$k : "{$path}.{$k}";
                        if (is_string($k)) {
                            $allKeysFound[$k] = ($allKeysFound[$k] ?? 0) + 1;
                            if (!in_array($k, $validLegisladorKeys) && !in_array($k, $validRootKeys)) {
                                if (!isset($results['unexpected_keys'][$k])) {
                                    $results['unexpected_keys'][$k] = ['count' => 0, 'samples' => []];
                                }
                                $results['unexpected_keys'][$k]['count']++;
                                if (count($results['unexpected_keys'][$k]['samples']) < 3) {
                                    $results['unexpected_keys'][$k]['samples'][] = [
                                        'file' => $relFile,
                                        'path' => $currentPath,
                                        'value_sample' => is_scalar($v) ? mb_substr((string)$v, 0, 80, 'UTF-8') : gettype($v),
                                    ];
                                }
                            }
                        }
                        $walk($v, $currentPath);
                    }
                };
                $walk($jsonData);
                unset($jsonData);
            }
            unset($rawContent, $contentLines);
        }

        // Verificación de entidades 1..32
        if (!empty($entitiesPresent)) {
            for ($i = 1; $i <= 32; $i++) {
                if (!isset($entitiesPresent[$i])) {
                    $results['missing_entities'][] = $i;
                } else {
                    $results['entities_found'][] = $i;
                }
            }
        }

        $results['domains_found'] = $uniqueDomains;
        $results['total_security_alerts'] = count($results['security_alerts']);
        $results['total_unexpected_keys'] = count($results['unexpected_keys']);

        return $results;
    }

    /**
     * Analiza en profundidad los datasets del Congreso de Tamaulipas (Leg 57 a 66).
     */
    public static function analyzeTamaulipasDatasets(string $rootDir): array
    {
        $jsonDir = $rootDir . '/storage/data/json';
        $legislaturaYears = [
            57 => '1999-2001',
            58 => '2002-2004',
            59 => '2005-2007',
            60 => '2008-2010',
            61 => '2011-2013',
            62 => '2013-2016',
            63 => '2016-2019',
            64 => '2019-2021',
            65 => '2021-2024',
            66 => '2024-2027 (Vigente)',
        ];

        $analysis = [
            'total_legislaturas' => 0,
            'total_legisladores_acumulados' => 0,
            'periodo_historico_abarcado' => '1999 - 2027 (~28 años)',
            'es_solo_ciclo_actual' => false,
            'legislaturas' => [],
            'campos_encontrados' => [],
            'fuentes_oficiales' => [],
        ];

        for ($leg = 57; $leg <= 66; $leg++) {
            $filePath = $jsonDir . "/congreso_tamaulipas_leg_{$leg}.json";
            if (!file_exists($filePath)) {
                continue;
            }

            $raw = @file_get_contents($filePath);
            $data = json_decode($raw ?: '', true);
            if (!is_array($data)) {
                continue;
            }

            $legisladores = $data['legisladores'] ?? [];
            $count = count($legisladores);
            $analysis['total_legislaturas']++;
            $analysis['total_legisladores_acumulados'] += $count;

            $partidos = [];
            $principios = [];
            $conFoto = 0;
            $conEmail = 0;
            $conTel = 0;
            $conComisiones = 0;
            $conTrayectoria = 0;

            foreach ($legisladores as $dip) {
                if (!is_array($dip)) continue;
                $p = trim($dip['partido'] ?? 'SIN_PARTIDO');
                $partidos[$p] = ($partidos[$p] ?? 0) + 1;

                $pr = trim($dip['principio_eleccion'] ?? 'NO_ESPECIFICADO');
                $principios[$pr] = ($principios[$pr] ?? 0) + 1;

                if (!empty($dip['foto_oficial_url'])) $conFoto++;
                if (!empty($dip['correo_institucional'])) $conEmail++;
                if (!empty($dip['telefono'])) $conTel++;
                if (!empty($dip['comisiones'])) $conComisiones++;
                if (!empty($dip['trayectoria_politica']) || !empty($dip['trayectoria_legislativa'])) $conTrayectoria++;

                foreach (array_keys($dip) as $k) {
                    $analysis['campos_encontrados'][$k] = true;
                }
                if (!empty($dip['metadata']['fuente_url'])) {
                    $host = parse_url($dip['metadata']['fuente_url'], PHP_URL_HOST);
                    if ($host) $analysis['fuentes_oficiales'][$host] = true;
                }
            }

            $analysis['legislaturas'][$leg] = [
                'archivo' => basename($filePath),
                'legislatura' => $leg,
                'periodo_estimado' => $legislaturaYears[$leg] ?? 'Desconocido',
                'total_integrantes' => $count,
                'partidos' => $partidos,
                'principios_eleccion' => $principios,
                'cobertura_datos' => [
                    'con_foto_oficial' => $conFoto,
                    'con_correo_institucional' => $conEmail,
                    'con_telefono' => $conTel,
                    'con_comisiones' => $conComisiones,
                    'con_trayectoria' => $conTrayectoria,
                ],
                'muestra_nombres' => array_slice(array_map(fn($d) => $d['nombre_completo'] ?? '', $legisladores), 0, 3),
            ];
        }

        $analysis['campos_encontrados'] = array_keys($analysis['campos_encontrados']);
        $analysis['fuentes_oficiales'] = array_keys($analysis['fuentes_oficiales']);

        return $analysis;
    }
}


