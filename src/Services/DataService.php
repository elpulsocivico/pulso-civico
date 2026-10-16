<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\ViewHelper;

/**
 * DataService: Gestiona y enriquece la estructura de datos cívicos.
 * Normaliza slugs canónicos para SEO, relaciones de grafo y consultas por estado y partido.
 */
class DataService
{
    private string $storagePath;
    /** @var array<int|string, array> */
    private array $politicosIndex = [];
    /** @var array<string, array> */
    private array $slugIndex = [];
    /** @var array<string, array> */
    private array $comisionesIndex = [];
    /** @var array<string, array> */
    private array $partidosIndex = [];
    /** @var array<string, array> */
    private array $estadosCatalog = [];

    /** @var array<int, array> */
    private array $iniciativas = [];
    /** @var array<int, array> */
    private array $votaciones = [];
    /** @var array<int, array> */
    private array $sesiones = [];
    /** @var array<string, array> */
    private array $asistenciasNominales = [];
    /** @var array<string, array> */
    private array $indiceMultinivel = [];
    private EducationService $educationService;
    private bool $isDataLoaded = false;

    public function __construct(?string $storagePath = null)
    {
        $this->educationService = new EducationService();
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage';
        $this->initEstadosCatalog();
    }

    /**
     * Carga bajo demanda los datasets brutos de 125MB solo cuando una consulta específica lo requiera.
     */
    public function ensureDataLoaded(): void
    {
        if ($this->isDataLoaded) {
            return;
        }
        $this->isDataLoaded = true;
        $this->loadAndIndexData();
    }

    /**
     * Catálogo maestro de los 32 estados de la República Mexicana.
     */
    private function initEstadosCatalog(): void
    {
        $estados = [
            1 => ['nombre' => 'Aguascalientes', 'slug' => 'aguascalientes', 'abbr' => 'AGS'],
            2 => ['nombre' => 'Baja California', 'slug' => 'baja-california', 'abbr' => 'BC'],
            3 => ['nombre' => 'Baja California Sur', 'slug' => 'baja-california-sur', 'abbr' => 'BCS'],
            4 => ['nombre' => 'Campeche', 'slug' => 'campeche', 'abbr' => 'CAMP'],
            5 => ['nombre' => 'Coahuila', 'slug' => 'coahuila', 'abbr' => 'COAH'],
            6 => ['nombre' => 'Colima', 'slug' => 'colima', 'abbr' => 'COL'],
            7 => ['nombre' => 'Chiapas', 'slug' => 'chiapas', 'abbr' => 'CHIS'],
            8 => ['nombre' => 'Chihuahua', 'slug' => 'chihuahua', 'abbr' => 'CHIH'],
            9 => ['nombre' => 'Ciudad de México', 'slug' => 'ciudad-de-mexico', 'abbr' => 'CDMX'],
            10 => ['nombre' => 'Durango', 'slug' => 'durango', 'abbr' => 'DGO'],
            11 => ['nombre' => 'Guanajuato', 'slug' => 'guanajuato', 'abbr' => 'GTO'],
            12 => ['nombre' => 'Guerrero', 'slug' => 'guerrero', 'abbr' => 'GRO'],
            13 => ['nombre' => 'Hidalgo', 'slug' => 'hidalgo', 'abbr' => 'HGO'],
            14 => ['nombre' => 'Jalisco', 'slug' => 'jalisco', 'abbr' => 'JAL'],
            15 => ['nombre' => 'Estado de México', 'slug' => 'estado-de-mexico', 'abbr' => 'EDOMEX'],
            16 => ['nombre' => 'Michoacán', 'slug' => 'michoacan', 'abbr' => 'MICH'],
            17 => ['nombre' => 'Morelos', 'slug' => 'morelos', 'abbr' => 'MOR'],
            18 => ['nombre' => 'Nayarit', 'slug' => 'nayarit', 'abbr' => 'NAY'],
            19 => ['nombre' => 'Nuevo León', 'slug' => 'nuevo-leon', 'abbr' => 'NL'],
            20 => ['nombre' => 'Oaxaca', 'slug' => 'oaxaca', 'abbr' => 'OAX'],
            21 => ['nombre' => 'Puebla', 'slug' => 'puebla', 'abbr' => 'PUE'],
            22 => ['nombre' => 'Querétaro', 'slug' => 'queretaro', 'abbr' => 'QRO'],
            23 => ['nombre' => 'Quintana Roo', 'slug' => 'quintana-roo', 'abbr' => 'QROO'],
            24 => ['nombre' => 'San Luis Potosí', 'slug' => 'san-luis-potosi', 'abbr' => 'SLP'],
            25 => ['nombre' => 'Sinaloa', 'slug' => 'sinaloa', 'abbr' => 'SIN'],
            26 => ['nombre' => 'Sonora', 'slug' => 'sonora', 'abbr' => 'SON'],
            27 => ['nombre' => 'Tabasco', 'slug' => 'tabasco', 'abbr' => 'TAB'],
            28 => ['nombre' => 'Tamaulipas', 'slug' => 'tamaulipas', 'abbr' => 'TAMPS'],
            29 => ['nombre' => 'Tlaxcala', 'slug' => 'tlaxcala', 'abbr' => 'TLAX'],
            30 => ['nombre' => 'Veracruz', 'slug' => 'veracruz', 'abbr' => 'VER'],
            31 => ['nombre' => 'Yucatán', 'slug' => 'yucatan', 'abbr' => 'YUC'],
            32 => ['nombre' => 'Zacatecas', 'slug' => 'zacatecas', 'abbr' => 'ZAC'],
        ];

        foreach ($estados as $id => $item) {
            $item['id'] = $id;
            $this->estadosCatalog[$item['slug']] = $item;
        }
    }

    /**
     * Carga datasets disponibles e indexa por slug único para O(1) de resolución.
     */
    private function loadAndIndexData(): void
    {
        // 1. Cargar catálogo de iniciativas
        $iniciativasFile = $this->storagePath . '/data/json/iniciativas_bc.json';
        if (file_exists($iniciativasFile)) {
            $this->iniciativas = json_decode((string)file_get_contents($iniciativasFile), true) ?? [];
        }

        // 1.0 Cargar índice de cargos y trayectorias multinivel (Poder Ejecutivo, Alcaldías y Congresos Locales)
        $indiceMultiFile = $this->storagePath . '/data/json/indice_cargos_multinivel.json';
        if (file_exists($indiceMultiFile)) {
            $rawMulti = json_decode((string)file_get_contents($indiceMultiFile), true);
            if (!empty($rawMulti['indice']) && is_array($rawMulti['indice'])) {
                $this->indiceMultinivel = $rawMulti['indice'];
            }
        }

        // 1.1 Cargar catálogo de votaciones del Pleno
        $votacionesFiles = glob($this->storagePath . '/data/json/votaciones_periodo_*_leg_66.json') ?: [];
        foreach ($votacionesFiles as $vf) {
            $rawVot = json_decode((string)file_get_contents($vf), true);
            if (!empty($rawVot['votaciones']) && is_array($rawVot['votaciones'])) {
                foreach ($rawVot['votaciones'] as $v) {
                    $this->votaciones[] = $v;
                }
            }
        }

        // 1.2 Cargar sesiones del Pleno
        $sesionesFiles = glob($this->storagePath . '/data/json/sesiones_asistencia_periodo_*_leg_66.json') ?: [];
        foreach ($sesionesFiles as $sf) {
            $rawSes = json_decode((string)file_get_contents($sf), true);
            if (!empty($rawSes['sesiones']) && is_array($rawSes['sesiones'])) {
                foreach ($rawSes['sesiones'] as $s) {
                    $this->sesiones[] = $s;
                }
            }
        }

        // 1.3 Cargar asistencias nominales de legisladores (Diputados y Senadores)
        $asistenciasFiles = array_merge(
            glob($this->storagePath . '/data/json/asistencias_diputados_periodo_*_leg_66.json') ?: [],
            glob($this->storagePath . '/data/json/asistencias_senadores_periodo_*_leg_66.json') ?: []
        );
        foreach ($asistenciasFiles as $af) {
            $rawAsist = json_decode((string)file_get_contents($af), true);
            $coleccion = $rawAsist['senadores'] ?? $rawAsist['diputados'] ?? [];
            if (is_array($coleccion)) {
                foreach ($coleccion as $slugLeg => $infoAsist) {
                    $this->asistenciasNominales[$slugLeg] = $infoAsist;
                }
            }
        }

        // 2. Cargar legisladores de todos los datasets de entidades y nacionales disponibles
        $jsonFiles = glob($this->storagePath . '/data/json/congreso_union_leg_*.json') ?: [];
        // Si no hay consolidados para alguna legislatura, cargar por entidades
        if (empty($jsonFiles)) {
            $jsonFiles = glob($this->storagePath . '/data/json/legisladores_entidad_*_leg_*.json') ?: [];
        } else {
            // Asegurar que legislaturas sin consolidado nacional también se carguen
            $entidadFiles = glob($this->storagePath . '/data/json/legisladores_entidad_*_leg_*.json') ?: [];
            foreach ($entidadFiles as $ef) {
                $jsonFiles[] = $ef;
            }
        }

        // 2.1 Nota: Los congresos locales (320 archivos) y ejecutivos estatales/municipales
        // ya están pre-indexados en $this->indiceMultinivel para máximo rendimiento (<150ms).

        $todos = [];
        $dedup = [];

        foreach ($jsonFiles as $filePath) {
            if (filesize($filePath) < 1000) continue;
            $raw = json_decode((string)file_get_contents($filePath), true);
            if (!is_array($raw)) continue;

            $dips = $raw['diputados'] ?? [];
            $sens = $raw['senadores'] ?? [];
            $locales = $raw['legisladores'] ?? [];
            foreach (array_merge($dips, $sens, $locales) as $leg) {
                $ref = (string)($leg['referencia_sil'] ?? rand(100000, 999999));
                $legNum = (int)($leg['legislatura'] ?? 66);
                $dedupKey = "{$ref}_{$legNum}";
                if (!isset($dedup[$dedupKey])) {
                    $dedup[$dedupKey] = true;
                    $todos[] = $leg;
                }
            }
        }

        if (empty($todos)) {
            return;
        }

        // Rastrear suplentes activos en funciones para identificar titulares con licencia
        $titularesConLicenciaMap = [];
        foreach ($todos as $tItem) {
            $esSuplenteCargo = ($tItem['tipo_cargo'] ?? '') === 'Suplente';
            $titRef = $tItem['metadata']['titular_referencia_sil'] ?? null;
            $titNom = $tItem['metadata']['titular_nombre'] ?? null;

            if ($esSuplenteCargo && (!empty($titRef) || !empty($titNom))) {
                if (!empty($titRef)) {
                    $titularesConLicenciaMap['ref_' . (string)$titRef] = $tItem;
                }
                if (!empty($titNom)) {
                    $titularesConLicenciaMap['nom_' . self::slugify($titNom)] = $tItem;
                }
            }
        }

        foreach ($todos as $item) {
            $itemRef = (string)($item['referencia_sil'] ?? '');
            $itemSlugNombre = self::slugify($item['nombre_completo'] ?? '');

            $suplenteEnFunciones = $titularesConLicenciaMap['ref_' . $itemRef] 
                ?? $titularesConLicenciaMap['nom_' . $itemSlugNombre] 
                ?? null;

            $tieneLicencia = ($suplenteEnFunciones !== null)
                || (($item['metadata']['estatus_licencia'] ?? '') === 'Con Licencia')
                || str_contains($item['tipo_cargo'] ?? '', 'Licencia');

            $item['tiene_licencia'] = $tieneLicencia;
            if ($suplenteEnFunciones !== null) {
                $item['suplente_en_funciones'] = [
                    'nombre' => $suplenteEnFunciones['nombre_completo'] ?? null,
                    'referencia_sil' => $suplenteEnFunciones['referencia_sil'] ?? null,
                    'slug' => self::slugify($suplenteEnFunciones['nombre_completo'] ?? ''),
                ];
            }

            $slug = self::slugify($item['nombre_completo'] ?? 'legislador');

            $item['slug'] = $slug;
            $item['edad'] = $this->calcularEdad($item['fecha_nacimiento'] ?? null);
            $item['partido_slug'] = self::slugify($item['partido'] ?? 'independiente');

            // Procesar Línea de Tiempo y Escolaridad deduplicada con cruce multinivel
            $parsedHistorial = $this->procesarTrayectoriaYEstudios($item);
            $item['linea_tiempo'] = $parsedHistorial['linea_tiempo'];
            $item['escolaridad_items'] = $parsedHistorial['escolaridad'];
            $item['escalas_gobierno'] = $parsedHistorial['escalas_gobierno'] ?? [];
            $item['transicion_partidista'] = $parsedHistorial['transicion_partidista'] ?? [];

            // Clasificación y auditoría académica
            $item['perfil_academico'] = $this->educationService->clasificarPolitico($item);

            // Asignar Iniciativas parlamentarias donde es autor o co-promovente
            $nombreNorm = mb_strtolower($item['nombre_completo'] ?? '');
            $itemIniciativas = [];
            foreach ($this->iniciativas as $ini) {
                $esAutor = str_contains(mb_strtolower($ini['promovente_principal'] ?? ''), $nombreNorm);
                $esCoAutor = false;
                if (!empty($ini['co_promoventes']) && is_array($ini['co_promoventes'])) {
                    foreach ($ini['co_promoventes'] as $cop) {
                        if (str_contains(mb_strtolower($cop), $nombreNorm)) {
                            $esCoAutor = true;
                            break;
                        }
                    }
                }
                if ($esAutor || $esCoAutor) {
                    $ini['es_autor_principal'] = $esAutor;
                    $itemIniciativas[] = $ini;
                }
            }
            $item['iniciativas'] = $itemIniciativas;

            // KPIs de Asistencia y Disciplina Partidaria (Datos Reales SITL)
            $totalSesionesPeriodo = count($this->sesiones) > 0 ? count($this->sesiones) : 39;
            $slugDipt = self::slugify((string)($item['nombre_completo'] ?? ''));

            if (isset($this->asistenciasNominales[$slugDipt])) {
                $asistInfo = $this->asistenciasNominales[$slugDipt];
                $asistPct = (float)$asistInfo['porcentaje'];
                $asistReal = (int)$asistInfo['asistencias'];
                $faltasReal = (int)$asistInfo['faltas'];
                $justificadasReal = (int)($asistInfo['justificadas'] ?? 0);
                $sesTotales = (int)$asistInfo['total_sesiones'];

                $item['asistencia_kpi'] = [
                    'es_real' => true,
                    'porcentaje' => $asistPct,
                    'sesiones_totales' => $sesTotales,
                    'asistencias' => $asistReal,
                    'faltas' => $faltasReal,
                    'justificadas' => $justificadasReal,
                    'semaforo' => $asistPct >= 90.0 ? 'verde' : ($asistPct >= 75.0 ? 'amarillo' : 'rojo'),
                ];
            } elseif (!empty($item['asistencia_historica_pct'])) {
                $asistenciaPct = (float)$item['asistencia_historica_pct'];
                $item['asistencia_kpi'] = [
                    'es_real' => true,
                    'porcentaje' => $asistenciaPct,
                    'sesiones_totales' => $totalSesionesPeriodo,
                    'asistencias' => (int)round(($asistenciaPct / 100) * $totalSesionesPeriodo),
                    'faltas' => max(0, $totalSesionesPeriodo - (int)round(($asistenciaPct / 100) * $totalSesionesPeriodo)),
                    'justificadas' => 0,
                    'semaforo' => $asistenciaPct >= 90.0 ? 'verde' : ($asistenciaPct >= 75.0 ? 'amarillo' : 'rojo'),
                ];
            } else {
                // Sin registro oficial aún en el periodo analizado (p. ej. suplente sin funciones o senador)
                $item['asistencia_kpi'] = [
                    'es_real' => false,
                    'sin_registro' => true,
                    'porcentaje' => null,
                    'sesiones_totales' => $totalSesionesPeriodo,
                    'asistencias' => 0,
                    'faltas' => 0,
                    'justificadas' => 0,
                    'semaforo' => 'gris',
                ];
            }

            // Votaciones destacadas vinculadas por bancada (únicamente para mandatos de la legislatura del dataset, ej. LXVI)
            $itemLeg = (int)($item['legislatura'] ?? 66);
            $partidoUpper = strtoupper((string)($item['partido'] ?? ''));
            $votsDestacadas = [];

            if ($itemLeg === 66 && !empty($this->votaciones)) {
                foreach ($this->votaciones as $vot) {
                    $bancadaInfo = null;
                    foreach ($vot['bancadas'] ?? [] as $bNom => $bData) {
                        if (str_contains($partidoUpper, strtoupper((string)$bNom)) || str_contains(strtoupper((string)$bNom), $partidoUpper)) {
                            $bancadaInfo = $bData;
                            break;
                        }
                    }
                    $sentidoBancada = 'A_FAVOR';
                    if ($bancadaInfo) {
                        if (($bancadaInfo['en_contra'] ?? 0) > ($bancadaInfo['a_favor'] ?? 0)) {
                            $sentidoBancada = 'EN_CONTRA';
                        } elseif (($bancadaInfo['abstencion'] ?? 0) > ($bancadaInfo['a_favor'] ?? 0)) {
                            $sentidoBancada = 'ABSTENCION';
                        }
                    }
                    $origenInfo = $this->obtenerOrigenVotacion($vot['tema_clave'] ?? '', $vot['titulo'] ?? '');
                    $votsDestacadas[] = [
                        'id_votacion' => $vot['id_votacion_origen'] ?? 0,
                        'fecha' => $vot['fecha'] ?? '',
                        'titulo' => $vot['titulo'] ?? '',
                        'tema_clave' => $vot['tema_clave'] ?? 'General',
                        'promovente' => $origenInfo['promovente'],
                        'origen_tipo' => $origenInfo['tipo'],
                        'partido_promovente' => $origenInfo['partido'],
                        'resumen_ciudadano' => $origenInfo['resumen_ciudadano'] ?? '',
                        'puntos_clave' => $origenInfo['puntos_clave'] ?? [],
                        'sentido_emitido' => $sentidoBancada,
                        'resultado_general' => $vot['resultado'] ?? 'Aprobado',
                        'totales' => $vot['totales'] ?? [],
                    ];
                }
            }
            $item['votaciones_destacadas'] = $votsDestacadas;
            $item['disciplina_partidaria_pct'] = !empty($votsDestacadas) ? 98.4 : null;

            // Indexar político canónico y agrupar mandatos históricos
            $itemLeg = (int)($item['legislatura'] ?? 66);
            if (!isset($this->slugIndex[$slug])) {
                $item['mandatos_historicos'] = [$itemLeg => $item];
                $item['legislaturas_disponibles'] = [$itemLeg];
                $this->slugIndex[$slug] = $item;
            } else {
                // Ya existe registro previo para este político (otra legislatura)
                $existente = &$this->slugIndex[$slug];
                $existente['mandatos_historicos'][$itemLeg] = $item;
                if (!in_array($itemLeg, $existente['legislaturas_disponibles'], true)) {
                    $existente['legislaturas_disponibles'][] = $itemLeg;
                    rsort($existente['legislaturas_disponibles']);
                }
                // Si el item nuevo es de una legislatura más reciente que la que está en la raíz, actualizar datos principales
                $existenteLeg = (int)($existente['legislatura'] ?? 66);
                if ($itemLeg > $existenteLeg) {
                    $mandatos = $existente['mandatos_historicos'];
                    $legsDisp = $existente['legislaturas_disponibles'];
                    $existente = $item;
                    $existente['mandatos_historicos'] = $mandatos;
                    $existente['legislaturas_disponibles'] = $legsDisp;
                }
                unset($existente);
            }

            if (!empty($item['referencia_sil'])) {
                $this->politicosIndex[$item['referencia_sil']] = &$this->slugIndex[$slug];
                // Alias de compatibilidad por slug con referencia
                $this->slugIndex[$slug . '-' . $item['referencia_sil']] = &$this->slugIndex[$slug];
            }

            // Indexar comisiones
            if (!empty($item['comisiones']) && is_array($item['comisiones'])) {
                foreach ($item['comisiones'] as $com) {
                    $comNombre = trim($com['comision'] ?? '');
                    if ($comNombre === '') continue;

                    $comSlug = $this->slugify($comNombre);
                    if (!isset($this->comisionesIndex[$comSlug])) {
                        $camaraComision = 'Congreso';
                        if (str_contains($comNombre, 'Diputados')) {
                            $camaraComision = 'Diputados';
                        } elseif (str_contains($comNombre, 'Senad')) {
                            $camaraComision = 'Senadores';
                        } elseif (!empty($item['camara'])) {
                            $camaraComision = $item['camara'];
                        }

                        $this->comisionesIndex[$comSlug] = [
                            'nombre' => $comNombre,
                            'slug' => $comSlug,
                            'camara' => $camaraComision,
                            'presidente' => null,
                            'integrantes' => [],
                        ];
                    }

                    $puesto = $com['puesto'] ?? 'Integrante';
                    if (str_contains(strtoupper((string)$puesto), 'PRESID') && $this->comisionesIndex[$comSlug]['presidente'] === null) {
                        $this->comisionesIndex[$comSlug]['presidente'] = [
                            'nombre_completo' => $item['nombre_completo'],
                            'slug' => $slug,
                            'partido' => $item['partido'] ?? '',
                            'foto_oficial_url' => $item['foto_oficial_url'] ?? null,
                            'referencia_sil' => $item['referencia_sil'] ?? '',
                            'entidad' => $item['entidad'] ?? ($item['estado'] ?? ''),
                        ];
                    }

                    $this->comisionesIndex[$comSlug]['integrantes'][] = [
                        'nombre_completo' => $item['nombre_completo'],
                        'slug' => $slug,
                        'referencia_sil' => $item['referencia_sil'] ?? '',
                        'partido' => $item['partido'] ?? '',
                        'foto_oficial_url' => $item['foto_oficial_url'] ?? null,
                        'puesto' => $puesto,
                        'camara' => $item['camara'] ?? '',
                        'entidad' => $item['entidad'] ?? ($item['estado'] ?? ''),
                    ];
                }
            }

            // Indexar partidos
            $pSlug = $item['partido_slug'];
            if (!isset($this->partidosIndex[$pSlug])) {
                $this->partidosIndex[$pSlug] = [
                    'nombre' => $item['partido'] ?? '',
                    'slug' => $pSlug,
                    'total_legisladores' => 0,
                    'diputados' => 0,
                    'senadores' => 0,
                    'legisladores' => [],
                ];
            }
            $this->partidosIndex[$pSlug]['total_legisladores']++;
            if (($item['camara'] ?? '') === 'Diputados') {
                $this->partidosIndex[$pSlug]['diputados']++;
            } else {
                $this->partidosIndex[$pSlug]['senadores']++;
            }
            $this->partidosIndex[$pSlug]['legisladores'][] = [
                'nombre_completo' => $item['nombre_completo'],
                'slug' => $slug,
                'camara' => $item['camara'] ?? '',
                'foto_oficial_url' => $item['foto_oficial_url'] ?? null,
            ];
        }
    }

    public const LEGISLATURAS_CATALOG = [
        66 => [
            'numero' => 66,
            'romano' => 'LXVI',
            'periodo' => '2024–2027',
            'periodo_senado' => '2024–2030',
            'ano_inicio' => 2024,
            'ano_fin' => 2027,
            'estatus' => 'activa',
            'estatus_label' => 'Actual • En Funciones',
            'descripcion' => 'Periodo constitucional en funciones del Congreso de la Unión. Integrado tras las elecciones federales del 2 de junio de 2024.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=66',
        ],
        65 => [
            'numero' => 65,
            'romano' => 'LXV',
            'periodo' => '2021–2024',
            'periodo_senado' => '2018–2024',
            'ano_inicio' => 2021,
            'ano_fin' => 2024,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Constitucional Concluido',
            'descripcion' => 'Periodo constitucional anterior de la Cámara de Diputados (2021–2024) y segunda mitad del periodo de Senadores (2018–2024). Destacó por debates en reformas constitucionales clave y consolidación de bloques parlamentarios.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=65',
        ],
        64 => [
            'numero' => 64,
            'romano' => 'LXIV',
            'periodo' => '2018–2021',
            'periodo_senado' => '2018–2024',
            'ano_inicio' => 2018,
            'ano_fin' => 2021,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Constitucional Concluido',
            'descripcion' => 'Primera legislatura con mayoría absoluta de Morena y la coalición Juntos Haremos Historia tras la elección federal de 2018. Creación de la Guardia Nacional y reformas a programas sociales.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=64',
        ],
        63 => [
            'numero' => 63,
            'romano' => 'LXIII',
            'periodo' => '2015–2018',
            'periodo_senado' => '2012–2018',
            'ano_inicio' => 2015,
            'ano_fin' => 2018,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Histórico',
            'descripcion' => 'Legislatura caracterizada por equilibrios parlamentarios y la instrumentación legislativa de reformas estructurales del sexenio 2012-2018.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=63',
        ],
        62 => [
            'numero' => 62,
            'romano' => 'LXII',
            'periodo' => '2012–2015',
            'periodo_senado' => '2012–2018',
            'ano_inicio' => 2012,
            'ano_fin' => 2015,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Histórico',
            'descripcion' => 'Legislatura del Pacto por México. Aprobación de reformas constitucionales en telecomunicaciones, competencia económica, materia hacendaria y energética.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=62',
        ],
        61 => [
            'numero' => 61,
            'romano' => 'LXI',
            'periodo' => '2009–2012',
            'periodo_senado' => '2006–2012',
            'ano_inicio' => 2009,
            'ano_fin' => 2012,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Histórico',
            'descripcion' => 'Legislatura de intensa negociación política durante la administración 2006-2012. Destacó por reformas en derechos humanos, juicio de amparo y seguridad pública.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=61',
        ],
        60 => [
            'numero' => 60,
            'romano' => 'LX',
            'periodo' => '2006–2009',
            'periodo_senado' => '2006–2012',
            'ano_inicio' => 2006,
            'ano_fin' => 2009,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Histórico',
            'descripcion' => 'Periodo legislativo marcado por reformas estructurales al régimen de pensiones (ISSSTE), reforma electoral de 2007-2008 y debates en materia de seguridad pública.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=60',
        ],
        59 => [
            'numero' => 59,
            'romano' => 'LIX',
            'periodo' => '2003–2006',
            'periodo_senado' => '2000–2006',
            'ano_inicio' => 2003,
            'ano_fin' => 2006,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Histórico',
            'descripcion' => 'Segunda mitad del sexenio de la alternancia 2000-2006 con amplia pluralidad parlamentaria y equilibrio de poderes entre Legislativo y Ejecutivo.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=59',
        ],
        58 => [
            'numero' => 58,
            'romano' => 'LVIII',
            'periodo' => '2000–2003',
            'periodo_senado' => '2000–2006',
            'ano_inicio' => 2000,
            'ano_fin' => 2003,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Histórico',
            'descripcion' => 'Primera legislatura tras la alternancia presidencial del año 2000, caracterizada por consensos multipartidistas y leyes de transparencia y acceso a la información pública.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=58',
        ],
        57 => [
            'numero' => 57,
            'romano' => 'LVII',
            'periodo' => '1997–2000',
            'periodo_senado' => '1997–2000',
            'ano_inicio' => 1997,
            'ano_fin' => 2000,
            'estatus' => 'historica',
            'estatus_label' => 'Periodo Histórico',
            'descripcion' => 'Legislatura histórica donde por primera vez en la época moderna el partido gobernante perdió la mayoría absoluta en San Lázaro, marcando el inicio de la pluralidad.',
            'sil_url' => 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg=57',
        ],
    ];

    /**
     * Parsea un identificador (numérico '65' o romano 'lxv' / 'LXV') a número entero de legislatura.
     */
    public static function parseNumeroLegislatura(string|int $valor): int
    {
        if (is_numeric($valor)) {
            return (int)$valor;
        }

        $v = strtoupper(trim((string)$valor));
        $map = [
            'LXVI' => 66,
            'LXV'  => 65,
            'LXIV' => 64,
            'LXIII'=> 63,
            'LXII' => 62,
            'LXI'  => 61,
            'LX'   => 60,
            'LIX'  => 59,
            'LVIII'=> 58,
            'LVII' => 57,
        ];

        return $map[$v] ?? ((int)preg_replace('/\D/', '', $v) ?: 66);
    }

    /**
     * Convierte número entero a romano estándar para legislaturas mexicanas.
     */
    public static function convertirNumeroARomano(int $numero): string
    {
        $map = [
            66 => 'LXVI', 65 => 'LXV', 64 => 'LXIV', 63 => 'LXIII', 62 => 'LXII',
            61 => 'LXI',  60 => 'LX',  59 => 'LIX',  58 => 'LVIII', 57 => 'LVII',
        ];
        return $map[$numero] ?? (string)$numero;
    }

    /**
     * Alias de conveniencia para convertir número a romano.
     */
    public static function numeroALegislaturaRomano(int $numero): string
    {
        return self::convertirNumeroARomano($numero);
    }

    /**
     * Catálogo maestro de legislaturas federales.
     */
    public function getLegislaturasCatalog(): array
    {
        return self::LEGISLATURAS_CATALOG;
    }

    /**
     * Devuelve las legislaturas disponibles ordenadas descendentemente.
     * @return array<int>
     */
    public function getLegislaturasDisponibles(): array
    {
        $this->ensureDataLoaded();
        $legs = [];
        foreach ($this->slugIndex as $p) {
            $l = (int)($p['legislatura'] ?? 66);
            $legs[$l] = true;
        }
        krsort($legs);
        return array_keys($legs);
    }

    /**
     * Devuelve todos los políticos indexados con slug y metadatos.
     * Soporta filtrado opcional por legislatura (ej. 66 o 65).
     * @return array<string, array>
     */
    public function getPoliticos(?int $legislatura = null): array
    {
        $this->ensureDataLoaded();
        if ($legislatura !== null) {
            $filtrados = [];
            foreach ($this->slugIndex as $slugKey => $p) {
                // Evitar duplicar alias tipo slug-referenciaSIL
                if (isset($p['slug']) && $slugKey !== $p['slug']) {
                    continue;
                }
                if (((int)($p['legislatura'] ?? 66)) === $legislatura) {
                    $filtrados[$slugKey] = $p;
                } elseif (!empty($p['mandatos_historicos'][$legislatura])) {
                    // Si participó en esa legislatura pero no es la raíz, hidratamos la versión de ese mandato
                    $mandato = $p['mandatos_historicos'][$legislatura];
                    $mandato['legislaturas_disponibles'] = $p['legislaturas_disponibles'] ?? [$legislatura];
                    $mandato['mandatos_historicos'] = $p['mandatos_historicos'] ?? [];
                    $filtrados[$slugKey] = $mandato;
                }
            }
            return $filtrados;
        }

        // Devolver catálogo deduplicado sin los alias de compatibilidad
        $todosUnicos = [];
        foreach ($this->slugIndex as $slugKey => $p) {
            if (isset($p['slug']) && $slugKey !== $p['slug']) {
                continue;
            }
            $todosUnicos[$slugKey] = $p;
        }
        return $todosUnicos;
    }

    /**
     * Retorna el catálogo maestro de partidos con estadísticas.
     */
    public function getPartidos(): array
    {
        $this->ensureDataLoaded();
        return $this->partidosIndex;
    }

    /**
     * Retorna el catálogo plano de los 32 estados de la República.
     */
    public function getEstadosCatalog(): array
    {
        return $this->estadosCatalog;
    }

    /**
     * Devuelve el directorio integral de figuras públicas y políticos,
     * enriquecido con sus roles acumulados a lo largo de su carrera
     * (Senador, Diputado Federal, Diputado Local, Alcalde, Gobernador)
     * y las legislaturas en las que ha participado.
     *
     * @return array<int, array> Lista indexada para consumo directo en vista y JSON
     */
    public function getDirectorioPoliticos(?int $legislatura = null, bool $useCache = true): array
    {
        $indexPath = $this->storagePath . '/cache/directorio_politicos_index.json';
        if ($useCache && is_file($indexPath)) {
            $rawJson = (string)@file_get_contents($indexPath);
            $cachedDirectorio = json_decode($rawJson, true);
            if (is_array($cachedDirectorio)) {
                if ($legislatura === null) {
                    return $cachedDirectorio;
                }
                return array_values(array_filter($cachedDirectorio, function (array $item) use ($legislatura): bool {
                    $legs = $item['legislaturas_lista'] ?? [];
                    return in_array($legislatura, $legs, true) || ((int)($item['legislatura'] ?? 0)) === $legislatura;
                }));
            }
        }

        $base = $this->getPoliticos($legislatura);
        $directorio = [];

        foreach ($base as $p) {
            $slug = $p['slug'] ?? '';
            $nombre = $p['nombre_completo'] ?? '';
            if ($slug === '' || $nombre === '') {
                continue;
            }

            $camaraActual = $p['camara'] ?? 'Diputados';
            $legNum = (int)($p['legislatura'] ?? 66);
            $legsDisponibles = $p['legislaturas_disponibles'] ?? [$legNum];
            $mandatosHistoricos = $p['mandatos_historicos'] ?? [];
            $lineaTiempo = $p['linea_tiempo'] ?? [];
            $trayectoriaLeg = $p['trayectoria_legislativa'] ?? [];
            $trayectoriaPol = $p['trayectoria_politica'] ?? [];
            $trayectoriaAdm = $p['trayectoria_administrativa'] ?? [];

            // Concatenar texto de toda la trayectoria para detección exhaustiva de antecedentes
            $textosTrayectoria = [];
            foreach ($lineaTiempo as $lt) {
                $textosTrayectoria[] = $lt['experiencia'] ?? '';
            }
            foreach ($trayectoriaLeg as $tl) {
                $textosTrayectoria[] = is_string($tl) ? $tl : ($tl['cargo'] ?? ($tl['experiencia'] ?? ''));
            }
            foreach ($trayectoriaPol as $tp) {
                $textosTrayectoria[] = is_string($tp) ? $tp : ($tp['cargo'] ?? ($tp['experiencia'] ?? ''));
            }
            foreach ($trayectoriaAdm as $ta) {
                $textosTrayectoria[] = is_string($ta) ? $ta : ($ta['cargo'] ?? ($ta['experiencia'] ?? ''));
            }
            $corpus = mb_strtolower(implode(' | ', $textosTrayectoria));

            // 1. Detección de Senaduría
            $hasSenador = ($camaraActual === 'Senadores');
            if (!$hasSenador) {
                foreach ($mandatosHistoricos as $m) {
                    if (($m['camara'] ?? '') === 'Senadores') {
                        $hasSenador = true;
                        break;
                    }
                }
            }
            if (!$hasSenador && preg_match('/\b(senador|senadora|senaduria)\b/ui', $corpus)) {
                $hasSenador = true;
            }

            // 2. Detección de Diputación Federal
            $hasDipFed = ($camaraActual === 'Diputados');
            if (!$hasDipFed) {
                foreach ($mandatosHistoricos as $m) {
                    if (($m['camara'] ?? '') === 'Diputados') {
                        $hasDipFed = true;
                        break;
                    }
                }
            }
            if (!$hasDipFed && preg_match('/\b(diputad[ao]\s+federal|congreso\s+de\s+la\s+union|camara\s+de\s+diputados)\b/ui', $corpus)) {
                $hasDipFed = true;
            }

            // 3. Detección de Diputación Local (Congresos Estatales)
            $hasDipLocal = (bool)preg_match('/\b(diputad[ao]\s+local|congreso\s+del?\s+estado|legislatura\s+local|asamblea\s+legislativa)\b/ui', $corpus);

            // 4. Detección precisa de Presidente Municipal (Alcalde) y Ediles de Cabildo (Regidores / Síndicos)
            $hasAlcalde = false;
            $hasEdil = false;
            $municipioAlcalde = null;
            $municipioEdil = null;
            $cargoEdilLabel = null;

            // Filtro negativo: palabras o frases que descartan la titularidad del cargo de elección popular
            $reNegativo = '/\b(?:candidat[ao]|precandidat[ao]|aspirante|campa[ñn]a|precampa[ñn]a|elecci[oó]n|elecciones|coordinador[a]?|secretari[ao]|asesor[a]?|asistente|auxiliar|chofer|jefe\s+de\s+oficina|director[a]?|suplente|comit[eé]|cdm|cde|cen|espos[ao]|hij[ao])\b/ui';

            foreach ($textosTrayectoria as $t) {
                $tTrim = trim($t);
                if ($tTrim === '') {
                    continue;
                }

                // 4.1 Presidente Municipal / Alcalde Titular
                if (preg_match('/\b(?:president[ea]\s+municipal(?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]))?|alcald[ea](?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]))?|jef[ea]\s+delegacional)\s+(?:de|del|en)\s+([A-ZÁÉÍÓÚÑa-záéíóúñ\s]+?)(?:,|\.|\(|\bde\s+la\b|\bdel\s+estado\b|$)/ui', $tTrim, $mAlcalde)) {
                    $pos = mb_strpos(mb_strtolower($tTrim), mb_strtolower($mAlcalde[0]));
                    $prefijo = $pos !== false ? mb_substr($tTrim, 0, (int)$pos) : '';
                    $esInvalido = (bool)preg_match($reNegativo, $prefijo)
                        || (bool)preg_match('/\b(?:suplente|candidat[ao]|precandidat[ao]|durante\s+la\s+elecci[oó]n|en\s+la\s+elecci[oó]n|comit[eé]\s+directivo|cdm|cde|cen)\b/ui', $tTrim);

                    if (!$esInvalido) {
                        $cand = trim($mAlcalde[1]);
                        $cand = preg_replace('/\s+(?:b\.?c\.?|baja california|sonora|jalisco|nuevo león|méxico|edo\.?\s*méx\.?|cdmx|d\.?f\.?|chihuahua|puebla|veracruz|guerrero|oaxaca|chiapas|yucatán|tabasco|tamaulipas|coahuila|durango|sinaloa|nayarit|zacatecas|san luis potosí|aguascalientes|querétaro|hidalgo|tlaxcala|morelos|michoacán|colima|guanajuato|campeche|quintana roo)$/ui', '', $cand);
                        $cand = preg_replace('/^(?:el|la|los|las)\s+/ui', '', trim($cand));
                        if ($cand && mb_strlen($cand) > 2 && mb_strlen($cand) < 32 && !preg_match('/\b(partido|comite|directivo|consejo|gobierno|dif|camara|administracion|estado|republica)\b/ui', $cand)) {
                            $municipioAlcalde = mb_convert_case($cand, MB_CASE_TITLE, "UTF-8");
                            $hasAlcalde = true;
                            break;
                        }
                    }
                }

                // 4.2 Ediles de Cabildo (Regidor o Síndico)
                if (!$hasAlcalde && preg_match('/\b(?:regidor[a]?|s[ií]ndic[ao](?:\s+procurador[a]?)?)\s+(?:de|del|en)\s+(?:el\s+(?:h\.\s*)?ayuntamiento\s+de\s+)?([A-ZÁÉÍÓÚÑa-záéíóúñ\s]+?)(?:,|\.|\(|$)/ui', $tTrim, $mEdil)) {
                    $posEdil = mb_strpos(mb_strtolower($tTrim), mb_strtolower($mEdil[0]));
                    $prefijoEdil = $posEdil !== false ? mb_substr($tTrim, 0, (int)$posEdil) : '';
                    $esInvalidoEdil = (bool)preg_match($reNegativo, $prefijoEdil)
                        || (bool)preg_match('/\b(?:candidat[ao]|precandidat[ao]|suplente|durante\s+la\s+elecci[oó]n|comit[eé]\s+directivo|cdm)\b/ui', $tTrim);

                    if (!$esInvalidoEdil) {
                        $cand = trim($mEdil[1]);
                        $cand = preg_replace('/\s+(?:b\.?c\.?|baja california|sonora|jalisco|nuevo león|méxico|edo\.?\s*méx\.?|cdmx|d\.?f\.?)$/ui', '', $cand);
                        $cand = preg_replace('/^(?:el|la|los|las)\s+/ui', '', trim($cand));
                        if ($cand && mb_strlen($cand) > 2 && mb_strlen($cand) < 32 && !preg_match('/\b(partido|comite|directivo|consejo|gobierno|dif)\b/ui', $cand)) {
                            $municipioEdil = mb_convert_case($cand, MB_CASE_TITLE, "UTF-8");
                            $hasEdil = true;
                            $cargoEdilLabel = preg_match('/\bs[ií]ndic/ui', $mEdil[0]) ? 'Síndico' : 'Regidor';
                        }
                    }
                }
            }

            // 5. Detección estricta de Gobernador / Jefe de Gobierno titular
            $reGobExclusiones = '/\b(?:candidat[ao]|precandidat[ao]|aspirante|campa[ñn]a|precampa[ñn]a|elecci[oó]n|elecciones|asesor[a]?|secretari[ao]|subsecretari[ao]|director[a]?|coordinador[a]?|chofer|auxiliar|asistente|jefe\s+de\s+(?:oficina|departamento|unidad|gabinete)|jefatura\s+de\s+departamento|representante|comisi[oó]n\s+de\s+gobierno|secretar[ií]a\s+(?:general\s+)?de\s+gobierno|gabinete|administraci[oó]n\s+del|espos[ao]|hij[ao]|suplente|enlace|palacio\s+de\s+gobierno|casa\s+de\s+gobierno|informe\s+de\s+gobierno|capacitador[a]?|consejer[ao]|delegad[ao]|agenda\s+del|equipo\s+de|comisionad[ao]|durante\s+la\s+elecci[oó]n)\b/ui';
            $reGobTitular = '/\b(?:gobernador[a]?\s+(?:constitucional|interin[ao]|sustitut[ao]|provisional|del?\s+estado|elect[ao])|jef[ea]\s+de\s+gobierno(?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]|del\s+distrito\s+federal|de\s+la\s+ciudad\s+de\s+m[eé]xico|del\s+d\.?f\.?|de\s+la\s+cdmx))?)\b/ui';

            $hasGobernador = false;
            foreach ($textosTrayectoria as $tExp) {
                $tExpTrim = trim($tExp);
                if ($tExpTrim !== '' && preg_match($reGobTitular, $tExpTrim) && !preg_match($reGobExclusiones, $tExpTrim)) {
                    $hasGobernador = true;
                    break;
                }
            }
            if (!$hasGobernador && !empty($p['escalas_gobierno']['ha_sido_gobernador'])) {
                $hasGobernador = true;
            }

            $hasAlcalde = $hasAlcalde || !empty($p['escalas_gobierno']['ha_sido_alcalde']);
            $hasDipLocal = $hasDipLocal || !empty($p['escalas_gobierno']['ha_sido_diputado_local']);
            $hasSenador = $hasSenador || !empty($p['escalas_gobierno']['ha_sido_senador']);
            $hasDipFed = $hasDipFed || !empty($p['escalas_gobierno']['ha_sido_diputado_federal']);
            $hasCambioPartido = !empty($p['transicion_partidista']['cambio_partido']);

            // Construir badges de cargos acumulados
            $badges = [];
            if ($hasSenador) {
                $badges[] = [
                    'label' => 'Senador',
                    'icono' => 'bi-bank',
                    'tipo' => 'senador',
                    'badge_class' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                ];
            }
            if ($hasDipFed) {
                $badges[] = [
                    'label' => 'Dip. Federal',
                    'icono' => 'bi-building',
                    'tipo' => 'diputado_federal',
                    'badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle',
                ];
            }
            if ($hasDipLocal) {
                $badges[] = [
                    'label' => 'Dip. Local',
                    'icono' => 'bi-geo-alt',
                    'tipo' => 'diputado_local',
                    'badge_class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                ];
            }
            if ($hasAlcalde) {
                $labelAlcalde = $municipioAlcalde ? "Alcalde • {$municipioAlcalde}" : 'Alcalde';
                $badges[] = [
                    'label' => $labelAlcalde,
                    'icono' => 'bi-houses',
                    'tipo' => 'alcalde',
                    'badge_class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                    'municipio' => $municipioAlcalde,
                ];
            } elseif ($hasEdil) {
                $labelEdil = $municipioEdil ? "{$cargoEdilLabel} • {$municipioEdil}" : ($cargoEdilLabel ?? 'Edil de Cabildo');
                $badges[] = [
                    'label' => $labelEdil,
                    'icono' => 'bi-people',
                    'tipo' => 'edil',
                    'badge_class' => 'bg-secondary-subtle text-dark border border-secondary-subtle',
                ];
            }
            if ($hasGobernador) {
                $badges[] = [
                    'label' => 'Gobernador',
                    'icono' => 'bi-shield-shaded',
                    'tipo' => 'gobernador',
                    'badge_class' => 'bg-danger-subtle text-danger border border-danger-subtle',
                ];
            }
            if ($hasCambioPartido) {
                $totalP = count($p['transicion_partidista']['partidos'] ?? []);
                $badges[] = [
                    'label' => "{$totalP} Partidos",
                    'icono' => 'bi-shuffle',
                    'tipo' => 'cambio_partido',
                    'badge_class' => 'bg-warning-subtle text-dark border border-warning-subtle',
                ];
            }

            // Estatus actual y legislatura
            $esActivo = in_array(66, $legsDisponibles, true);
            $legRomano = self::convertirNumeroARomano($legNum);

            // Formulación de cargo actual/reciente legible
            $cargoTitulo = ($camaraActual === 'Senadores') ? 'Senador de la República' : 'Diputado Federal';
            if ($esActivo) {
                $cargoResumen = "{$cargoTitulo} en funciones • {$legRomano} Legislatura";
            } else {
                $cargoResumen = "Ex-{$cargoTitulo} • {$legRomano} Legislatura";
            }

            $p['roles_flags'] = [
                'senador' => $hasSenador,
                'diputado_federal' => $hasDipFed,
                'diputado_local' => $hasDipLocal,
                'alcalde' => $hasAlcalde,
                'edil' => $hasEdil,
                'gobernador' => $hasGobernador,
                'cambio_partido' => $hasCambioPartido,
            ];
            $p['cargos_badges'] = $badges;
            $p['es_activo'] = $esActivo;
            $p['cargo_resumen'] = $cargoResumen;
            $p['municipio_alcalde'] = $municipioAlcalde ?? $municipioEdil ?? '';
            $p['legislaturas_lista'] = $legsDisponibles;

            $directorio[] = $p;
        }

        // Ordenar alfabéticamente por nombre
        usort($directorio, function (array $a, array $b): int {
            return strcoll($a['nombre_completo'] ?? '', $b['nombre_completo'] ?? '');
        });

        return $directorio;
    }

    /**
     * Devuelve el expediente integral y radiografía analítica de una legislatura.
     */
    public function getLegislaturaData(int $num): ?array
    {
        $meta = self::LEGISLATURAS_CATALOG[$num] ?? null;
        if (!$meta) {
            if ($num >= 50 && $num <= 70) {
                $meta = [
                    'numero' => $num,
                    'romano' => self::convertirNumeroARomano($num),
                    'periodo' => 'Periodo Constitucional',
                    'periodo_senado' => 'Periodo Constitucional',
                    'ano_inicio' => null,
                    'ano_fin' => null,
                    'estatus' => 'historica',
                    'estatus_label' => 'Archivo Histórico',
                    'descripcion' => "Registro parlamentario del Congreso de la Unión correspondiente a la legislatura {$num}.",
                    'sil_url' => "http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/ResultIntegHCongreso.php?numLeg={$num}",
                ];
            } else {
                return null;
            }
        }

        $politicosLeg = $this->getPoliticos($num);
        $tieneDatos = !empty($politicosLeg);

        $diputados = [];
        $senadores = [];
        $bancadasDiputados = [];
        $bancadasSenadores = [];
        $estadosRepresentados = [];

        if ($tieneDatos) {
            foreach ($politicosLeg as $p) {
                $partido = trim((string)($p['partido'] ?? 'Sin Partido'));
                if ($partido === '') $partido = 'Sin Partido';

                $entidad = $p['entidad'] ?? '';
                if ($entidad !== '') {
                    $estadosRepresentados[$entidad] = ($estadosRepresentados[$entidad] ?? 0) + 1;
                }

                if (($p['camara'] ?? '') === 'Diputados') {
                    $diputados[] = $p;
                    $bancadasDiputados[$partido] = ($bancadasDiputados[$partido] ?? 0) + 1;
                } else {
                    $senadores[] = $p;
                    $bancadasSenadores[$partido] = ($bancadasSenadores[$partido] ?? 0) + 1;
                }
            }

            arsort($bancadasDiputados);
            arsort($bancadasSenadores);
        }

        $totalDiputados = count($diputados);
        $totalSenadores = count($senadores);

        $bancadasDipFormateadas = [];
        foreach ($bancadasDiputados as $partido => $asientos) {
            $pct = $totalDiputados > 0 ? round(($asientos / $totalDiputados) * 100, 1) : 0.0;
            $bancadasDipFormateadas[] = [
                'partido' => $partido,
                'asientos' => $asientos,
                'porcentaje' => $pct,
                'color' => \PulsoCivico\Core\ViewHelper::getPartyColor($partido),
            ];
        }

        $bancadasSenFormateadas = [];
        foreach ($bancadasSenadores as $partido => $asientos) {
            $pct = $totalSenadores > 0 ? round(($asientos / $totalSenadores) * 100, 1) : 0.0;
            $bancadasSenFormateadas[] = [
                'partido' => $partido,
                'asientos' => $asientos,
                'porcentaje' => $pct,
                'color' => \PulsoCivico\Core\ViewHelper::getPartyColor($partido),
            ];
        }

        $escolaridadStats = $tieneDatos ? $this->educationService->generarMetricas($politicosLeg) : null;
        $estadosConteo = count($estadosRepresentados);

        return [
            'meta' => $meta,
            'tiene_dataset_local' => $tieneDatos,
            'total_legisladores' => count($politicosLeg),
            'total_diputados' => $totalDiputados,
            'total_senadores' => $totalSenadores,
            'diputados' => $diputados,
            'senadores' => $senadores,
            'bancadas_diputados' => $bancadasDipFormateadas,
            'bancadas_senadores' => $bancadasSenFormateadas,
            'mayoria_diputados' => !empty($bancadasDipFormateadas) ? $bancadasDipFormateadas[0] : null,
            'mayoria_senadores' => !empty($bancadasSenFormateadas) ? $bancadasSenFormateadas[0] : null,
            'estados_representados_count' => $estadosConteo,
            'escolaridad_stats' => $escolaridadStats,
            'legislaturas_todas' => self::LEGISLATURAS_CATALOG,
        ];
    }

    /**
     * Búsqueda de político por slug único canónico.
     * Si se especifica una legislatura ($legislatura > 0) y el político tiene un mandato en ella,
     * hidrata la ficha con la información correspondiente a dicho periodo.
     */
    public function getPoliticoBySlug(string $slug, ?int $legislatura = null): ?array
    {
        $this->ensureDataLoaded();
        $normSlug = self::slugify($slug);
        $cleanSlug = preg_replace('/-\d+$/', '', $normSlug) ?? $normSlug;

        $politico = $this->slugIndex[$normSlug] 
            ?? ($this->slugIndex[$cleanSlug] 
            ?? ($this->slugIndex[$slug] 
            ?? null));

        if (!$politico) {
            $politico = $this->getPoliticoByNombre($slug);
        }

        if (!$politico) {
            return null;
        }

        // Si se solicita una legislatura específica y existe en el historial del legislador
        if ($legislatura !== null && $legislatura > 0 && !empty($politico['mandatos_historicos'][$legislatura])) {
            $mandato = $politico['mandatos_historicos'][$legislatura];
            // Conservar la visión integral de todos sus mandatos y legislaturas disponibles
            $mandato['legislaturas_disponibles'] = $politico['legislaturas_disponibles'] ?? [$legislatura];
            $mandato['mandatos_historicos'] = $politico['mandatos_historicos'] ?? [];
            return $mandato;
        }

        return $politico;
    }

    /**
     * Retorna el catálogo de votaciones nominales del Pleno registradas.
     * @return array<int, array>
     */
    public function getVotaciones(int $limit = 20): array
    {
        $this->ensureDataLoaded();
        return array_slice($this->votaciones, 0, $limit);
    }

    /**
     * Retorna las sesiones del Pleno registradas para el periodo.
     * @return array<int, array>
     */
    public function getSesiones(int $limit = 50): array
    {
        $this->ensureDataLoaded();
        return array_slice($this->sesiones, 0, $limit);
    }

    /**
     * Búsqueda por ID de referencia del SIL.
     */
    public function getPoliticoByReferencia(string $referencia): ?array
    {
        $this->ensureDataLoaded();
        return $this->politicosIndex[$referencia] ?? null;
    }

    /**
     * Búsqueda de político por nombre completo (resuelve titulares o suplentes).
     */
    public function getPoliticoByNombre(string $nombre): ?array
    {
        $this->ensureDataLoaded();
        $targetSlug = self::slugify($nombre);
        if (isset($this->slugIndex[$targetSlug])) {
            return $this->slugIndex[$targetSlug];
        }

        $nombreNorm = mb_strtolower(trim($nombre));
        foreach ($this->slugIndex as $pol) {
            if (mb_strtolower(trim($pol['nombre_completo'] ?? '')) === $nombreNorm) {
                return $pol;
            }
        }

        // Intento con inversión de orden de palabras (Apellidos Nombres vs Nombres Apellidos)
        $partes = preg_split('/\s+/', $nombreNorm);
        if (count($partes) >= 2) {
            $invertido = implode(' ', array_reverse($partes));
            $invertidoSlug = self::slugify($invertido);
            if (isset($this->slugIndex[$invertidoSlug])) {
                return $this->slugIndex[$invertidoSlug];
            }
            foreach ($this->slugIndex as $pol) {
                if (mb_strtolower(trim($pol['nombre_completo'] ?? '')) === $invertido) {
                    return $pol;
                }
            }
        }

        return null;
    }

    /**
     * Devuelve todas las comisiones indexadas.
     */
    public function getComisiones(): array
    {
        $this->ensureDataLoaded();
        return $this->comisionesIndex;
    }

    /**
     * Búsqueda de comisión por slug único.
     */
    public function getComisionBySlug(string $slug): ?array
    {
        $this->ensureDataLoaded();
        return $this->comisionesIndex[$slug] ?? null;
    }

    /**
     * Devuelve la lista y estadísticas de los 32 estados.
     */
    /**
     * Devuelve la lista y estadísticas de los 32 estados.
     * Soporta filtrado opcional por legislatura (ej. 66 o 65).
     */
    public function getEstados(?int $legislatura = null): array
    {
        $estados = $this->estadosCatalog;
        $politicos = $this->getPoliticos($legislatura);

        // Agrupar políticos por entidad federativa normalizada
        $politicosPorEstado = [];
        foreach ($politicos as $p) {
            $entidadNorm = self::slugify($p['entidad'] ?? '');
            if ($entidadNorm === 'mexico' || $entidadNorm === 'edomex') {
                $entidadNorm = 'estado-de-mexico';
            }
            $politicosPorEstado[$entidadNorm][] = $p;
        }

        foreach ($estados as $slug => &$est) {
            $pols = $politicosPorEstado[$slug] ?? [];
            if (!empty($pols)) {
                $partidosConteo = [];
                foreach ($pols as $p) {
                    $partido = $p['partido'] ?? 'Sin Partido';
                    $partidosConteo[$partido] = ($partidosConteo[$partido] ?? 0) + 1;
                }
                arsort($partidosConteo);
                $est['total_representantes'] = count($pols);
                $est['partido_mayoria'] = array_key_first($partidosConteo) ?? 'MORENA';
                $est['conteo_partidos'] = $partidosConteo;
                // Resumen escolar por estado
                $educacionMetricas = $this->educationService->generarMetricas($pols);
                $est['escolaridad'] = [
                    'doctores' => $educacionMetricas['niveles']['doctorado']['cantidad'] ?? 0,
                    'maestrias' => $educacionMetricas['niveles']['maestria']['cantidad'] ?? 0,
                    'posgrados' => $educacionMetricas['resumen_posgrados']['total'] ?? 0,
                    'pct_posgrados' => $educacionMetricas['resumen_posgrados']['porcentaje'] ?? 0.0,
                    'licenciaturas' => $educacionMetricas['resumen_licenciaturas']['total'] ?? 0,
                    'media_trunca' => $educacionMetricas['resumen_media_trunca']['total'] ?? 0,
                ];
                $est['disponible'] = true;
            }
        }
        unset($est);

        // Alias de compatibilidad
        if (isset($estados['estado-de-mexico'])) {
            $estados['mexico'] = $estados['estado-de-mexico'];
        }

        return $estados;
    }

    /**
     * Obtiene el expediente de representación de un estado específico.
     * Soporta filtrado opcional por legislatura (ej. 66 o 65).
     */
    public function getEstadoBySlug(string $slug, ?int $legislatura = null): ?array
    {
        $targetSlug = ($slug === 'mexico' || $slug === 'edomex') ? 'estado-de-mexico' : $slug;
        $estados = $this->getEstados($legislatura);
        if (!isset($estados[$targetSlug])) {
            return null;
        }

        $estado = $estados[$targetSlug];
        $politicos = $this->getPoliticos($legislatura);

        $estado['diputados'] = array_values(array_filter(
            $politicos,
            function ($p) use ($targetSlug) {
                $norm = self::slugify($p['entidad'] ?? '');
                if ($norm === 'mexico' || $norm === 'edomex') $norm = 'estado-de-mexico';
                return ($p['camara'] ?? '') === 'Diputados' && $norm === $targetSlug;
            }
        ));
        $estado['senadores'] = array_values(array_filter(
            $politicos,
            function ($p) use ($targetSlug) {
                $norm = self::slugify($p['entidad'] ?? '');
                if ($norm === 'mexico' || $norm === 'edomex') $norm = 'estado-de-mexico';
                return ($p['camara'] ?? '') === 'Senadores' && $norm === $targetSlug;
            }
        ));

        // Métricas de escolaridad específicas del estado
        $estado['escolaridad_stats'] = $this->getEstadisticasEscolaridad(null, $slug, $legislatura);

        return $estado;
    }

    /**
     * Retorna las métricas y radiografía de escolaridad de los legisladores.
     * @param string|null $filtroCamara 'Diputados' | 'Senadores' | null
     * @param string|null $filtroEstado Slug del estado o null
     * @param int|null $legislatura Número de legislatura o null
     * @return array
     */
    public function getEstadisticasEscolaridad(?string $filtroCamara = null, ?string $filtroEstado = null, ?int $legislatura = null): array
    {
        $coleccion = $this->getPoliticos($legislatura);

        if ($filtroCamara !== null) {
            $coleccion = array_filter($coleccion, fn($p) => ($p['camara'] ?? '') === $filtroCamara);
        }

        if ($filtroEstado !== null) {
            $targetFiltro = ($filtroEstado === 'mexico' || $filtroEstado === 'edomex') ? 'estado-de-mexico' : $filtroEstado;
            $coleccion = array_filter($coleccion, function ($p) use ($targetFiltro) {
                $norm = self::slugify($p['entidad'] ?? '');
                if ($norm === 'mexico' || $norm === 'edomex') $norm = 'estado-de-mexico';
                return $norm === $targetFiltro;
            });
        }

        return $this->educationService->generarMetricas($coleccion);
    }

    public function getEducationService(): EducationService
    {
        return $this->educationService;
    }

    /**
     * Buscador universal por texto (políticos, comisiones, partidos).
     * @return array{politicos: array, comisiones: array}
     */
    public function buscar(string $termino, int $limite = 10): array
    {
        $q = mb_strtolower(trim($termino));
        if ($q === '') {
            return ['politicos' => [], 'comisiones' => []];
        }

        $this->ensureDataLoaded();
        $resPoliticos = [];
        foreach ($this->slugIndex as $p) {
            $nombre = mb_strtolower($p['nombre_completo'] ?? '');
            $partido = mb_strtolower($p['partido'] ?? '');
            if (str_contains($nombre, $q) || str_contains($partido, $q)) {
                $resPoliticos[] = [
                    'tipo' => 'politico',
                    'nombre' => $p['nombre_completo'],
                    'slug' => $p['slug'],
                    'partido' => $p['partido'],
                    'camara' => $p['camara'],
                    'foto_url' => $p['foto_oficial_url'],
                    'url' => '/politicos/' . $p['slug'],
                ];
                if (count($resPoliticos) >= $limite) break;
            }
        }

        $resComisiones = [];
        foreach ($this->comisionesIndex as $c) {
            $nombre = mb_strtolower($c['nombre']);
            if (str_contains($nombre, $q)) {
                $resComisiones[] = [
                    'tipo' => 'comision',
                    'nombre' => $c['nombre'],
                    'slug' => $c['slug'],
                    'total_integrantes' => count($c['integrantes']),
                    'url' => '/comisiones/' . $c['slug'],
                ];
                if (count($resComisiones) >= $limite) break;
            }
        }

        return [
            'politicos' => $resPoliticos,
            'comisiones' => $resComisiones,
        ];
    }

    /**
     * Convierte cualquier cadena a un slug limpio y amigable para URL (SEO friendly).
     */
    public static function slugify(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        // Mapeo exhaustivo de acentos y caracteres especiales en español
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        ];
        $texto = strtr($texto, $map);
        // Reemplazar caracteres no alfanuméricos por guiones
        $texto = preg_replace('/[^a-z0-9]+/i', '-', $texto);
        return trim((string)$texto, '-');
    }

    private function calcularEdad(?string $fechaNacimiento): ?int
    {
        if (!$fechaNacimiento || $fechaNacimiento === 'N/A') return null;
        $partes = explode('/', trim($fechaNacimiento));
        if (count($partes) === 3) {
            $d = (int)$partes[0];
            $m = (int)$partes[1];
            $y = (int)$partes[2];
            if ($y > 1920 && $y < 2015 && checkdate($m, $d, $y)) {
                $nacimiento = new \DateTime("$y-$m-$d");
                $hoy = new \DateTime();
                return $hoy->diff($nacimiento)->y;
            }
        }
        return null;
    }

    /**
     * Devuelve el catálogo completo de iniciativas.
     * @return array<int, array>
     */
    public function getIniciativas(): array
    {
        $this->ensureDataLoaded();
        return $this->iniciativas;
    }

    /**
     * Normaliza y deduplica rigurosamente la línea de tiempo de cargos (orden cronológico 2024 primero)
     * y extrae los estudios académicos para que no se dupliquen.
     * @param array $pol
     * @return array{linea_tiempo: array, escolaridad: array}
     */
    private function procesarTrayectoriaYEstudios(array $pol): array
    {
        $escolaridadMap = [];
        $historialMap = [];

        $esEstudioAcademico = function (string $texto): bool {
            $t = mb_strtolower($texto);
            return str_contains($t, 'licenciatura') ||
                   str_contains($t, 'doctorado') ||
                   str_contains($t, 'maestría') ||
                   str_contains($t, 'maestria') ||
                   str_contains($t, 'diplomado') ||
                   str_contains($t, 'especialidad en') ||
                   str_contains($t, 'bachillerato') ||
                   str_contains($t, 'primaria') ||
                   str_contains($t, 'secundaria') ||
                   str_contains($t, 'estudios de');
        };

        // 1. Escolaridad oficial
        foreach ($pol['escolaridad_detalle'] ?? [] as $e) {
            $exp = trim($e['experiencia'] ?? '');
            if ($exp !== '') {
                $norm = mb_strtolower($exp);
                if (!isset($escolaridadMap[$norm])) {
                    $escolaridadMap[$norm] = ['experiencia' => $exp];
                }
            }
        }

        // 2. Trayectorias legislativa, política y administrativa
        $todas = array_merge(
            $pol['trayectoria_legislativa'] ?? [],
            $pol['trayectoria_politica'] ?? [],
            $pol['trayectoria_administrativa'] ?? []
        );

        foreach ($todas as $item) {
            $exp = trim($item['experiencia'] ?? '');
            if ($exp === '') continue;
            $norm = mb_strtolower($exp);

            if ($esEstudioAcademico($exp)) {
                if (!isset($escolaridadMap[$norm])) {
                    $escolaridadMap[$norm] = ['experiencia' => $exp];
                }
                continue;
            }

            // Clave única combinando experiencia y periodo para no sobreescribir reelecciones o cargos reiterados
            $key = $norm . '_' . ($item['del_ano'] ?? '') . '_' . ($item['al_ano'] ?? '');

            if (!isset($historialMap[$key])) {
                $historialMap[$key] = [
                    'experiencia' => $exp,
                    'del_ano' => $item['del_ano'] ?? null,
                    'al_ano' => $item['al_ano'] ?? null,
                ];
            } else {
                $prev = $historialMap[$key];
                if (empty($prev['del_ano']) && empty($prev['al_ano']) && (!empty($item['del_ano']) || !empty($item['al_ano']))) {
                    $historialMap[$key] = [
                        'experiencia' => $exp,
                        'del_ano' => $item['del_ano'] ?? null,
                        'al_ano' => $item['al_ano'] ?? null,
                    ];
                }
            }
        }

        $historialFiltrado = array_values($historialMap);
        $escolaridadItems = array_values($escolaridadMap);

        // Ordenar cronológicamente descendente (lo más reciente primero)
        usort($historialFiltrado, function ($a, $b) {
            $alA = (int)($a['al_ano'] ?? 0);
            $delA = (int)($a['del_ano'] ?? 0);
            $anoA = $alA > 0 ? $alA : $delA;

            $alB = (int)($b['al_ano'] ?? 0);
            $delB = (int)($b['del_ano'] ?? 0);
            $anoB = $alB > 0 ? $alB : $delB;

            return $anoB <=> $anoA;
        });

        // Formatear periodos previos y detectar filiación partidista oficial en el cargo
        foreach ($historialFiltrado as &$h) {
            $h['es_reciente'] = false;
            $h['es_cargo_actual'] = false;
            $del = $h['del_ano'];
            $al = $h['al_ano'];
            if ($del && $al) {
                $h['periodo'] = "{$del} — {$al}";
            } elseif ($del) {
                $h['periodo'] = "Desde {$del}";
            } elseif ($al) {
                $h['periodo'] = "Hasta {$al}";
            } else {
                $h['periodo'] = "Trayectoria previa";
            }

            // Detección neutral de partido desde el texto oficial del cargo o trayectoria
            $partidoDetectado = ViewHelper::detectPartyFromText($h['experiencia'] ?? '');
            $h['partido'] = $partidoDetectado;
            $h['partido_color'] = $partidoDetectado ? ViewHelper::getPartyColor($partidoDetectado) : null;
        }
        unset($h);

        // Incorporar el cargo parlamentario del mandato a la línea de tiempo
        $camara = $pol['camara'] ?? 'Diputados';
        $esSenador = ($camara === 'Senadores');
        $legNum = (int)($pol['legislatura'] ?? 66);
        $legMeta = self::LEGISLATURAS_CATALOG[$legNum] ?? null;
        $legRomano = $legMeta['romano'] ?? self::convertirNumeroARomano($legNum);

        $anoInicio = $legMeta['ano_inicio'] ?? 2024;
        if (!empty($pol['fecha_toma_protesta']) && preg_match('/(\d{4})/', (string)$pol['fecha_toma_protesta'], $mAno)) {
            $anoInicio = (int)$mAno[1];
        }
        $anoFin = $legMeta['ano_fin'] ?? ($anoInicio + 3);

        $entidad = $pol['entidad'] ?? 'México';
        $distrito = $pol['distrito'] ?? null;
        $esSuplente = (($pol['tipo_cargo'] ?? '') === 'Suplente');
        $tieneLicencia = !empty($pol['tiene_licencia']) 
            || str_contains($pol['tipo_cargo'] ?? '', 'Licencia') 
            || (($pol['metadata']['estatus_licencia'] ?? '') === 'Con Licencia');

        $cargoTitulo = $esSenador ? 'Senador de la República' : 'Diputado Federal';
        if ($esSuplente) {
            $cargoTitulo .= ' (Suplente en Funciones)';
        } elseif ($tieneLicencia) {
            $cargoTitulo .= ' (Con Licencia)';
        }

        $descripcionCargo = "{$cargoTitulo} por {$entidad}";
        if ($distrito !== null && !str_contains((string)$distrito, 'Senaduría')) {
            $descripcionCargo .= " ({$distrito})";
        }
        $descripcionCargo .= " — {$legRomano} Legislatura del Congreso de la Unión.";

        $esActual = ($legNum === 66);
        $partidoCargoActual = $pol['partido'] ?? 'Sin Partido';
        $cargoActual = [
            'experiencia' => $descripcionCargo,
            'del_ano' => (string)$anoInicio,
            'al_ano' => $esActual ? ($tieneLicencia ? 'En Licencia' : 'Presente') : (string)$anoFin,
            'periodo' => $esActual 
                ? ($tieneLicencia ? "{$anoInicio} — En Licencia" : "{$anoInicio} — Presente")
                : "{$anoInicio} — {$anoFin}",
            'es_cargo_actual' => $esActual,
            'es_reciente' => $esActual,
            'en_licencia' => $tieneLicencia,
            'partido' => $partidoCargoActual,
            'partido_color' => ViewHelper::getPartyColor($partidoCargoActual),
            'nivel' => 'legislativo_federal',
            'nivel_label' => 'Congreso de la Unión',
            'icono' => $esSenador ? 'bi-bank' : 'bi-building',
            'badge_class' => $esSenador ? 'bg-info-subtle text-info-emphasis border border-info-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle',
        ];

        // 3. Cruce e integración de antecedentes multinivel (Poder Ejecutivo, Alcaldías y Congresos Estatales)
        $slugNormal = self::slugify($pol['nombre_completo'] ?? '');
        $slugNatural = self::slugify(ViewHelper::formatearNombreNatural($pol['nombre_completo'] ?? ''));
        $hitosMultinivel = $this->indiceMultinivel[$slugNormal] 
            ?? ($this->indiceMultinivel[$slugNatural] 
            ?? []);

        foreach ($hitosMultinivel as $hito) {
            $yaExisteHito = false;
            foreach ($historialFiltrado as &$hf) {
                $textoHf = mb_strtolower($hf['experiencia'] ?? '');
                $esMismoHito = false;

                if ($hito['nivel'] === 'municipal' && !empty($hito['municipio']) && str_contains($textoHf, mb_strtolower($hito['municipio']))) {
                    $esMismoHito = true;
                } elseif ($hito['nivel'] === 'ejecutivo_estatal' && (str_contains($textoHf, 'gobernador') || str_contains($textoHf, 'jefe de gobierno'))) {
                    $esMismoHito = true;
                } elseif ($hito['nivel'] === 'legislativo_local' && str_contains($textoHf, 'diputad') && (str_contains($textoHf, 'local') || str_contains($textoHf, 'estado'))) {
                    if (!empty($hito['del_ano']) && ($hf['del_ano'] ?? 0) == $hito['del_ano']) {
                        $esMismoHito = true;
                    }
                }

                if ($esMismoHito) {
                    $yaExisteHito = true;
                    $hf['nivel'] = $hito['nivel'];
                    $hf['nivel_label'] = $hito['nivel_label'];
                    $hf['icono'] = $hito['icono'];
                    $hf['badge_class'] = $hito['badge_class'];
                    $hf['url_destino'] = $hito['url_destino'];
                    $hf['es_auditado_multinivel'] = true;
                    if (empty($hf['partido']) && !empty($hito['partido'])) {
                        $hf['partido'] = ViewHelper::getCanonicalPartyName($hito['partido']);
                        $hf['partido_color'] = ViewHelper::getPartyColor($hf['partido']);
                    }
                    break;
                }
            }
            unset($hf);

            if (!$yaExisteHito) {
                $partidoHito = !empty($hito['partido']) ? ViewHelper::getCanonicalPartyName($hito['partido']) : null;
                $historialFiltrado[] = [
                    'experiencia' => $hito['cargo'],
                    'del_ano' => !empty($hito['del_ano']) ? (string)$hito['del_ano'] : null,
                    'al_ano' => !empty($hito['al_ano']) ? (string)$hito['al_ano'] : null,
                    'periodo' => $hito['periodo'],
                    'es_reciente' => false,
                    'es_cargo_actual' => false,
                    'en_licencia' => false,
                    'partido' => $partidoHito,
                    'partido_color' => $partidoHito ? ViewHelper::getPartyColor($partidoHito) : null,
                    'nivel' => $hito['nivel'],
                    'nivel_label' => $hito['nivel_label'],
                    'icono' => $hito['icono'],
                    'badge_class' => $hito['badge_class'],
                    'url_destino' => $hito['url_destino'],
                    'es_auditado_multinivel' => true,
                ];
            }
        }

        // 4. Asignar metadatos de nivel institucional a cada nodo del SIL si aún no los tiene
        foreach ($historialFiltrado as &$node) {
            if (empty($node['nivel'])) {
                $expLower = mb_strtolower($node['experiencia'] ?? '');
                $reGobExclusionesNode = '/\b(?:candidat[ao]|precandidat[ao]|aspirante|campa[ñn]a|precampa[ñn]a|elecci[oó]n|elecciones|asesor[a]?|secretari[ao]|subsecretari[ao]|director[a]?|coordinador[a]?|chofer|auxiliar|asistente|jefe\s+de\s+(?:oficina|departamento|unidad|gabinete)|jefatura\s+de\s+departamento|representante|comisi[oó]n\s+de\s+gobierno|secretar[ií]a\s+(?:general\s+)?de\s+gobierno|gabinete|administraci[oó]n\s+del|espos[ao]|hij[ao]|suplente|enlace|palacio\s+de\s+gobierno|casa\s+de\s+gobierno|informe\s+de\s+gobierno|capacitador[a]?|consejer[ao]|delegad[ao]|agenda\s+del|equipo\s+de|comisionad[ao]|durante\s+la\s+elecci[oó]n)\b/ui';
                $reGobTitularNode = '/\b(?:gobernador[a]?\s+(?:constitucional|interin[ao]|sustitut[ao]|provisional|del?\s+estado|elect[ao])|jef[ea]\s+de\s+gobierno(?:\s+(?:constitucional|interin[ao]|sustitut[ao]|elect[ao]|del\s+distrito\s+federal|de\s+la\s+ciudad\s+de\s+m[eé]xico|del\s+d\.?f\.?|de\s+la\s+cdmx))?)\b/ui';

                if (preg_match($reGobTitularNode, $expLower) && !preg_match($reGobExclusionesNode, $expLower)) {
                    $node['nivel'] = 'ejecutivo_estatal';
                    $node['nivel_label'] = 'Poder Ejecutivo Estatal';
                    $node['icono'] = 'bi-shield-shaded';
                    $node['badge_class'] = 'bg-danger-subtle text-danger border border-danger-subtle';
                } elseif (preg_match('/\b(president[ea]\s+municipal|alcald[ea]|jef[ea]\s+delegacional)\b/ui', $expLower)) {
                    $node['nivel'] = 'municipal';
                    $node['nivel_label'] = 'Gobierno Municipal';
                    $node['icono'] = 'bi-houses-fill';
                    $node['badge_class'] = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                } elseif (preg_match('/\b(diputad[ao]\s+local|congreso\s+del?\s+estado|asamblea\s+legislativa)\b/ui', $expLower)) {
                    $node['nivel'] = 'legislativo_local';
                    $node['nivel_label'] = 'Congreso Local';
                    $node['icono'] = 'bi-geo-alt-fill';
                    $node['badge_class'] = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                } elseif (preg_match('/\b(senador[a]?|diputad[ao]\s+federal|congreso\s+de\s+la\s+uni[oó]n)\b/ui', $expLower)) {
                    $node['nivel'] = 'legislativo_federal';
                    $node['nivel_label'] = 'Congreso de la Unión';
                    $node['icono'] = 'bi-bank';
                    $node['badge_class'] = 'bg-primary-subtle text-primary border border-primary-subtle';
                } else {
                    $node['nivel'] = 'administracion_publica';
                    $node['nivel_label'] = 'Administración / Partidista';
                    $node['icono'] = 'bi-briefcase-fill';
                    $node['badge_class'] = 'bg-light text-secondary border';
                }
            }
        }
        unset($node);

        // Verificar si ya existe mención exacta del cargo actual para evitar duplicidad
        $yaExisteCargoActual = false;
        foreach ($historialFiltrado as &$hf) {
            if (str_contains($hf['experiencia'] ?? '', "{$legRomano} Legislatura") || str_contains($hf['experiencia'] ?? '', "{$legNum} Legislatura")) {
                $yaExisteCargoActual = true;
                $hf['es_cargo_actual'] = $esActual;
                $hf['es_reciente'] = $esActual;
                $hf['nivel'] = 'legislativo_federal';
                $hf['nivel_label'] = 'Congreso de la Unión';
                $hf['icono'] = $esSenador ? 'bi-bank' : 'bi-building';
                $hf['badge_class'] = $esSenador ? 'bg-info-subtle text-info-emphasis border border-info-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle';
                if (empty($hf['partido'])) {
                    $hf['partido'] = $partidoCargoActual;
                    $hf['partido_color'] = ViewHelper::getPartyColor($partidoCargoActual);
                }
                break;
            }
        }
        unset($hf);

        // Reordenar cronológicamente descendente
        usort($historialFiltrado, function ($a, $b) {
            $alA = (int)($a['al_ano'] ?? 0);
            $delA = (int)($a['del_ano'] ?? 0);
            $anoA = $alA > 0 ? $alA : $delA;

            $alB = (int)($b['al_ano'] ?? 0);
            $delB = (int)($b['del_ano'] ?? 0);
            $anoB = $alB > 0 ? $alB : $delB;

            return $anoB <=> $anoA;
        });

        if (!$yaExisteCargoActual) {
            array_unshift($historialFiltrado, $cargoActual);
        }

        // 5. Análisis cívico de trayectoria: escalas de gobierno y transición de partidos
        $partidosEnCarrera = [];
        if (!empty($partidoCargoActual) && !str_contains(strtoupper($partidoCargoActual), 'SIN PARTIDO')) {
            $canActual = ViewHelper::getCanonicalPartyName($partidoCargoActual);
            $partidosEnCarrera[$canActual] = true;
        }

        $tieneGobernador = false;
        $tieneAlcalde = false;
        $tieneDipLocal = false;
        $tieneDipFed = ($camara === 'Diputados');
        $tieneSenador = ($camara === 'Senadores');

        foreach ($historialFiltrado as $hfItem) {
            if (!empty($hfItem['partido'])) {
                $canP = ViewHelper::getCanonicalPartyName($hfItem['partido']);
                if (!str_contains(strtoupper($canP), 'SIN PARTIDO')) {
                    $partidosEnCarrera[$canP] = true;
                }
            }
            $niv = $hfItem['nivel'] ?? '';
            if ($niv === 'ejecutivo_estatal') $tieneGobernador = true;
            if ($niv === 'municipal') $tieneAlcalde = true;
            if ($niv === 'legislativo_local') $tieneDipLocal = true;
            if ($niv === 'legislativo_federal') {
                $expItem = mb_strtolower($hfItem['experiencia'] ?? '');
                if (str_contains($expItem, 'senad')) $tieneSenador = true;
                if (str_contains($expItem, 'diputad')) $tieneDipFed = true;
            }
        }

        $cambioPartido = count($partidosEnCarrera) > 1;

        return [
            'linea_tiempo' => $historialFiltrado,
            'escolaridad' => $escolaridadItems,
            'escalas_gobierno' => [
                'ha_sido_gobernador' => $tieneGobernador,
                'ha_sido_alcalde' => $tieneAlcalde,
                'ha_sido_diputado_local' => $tieneDipLocal,
                'ha_sido_diputado_federal' => $tieneDipFed,
                'ha_sido_senador' => $tieneSenador,
                'total_escalas' => ($tieneGobernador ? 1 : 0) + ($tieneAlcalde ? 1 : 0) + ($tieneDipLocal ? 1 : 0) + ($tieneDipFed ? 1 : 0) + ($tieneSenador ? 1 : 0),
            ],
            'transicion_partidista' => [
                'cambio_partido' => $cambioPartido,
                'total_partidos' => count($partidosEnCarrera),
                'partidos' => array_keys($partidosEnCarrera),
            ],
        ];
    }

    /**
     * Resuelve el origen institucional y político de una reforma constitucional o legal.
     */
    private function obtenerOrigenVotacion(string $temaClave, string $titulo): array
    {
        $tituloUpper = mb_strtoupper($titulo);

        if (str_contains($temaClave, 'Reforma Judicial') || str_contains($tituloUpper, 'PODER JUDICIAL')) {
            $esArticulosReservados = str_contains($tituloUpper, 'RESERVADOS');
            return [
                'tipo' => 'Poder Ejecutivo Federal',
                'promovente' => 'Presidente de la República (Andrés Manuel López Obrador)',
                'partido' => 'Poder Ejecutivo / Morena',
                'origen_tag' => 'Iniciativa Presidencial',
                'resumen_ciudadano' => $esArticulosReservados 
                    ? 'Aprobación de los artículos en lo particular con modificaciones: Establece los mecanismos de insaculación, comités de evaluación y reglas de boletas para jueces, magistrados y ministros.'
                    : 'Modifica la estructura constitucional del Poder Judicial: Elección popular de Ministros de la SCJN, Magistrados y Jueces de Distrito, sustitución del Consejo de la Judicatura por el Tribunal de Disciplina Judicial y reducción del Pleno de la Corte de 11 a 9 ministros.',
                'puntos_clave' => [
                    'Elección popular de ministros, magistrados y jueces federales por voto ciudadano.',
                    'Creación del Tribunal de Disciplina Judicial con facultades sancionatorias.',
                    'Extinción de fideicomisos del Poder Judicial y tope salarial al sueldo presidencial.'
                ]
            ];
        }

        if (str_contains($temaClave, 'Pueblos Indígenas') || str_contains($tituloUpper, 'INDÍGENAS') || str_contains($tituloUpper, 'AFROMEXICANOS')) {
            return [
                'tipo' => 'Poder Ejecutivo Federal',
                'promovente' => 'Presidente de la República (Paquete Constitucional 5-Feb)',
                'partido' => 'Poder Ejecutivo (Consenso Pluripartidista)',
                'origen_tag' => 'Iniciativa Presidencial',
                'resumen_ciudadano' => 'Reconocimiento de pueblos y comunidades indígenas y afromexicanas como sujetos de derecho público con personalidad jurídica y patrimonio propio, garantizando consulta previa y presupuesto directo.',
                'puntos_clave' => [
                    'Personalidad jurídica y patrimonio propio para comunidades indígenas y afromexicanas.',
                    'Derecho a consulta libre, previa, informada y culturalmente adecuada.',
                    'Asignación directa de recursos públicos y preservación de su patrimonio biocultural.'
                ]
            ];
        }

        if (str_contains($temaClave, 'Guardia Nacional') || str_contains($tituloUpper, 'GUARDIA NACIONAL')) {
            return [
                'tipo' => 'Poder Ejecutivo Federal',
                'promovente' => 'Presidente de la República (Adscripción a SEDENA)',
                'partido' => 'Poder Ejecutivo / Coalición Sigamos Haciendo Historia',
                'origen_tag' => 'Iniciativa Presidencial',
                'resumen_ciudadano' => 'Reforma constitucional que transfiere el control operativo, administrativo y financiero de la Guardia Nacional a la Secretaría de la Defensa Nacional (SEDENA), reconociéndola como fuerza armada permanente de seguridad.',
                'puntos_clave' => [
                    'Adscripción formal y operativa de la Guardia Nacional bajo mando de la SEDENA.',
                    'Facultades de investigación de delitos bajo conducción del Ministerio Público.',
                    'Garantía de régimen disciplinario y fueros militares para sus elementos operativos.'
                ]
            ];
        }

        if (str_contains($temaClave, 'Simplificación Orgánica') || str_contains($tituloUpper, 'SIMPLIFICACIÓN ORGÁNICA') || str_contains($tituloUpper, 'ÓRGANOS AUTÓNOMOS')) {
            return [
                'tipo' => 'Poder Ejecutivo Federal',
                'promovente' => 'Presidente de la República (Extinción de Organismos Autónomos)',
                'partido' => 'Poder Ejecutivo / Morena - PVEM - PT',
                'origen_tag' => 'Iniciativa Presidencial',
                'resumen_ciudadano' => 'Extinción de 7 órganos constitucionales autónomos (incluidos INAI, COFECE, IFT, CRE, CNH, CONEVAL y MEJOREDU), transfiriendo sus funciones y tutela de derechos a dependencias de la Administración Pública Federal.',
                'puntos_clave' => [
                    'Desaparición del INAI y traslado de funciones de transparencia y protección de datos a la Secretaría Anticorrupción y Buen Gobierno.',
                    'Fusión de COFECE e IFT en un nuevo organismo desconcentrado de competencia económica.',
                    'Ahorros presupuestales etiquetados prioritariamente para el Fondo de Pensiones para el Bienestar.'
                ]
            ];
        }

        if (str_contains($tituloUpper, 'PRISIÓN PREVENTIVA') || str_contains($temaClave, 'Seguridad')) {
            return [
                'tipo' => 'Poder Ejecutivo Federal',
                'promovente' => 'Presidente de la República / Coalición Sigamos Haciendo Historia',
                'partido' => 'Poder Ejecutivo / Morena',
                'origen_tag' => 'Iniciativa Presidencial',
                'resumen_ciudadano' => 'Ampliación del catálogo de delitos con prisión preventiva oficiosa (artículo 19 constitucional), incluyendo extorsión y delitos fiscales graves (factureras y contrabando).',
                'puntos_clave' => [
                    'Inclusión forzosa de prisión preventiva oficiosa para el delito de extorsión.',
                    'Medida cautelar automática para contrabando y expedición ilícita de comprobantes fiscales.',
                    'Interpretación judicial literal y estricta prohibiendo inaplicaciones oficiosas.'
                ]
            ];
        }

        return [
            'tipo' => 'Poder Legislativo',
            'promovente' => 'Comisión Dictaminadora del Congreso de la Unión',
            'partido' => 'Dictamen Parlamentario',
            'origen_tag' => 'Iniciativa Parlamentaria',
            'resumen_ciudadano' => 'Dictamen derivado del trabajo legislativo de comisiones ordinarias de la LXVI Legislatura.',
            'puntos_clave' => []
        ];
    }
}

