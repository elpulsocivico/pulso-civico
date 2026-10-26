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

        html, body {
            overflow-x: hidden !important;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
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

        .container {
            max-width: 100%;
        }

        /* Glassmorphism Navbar */
        .navbar-ios {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--ios-separator);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        /* Tira de Navegación Móvil Horizontal */
        .nav-mobile-scroll {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 2px 0 4px 0;
            width: 100%;
        }
        .nav-mobile-scroll::-webkit-scrollbar {
            display: none;
        }

        /* Corrección Crítica: Evitar que el dropdown se corte a la izquierda o derecha en móviles */
        @media (max-width: 576px) {
            .navbar-ios .dropdown-menu {
                position: fixed !important;
                top: 60px !important;
                left: 12px !important;
                right: 12px !important;
                width: auto !important;
                min-width: 0 !important;
                max-width: calc(100vw - 24px) !important;
                transform: none !important;
                margin: 0 !important;
                border-radius: 20px !important;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35) !important;
                z-index: 2050 !important;
            }
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

<nav class="navbar navbar-ios py-2">
    <div class="container d-flex flex-column gap-2">
        <!-- Fila 1: Logo + Buscador + Hamburguesa Móvil / Accesos Desktop -->
        <div class="d-flex align-items-center justify-content-between w-100">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark m-0" href="<?= $homeUrl ?>">
                <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-2xs" style="width: 32px; height: 32px;">
                    <i class="bi bi-activity"></i>
                </span>
                <span class="d-none d-sm-inline">El Pulso de la Política</span>
                <span class="d-inline d-sm-none">El Pulso</span>
            </a>

            <!-- Acciones Derecha -->
            <div class="d-flex align-items-center gap-2">
                <!-- Buscador Rápido (Icono en móvil, con atajo en desktop) -->
                <button class="btn btn-sm btn-light border rounded-pill px-2 px-md-3 d-flex align-items-center gap-1 gap-md-2 text-secondary shadow-xs" id="btnOpenSpotlight" type="button" title="Buscar político, estado o cargo">
                    <i class="bi bi-search"></i>
                    <span class="d-none d-md-inline small">Buscar...</span>
                    <kbd class="bg-white border text-muted d-none d-lg-inline-block px-1" style="font-size: 0.65rem;">Ctrl+K</kbd>
                </button>

                <!-- Botón Menú Móvil (Offcanvas Toggler) -->
                <button class="btn btn-sm btn-light border rounded-pill px-2 py-1 d-inline-flex d-lg-none align-items-center justify-content-center text-dark shadow-xs" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Abrir menú" title="Menú">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <!-- Enlaces Desktop Completos (>= 992px) -->
                <div class="d-none d-lg-flex align-items-center gap-2">
                    <!-- Dropdown Legislatura Desktop -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border rounded-pill px-3 d-inline-flex align-items-center gap-2 text-dark shadow-xs" type="button" id="btnCambiarLegislatura" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar periodo de Legislatura">
                            <i class="bi bi-bank text-primary"></i>
                            <span class="small text-secondary">Leg:</span>
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
                                
                                if (isset($estado['slug'])) {
                                    $legUrl = Router::url('/estados/' . $estado['slug'] . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                                } elseif (isset($comision['slug'])) {
                                    $legUrl = Router::url('/comisiones/' . $comision['slug'] . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                                } elseif (str_contains($canonicalUrl, '/comisiones')) {
                                    $legUrl = Router::url('/comisiones' . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                                } elseif (str_contains($canonicalUrl, '/politicos')) {
                                    $legUrl = Router::url('/politicos' . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                                } elseif (str_starts_with($canonicalUrl, $homeUrl) && ($canonicalUrl === $homeUrl || str_contains($canonicalUrl, '?leg='))) {
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

                    <a href="<?= Router::url('/remuneraciones') ?>" class="btn btn-sm btn-light border text-success rounded-pill px-3 d-inline-flex align-items-center gap-1" style="font-size: 0.82rem;" title="Cuánto cuesta la clase política en México">
                        <i class="bi bi-cash-stack"></i>
                        <span>Costo Político</span>
                    </a>

                    <a href="<?= $homeUrl ?>#seccion-mapa" class="btn btn-sm btn-light border rounded-pill px-3 d-inline-flex align-items-center gap-1 text-secondary" style="font-size: 0.82rem;">
                        <i class="bi bi-geo-alt text-danger"></i>
                        <span>Mapa Político</span>
                    </a>

                    <?php
                    $estudiosNavUrl = Router::url('/estudios');
                    $esPaginaEstudios = str_starts_with($canonicalUrl, $estudiosNavUrl);
                    ?>
                    <a href="<?= $estudiosNavUrl ?>" class="btn btn-sm <?= $esPaginaEstudios ? 'btn-primary text-white shadow-xs' : 'btn-light border text-secondary' ?> rounded-pill px-3 d-inline-flex align-items-center gap-1" style="font-size: 0.82rem;">
                        <i class="bi bi-mortarboard<?= $esPaginaEstudios ? '-fill text-white' : ' text-primary' ?>"></i>
                        <span>Nivel de Estudios</span>
                    </a>

                    <a href="<?= Router::url('/comisiones') ?>" class="btn btn-sm btn-light border rounded-pill px-3 d-inline-flex align-items-center gap-1 text-secondary" style="font-size: 0.82rem;">
                        <i class="bi bi-briefcase text-secondary"></i>
                        <span>Comisiones</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Fila 2 (Móvil): Píldoras Rápidas con Scroll Horizontal Suave -->
        <div class="d-flex d-lg-none nav-mobile-scroll">
            <!-- Selector Legislatura Móvil -->
            <div class="dropdown flex-shrink-0">
                <button class="btn btn-sm btn-light border rounded-pill px-2 d-inline-flex align-items-center gap-1 text-dark shadow-xs" type="button" id="btnCambiarLegMob" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar periodo">
                    <i class="bi bi-bank text-primary"></i>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-0 fw-bold" style="font-size: 0.72rem;"><?= ViewHelper::e($currentLegRomano) ?></span>
                    <i class="bi bi-chevron-down text-muted" style="font-size: 0.6rem;"></i>
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-4 p-2 mt-2" aria-labelledby="btnCambiarLegMob" style="max-height: 380px; overflow-y: auto;">
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
                        $legUrlMob = Router::url('/' . ($legNum !== 66 ? '?leg=' . $legNum : ''));
                        ?>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-2 d-flex align-items-center justify-content-between <?= $esSeleccionada ? 'bg-primary-subtle text-primary fw-bold' : 'text-dark' ?>" href="<?= $legUrlMob ?>">
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

            <a href="<?= $politicosNavUrl ?>" class="btn btn-sm <?= $esPaginaPoliticos ? 'btn-primary text-white shadow-xs' : 'btn-light border text-secondary' ?> rounded-pill px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.8rem;">
                <i class="bi bi-people<?= $esPaginaPoliticos ? '-fill text-white' : ' text-primary' ?>"></i>
                <span>Políticos</span>
            </a>

            <a href="<?= $alcaldesNavUrl ?>" class="btn btn-sm <?= $esPaginaAlcaldes ? 'btn-primary text-white shadow-xs' : 'btn-light border text-secondary' ?> rounded-pill px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.8rem;">
                <i class="bi bi-buildings<?= $esPaginaAlcaldes ? '-fill text-white' : ' text-primary' ?>"></i>
                <span>Alcaldías</span>
            </a>

            <a href="<?= Router::url('/remuneraciones') ?>" class="btn btn-sm btn-light border text-success rounded-pill px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.8rem;">
                <i class="bi bi-cash-stack"></i>
                <span>Costo Político</span>
            </a>

            <a href="<?= Router::url('/reloj') ?>" class="btn btn-sm btn-light border text-warning rounded-pill px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.8rem;">
                <i class="bi bi-stopwatch text-danger"></i>
                <span>Reloj Erario</span>
            </a>

            <a href="<?= $homeUrl ?>#seccion-mapa" class="btn btn-sm btn-light border text-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.8rem;">
                <i class="bi bi-geo-alt text-danger"></i>
                <span>Mapa</span>
            </a>

            <a href="<?= $estudiosNavUrl ?>" class="btn btn-sm btn-light border text-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.8rem;">
                <i class="bi bi-mortarboard text-primary"></i>
                <span>Estudios</span>
            </a>

            <a href="<?= Router::url('/comisiones') ?>" class="btn btn-sm btn-light border text-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.8rem;">
                <i class="bi bi-briefcase text-secondary"></i>
                <span>Comisiones</span>
            </a>
        </div>
    </div>
</nav>

<!-- Menú Lateral Offcanvas Móvil Super Responsive -->
<div class="offcanvas offcanvas-end rounded-start-4 border-0 shadow-lg" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel" style="max-width: 320px;">
    <div class="offcanvas-header border-bottom py-3">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                <i class="bi bi-activity"></i>
            </span>
            <h5 class="offcanvas-title fw-bold text-dark fs-6" id="offcanvasNavbarLabel">El Pulso de la Política</h5>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body p-3">
        <!-- Directorio -->
        <div class="text-uppercase text-muted fw-bold mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em;">Directorio y Cargos</div>
        <div class="list-group list-group-flush rounded-3 border mb-3 overflow-hidden shadow-2xs">
            <a href="<?= $politicosNavUrl ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-people-fill text-primary"></i> Directorio de Políticos</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
            </a>
            <a href="<?= $alcaldesNavUrl ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-buildings-fill text-info"></i> Alcaldías y Municipios</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
            </a>
            <a href="<?= Router::url('/legislaturas/' . $currentLegNum) ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-bank text-primary"></i> Congreso de la Unión (<?= ViewHelper::e($currentLegRomano) ?>)</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
            </a>
        </div>

        <!-- Transparencia -->
        <div class="text-uppercase text-muted fw-bold mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em;">Transparencia y Erario</div>
        <div class="list-group list-group-flush rounded-3 border mb-3 overflow-hidden shadow-2xs">
            <a href="<?= Router::url('/remuneraciones') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-cash-stack text-success"></i> Costo Político (Sueldos)</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Oficial</span>
            </a>
            <a href="<?= Router::url('/reloj') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-stopwatch text-danger"></i> Reloj del Erario en Vivo</span>
                <span class="badge bg-danger text-white">En Vivo</span>
            </a>
        </div>

        <!-- Datos -->
        <div class="text-uppercase text-muted fw-bold mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em;">Exploración y Análisis</div>
        <div class="list-group list-group-flush rounded-3 border mb-3 overflow-hidden shadow-2xs">
            <a href="<?= $homeUrl ?>#seccion-mapa" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small" data-bs-dismiss="offcanvas">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-geo-alt-fill text-danger"></i> Mapa Político de México</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
            </a>
            <a href="<?= $estudiosNavUrl ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-mortarboard-fill text-secondary"></i> Nivel de Estudios</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
            </a>
            <a href="<?= Router::url('/comisiones') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-briefcase-fill text-secondary"></i> Comisiones Ordinarias</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
            </a>
            <a href="<?= Router::url('/legislaturas') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 small">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-clock-history text-muted"></i> Archivo Histórico</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
            </a>
        </div>

        <div class="d-grid mt-4">
            <button class="btn btn-outline-primary rounded-pill py-2 small fw-semibold" onclick="document.getElementById('btnOpenSpotlight').click(); const oc = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasNavbar')); if(oc) oc.hide();">
                <i class="bi bi-search me-1"></i> Búsqueda Universal (Ctrl+K)
            </button>
        </div>
    </div>
</div>

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
