<?php
declare(strict_types=1);

namespace PulsoCivico\Controllers;

use PulsoCivico\Core\Router;
use PulsoCivico\Core\PageCache;
use PulsoCivico\Services\DataService;

/**
 * WebController: Despacha las vistas canónicas optimizadas para SEO y las APIs internas.
 */
class WebController
{
    private DataService $dataService;

    public function __construct(?DataService $dataService = null)
    {
        $this->dataService = $dataService ?? new DataService();
    }

    /**
     * Home: Hero, Buscador Central y Mapa Interactivo de México.
     */
    public function home(): void
    {
        $numLeg = isset($_GET['leg']) ? DataService::parseNumeroLegislatura((string)$_GET['leg']) : 66;
        $pageCache = new PageCache('home_leg_' . $numLeg, 21600);
        $cachedHtml = $pageCache->get();
        if ($cachedHtml !== null) {
            if (!headers_sent()) {
                header('X-Pulso-Cache: HIT');
            }
            echo $cachedHtml;
            return;
        }

        $catalog = DataService::LEGISLATURAS_CATALOG;
        $metaLeg = $catalog[$numLeg] ?? ($catalog[66] ?? [
            'numero' => $numLeg,
            'romano' => DataService::convertirNumeroARomano($numLeg),
            'periodo' => 'Periodo Constitucional',
        ]);
        $romano = $metaLeg['romano'];
        $periodo = $metaLeg['periodo'];

        $estados = $this->dataService->getEstados($numLeg);
        $comisiones = $this->dataService->getComisiones();
        $politicos = $this->dataService->getPoliticos($numLeg);
        $escolaridadStats = $this->dataService->getEstadisticasEscolaridad(null, null, $numLeg);

        $canonical = Router::url('/' . ($numLeg !== 66 ? '?leg=' . $numLeg : ''));

        $seo = [
            'title' => "El Pulso de la Política — Observatorio del Congreso Mexicano ({$romano} Legislatura)",
            'description' => "Radiografía, composición parlamentaria y trabajo de los senadores y diputados federales de México en la {$romano} Legislatura ({$periodo}).",
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'El Pulso de la Política',
                'url' => $canonical,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => Router::url('/buscar?q={search_term_string}'),
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];

        if (!headers_sent()) {
            header('X-Pulso-Cache: MISS');
        }
        ob_start();
        require dirname(__DIR__, 2) . '/views/home.php';
        $html = (string)ob_get_clean();
        $pageCache->set($html);
        echo $html;
    }

    /**
     * Ficha Canónica de Legislador (/politicos/{slug})
     */
    public function politico(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $numLeg = isset($_GET['leg']) ? DataService::parseNumeroLegislatura((string)$_GET['leg']) : 0;
        $politico = $this->dataService->getPoliticoBySlug($slug, $numLeg > 0 ? $numLeg : null);

        if (!$politico) {
            http_response_code(404);
            $homeUrl = Router::url('/');
            echo <<<HTML
            <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Legislador no encontrado</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
            <body class="bg-light d-flex align-items-center justify-content-center vh-100">
            <div class="text-center p-4 bg-white rounded-4 shadow-sm" style="max-width: 420px;">
                <h1 class="h5 fw-bold mb-2">Legislador no encontrado</h1>
                <p class="text-muted small mb-4">No se localizó ningún perfil activo con el slug <code>{$slug}</code>.</p>
                <a href="{$homeUrl}" class="btn btn-primary rounded-pill btn-sm px-4">Volver al Inicio</a>
            </div></body></html>
HTML;
            return;
        }

        $nombre = $politico['nombre_completo'];
        $camara = $politico['camara'];
        $partido = $politico['partido'];
        $entidad = $politico['entidad'];
        $politicoLeg = (int)($politico['legislatura'] ?? 66);
        $romano = DataService::convertirNumeroARomano($politicoLeg);
        $canonical = Router::url('/politicos/' . $slug . ($numLeg > 0 && $numLeg !== 66 ? '?leg=' . $numLeg : ''));

        $seo = [
            'title' => "{$nombre} ({$partido}) — {$camara} ({$romano} Legislatura) | El Pulso de la Política",
            'description' => "Expediente oficial de {$nombre}, {$camara} de la {$romano} Legislatura por {$entidad}. Bancada {$partido}, comisiones legislativas e iniciativas.",
            'canonical' => $canonical,
            'og_image' => $politico['foto_oficial_url'] ?? '',
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $nombre,
                'jobTitle' => "{$camara} de la República",
                'worksFor' => [
                    '@type' => 'GovernmentOrganization',
                    'name' => ($camara === 'Senadores') ? 'Senado de la República' : 'Cámara de Diputados',
                ],
                'memberOf' => [
                    '@type' => 'PoliticalParty',
                    'name' => $partido,
                ],
                'image' => $politico['foto_oficial_url'] ?? '',
                'url' => $canonical,
            ],
        ];

        $dataService = $this->dataService;
        $suplenteRef = !empty($politico['metadata']['suplente_referencia_sil']) ? (string)$politico['metadata']['suplente_referencia_sil'] : null;
        $suplentePolitico = $suplenteRef ? $this->dataService->getPoliticoByReferencia($suplenteRef) : null;
        if (!$suplentePolitico && !empty($politico['suplente_nombre'])) {
            $suplentePolitico = $this->dataService->getPoliticoByNombre($politico['suplente_nombre']);
        }

        $titularRef = !empty($politico['metadata']['titular_referencia_sil']) ? (string)$politico['metadata']['titular_referencia_sil'] : null;
        $titularPolitico = $titularRef ? $this->dataService->getPoliticoByReferencia($titularRef) : null;
        if (!$titularPolitico && !empty($politico['metadata']['titular_nombre'])) {
            $titularPolitico = $this->dataService->getPoliticoByNombre($politico['metadata']['titular_nombre']);
        }

        require dirname(__DIR__, 2) . '/views/politico.php';
    }

    /**
     * Directorio General de Figuras Públicas y Políticos (/politicos)
     * Soporta búsqueda reactiva y filtros combinados por legislatura, cargo histórico, estado, partido y estatus activo.
     */
    public function politicos(): void
    {
        $numLegParam = isset($_GET['leg']) && trim((string)$_GET['leg']) !== '' && trim((string)$_GET['leg']) !== 'todas'
            ? DataService::parseNumeroLegislatura((string)$_GET['leg']) 
            : null;
        
        $politicos = $this->dataService->getDirectorioPoliticos($numLegParam);
        $catalogLegislaturas = $this->dataService->getLegislaturasCatalog();
        $estadosCatalog = $this->dataService->getEstadosCatalog();
        $partidosCatalog = [];
        
        $totalPoliticos = count($politicos);
        $canonical = Router::url('/politicos' . ($numLegParam ? '?leg=' . $numLegParam : ''));

        $seo = [
            'title' => 'Directorio de Políticos y Representantes de México | El Pulso de la Política',
            'description' => "Directorio ciudadano con radiografía, trayectoria política y cargos acumulados (senadores, diputados federales, locales, alcaldes y gobernadores) de {$totalPoliticos} figuras públicas mexicanas.",
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => 'Directorio de Políticos y Legisladores de México',
                'description' => 'Directorio de figuras públicas con cargos actuales e históricos en el gobierno y poder legislativo.',
                'url' => $canonical,
            ],
        ];

        require dirname(__DIR__, 2) . '/views/politicos.php';
    }

    /**
     * Ficha de Estado (/estados/{slug})
     */
    public function estado(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $numLeg = isset($_GET['leg']) ? DataService::parseNumeroLegislatura((string)$_GET['leg']) : 66;
        $estado = $this->dataService->getEstadoBySlug($slug, $numLeg);

        if (!$estado) {
            http_response_code(404);
            $homeUrl = Router::url('/');
            echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Estado no encontrado</h2><a href='{$homeUrl}'>Volver</a></div>";
            return;
        }

        $nombre = $estado['nombre'];
        $romano = DataService::numeroALegislaturaRomano($numLeg);
        $canonical = Router::url('/estados/' . $slug . ($numLeg !== 66 ? '?leg=' . $numLeg : ''));

        $seo = [
            'title' => "Legisladores Federales de {$nombre} ({$romano} Legislatura) | El Pulso de la Política",
            'description' => "Radiografía completa de los senadores y diputados federales que representan a {$nombre} en el Congreso de la Unión durante la {$romano} Legislatura.",
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'AdministrativeArea',
                'name' => $nombre,
                'url' => $canonical,
            ],
        ];

        require dirname(__DIR__, 2) . '/views/estado.php';
    }

    /**
     * Directorio de Comisiones (/comisiones)
     */
    public function comisiones(): void
    {
        $comisiones = $this->dataService->getComisiones();
        $canonical = Router::url('/comisiones');

        $seo = [
            'title' => 'Comisiones Ordinarias del Congreso de la Unión (LXVI Legislatura) | El Pulso de la Política',
            'description' => 'Directorio y buscador de las comisiones de la Cámara de Diputados y el Senado de la República.',
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => 'Comisiones Legislativas del Congreso',
                'url' => $canonical,
            ],
        ];

        require dirname(__DIR__, 2) . '/views/comisiones.php';
    }

    /**
     * Ficha de Comisión (/comisiones/{slug})
     */
    public function comision(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $comision = $this->dataService->getComisionBySlug($slug);

        if (!$comision) {
            http_response_code(404);
            $homeUrl = Router::url('/');
            echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Comisión no encontrada</h2><a href='{$homeUrl}'>Volver</a></div>";
            return;
        }

        $nombre = $comision['nombre'];
        $canonical = Router::url('/comisiones/' . $slug);

        $seo = [
            'title' => "{$nombre} — Integrantes y Mesa Directiva | El Pulso de la Política",
            'description' => "Composición, presidencia e integrantes de la {$nombre} en el Congreso de la Unión.",
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $nombre,
                'url' => $canonical,
            ],
        ];

        require dirname(__DIR__, 2) . '/views/comision.php';
    }

    /**
     * API JSON / Infinite Scroll: Lotes paginados de tarjetas de políticos con filtrado ultrarrápido.
     */
    public function apiPoliticos(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: public, max-age=300');
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(8, (int)($_GET['limit'] ?? 32)));

        $numLegParam = isset($_GET['leg']) && trim((string)$_GET['leg']) !== '' && trim((string)$_GET['leg']) !== 'todas'
            ? DataService::parseNumeroLegislatura((string)$_GET['leg']) 
            : null;

        $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $cargo = trim((string)($_GET['cargo'] ?? 'todos'));
        $estado = trim((string)($_GET['estado'] ?? 'todos'));
        $partido = trim((string)($_GET['partido'] ?? 'todos'));
        $soloActivos = !empty($_GET['activo']) && $_GET['activo'] !== '0' && $_GET['activo'] !== 'false';

        $todos = $this->dataService->getDirectorioPoliticos(null);
        $totalGeneral = count($todos);

        // Función para normalizar slug de partido al value de las opciones del select
        $normalizarPartidoKey = function (string $pSlug): string {
            if (str_contains($pSlug, 'morena')) return 'morena';
            if (str_contains($pSlug, 'pan') || str_contains($pSlug, 'accion-nacional')) return 'pan';
            if (str_contains($pSlug, 'pri') || str_contains($pSlug, 'revolucionario-institucional')) return 'pri';
            if (str_contains($pSlug, 'pvem') || str_contains($pSlug, 'verde')) return 'pvem';
            if (str_contains($pSlug, 'pt') || str_contains($pSlug, 'trabajo')) return 'pt';
            if (str_contains($pSlug, 'mc') || str_contains($pSlug, 'movimiento-ciudadano')) return 'mc';
            if (str_contains($pSlug, 'prd') || str_contains($pSlug, 'revolucion-democratica')) return 'prd';
            if (str_contains($pSlug, 'independiente') || str_contains($pSlug, 'sin-partido')) return 'independiente';
            return $pSlug;
        };

        // Recolectar facetas dinámicas disjuntas y elementos filtrados en una sola pasada
        $filtrados = [];
        $facetasPartidos = [];
        $facetasEstados = [];
        $facetasCargos = [];
        $facetasLegislaturas = [];

        foreach ($todos as $pol) {
            $nombrePol = $pol['nombre_completo'] ?? '';
            $nombreMostrar = $pol['nombre_natural'] ?? '';
            $partidoPol = $pol['partido'] ?? '';
            $entidadPol = $pol['entidad'] ?? '';
            $camaraPol = $pol['camara'] ?? '';
            $munAlcalde = $pol['municipio_alcalde'] ?? '';
            $pSlugRaw = $pol['partido_slug'] ?? DataService::slugify($partidoPol);
            $pKey = $normalizarPartidoKey($pSlugRaw);

            $entidadSlug = $pol['entidad_slug'] ?? DataService::slugify($entidadPol);
            if ($entidadSlug === 'mexico' || $entidadSlug === 'edomex') $entidadSlug = 'estado-de-mexico';

            $roles = $pol['roles_flags'] ?? [];
            $legs = $pol['legislaturas_lista'] ?? [(int)($pol['legislatura'] ?? 66)];
            $esActivo = !empty($pol['es_activo']);

            // Evaluación individual de condiciones
            $passTexto = ($q === '') || str_contains(mb_strtolower("{$nombreMostrar} {$nombrePol} {$partidoPol} {$entidadPol} {$camaraPol} {$munAlcalde}"), $q);
            $passLeg = ($numLegParam === null) || in_array($numLegParam, $legs, true) || ((int)($pol['legislatura'] ?? 0)) === $numLegParam;
            $passCargo = ($cargo === 'todos') || !empty($roles[$cargo]);
            $passEstado = ($estado === 'todos') || ($entidadSlug === $estado);
            $passPartido = ($partido === 'todos') || ($pKey === $partido) || str_contains($pSlugRaw, $partido);
            $passActivo = !$soloActivos || $esActivo;

            // 1. Faceta Partidos (cumple todo excepto partido para que el dropdown muestre opciones viables)
            if ($passTexto && $passLeg && $passCargo && $passEstado && $passActivo) {
                $facetasPartidos[$pKey] = ($facetasPartidos[$pKey] ?? 0) + 1;
            }

            // 2. Faceta Estados (cumple todo excepto estado)
            if ($passTexto && $passLeg && $passCargo && $passPartido && $passActivo) {
                $facetasEstados[$entidadSlug] = ($facetasEstados[$entidadSlug] ?? 0) + 1;
            }

            // 3. Faceta Cargos (cumple todo excepto cargo)
            if ($passTexto && $passLeg && $passEstado && $passPartido && $passActivo) {
                foreach ($roles as $rK => $rV) {
                    if ($rV) {
                        $facetasCargos[$rK] = ($facetasCargos[$rK] ?? 0) + 1;
                    }
                }
            }

            // 4. Faceta Legislaturas (cumple todo excepto legislatura)
            if ($passTexto && $passCargo && $passEstado && $passPartido && $passActivo) {
                foreach ($legs as $lL) {
                    $facetasLegislaturas[(string)$lL] = ($facetasLegislaturas[(string)$lL] ?? 0) + 1;
                }
            }

            // Coincidencia total para la lista paginada
            if ($passTexto && $passLeg && $passCargo && $passEstado && $passPartido && $passActivo) {
                $filtrados[] = $pol;
            }
        }

        $totalFiltrados = count($filtrados);
        $totalPages = (int)ceil($totalFiltrados / $limit);
        $offset = ($page - 1) * $limit;
        $lote = array_slice($filtrados, $offset, $limit);

        // Renderizar el HTML de las tarjetas para inyección directa
        $partialPath = dirname(__DIR__, 2) . '/views/partials/card_politico.php';
        ob_start();
        foreach ($lote as $pol) {
            require $partialPath;
        }
        $htmlCards = (string)ob_get_clean();

        echo json_encode([
            'ok' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $totalGeneral,
            'total_filtrados' => $totalFiltrados,
            'total_pages' => $totalPages,
            'has_more' => ($page < $totalPages),
            'html' => $htmlCards,
            'facetas' => [
                'partidos' => array_keys($facetasPartidos),
                'estados' => array_keys($facetasEstados),
                'cargos' => array_keys($facetasCargos),
                'legislaturas' => array_keys($facetasLegislaturas),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * API JSON: Búsqueda rápida Spotlight.
     */
    public function apiBuscar(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $q = (string)($_GET['q'] ?? '');
        $results = $this->dataService->buscar($q, 8);
        echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * API JSON: Datos de estado para el modal rápido del mapa.
     */
    public function apiEstado(array $params): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $slug = $params['slug'] ?? '';
        $estado = $this->dataService->getEstadoBySlug($slug);

        if (!$estado) {
            http_response_code(404);
            echo json_encode(['error' => 'Estado no encontrado']);
            return;
        }

        echo json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Directorio y Radiografía de Legisladores por Nivel de Estudios (/estudios y /estudios/{categoria})
     */
    public function estudios(array $params = []): void
    {
        $categoriaRaw = trim((string)($params['categoria'] ?? 'todos'));
        $categoriasValidas = ['todos', 'posgrados', 'licenciatura', 'media-trunca'];
        $categoriaActiva = in_array($categoriaRaw, $categoriasValidas, true) ? $categoriaRaw : 'todos';

        $cacheKey = 'estudios_' . $categoriaActiva;
        $pageCache = new PageCache($cacheKey, 21600);
        $cachedHtml = $pageCache->get();
        if ($cachedHtml !== null) {
            if (!headers_sent()) {
                header('X-Pulso-Cache: HIT');
            }
            echo $cachedHtml;
            return;
        }

        $politicos = $this->dataService->getPoliticos();
        $educationService = $this->dataService->getEducationService();
        $escolaridadStats = $this->dataService->getEstadisticasEscolaridad();
        $estadosCatalog = $this->dataService->getEstados();

        // Enriquecer cada político con su clasificación académica individual
        $politicosConEstudios = [];
        foreach ($politicos as $pol) {
            $analisis = $educationService->clasificarPolitico($pol);
            $pol['analisis_academico'] = $analisis;
            $pol['categoria_grupo'] = $educationService->determinarCategoriaAgrupada($analisis['grado_maximo']);
            $politicosConEstudios[] = $pol;
        }

        $titulosSeo = [
            'todos' => 'Nivel de Estudios de los Legisladores de México',
            'posgrados' => 'Legisladores Federales con Posgrado (Maestría y Doctorado)',
            'licenciatura' => 'Legisladores Federales con Licenciatura e Ingeniería',
            'media-trunca' => 'Legisladores con Educación Media Superior y Carrera Trunca',
        ];

        $seo = [
            'title' => ($titulosSeo[$categoriaActiva] ?? $titulosSeo['todos']) . ' — El Pulso de la Política',
            'description' => 'Radiografía curricular y grados académicos de los diputados y senadores de la LXVI Legislatura del Congreso de la Unión.',
            'canonical' => Router::url('/estudios' . ($categoriaActiva !== 'todos' ? '/' . $categoriaActiva : '')),
        ];

        if (!headers_sent()) {
            header('X-Pulso-Cache: MISS');
        }
        ob_start();
        require dirname(__DIR__, 2) . '/views/estudios.php';
        $html = (string)ob_get_clean();
        $pageCache->set($html);
        echo $html;
    }

    /**
     * Generador dinámico de Sitemap XML para Googlebot (sitemap principal de páginas).
     */
    public function sitemap(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
        }
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $now = date('Y-m-d');

        $urls = [
            ['loc' => $host . Router::url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $host . Router::url('/politicos'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => $host . Router::url('/legislaturas'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/comisiones'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/alcaldes'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios/posgrados'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios/licenciatura'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios/media-trunca'), 'priority' => '0.8', 'changefreq' => 'weekly'],
        ];

        // Agregar legislaturas del catálogo
        foreach (DataService::LEGISLATURAS_CATALOG as $legNum => $legInfo) {
            $urls[] = [
                'loc' => $host . Router::url('/legislaturas/' . $legNum),
                'priority' => ($legInfo['estatus'] ?? '') === 'activa' ? '0.9' : '0.7',
                'changefreq' => ($legInfo['estatus'] ?? '') === 'activa' ? 'daily' : 'monthly',
            ];
        }

        // Agregar estados (con slug)
        foreach ($this->dataService->getEstados() as $estado) {
            if (!empty($estado['slug'])) {
                $urls[] = [
                    'loc' => $host . Router::url('/estados/' . $estado['slug']),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            }
        }

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
        echo "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
        echo "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";
        foreach ($urls as $u) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "    <changefreq>{$u['changefreq']}</changefreq>\n";
            echo "    <priority>{$u['priority']}</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>";
    }

    /**
     * Sitemap Index — agrupa todos los sitemaps segmentados (/sitemap-index.xml).
     */
    public function sitemapIndex(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
        }
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $now = date('Y-m-d');

        $sitemaps = [
            $host . Router::url('/sitemap.xml'),
            $host . Router::url('/sitemap-politicos.xml'),
            $host . Router::url('/sitemap-estados.xml'),
            $host . Router::url('/sitemap-comisiones.xml'),
            $host . Router::url('/sitemap-alcaldes.xml'),
        ];

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($sitemaps as $url) {
            echo "  <sitemap>\n";
            echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "  </sitemap>\n";
        }
        echo "</sitemapindex>";
    }

    /**
     * Sitemap segmentado de Políticos (/sitemap-politicos.xml).
     */
    public function sitemapPoliticos(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
        }
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $now = date('Y-m-d');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
        echo "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
        echo "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";
        foreach ($this->dataService->getPoliticos() as $p) {
            if (empty($p['slug'])) {
                continue;
            }
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($host . Router::url('/politicos/' . $p['slug'])) . "</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>";
    }

    /**
     * Sitemap segmentado de Estados (/sitemap-estados.xml).
     */
    public function sitemapEstados(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
        }
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $now = date('Y-m-d');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($this->dataService->getEstados() as $estado) {
            if (empty($estado['slug'])) {
                continue;
            }
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($host . Router::url('/estados/' . $estado['slug'])) . "</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>";
    }

    /**
     * Sitemap segmentado de Comisiones (/sitemap-comisiones.xml).
     */
    public function sitemapComisiones(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
        }
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $now = date('Y-m-d');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($this->dataService->getComisiones() as $c) {
            if (empty($c['slug'])) {
                continue;
            }
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($host . Router::url('/comisiones/' . $c['slug'])) . "</loc>\n";
            echo "    <lastmod>{$now}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>";
    }

    /**
     * Sitemap segmentado de Alcaldes/Municipios (/sitemap-alcaldes.xml).
     */
    public function sitemapAlcaldes(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
        }
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $now = date('Y-m-d');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        // Página raíz de alcaldes
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($host . Router::url('/alcaldes')) . "</loc>\n";
        echo "    <lastmod>{$now}</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";

        // Páginas individuales de municipios
        $municipioService = $this->dataService->getMunicipioService();
        if ($municipioService !== null) {
            foreach ($municipioService->listarMunicipios() as $municipio) {
                $cvegeo = $municipio['cvegeo'] ?? '';
                if (empty($cvegeo)) {
                    continue;
                }
                echo "  <url>\n";
                echo "    <loc>" . htmlspecialchars($host . Router::url('/municipios/' . $cvegeo)) . "</loc>\n";
                echo "    <lastmod>{$now}</lastmod>\n";
                echo "    <changefreq>monthly</changefreq>\n";
                echo "    <priority>0.6</priority>\n";
                echo "  </url>\n";
            }
        }
        echo "</urlset>";
    }

    /**
     * Sirve llms.txt dinámicamente con la URL base correcta (/llms.txt).
     * El archivo estático en raíz tiene prioridad si existe (via .htaccess).
     */
    public function llmsTxt(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: public, max-age=86400');
        }
        $filePath = dirname(__DIR__, 2) . '/llms.txt';
        if (is_file($filePath)) {
            readfile($filePath);
            return;
        }
        // Fallback mínimo si no existe el archivo estático
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        echo "# El Pulso de la Política — llms.txt\n";
        echo "> Observatorio cívico de datos abiertos sobre el poder legislativo y político de México.\n\n";
        echo "- [Inicio]({$host}" . Router::url('/') . ")\n";
        echo "- [Políticos]({$host}" . Router::url('/politicos') . ")\n";
        echo "- [Comisiones]({$host}" . Router::url('/comisiones') . ")\n";
        echo "- [Alcaldes]({$host}" . Router::url('/alcaldes') . ")\n";
    }

    /**
     * Directorio y Archivo Histórico de Legislaturas (/legislaturas)
     */
    public function legislaturas(): void
    {
        $catalog = $this->dataService->getLegislaturasCatalog();
        $disponibles = $this->dataService->getLegislaturasDisponibles();

        $seo = [
            'title' => 'Legislaturas del Congreso de la Unión — Archivo Histórico y Actual | El Pulso de la Política',
            'description' => 'Explora los periodos constitucionales del Congreso de la Unión: LXVI (actual), LXV, LXIV y archivo parlamentario federal de México.',
            'canonical' => Router::url('/legislaturas'),
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => 'Legislaturas del Congreso de la Unión',
                'url' => Router::url('/legislaturas'),
            ],
        ];

        require dirname(__DIR__, 2) . '/views/legislaturas_index.php';
    }

    /**
     * Expediente y Radiografía de una Legislatura (/legislaturas/{id})
     */
    public function legislatura(array $params): void
    {
        $idRaw = (string)($params['id'] ?? '66');
        $num = DataService::parseNumeroLegislatura($idRaw);

        $legData = $this->dataService->getLegislaturaData($num);
        if (!$legData) {
            http_response_code(404);
            $homeUrl = Router::url('/');
            echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Legislatura no encontrada</h2><a href='{$homeUrl}'>Volver al Inicio</a></div>";
            return;
        }

        $meta = $legData['meta'];
        $romano = $meta['romano'];
        $periodo = $meta['periodo'];
        $legislaturaRomano = $romano;
        $canonical = Router::url('/legislaturas/' . $num);

        $seo = [
            'title' => "{$romano} Legislatura ({$periodo}) — Integración y Fuerza Parlamentaria | El Pulso de la Política",
            'description' => "Composición parlamentaria, bancadas de partidos, senadores, diputados y radiografía cívica de la {$romano} Legislatura del Congreso de la Unión ({$periodo}).",
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'GovernmentOrganization',
                'name' => "{$romano} Legislatura del Congreso de la Unión",
                'url' => $canonical,
            ],
        ];

        require dirname(__DIR__, 2) . '/views/legislatura.php';
    }
}

