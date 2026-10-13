<?php
declare(strict_types=1);

namespace PulsoCivico\Controllers;

use PulsoCivico\Core\Router;
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
        if (isset($_GET['leg'])) {
            $numLeg = DataService::parseNumeroLegislatura((string)$_GET['leg']);
            header('Location: ' . Router::url('/legislaturas/' . $numLeg), true, 301);
            exit;
        }

        $estados = $this->dataService->getEstados(66);
        $comisiones = $this->dataService->getComisiones();
        $politicos = $this->dataService->getPoliticos(66);
        $escolaridadStats = $this->dataService->getEstadisticasEscolaridad(null, null, 66);

        $seo = [
            'title' => 'El Pulso de la Política — Observatorio del Congreso Mexicano (LXVI Legislatura)',
            'description' => 'Radiografía, composición parlamentaria y trabajo de los senadores y diputados federales de México.',
            'canonical' => Router::url('/'),
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'El Pulso de la Política',
                'url' => Router::url('/'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => Router::url('/buscar?q={search_term_string}'),
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];

        require dirname(__DIR__, 2) . '/views/home.php';
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

        $politicos = $this->dataService->getPoliticos();
        $educationService = $this->dataService->getEducationService();
        $escolaridadStats = $this->dataService->getEstadisticasEscolaridad();

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

        require dirname(__DIR__, 2) . '/views/estudios.php';
    }

    /**
     * Generador dinámico de Sitemap XML para Googlebot.
     */
    public function sitemap(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
        }
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $now = date('Y-m-d');

        $urls = [
            ['loc' => $host . Router::url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $host . Router::url('/estados/baja-california'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios/posgrados'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios/licenciatura'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $host . Router::url('/estudios/media-trunca'), 'priority' => '0.8', 'changefreq' => 'weekly'],
        ];

        foreach ($this->dataService->getPoliticos() as $p) {
            $urls[] = [
                'loc' => $host . Router::url('/politicos/' . $p['slug']),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        foreach ($this->dataService->getComisiones() as $c) {
            $urls[] = [
                'loc' => $host . Router::url('/comisiones/' . $c['slug']),
                'priority' => '0.7',
                'changefreq' => 'monthly',
            ];
        }

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
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
