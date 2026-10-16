<?php
declare(strict_types=1);

/**
 * Pulso Cívico — Front Controller Principal (SEO-First & Observatorio Cívico).
 * Enrutador de URLs canónicas, buscador y mapa interactivo de la República Mexicana.
 */

// Autoload PSR-4 nativo
spl_autoload_register(function (string $class): void {
    $prefix = 'PulsoCivico\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use PulsoCivico\Core\Router;
use PulsoCivico\Controllers\WebController;
use PulsoCivico\Controllers\MunicipioController;
use PulsoCivico\Controllers\AuditController;

$router = new Router();
$ctrl = new WebController();
$munCtrl = new MunicipioController();
$auditCtrl = new AuditController();

// Rutas Canónicas
$router->get('/', [$ctrl, 'home']);
$router->get('/legislaturas', [$ctrl, 'legislaturas']);
$router->get('/legislaturas/{id}', [$ctrl, 'legislatura']);
$router->get('/politicos', [$ctrl, 'politicos']);
$router->get('/politicos/{slug}', [$ctrl, 'politico']);
$router->get('/alcaldes', [$munCtrl, 'alcaldes']);
$router->get('/municipios', [$munCtrl, 'alcaldes']);
$router->get('/municipios/{cvegeo}', [$munCtrl, 'municipio']);
$router->get('/estados/{slug}', [$ctrl, 'estado']);
$router->get('/comisiones', [$ctrl, 'comisiones']);
$router->get('/comisiones/{slug}', [$ctrl, 'comision']);
$router->get('/estudios', [$ctrl, 'estudios']);
$router->get('/estudios/{categoria}', [$ctrl, 'estudios']);
$router->get('/baneado', [$auditCtrl, 'paginaBaneado']);
$router->get('/sitemap.xml', [$ctrl, 'sitemap']);
$router->get('/sitemap-index.xml', [$ctrl, 'sitemapIndex']);
$router->get('/sitemap-politicos.xml', [$ctrl, 'sitemapPoliticos']);
$router->get('/sitemap-estados.xml', [$ctrl, 'sitemapEstados']);
$router->get('/sitemap-comisiones.xml', [$ctrl, 'sitemapComisiones']);
$router->get('/sitemap-alcaldes.xml', [$ctrl, 'sitemapAlcaldes']);
$router->get('/llms.txt', [$ctrl, 'llmsTxt']);

// APIs Internas
$router->get('/api/politicos', [$ctrl, 'apiPoliticos']);
$router->get('/api/buscar', [$ctrl, 'apiBuscar']);
$router->get('/api/estados/{slug}', [$ctrl, 'apiEstado']);
$router->get('/api/alcaldes', [$munCtrl, 'apiAlcaldes']);
$router->post('/api/reportar-anomalia', [$auditCtrl, 'apiReportar']);

// Despachar petición
$router->dispatch();
