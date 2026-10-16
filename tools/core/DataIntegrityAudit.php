<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

/**
 * DataIntegrityAudit: Auditor de integridad semántica de datos, homónimos y anomalías de scrapers.
 * Detecta automáticamente:
 * 1. Colisiones de homónimos (mismo nombre en periodos incompatibles o estados lejanos).
 * 2. Suplentes mal etiquetados o nombres con residuos ("Suplente", "Lic.", "Dip.", números).
 * 3. Partidos que son nombres de coaliciones en lugar de partidos individuales.
 * 4. Colisiones de fotografías (misma foto con nombres distintos).
 */
class DataIntegrityAudit
{
    /** @var array<string, string> */
    private const KNOWN_COALITIONS = [
        'juntos haremos historia' => 'Coalición MORENA-PT-PVEM/PES',
        'sigamos haciendo historia' => 'Coalición MORENA-PT-PVEM',
        'va por mexico' => 'Coalición PAN-PRI-PRD',
        'fuerza y corazon por mexico' => 'Coalición PAN-PRI-PRD',
        'por mexico al frente' => 'Coalición PAN-PRD-MC',
        'alianza por mexico' => 'Coalición',
        'compromiso por mexico' => 'Coalición PRI-PVEM',
    ];

    /**
     * Ejecuta una auditoría integral de integridad sobre todos los datasets JSON.
     * @return array<string, mixed>
     */
    public static function run(string $rootDir): array
    {
        $jsonDir = $rootDir . '/storage/data/json';
        if (!is_dir($jsonDir)) {
            return ['ok' => false, 'error' => "Directorio $jsonDir no existe."];
        }

        $files = glob($jsonDir . '/*.json') ?: [];
        $totalFiles = count($files);

        $anomaliasScraper = [];
        $alertasCoalicion = [];
        $homonimosSospechosos = [];
        $colisionesFotos = [];

        // Estructuras de rastreo global para cruce
        $nombresIndex = []; // nombre_normalizado => [ [archivo, cargo, periodo, entidad, ref_sil, foto] ]
        $fotosIndex = [];   // ref_sil / foto_hash => [ [nombre, archivo] ]

        $totalRegistrosRevisados = 0;

        foreach ($files as $filePath) {
            $baseName = basename($filePath);

            // Enfocar en legisladores y cargos electos
            if (!str_starts_with($baseName, 'congreso_union_leg_') &&
                !str_starts_with($baseName, 'legisladores_entidad_') &&
                !str_starts_with($baseName, 'alcaldes_') &&
                !str_starts_with($baseName, 'directorio_')) {
                continue;
            }

            $content = (string)file_get_contents($filePath);
            $data = json_decode($content, true);
            if (!is_array($data)) {
                continue;
            }

            // Normalizar listas de políticos dentro del JSON
            $listas = [];
            foreach (['diputados', 'senadores', 'legisladores', 'alcaldes', 'politicos'] as $k) {
                if (isset($data[$k]) && is_array($data[$k])) {
                    $listas[] = $data[$k];
                }
            }
            if (empty($listas) && isset($data[0]) && is_array($data[0])) {
                $listas[] = $data;
            }

            foreach ($listas as $lista) {
                foreach ($lista as $pol) {
                    if (!is_array($pol)) continue;
                    $totalRegistrosRevisados++;

                    $nombre = trim((string)($pol['nombre_completo'] ?? $pol['nombre'] ?? ''));
                    if ($nombre === '') continue;

                    $partido = trim((string)($pol['partido'] ?? ''));
                    $entidad = trim((string)($pol['entidad'] ?? $pol['estado'] ?? ''));
                    $leg = (int)($pol['legislatura'] ?? 0);
                    $refSil = (string)($pol['referencia_sil'] ?? '');
                    $foto = (string)($pol['foto_oficial_url'] ?? '');
                    $cargo = (string)($pol['tipo_cargo'] ?? $pol['cargo'] ?? 'Propietario');

                    // 1. Detección de residuos en nombres de scrapers
                    if (preg_match('/^(lic\.|dip\.|sen\.|dr\.|dra\.|c\.|ing\.)\s+/i', $nombre, $m) ||
                        preg_match('/\b(suplente|propietario|electo|licencia)\b/i', $nombre) ||
                        preg_match('/[0-9]{2,}/', $nombre)) {
                        $anomaliasScraper[] = [
                            'archivo' => $baseName,
                            'nombre' => $nombre,
                            'motivo' => 'Residuo de cargo, título profesional o dígitos en campo de nombre',
                            'severidad' => 'AMARILLO',
                        ];
                    }

                    // 2. Detección de partidos que son coaliciones
                    $partidoLower = mb_strtolower($partido);
                    foreach (self::KNOWN_COALITIONS as $coalicionKey => $desc) {
                        if (str_contains($partidoLower, $coalicionKey)) {
                            $alertasCoalicion[] = [
                                'archivo' => $baseName,
                                'nombre' => $nombre,
                                'partido_detectado' => $partido,
                                'coalicion_sugerida' => $desc,
                                'severidad' => 'AMARILLO',
                            ];
                            break;
                        }
                    }

                    // 3. Rastreo para detección de homónimos
                    $nombreNorm = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $nombre));
                    $nombreNorm = trim(preg_replace('/\s+/', ' ', $nombreNorm));

                    if (!isset($nombresIndex[$nombreNorm])) {
                        $nombresIndex[$nombreNorm] = [];
                    }
                    $nombresIndex[$nombreNorm][] = [
                        'archivo' => $baseName,
                        'nombre_original' => $nombre,
                        'entidad' => $entidad,
                        'legislatura' => $leg,
                        'referencia_sil' => $refSil,
                        'cargo' => $cargo,
                    ];

                    // 4. Rastreo de fotos duplicadas con nombres distintos
                    if ($refSil !== '') {
                        if (!isset($fotosIndex[$refSil])) {
                            $fotosIndex[$refSil] = [];
                        }
                        $fotosIndex[$refSil][] = [
                            'nombre' => $nombre,
                            'archivo' => $baseName,
                        ];
                    }
                }
            }
        }

        // Evaluar índice de nombres para detectar homónimos con conflicto duro (ROJO)
        foreach ($nombresIndex as $nombreNorm => $ocurrencias) {
            if (count($ocurrencias) <= 1) continue;

            // Comparar pares de ocurrencias
            $len = count($ocurrencias);
            for ($i = 0; $i < $len; $i++) {
                for ($j = $i + 1; $j < $len; $j++) {
                    $a = $ocurrencias[$i];
                    $b = $ocurrencias[$j];

                    // Si tienen la misma legislatura pero entidades radicalmente distintas
                    if ($a['legislatura'] > 0 && $a['legislatura'] === $b['legislatura']) {
                        if ($a['entidad'] !== '' && $b['entidad'] !== '' && $a['entidad'] !== $b['entidad']) {
                            $homonimosSospechosos[] = [
                                'nombre' => $a['nombre_original'],
                                'conflicto' => 'Mismo nombre en la misma legislatura (Leg ' . $a['legislatura'] . ') pero en estados distintos: ' . $a['entidad'] . ' vs ' . $b['entidad'],
                                'severidad' => 'ROJO (Falso positivo casi seguro si se unifica)',
                                'archivos' => [$a['archivo'], $b['archivo']],
                            ];
                        }
                    }
                }
            }
        }

        // Evaluar índice de fotos para detectar colisiones
        foreach ($fotosIndex as $refSil => $ocurrencias) {
            if (count($ocurrencias) <= 1) continue;
            $nombresDiferentes = array_unique(array_map(fn($o) => mb_strtolower($o['nombre']), $ocurrencias));
            if (count($nombresDiferentes) > 1) {
                $colisionesFotos[] = [
                    'referencia_sil' => $refSil,
                    'nombres_asociados' => array_values(array_unique(array_map(fn($o) => $o['nombre'], $ocurrencias))),
                    'archivos' => array_values(array_unique(array_map(fn($o) => $o['archivo'], $ocurrencias))),
                    'severidad' => 'ROJO (Misma foto asociada a personas con nombres distintos)',
                ];
            }
        }

        return [
            'ok' => true,
            'archivos_evaluados' => $totalFiles,
            'registros_auditados' => $totalRegistrosRevisados,
            'resumen' => [
                'anomalias_nombres_scraper' => count($anomaliasScraper),
                'alertas_partidos_coalicion' => count($alertasCoalicion),
                'homonimos_en_conflicto' => count($homonimosSospechosos),
                'colisiones_fotos' => count($colisionesFotos),
            ],
            'alertas_homonimos' => array_slice($homonimosSospechosos, 0, 20),
            'alertas_scrapers' => array_slice($anomaliasScraper, 0, 20),
            'alertas_coaliciones' => array_slice($alertasCoalicion, 0, 20),
            'alertas_fotos' => array_slice($colisionesFotos, 0, 20),
        ];
    }
}
