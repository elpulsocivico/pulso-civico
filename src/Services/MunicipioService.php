<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\DTO\AlcaldeDTO;

/**
 * Servicio para la gestión, ingesta y consulta del Directorio Nacional de Alcaldes
 * y del Histórico de Presidentes Municipales de México.
 */
class MunicipioService
{
    private string $storagePath;
    /** @var array<int, string> */
    private static array $estadosMap = [
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
        15 => 'Estado de México',
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

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage';
    }

    /**
     * Procesa e ingesta el archivo CSV consolidado histórico (1989-2024),
     * dividiendo la información en directorio vigente e historias municipales.
     *
     * @return array<string, mixed> Resumen de la ingesta
     */
    public function ingestarHistoricoIncumbents(string $csvContentOrPath): array
    {
        $lines = [];
        if (str_starts_with($csvContentOrPath, 'http://') || str_starts_with($csvContentOrPath, 'https://')) {
            $context = stream_context_create([
                'http' => [
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PulsoCivicoBot/1.0',
                    'timeout' => 60,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $rawContent = (string)@file_get_contents($csvContentOrPath, false, $context);
            if ($rawContent !== '') {
                $lines = explode("\n", $rawContent);
            }
        } elseif (is_file($csvContentOrPath)) {
            $lines = file($csvContentOrPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        } else {
            $lines = explode("\n", $csvContentOrPath);
        }

        if (empty($lines) || count($lines) <= 1) {
            return ['ok' => false, 'error' => 'No se encontraron líneas válidas en el archivo o contenido CSV'];
        }

        $header = null;
        $registrosPorMunicipio = [];
        $totalProcesados = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, 'Title:') || str_starts_with($line, 'Source:')) {
                continue;
            }

            $cols = str_getcsv($line);
            if ($header === null) {
                $header = array_map('trim', $cols);
                continue;
            }

            if (count($cols) < 11) {
                continue;
            }

            $row = [];
            foreach ($header as $idx => $colName) {
                $row[$colName] = $cols[$idx] ?? '';
            }

            $inegiRaw = (int)($row['inegi'] ?? 0);
            if ($inegiRaw <= 0) {
                continue;
            }

            $cvegeo = sprintf('%05d', $inegiRaw);
            $cveEnt = (int)substr($cvegeo, 0, 2);
            $cveMun = (int)substr($cvegeo, 2, 3);
            $entidad = self::$estadosMap[$cveEnt] ?? "Entidad {$cveEnt}";
            $municipio = mb_convert_case(trim($row['mun'] ?? ''), MB_CASE_TITLE, 'UTF-8');
            $nombre = mb_convert_case(trim($row['incumbent'] ?? ''), MB_CASE_TITLE, 'UTF-8');

            if ($nombre === '' || strtolower($nombre) === 'na') {
                continue;
            }

            $anoEleccion = (int)($row['yr'] ?? 0);
            $partido = strtoupper(trim($row['part'] ?? ''));
            $partido = $partido === '' || $partido === 'NA' ? null : $partido;

            // Período estimado del mandato (trianual típicamente en México)
            $anoInicio = $anoEleccion;
            $anoFin = $anoInicio + 3;
            $periodo = "{$anoInicio}-{$anoFin}";

            $dto = new AlcaldeDTO(
                cvegeo: $cvegeo,
                entidad: $entidad,
                cveEnt: $cveEnt,
                municipio: $municipio,
                cveMun: $cveMun,
                nombreCompleto: $nombre,
                periodo: $periodo,
                anoInicio: $anoInicio,
                anoFin: $anoFin,
                partido: $partido,
                esVigente: false, // se recalcula después con la elección más reciente
                genero: isset($row['dmujer']) && $row['dmujer'] === '1' ? 'F' : 'M',
                reeleccion: isset($row['drepe']) && $row['drepe'] === '1',
                margenVictoria: isset($row['mg']) && is_numeric($row['mg']) ? (float)$row['mg'] : null,
                segundoLugar: !empty($row['runnerup']) ? mb_convert_case(trim($row['runnerup']), MB_CASE_TITLE, 'UTF-8') : null,
                partidoSegundoLugar: !empty($row['part2nd']) ? strtoupper(trim($row['part2nd'])) : null,
                notas: !empty($row['notas']) ? trim($row['notas']) : null,
                fuenteUrl: 'https://github.com/emagar/elecRetrns (CIDAC / OPLEs / INAFED)',
                fechaExtraccion: date('Y-m-d')
            );

            $registrosPorMunicipio[$cvegeo][] = $dto;
            $totalProcesados++;
        }

        // Crear directorios si no existen
        $alcaldesDir = $this->storagePath . '/data/json/alcaldes';
        $municipiosDir = $this->storagePath . '/data/json/municipios';
        if (!is_dir($alcaldesDir)) {
            mkdir($alcaldesDir, 0777, true);
        }
        if (!is_dir($municipiosDir)) {
            mkdir($municipiosDir, 0777, true);
        }

        $directorioVigente = [];
        $totalMunicipios = count($registrosPorMunicipio);

        foreach ($registrosPorMunicipio as $cvegeo => $alcaldes) {
            // Ordenar cronológicamente (más antiguo al más reciente)
            usort($alcaldes, static fn(AlcaldeDTO $a, AlcaldeDTO $b) => $a->anoInicio <=> $b->anoInicio);

            // El último registro es el mandato más reciente/vigente
            $ultimoIdx = count($alcaldes) - 1;
            $ultimoAlcalde = $alcaldes[$ultimoIdx];

            // Reconstruir el DTO marcando esVigente = true
            $alcaldes[$ultimoIdx] = new AlcaldeDTO(
                cvegeo: $ultimoAlcalde->cvegeo,
                entidad: $ultimoAlcalde->entidad,
                cveEnt: $ultimoAlcalde->cveEnt,
                municipio: $ultimoAlcalde->municipio,
                cveMun: $ultimoAlcalde->cveMun,
                nombreCompleto: $ultimoAlcalde->nombreCompleto,
                periodo: $ultimoAlcalde->periodo,
                anoInicio: $ultimoAlcalde->anoInicio,
                anoFin: $ultimoAlcalde->anoFin,
                partido: $ultimoAlcalde->partido,
                esVigente: true,
                genero: $ultimoAlcalde->genero,
                reeleccion: $ultimoAlcalde->reeleccion,
                margenVictoria: $ultimoAlcalde->margenVictoria,
                segundoLugar: $ultimoAlcalde->segundoLugar,
                partidoSegundoLugar: $ultimoAlcalde->partidoSegundoLugar,
                notas: $ultimoAlcalde->notas,
                fuenteUrl: $ultimoAlcalde->fuenteUrl,
                fechaExtraccion: $ultimoAlcalde->fechaExtraccion
            );

            // Agregar al directorio nacional vigente
            $directorioVigente[$cvegeo] = $alcaldes[$ultimoIdx]->toArray();

            // Guardar archivo histórico específico para este municipio
            $municipioData = [
                'cvegeo' => $cvegeo,
                'entidad' => $ultimoAlcalde->entidad,
                'cve_ent' => $ultimoAlcalde->cveEnt,
                'municipio' => $ultimoAlcalde->municipio,
                'cve_mun' => $ultimoAlcalde->cveMun,
                'total_mandatos_registrados' => count($alcaldes),
                'alcalde_actual' => $alcaldes[$ultimoIdx]->toArray(),
                'cronologia' => array_map(static fn(AlcaldeDTO $d) => $d->toArray(), $alcaldes),
                'metadata' => [
                    'fuente_primaria' => 'Bases Electorales Municipales / CIDAC / OPLEs / INAFED',
                    'fecha_actualizacion' => date('Y-m-d H:i:s'),
                ],
            ];

            file_put_contents(
                "{$municipiosDir}/{$cvegeo}.json",
                json_encode($municipioData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        // Guardar dataset nacional consolidado de vigentes
        $resumenVigentes = [
            'metadata' => [
                'titulo' => 'Directorio Nacional de Presidentas y Presidentes Municipales de México',
                'alcance' => 'Nacional (32 Entidades)',
                'total_municipios' => count($directorioVigente),
                'generado_en' => date('Y-m-d H:i:s'),
                'fuente_url' => 'https://github.com/emagar/elecRetrns / INAFED',
                'fecha_extraccion' => date('Y-m-d'),
            ],
            'alcaldes' => $directorioVigente,
        ];

        file_put_contents(
            "{$alcaldesDir}/directorio_nacional_alcaldes_vigentes.json",
            json_encode($resumenVigentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return [
            'ok' => true,
            'total_registros_procesados' => $totalProcesados,
            'total_municipios' => $totalMunicipios,
            'archivo_directorio_vigente' => "{$alcaldesDir}/directorio_nacional_alcaldes_vigentes.json",
            'directorio_municipios_historico' => $municipiosDir,
        ];
    }

    /**
     * Devuelve el directorio nacional de alcaldes vigentes, opcionalmente filtrado.
     * @return array<string, mixed>
     */
    public function getDirectorioVigente(?int $cveEnt = null, ?string $partido = null): array
    {
        $archivo = $this->storagePath . '/data/json/alcaldes/directorio_nacional_alcaldes_vigentes.json';
        if (!file_exists($archivo)) {
            return [];
        }

        $raw = json_decode((string)file_get_contents($archivo), true);
        $alcaldes = $raw['alcaldes'] ?? [];

        if ($cveEnt === null && $partido === null) {
            return $alcaldes;
        }

        $filtrados = [];
        $partidoNorm = $partido !== null ? strtoupper(trim($partido)) : null;

        foreach ($alcaldes as $cvegeo => $item) {
            if ($cveEnt !== null && (int)($item['cve_ent'] ?? 0) !== $cveEnt) {
                continue;
            }
            if ($partidoNorm !== null && !str_contains(strtoupper((string)($item['partido'] ?? '')), $partidoNorm)) {
                continue;
            }
            $filtrados[$cvegeo] = $item;
        }

        return $filtrados;
    }

    /**
     * Devuelve la historia cronológica completa de un municipio por su clave INEGI de 5 dígitos.
     * @return array<string, mixed>|null
     */
    public function getHistorialMunicipio(string $cvegeo): ?array
    {
        $cvegeo = sprintf('%05d', (int)$cvegeo);
        $archivo = "{$this->storagePath}/data/json/municipios/{$cvegeo}.json";
        if (!file_exists($archivo)) {
            return null;
        }

        return json_decode((string)file_get_contents($archivo), true);
    }

    /**
     * Integra al directorio nacional los 417 municipios de Oaxaca que se rigen por Sistemas Normativos Indígenas (SNI).
     * @return array<string, mixed>
     */
    public function integrarMunicipiosIndigenasOaxaca(?string $catalogoPath = null): array
    {
        $catFile = $catalogoPath ?? $this->storagePath . '/data/json/catalogo_municipios_oaxaca.json';
        if (!file_exists($catFile)) {
            return ['ok' => false, 'error' => "Catálogo no encontrado en {$catFile}"];
        }

        $catalogo = json_decode((string)file_get_contents($catFile), true);
        $muns = $catalogo['municipios'] ?? [];
        $municipiosDir = $this->storagePath . '/data/json/municipios';
        $directorioFile = $this->storagePath . '/data/json/alcaldes/directorio_nacional_alcaldes_vigentes.json';

        $directorioData = file_exists($directorioFile) ? json_decode((string)file_get_contents($directorioFile), true) : ['alcaldes' => []];
        $alcaldesVigentes = $directorioData['alcaldes'] ?? [];

        $integrados = 0;
        foreach ($muns as $m) {
            $cvegeo = $m['cvegeo'] ?? '';
            if ($cvegeo === '') continue;

            $archivoMun = "{$municipiosDir}/{$cvegeo}.json";
            $esSni = !empty($m['es_usos_costumbres']) || ($m['regimen_electoral'] ?? '') === 'Sistemas Normativos Indígenas';

            if (!file_exists($archivoMun)) {
                $munData = [
                    'cvegeo' => $cvegeo,
                    'entidad' => 'Oaxaca',
                    'cve_ent' => 20,
                    'municipio' => $m['nombre'] ?? "Municipio {$cvegeo}",
                    'cve_mun' => (int)($m['cve_municipio'] ?? substr($cvegeo, 2, 3)),
                    'poblacion_total' => $m['poblacion_total'] ?? null,
                    'regimen_electoral' => $m['regimen_electoral'] ?? 'Sistemas Normativos Indígenas',
                    'es_usos_costumbres' => $esSni,
                    'total_mandatos_registrados' => 1,
                    'alcalde_actual' => [
                        'cvegeo' => $cvegeo,
                        'entidad' => 'Oaxaca',
                        'cve_ent' => 20,
                        'municipio' => $m['nombre'] ?? "Municipio {$cvegeo}",
                        'cve_mun' => (int)($m['cve_municipio'] ?? substr($cvegeo, 2, 3)),
                        'nombre_completo' => 'Autoridad Comunitaria Tradicional',
                        'periodo' => '2023-2025',
                        'ano_inicio' => 2023,
                        'ano_fin' => 2025,
                        'partido' => null,
                        'es_vigente' => true,
                        'tipo_cargo' => 'Presidente Municipal / Concejal Presidente',
                        'sistema' => 'Sistemas Normativos Indígenas (Asamblea Comunitaria)',
                        'metadata' => [
                            'fuente_url' => 'https://www.ieepco.org.mx/sistemas-normativos-indigenas',
                            'fecha_extraccion' => date('Y-m-d'),
                        ],
                    ],
                    'cronologia' => [],
                    'metadata' => [
                        'fuente_primaria' => 'INEGI Marco Geoestadístico / IEEPCO Catálogo SNI',
                        'fecha_actualizacion' => date('Y-m-d H:i:s'),
                    ],
                ];

                file_put_contents($archivoMun, json_encode($munData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $integrados++;
            }

            if (!isset($alcaldesVigentes[$cvegeo])) {
                $alcaldesVigentes[$cvegeo] = [
                    'cvegeo' => $cvegeo,
                    'entidad' => 'Oaxaca',
                    'cve_ent' => 20,
                    'municipio' => $m['nombre'] ?? "Municipio {$cvegeo}",
                    'cve_mun' => (int)($m['cve_municipio'] ?? substr($cvegeo, 2, 3)),
                    'nombre_completo' => 'Autoridad Comunitaria Tradicional',
                    'periodo' => '2023-2025',
                    'ano_inicio' => 2023,
                    'ano_fin' => 2025,
                    'partido' => null,
                    'es_vigente' => true,
                    'tipo_cargo' => 'Presidente Municipal / Concejal Presidente',
                    'sistema' => 'Sistemas Normativos Indígenas',
                    'metadata' => [
                        'fuente_url' => 'https://www.ieepco.org.mx/sistemas-normativos-indigenas',
                        'fecha_extraccion' => date('Y-m-d'),
                    ],
                ];
            }
        }

        ksort($alcaldesVigentes);
        $directorioData['alcaldes'] = $alcaldesVigentes;
        $directorioData['metadata']['total_municipios'] = count($alcaldesVigentes);
        $directorioData['metadata']['fecha_actualizacion'] = date('Y-m-d H:i:s');

        file_put_contents($directorioFile, json_encode($directorioData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            'ok' => true,
            'nuevos_municipios_incorporados' => $integrados,
            'total_nacional_municipios' => count($alcaldesVigentes),
            'directorio_actualizado' => $directorioFile,
        ];
    }

    /**
     * Extiende la serie histórica incorporando elecciones de 1970 a 1988 desde el dataset electoral.
     * @return array<string, mixed>
     */
    public function extenderHistorico1970(string $csvContentOrPath): array
    {
        $lines = [];
        if (str_starts_with($csvContentOrPath, 'http://') || str_starts_with($csvContentOrPath, 'https://')) {
            $context = stream_context_create([
                'http' => ['user_agent' => 'Mozilla/5.0 PulsoCivicoBot/1.0', 'timeout' => 60],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $rawContent = (string)@file_get_contents($csvContentOrPath, false, $context);
            if ($rawContent !== '') $lines = explode("\n", $rawContent);
        } elseif (is_file($csvContentOrPath)) {
            $lines = file($csvContentOrPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        }

        if (empty($lines)) {
            return ['ok' => false, 'error' => 'No se pudo leer el CSV de 1970'];
        }

        $header = null;
        $municipiosDir = $this->storagePath . '/data/json/municipios';
        $mandatosPorMun = [];
        $totalHistoricos = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, 'Title:')) continue;
            $cols = str_getcsv($line);
            if ($header === null) {
                $header = array_map('trim', $cols);
                continue;
            }
            if (count($cols) < 12) continue;

            $row = array_combine(array_slice($header, 0, count($cols)), $cols);
            $ano = (int)($row['yr'] ?? 0);
            if ($ano < 1970 || $ano >= 1989) continue; // Solo nos interesan los comicios 1970-1988

            $inegi = (int)($row['inegi'] ?? 0);
            if ($inegi <= 0) continue;
            $cvegeo = sprintf('%05d', $inegi);

            $partido = strtoupper(trim($row['win'] ?? 'PRI'));
            $margen = isset($row['mg']) && is_numeric($row['mg']) ? (float)$row['mg'] : null;
            $totalVotos = isset($row['tot']) && is_numeric($row['tot']) ? (int)$row['tot'] : null;

            $mandatosPorMun[$cvegeo][] = [
                'cvegeo' => $cvegeo,
                'ano_inicio' => $ano,
                'ano_fin' => $ano + 3,
                'periodo' => "{$ano}-" . ($ano + 3),
                'partido' => $partido,
                'es_vigente' => false,
                'margen_victoria' => $margen,
                'total_votos' => $totalVotos,
                'nombre_completo' => "Presidente Municipal Constitucional ({$partido})",
                'tipo_cargo' => 'Presidente Municipal Constitucional',
                'metadata' => [
                    'fuente_url' => 'https://github.com/emagar/elecRetrns (Bases Electorales Históricas 1970-1988)',
                    'fecha_extraccion' => date('Y-m-d'),
                ],
            ];
            $totalHistoricos++;
        }

        $municipiosActualizados = 0;
        foreach ($mandatosPorMun as $cvegeo => $previos) {
            $archivo = "{$municipiosDir}/{$cvegeo}.json";
            if (!file_exists($archivo)) continue;

            $munData = json_decode((string)file_get_contents($archivo), true);
            $crono = $munData['cronologia'] ?? [];

            // Evitar duplicar años
            $anosExistentes = array_column($crono, 'ano_inicio');
            $nuevos = [];
            foreach ($previos as $p) {
                if (!in_array($p['ano_inicio'], $anosExistentes, true)) {
                    $p['entidad'] = $munData['entidad'] ?? '';
                    $p['cve_ent'] = $munData['cve_ent'] ?? (int)substr($cvegeo, 0, 2);
                    $p['municipio'] = $munData['municipio'] ?? '';
                    $p['cve_mun'] = $munData['cve_mun'] ?? (int)substr($cvegeo, 2, 3);
                    $nuevos[] = $p;
                }
            }

            if (!empty($nuevos)) {
                $todos = array_merge($nuevos, $crono);
                usort($todos, static fn($a, $b) => ($a['ano_inicio'] ?? 0) <=> ($b['ano_inicio'] ?? 0));
                $munData['cronologia'] = $todos;
                $munData['total_mandatos_registrados'] = count($todos);
                file_put_contents($archivo, json_encode($munData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $municipiosActualizados++;
            }
        }

        return [
            'ok' => true,
            'mandatos_1970_1988_extraidos' => $totalHistoricos,
            'municipios_enriquecidos' => $municipiosActualizados,
        ];
    }
}
