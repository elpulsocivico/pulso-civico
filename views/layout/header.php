<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;
use PulsoCivico\Services\DataService;

/** @var array{title?: string, description?: string, canonical?: string, og_image?: string, schema?: array} $seo */
$pageTitle = $seo['title'] ?? 'El Pulso de la Política — Observatorio del Congreso Mexicano';
$pageDesc = $seo['description'] ?? 'Monitoreo ciudadano y radiografía del poder legislativo en México.';
$canonicalUrl = $seo['canonical'] ?? Router::url('/');
$ogImage = $seo['og_image'] ?? Router::url('/assets/og-cover.png');
$homeUrl = Router::url('/');

// Resolver legislatura activa para navegación contextual
$currentLegNum = 66;
if (isset($legData['meta']['numero'])) {
    $currentLegNum = (int)$legData['meta']['numero'];
} elseif (isset($politico['legislatura'])) {
    $currentLegNum = (int)$politico['legislatura'];
} elseif (isset($_GET['leg'])) {
    $currentLegNum = DataService::parseNumeroLegislatura((string)$_GET['leg']);
} else {
    $reqUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    if (preg_match('#/legislaturas/([a-zA-Z0-9]+)#', $reqUri, $uriMatches)) {
        $currentLegNum = DataService::parseNumeroLegislatura($uriMatches[1]);
    }
}
$currentLegRomano = DataService::convertirNumeroARomano($currentLegNum);
$catalogLegislaturas = DataService::LEGISLATURAS_CATALOG;
?>
<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= ViewHelper::e($pageTitle) ?></title>
    <meta name="description" content="<?= ViewHelper::e($pageDesc) ?>">
    <link rel="canonical" href="<?= ViewHelper::e($canonicalUrl) ?>">

    <!-- Indexación y robots -->
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="language" content="es">
    <meta name="geo.region" content="MX">
    <meta name="geo.country" content="Mexico">
    <meta name="coverage" content="Mexico">
    <meta name="revisit-after" content="7 days">

    <!-- Autoría y atribución -->
    <meta name="author" content="El Pulso de la Política — Observatorio Cívico">
    <meta name="copyright" content="El Pulso de la Política — Datos bajo CC BY 4.0">
    <meta name="generator" content="El Pulso de la Política v1.0">

    <!-- Open Graph / Redes Sociales -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_MX">
    <meta property="og:site_name" content="El Pulso de la Política">
    <meta property="og:title" content="<?= ViewHelper::e($pageTitle) ?>">
    <meta property="og:description" content="<?= ViewHelper::e($pageDesc) ?>">
    <meta property="og:url" content="<?= ViewHelper::e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= ViewHelper::e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= ViewHelper::e($pageTitle) ?>">

    <!-- Twitter / X Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@PulsoPoliticoMX">
    <meta name="twitter:title" content="<?= ViewHelper::e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= ViewHelper::e($pageDesc) ?>">
    <meta name="twitter:image" content="<?= ViewHelper::e($ogImage) ?>">

    <!-- Favicon y App Manifest -->
    <link rel="icon" type="image/svg+xml" href="<?= Router::url('/assets/favicon.svg') ?>">
    <link rel="alternate icon" href="<?= Router::url('/assets/favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= Router::url('/assets/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= Router::url('/site.webmanifest') ?>">
    <meta name="theme-color" content="#007AFF">
    <meta name="msapplication-TileColor" content="#007AFF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <?php if (!empty($seo['schema'])): ?>
    <!-- Schema.org Datos Estructurados JSON-LD -->
    <script type="application/ld+json">
        <?= json_encode($seo['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>

    <!-- DNS Prefetch y Preconnects críticos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">

    <!-- Bootstrap 5.3 CSS & Icons — preload para LCP -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">


    <style>
        :root {
            --ios-bg: #F2F2F7;
            --ios-surface: #FFFFFF;
            --ios-text: #1C1C1E;
            --ios-secondary: #8E8E93;
            --ios-separator: rgba(60, 60, 67, 0.12);
            --ios-blue: #007AFF;
            --ios-blue-light: #EBF3FF;
            --ios-gray-fill: #E5E5EA;
            --ios-card-radius: 20px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, sans-serif;
            background-color: var(--ios-bg);
            color: var(--ios-text);
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.015em;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Glassmorphism Navbar */
        .navbar-ios {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--ios-separator);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .party-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 600;
            background: #F2F2F7;
            color: var(--ios-text);
            border: 1px solid rgba(0, 0, 0, 0.06);
            line-height: 1;
        }

        .party-icon {
            flex-shrink: 0;
            vertical-align: middle;
        }

        .card-civic {
            background: var(--ios-surface);
            border-radius: var(--ios-card-radius);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-civic:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .breadcrumb-ios {
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .breadcrumb-ios a {
            color: var(--ios-secondary);
            text-decoration: none;
        }
        .breadcrumb-ios a:hover {
            color: var(--ios-blue);
        }

        /* Spotlight Modal Search */
        .spotlight-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 10vh;
        }
        .spotlight-card {
            background: #FFFFFF;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-ios py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" href="<?= $homeUrl ?>">
            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-activity"></i>
            </span>
            <span>El Pulso de la Política</span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <!-- Botón Cambiar Legislatura -->
            <div class="dropdown">
                <button class="btn btn-sm btn-light border rounded-pill px-2 px-md-3 d-inline-flex align-items-center gap-1 gap-md-2 text-dark shadow-xs" type="button" id="btnCambiarLegislatura" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar periodo de Legislatura">
                    <i class="bi bi-bank text-primary"></i>
                    <span class="d-none d-sm-inline small text-secondary">Leg:</span>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-0 fw-bold" style="font-size: 0.75rem;"><?= ViewHelper::e($currentLegRomano) ?></span>
                    <i class="bi bi-chevron-down text-muted" style="font-size: 0.65rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" aria-labelledby="btnCambiarLegislatura" style="min-width: 295px; max-width: 330px; max-height: 420px; overflow-y: auto;">
                    <li class="px-2 py-1 mb-1 border-bottom sticky-top bg-white" style="top: -8px; z-index: 2;">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">
                                <i class="bi bi-clock-history me-1 text-primary"></i> Cambiar Legislatura
                            </span>
                            <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">H. Congreso</span>
                        </div>
                    </li>
                    <?php foreach ($catalogLegislaturas as $legNum => $legInfo): ?>
                        <?php
                        $esSeleccionada = ($legNum === $currentLegNum);
                        $esActiva = (($legInfo['estatus'] ?? '') === 'activa');
                        
                        // Si el usuario está consultando un estado, mantenemos el contexto de ese estado con ?leg=
                        if (isset($estado['slug'])) {
                            $legUrl = Router::url('/estados/' . $estado['slug'] . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                        } elseif (isset($comision['slug'])) {
                            $legUrl = Router::url('/comisiones/' . $comision['slug'] . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                        } elseif (str_contains($canonicalUrl, '/comisiones')) {
                            $legUrl = Router::url('/comisiones' . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                        } elseif (str_contains($canonicalUrl, '/politicos')) {
                            $legUrl = Router::url('/politicos' . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                        } elseif (str_starts_with($canonicalUrl, $homeUrl) && ($canonicalUrl === $homeUrl || str_contains($canonicalUrl, '?leg='))) {
                            // En portada principal, actualizamos la vista de inicio y el mapa interactivo para esa legislatura
                            $legUrl = Router::url('/' . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                        } else {
                            $legUrl = Router::url('/legislaturas/' . $legNum);
                        }
                        ?>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-2 d-flex align-items-center justify-content-between <?= $esSeleccionada ? 'bg-primary-subtle text-primary fw-bold' : 'text-dark' ?>" href="<?= $legUrl ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= $esSeleccionada ? 'bg-primary text-white' : ($esActiva ? 'bg-success text-white' : 'bg-light text-secondary border') ?> rounded-pill" style="min-width: 44px; font-size: 0.72rem;">
                                        <?= ViewHelper::e($legInfo['romano']) ?>
                                    </span>
                                    <div>
                                        <div class="small fw-semibold leading-tight"><?= ViewHelper::e($legInfo['romano']) ?> Legislatura</div>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?= ViewHelper::e($legInfo['periodo']) ?></div>
                                    </div>
                                </div>
                                <?php if ($esSeleccionada): ?>
                                    <i class="bi bi-check2 text-primary fs-6"></i>
                                <?php elseif ($esActiva): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.62rem;">Activa</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php
            $politicosNavUrl = Router::url('/politicos');
            $esPaginaPoliticos = str_starts_with($canonicalUrl, $politicosNavUrl) && !preg_match('#/politicos/[a-z0-9\-]+#', $canonicalUrl);
            ?>
            <a href="<?= $politicosNavUrl ?>" class="btn btn-sm <?= $esPaginaPoliticos ? 'btn-primary text-white shadow-xs' : 'btn-light border text-secondary' ?> rounded-pill px-3 d-inline-flex align-items-center gap-1" style="font-size: 0.82rem;" title="Directorio de Políticos y Representantes">
                <i class="bi bi-people<?= $esPaginaPoliticos ? '-fill text-white' : ' text-primary' ?>"></i>
                <span>Políticos</span>
            </a>

            <?php
            $alcaldesNavUrl = Router::url('/alcaldes');
            $esPaginaAlcaldes = str_starts_with($canonicalUrl, $alcaldesNavUrl) || str_contains($canonicalUrl, '/municipios');
            ?>
            <a href="<?= $alcaldesNavUrl ?>" class="btn btn-sm <?= $esPaginaAlcaldes ? 'btn-primary text-white shadow-xs' : 'btn-light border text-secondary' ?> rounded-pill px-3 d-inline-flex align-items-center gap-1" style="font-size: 0.82rem;" title="Directorio Nacional de Alcaldes y Municipios">
                <i class="bi bi-buildings<?= $esPaginaAlcaldes ? '-fill text-white' : ' text-primary' ?>"></i>
                <span>Alcaldías</span>
            </a>

            <a href="<?= $homeUrl ?>#seccion-mapa" class="btn btn-sm btn-light border rounded-pill px-3 d-none d-lg-inline-flex align-items-center gap-1 text-secondary" style="font-size: 0.82rem;">
                <i class="bi bi-geo-alt text-danger"></i>
                <span>Mapa Político</span>
            </a>
            <?php
            $estudiosNavUrl = Router::url('/estudios');
            $esPaginaEstudios = str_starts_with($canonicalUrl, $estudiosNavUrl);
            ?>
            <a href="<?= $estudiosNavUrl ?>" class="btn btn-sm <?= $esPaginaEstudios ? 'btn-primary text-white shadow-xs' : 'btn-light border text-secondary' ?> rounded-pill px-3 d-none d-md-inline-flex align-items-center gap-1" style="font-size: 0.82rem;">
                <i class="bi bi-mortarboard<?= $esPaginaEstudios ? '-fill text-white' : ' text-primary' ?>"></i>
                <span>Nivel de Estudios</span>
            </a>
            <a href="<?= Router::url('/comisiones') ?>" class="btn btn-sm btn-light border rounded-pill px-3 d-none d-md-inline-flex align-items-center gap-1 text-secondary" style="font-size: 0.82rem;">
                <i class="bi bi-briefcase text-secondary"></i>
                <span>Comisiones</span>
            </a>
            <button class="btn btn-sm btn-light border rounded-pill px-3 d-flex align-items-center gap-2 text-secondary" id="btnOpenSpotlight" type="button">
                <i class="bi bi-search"></i>
                <span class="d-none d-sm-inline">Buscar político o comisión...</span>
                <kbd class="bg-white border text-muted d-none d-md-inline-block px-1" style="font-size: 0.65rem;">Ctrl+K</kbd>
            </button>
        </div>
    </div>
</nav>

<!-- Modal Spotlight Search Universal -->
<div class="spotlight-backdrop" id="spotlightModal">
    <div class="spotlight-card">
        <div class="p-3 border-bottom d-flex align-items-center gap-2">
            <i class="bi bi-search text-secondary fs-5"></i>
            <input type="text" id="spotlightInput" class="form-control border-0 shadow-none fs-6" placeholder="Escribe el nombre de un legislador, estado o comisión..." autocomplete="off">
            <button type="button" class="btn btn-sm btn-light rounded-circle" id="btnCloseSpotlight">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="p-2" id="spotlightResults" style="max-height: 380px; overflow-y: auto;">
            <div class="text-center text-muted p-4 small">Comienza a escribir para buscar...</div>
        </div>
    </div>
</div>

<main class="flex-grow-1 py-4">
    <div class="container">
