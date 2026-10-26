<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

$jsonFile = dirname(__DIR__) . '/storage/data/json/tabuladores_cargos_oficiales.json';
$masterData = file_exists($jsonFile) ? json_decode((string)file_get_contents($jsonFile), true) : [];
$totales = $masterData['totales_nacionales'] ?? [];
$granTotalAnual = $totales['gran_total_anual_mxn'] ?? 2029943880.00;
$granTotalDiario = $totales['gran_total_por_dia_mxn'] ?? 5561490.08;
$gastoSegundo = $totales['gran_total_por_segundo_mxn'] ?? 64.37;
$totalPuestos = $totales['total_puestos_evaluados'] ?? 4274;

$desglose = $totales['desglose_por_orden'] ?? [];
$dDip = $desglose['camara_diputados_federal'] ?? ['puestos' => 500, 'costo_por_segundo' => 14.41, 'costo_diario' => 1245422.14];
$dSen = $desglose['camara_senadores_federal'] ?? ['puestos' => 128, 'costo_por_segundo' => 6.18, 'costo_diario' => 533602.19];
$dGob = $desglose['poderes_ejecutivos_estatales'] ?? ['puestos' => 32, 'costo_por_segundo' => 0.85, 'costo_diario' => 73643.84];
$dLoc = $desglose['congresos_estatales_locales'] ?? ['puestos' => 1123, 'costo_por_segundo' => 19.23, 'costo_diario' => 1661424.66];
$dMun = $desglose['presidencias_municipales_alcaldias'] ?? ['puestos' => 2491, 'costo_por_segundo' => 23.70, 'costo_diario' => 2047397.26];

$seo = [
    'title' => 'Reloj en Vivo del Erario — ¿Cuánto Dinero se va a Políticos? | El Pulso de la Política',
    'description' => 'Contador en tiempo real de los sueldos y dietas base de la clase política mexicana: al menos $64.37 pesos cada segundo ($5.56M al día) bajo piso mínimo conservador.',
    'canonical' => Router::url('/reloj')
];

require __DIR__ . '/layout/header.php';
?>

<style>
    .reloj-screen {
        background: radial-gradient(circle at 50% 20%, #1A1C23 0%, #0D0E12 100%);
        color: #F2F2F7;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    }
    .money-display-huge {
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif;
        font-variant-numeric: tabular-nums;
        font-weight: 800;
        letter-spacing: -0.03em;
        font-size: clamp(2rem, 7.5vw, 4.4rem);
        line-height: 1.15;
        background: linear-gradient(180deg, #FFFFFF 30%, #E2E8F0 75%, #94A3B8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 0 35px rgba(255, 255, 255, 0.12);
        word-break: normal;
    }
    .glow-green {
        color: #34D399;
        text-shadow: 0 0 20px rgba(52, 211, 153, 0.45);
    }
    
    /* Contenedor Horizontal para Filtros en Móvil */
    .reloj-timeframe-wrap {
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 4px 2px 8px 2px;
    }
    .reloj-timeframe-wrap::-webkit-scrollbar {
        display: none;
    }
    .reloj-timeframe-list {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    @media (min-width: 768px) {
        .reloj-timeframe-wrap {
            display: flex;
            justify-content: center;
            white-space: normal;
        }
        .reloj-timeframe-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
    }

    .pill-filter-reloj {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: #94A3B8;
        border-radius: 9999px;
        padding: 8px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
        user-select: none;
    }
    .pill-filter-reloj:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #FFFFFF;
        transform: translateY(-1px);
    }
    .pill-filter-reloj.active {
        background: #007AFF;
        border-color: #007AFF;
        color: #FFFFFF;
        box-shadow: 0 4px 20px rgba(0, 122, 255, 0.45);
    }

    /* Tarjetas del Velocímetro */
    .breakdown-card-reloj {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 16px;
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }
    .breakdown-card-reloj:hover {
        background: rgba(255, 255, 255, 0.09);
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    .breakdown-card-total {
        background: linear-gradient(135deg, rgba(0, 122, 255, 0.14) 0%, rgba(16, 185, 129, 0.18) 100%);
        border: 1px solid rgba(52, 211, 153, 0.45) !important;
        box-shadow: 0 6px 24px rgba(16, 185, 129, 0.12);
    }

    .live-pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #EF4444;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        animation: pulse-red 1.6s infinite;
    }
    @keyframes pulse-red {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    @media (max-width: 576px) {
        .reloj-screen {
            padding: 1.25rem 1rem !important;
            border-radius: 20px;
        }
        .pill-filter-reloj {
            padding: 7px 13px;
            font-size: 0.78rem;
        }
    }
</style>

<div class="py-2">
    <!-- Navegación superior y herramientas -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="<?= Router::url('/') ?>" class="text-decoration-none">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= Router::url('/remuneraciones') ?>" class="text-decoration-none">Costo Político</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reloj del Erario</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center gap-2">
            <button id="btnFullscreen" class="btn btn-sm btn-outline-secondary rounded-pill px-2 px-sm-3 d-inline-flex align-items-center gap-1" title="Pantalla completa">
                <i class="bi bi-arrows-fullscreen"></i>
                <span class="d-none d-sm-inline">Pantalla Completa</span>
            </button>
            <button id="btnCompartir" class="btn btn-sm btn-primary rounded-pill px-2 px-sm-3 d-inline-flex align-items-center gap-1">
                <i class="bi bi-share"></i>
                <span class="d-none d-sm-inline">Compartir</span>
            </button>
        </div>
    </div>

    <!-- Pantalla Principal del Reloj -->
    <div class="reloj-screen p-4 p-md-5 mb-4 text-center position-relative overflow-hidden" id="contenedorReloj">
        
        <!-- Indicador En Vivo -->
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white-50 small mb-3 border border-white border-opacity-10">
            <span class="live-pulse-dot"></span>
            <span class="fw-semibold text-white" style="font-size: 0.75rem;">RELOJ DEL ERARIO &bull; PISO MÍNIMO CONSERVADOR</span>
        </div>

        <h1 class="h6 text-uppercase text-secondary tracking-wider mb-2 px-2" id="timeframeLabel" style="font-size: 0.8rem; line-height: 1.4;">
            Gasto público base transcurrido en el día de hoy (desde 00:00h)
        </h1>

        <!-- MONTO DINÁMICO GIGANTE CON AUTOESCALADO -->
        <div class="money-display-huge my-2" id="dynamicMoneyCounter">
            $0.00 MXN
        </div>

        <div class="text-secondary small mb-4 px-2">
            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
            El erario transfiere al menos <strong>$<?= number_format((float)$gastoSegundo, 2) ?> pesos cada segundo</strong> en dietas y sueldos base de <strong><?= number_format((float)$totalPuestos) ?> titulares de elección popular</strong>.
        </div>

        <!-- Selector de Marcos Temporales: Scrollable horizontal en móvil -->
        <div class="reloj-timeframe-wrap mb-4">
            <div class="reloj-timeframe-list">
                <button class="pill-filter-reloj active" data-mode="hoy">
                    <i class="bi bi-sun-fill text-warning"></i>
                    <span>Hoy</span>
                    <span class="d-none d-sm-inline text-white-50 small">(00:00h)</span>
                </button>
                <button class="pill-filter-reloj" data-mode="mes">
                    <i class="bi bi-calendar-month text-info"></i>
                    <span>En el Mes</span>
                </button>
                <button class="pill-filter-reloj" data-mode="ano">
                    <i class="bi bi-calendar3 text-success"></i>
                    <span>En el Año</span>
                </button>
                <button class="pill-filter-reloj" data-mode="legislatura">
                    <i class="bi bi-bank text-primary"></i>
                    <span>LXVI Legislatura</span>
                    <span class="d-none d-sm-inline text-white-50 small">(Sep 2024)</span>
                </button>
                <button class="pill-filter-reloj" data-mode="sesion">
                    <i class="bi bi-stopwatch text-danger"></i>
                    <span>Esta Sesión</span>
                </button>
            </div>
        </div>

        <!-- Velocímetro del Gasto por Segundo por Puesto (100% cuadrado y dinámico) -->
        <div class="row g-2 g-md-3 text-start justify-content-center mb-4">
            <!-- Diputados Federales -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="breakdown-card-reloj p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.03em;">Diputados Fed.</span>
                        <span class="badge bg-primary bg-opacity-25 text-info border border-primary border-opacity-25 py-0 px-1" style="font-size: 0.6rem;"><?= (int)($dDip['puestos'] ?? 500) ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-white mb-0">$<?= number_format((float)($dDip['costo_por_segundo'] ?? 14.41), 2) ?> <span class="small fs-6 text-white-50">/s</span></div>
                    <div class="text-white-50 mt-1" style="font-size: 0.68rem;"><i class="bi bi-clock me-1 text-primary"></i>$<?= number_format(((float)($dDip['costo_diario'] ?? 1245422.14)) / 1000000, 2) ?>M al día</div>
                    <div class="text-white-50" style="font-size: 0.62rem;">Dieta PEF: $75,763/mes</div>
                </div>
            </div>

            <!-- Senadores -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="breakdown-card-reloj p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.03em;">Senadores</span>
                        <span class="badge bg-purple-subtle text-white border border-secondary py-0 px-1" style="font-size: 0.6rem;"><?= (int)($dSen['puestos'] ?? 128) ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-white mb-0">$<?= number_format((float)($dSen['costo_por_segundo'] ?? 6.18), 2) ?> <span class="small fs-6 text-white-50">/s</span></div>
                    <div class="text-white-50 mt-1" style="font-size: 0.68rem;"><i class="bi bi-clock me-1 text-info"></i>$<?= number_format(((float)($dSen['costo_diario'] ?? 533602.19)) / 1000, 0) ?>k al día</div>
                    <div class="text-white-50" style="font-size: 0.62rem;">Dieta PEF: $126,800/mes</div>
                </div>
            </div>

            <!-- Gobernadores -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="breakdown-card-reloj p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.03em;">Gobernadores</span>
                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 py-0 px-1" style="font-size: 0.6rem;"><?= (int)($dGob['puestos'] ?? 32) ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-white mb-0">$<?= number_format((float)($dGob['costo_por_segundo'] ?? 0.85), 2) ?> <span class="small fs-6 text-white-50">/s</span></div>
                    <div class="text-white-50 mt-1" style="font-size: 0.68rem;"><i class="bi bi-clock me-1 text-warning"></i>$<?= number_format(((float)($dGob['costo_diario'] ?? 73643.84)) / 1000, 1) ?>k al día</div>
                    <div class="text-white-50" style="font-size: 0.62rem;">Piso único: $70,000/mes</div>
                </div>
            </div>

            <!-- Diputados Locales -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="breakdown-card-reloj p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.03em;">Dips. Locales</span>
                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 py-0 px-1" style="font-size: 0.6rem;"><?= number_format((float)($dLoc['puestos'] ?? 1123)) ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-white mb-0">$<?= number_format((float)($dLoc['costo_por_segundo'] ?? 19.23), 2) ?> <span class="small fs-6 text-white-50">/s</span></div>
                    <div class="text-white-50 mt-1" style="font-size: 0.68rem;"><i class="bi bi-clock me-1 text-danger"></i>$<?= number_format(((float)($dLoc['costo_diario'] ?? 1661424.66)) / 1000000, 2) ?>M al día</div>
                    <div class="text-white-50" style="font-size: 0.62rem;">Piso único: $45,000/mes</div>
                </div>
            </div>

            <!-- Alcaldes -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="breakdown-card-reloj p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.03em;">Alcaldes Mpales.</span>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 py-0 px-1" style="font-size: 0.6rem;"><?= number_format((float)($dMun['puestos'] ?? 2491)) ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-white mb-0">$<?= number_format((float)($dMun['costo_por_segundo'] ?? 23.70), 2) ?> <span class="small fs-6 text-white-50">/s</span></div>
                    <div class="text-white-50 mt-1" style="font-size: 0.68rem;"><i class="bi bi-clock me-1 text-success"></i>$<?= number_format(((float)($dMun['costo_diario'] ?? 2047397.26)) / 1000000, 2) ?>M al día</div>
                    <div class="text-white-50" style="font-size: 0.62rem;">Piso único: $25,000/mes</div>
                </div>
            </div>

            <!-- Total Combinado (Destacado de resumen) -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="breakdown-card-reloj breakdown-card-total p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-info text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.03em;">Total Base</span>
                        <span class="badge bg-success text-white py-0 px-1" style="font-size: 0.6rem;">Piso Mínimo</span>
                    </div>
                    <div class="fs-5 fw-bold glow-green mb-0">$<?= number_format((float)$gastoSegundo, 2) ?> <span class="small fs-6 text-white-50">/s</span></div>
                    <div class="text-white-50 mt-1" style="font-size: 0.68rem;"><i class="bi bi-lightning-charge text-success me-1"></i>$<?= number_format(((float)$granTotalDiario) / 1000000, 2) ?>M al día</div>
                    <div class="text-white-50" style="font-size: 0.62rem;">Suma exacta 4,274 cargos</div>
                </div>
            </div>
        </div>

        <!-- Nota de Transparencia y Piso Conservador -->
        <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-25 text-start mb-3">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-shield-check text-warning fs-5 mt-1"></i>
                <div class="small text-white-50" style="font-size: 0.76rem; line-height: 1.45;">
                    <strong class="text-white">Nota Metodológica de Piso Mínimo Auditable:</strong>
                    Para <strong>Diputados Federales ($75,763.18/mes)</strong> y <strong>Senadores ($126,800/mes)</strong> se toma únicamente la dieta neta oficial publicada en el PEF/DOF por 12 meses (sin sumar aguinaldos ni apoyos legislativos). Debido a que los sueldos de <strong>Gobernadores</strong>, <strong>Diputados Locales</strong> y <strong>Presidentes Municipales (1 titular por municipio, sin regidores)</strong> no son iguales en cada estado, con fines de mostrar la realidad sin inflar cifras se consideró un <strong>sueldo único y bajo para todos</strong> ($70,000, $45,000 y $25,000 mensuales respectivamente). <strong>Por lo tanto, el gasto real para el erario es mayor a la cantidad que aquí se muestra.</strong>
                </div>
            </div>
        </div>

        <!-- Hitos y Frecuencias Ciudadanas (2x2 en móvil, 4 en desktop) -->
        <div class="p-3 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10 text-start">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-white" style="font-size: 0.8rem;">
                    <i class="bi bi-clock-history text-info me-1"></i> Equivalencias del Erario en Tiempo Real
                </span>
                <span class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10" style="font-size: 0.65rem;">Sobre piso base ($64.37/s)</span>
            </div>
            <div class="row g-2 small text-secondary">
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-3 bg-black bg-opacity-30 h-100 border border-white border-opacity-5">
                        <span class="d-block text-white fw-semibold" style="font-size: 0.75rem;"><i class="bi bi-mortarboard text-warning me-1"></i> Cada 2.4 min:</span>
                        <span style="font-size: 0.72rem;">1 Beca Escolar anual ($9,200)</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-3 bg-black bg-opacity-30 h-100 border border-white border-opacity-5">
                        <span class="d-block text-white fw-semibold" style="font-size: 0.75rem;"><i class="bi bi-wallet2 text-success me-1"></i> Cada 23.2 min:</span>
                        <span style="font-size: 0.72rem;">1 año Salario Mínimo ($89k)</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-3 bg-black bg-opacity-30 h-100 border border-white border-opacity-5">
                        <span class="d-block text-white fw-semibold" style="font-size: 0.75rem;"><i class="bi bi-hospital text-info me-1"></i> Cada 2.80 hrs:</span>
                        <span style="font-size: 0.72rem;">1 Aula Escolar ($650k)</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-3 bg-black bg-opacity-30 h-100 border border-white border-opacity-5">
                        <span class="d-block text-white fw-semibold" style="font-size: 0.75rem;"><i class="bi bi-shield-shaded text-danger me-1"></i> Cada 4.10 hrs:</span>
                        <span style="font-size: 0.72rem;">1 Patrulla Policial ($950k)</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Enlaces Inferiores -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card-civic p-3 p-sm-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="p-2 rounded-circle bg-primary-subtle text-primary fs-5"><i class="bi bi-pie-chart-fill"></i></span>
                    <div>
                        <h4 class="h6 fw-bold mb-0">¿A qué bancadas se va este dinero?</h4>
                        <div class="text-secondary small">Morena, PAN, PRI, PVEM, PT, MC</div>
                    </div>
                </div>
                <p class="text-secondary small mb-3">
                    Revisa qué porcentaje de esta nómina política pública administra cada partido según la cantidad de diputados, senadores, gobernadores y alcaldes que ganaron en las urnas.
                </p>
                <a href="<?= Router::url('/remuneraciones') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    Ver Desglose por Partido &rarr;
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-civic p-3 p-sm-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="p-2 rounded-circle bg-success-subtle text-success fs-5"><i class="bi bi-geo-alt-fill"></i></span>
                    <div>
                        <h4 class="h6 fw-bold mb-0">Ranking por Estado y Costo per Cápita</h4>
                        <div class="text-secondary small">Las 32 entidades federativas</div>
                    </div>
                </div>
                <p class="text-secondary small mb-3">
                    Consulta cuánto cuesta la clase política de tu estado y cuánto le cuesta a cada habitante sostener a su gobernador, congreso local y alcaldías.
                </p>
                <a href="<?= Router::url('/remuneraciones') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                    Ver Ranking por Estado &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    const GASTO_POR_SEGUNDO = <?= (float)$gastoSegundo ?>;
    let currentMode = 'hoy';
    const sessionStartTime = Date.now();

    const displayElement = document.getElementById('dynamicMoneyCounter');
    const labelElement = document.getElementById('timeframeLabel');

    function getBaseAmountForMode(mode) {
        const now = new Date();
        if (mode === 'hoy') {
            const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
            const elapsedSeconds = (now.getTime() - startOfDay) / 1000;
            return elapsedSeconds * GASTO_POR_SEGUNDO;
        } else if (mode === 'mes') {
            const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1).getTime();
            const elapsedSeconds = (now.getTime() - startOfMonth) / 1000;
            return elapsedSeconds * GASTO_POR_SEGUNDO;
        } else if (mode === 'ano') {
            const startOfYear = new Date(now.getFullYear(), 0, 1).getTime();
            const elapsedSeconds = (now.getTime() - startOfYear) / 1000;
            return elapsedSeconds * GASTO_POR_SEGUNDO;
        } else if (mode === 'legislatura') {
            const startLeg = new Date(2024, 8, 1).getTime();
            const elapsedSeconds = (now.getTime() - startLeg) / 1000;
            return elapsedSeconds * GASTO_POR_SEGUNDO;
        } else if (mode === 'sesion') {
            const elapsedSeconds = (Date.now() - sessionStartTime) / 1000;
            return elapsedSeconds * GASTO_POR_SEGUNDO;
        }
        return 0;
    }

    function updateCounter() {
        const amount = getBaseAmountForMode(currentMode);
        displayElement.textContent = '$' + amount.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' MXN';

        requestAnimationFrame(updateCounter);
    }

    requestAnimationFrame(updateCounter);

    document.querySelectorAll('.pill-filter-reloj').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.pill-filter-reloj').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentMode = this.getAttribute('data-mode');

            if (currentMode === 'hoy') {
                labelElement.textContent = 'Gasto público base transcurrido en el día de hoy (desde las 00:00 hrs)';
            } else if (currentMode === 'mes') {
                labelElement.textContent = 'Gasto base acumulado en lo que va del mes actual';
            } else if (currentMode === 'ano') {
                labelElement.textContent = 'Gasto público base acumulado en el año en curso';
            } else if (currentMode === 'legislatura') {
                labelElement.textContent = 'Gasto base acumulado desde que inició funciones la LXVI Legislatura (1 Sept 2024)';
            } else if (currentMode === 'sesion') {
                labelElement.textContent = 'Dinero público transferido a dietas y sueldos base mientras navegas en esta página';
            }
        });
    });

    const btnFullscreen = document.getElementById('btnFullscreen');
    if (btnFullscreen) {
        btnFullscreen.addEventListener('click', function() {
            const el = document.getElementById('contenedorReloj');
            if (!document.fullscreenElement) {
                if (el.requestFullscreen) {
                    el.requestFullscreen();
                } else if (el.webkitRequestFullscreen) {
                    el.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    }

    const btnCompartir = document.getElementById('btnCompartir');
    if (btnCompartir) {
        btnCompartir.addEventListener('click', async function() {
            const shareData = {
                title: 'Reloj del Erario: Costo Base de Cargos de Elección en México',
                text: '¿Sabías que al menos $64.37 pesos por segundo ($5.56M al día) se destinan a dietas y sueldos base de 4,274 políticos en México (bajo criterio de piso mínimo conservador)? Míralo en vivo en El Pulso:',
                url: window.location.href
            };
            if (navigator.share) {
                try {
                    await navigator.share(shareData);
                } catch (e) {}
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('¡Enlace del Reloj copiado al portapapeles!');
            }
        });
    }
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
