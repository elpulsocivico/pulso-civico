<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

$jsonFile = dirname(__DIR__) . '/storage/data/json/tabuladores_cargos_oficiales.json';
$masterData = file_exists($jsonFile) ? json_decode((string)file_get_contents($jsonFile), true) : [];

$totales = $masterData['totales_nacionales'] ?? [];
$cargosFed = $masterData['cargos_federales'] ?? [];
$gobernadores = $masterData['gobernadores'] ?? [];
$congresos = $masterData['congresos_locales'] ?? [];
$alcaldias = $masterData['alcaldias_categorias'] ?? [];
$bancadas = $masterData['bancadas_distribucion'] ?? [];
$estados = $masterData['estados_ranking'] ?? [];

$sortBy = $_GET['sort_by'] ?? 'costo_total_anual';
if ($sortBy === 'costo_per_capita_anual') {
    usort($estados, fn($a, $b) => ($b['costo_per_capita_anual'] ?? 0) <=> ($a['costo_per_capita_anual'] ?? 0));
} elseif ($sortBy === 'poblacion') {
    usort($estados, fn($a, $b) => ($b['poblacion'] ?? 0) <=> ($a['poblacion'] ?? 0));
} elseif ($sortBy === 'nombre') {
    usort($estados, fn($a, $b) => strcmp($a['nombre'] ?? '', $b['nombre'] ?? ''));
} else {
    usort($estados, fn($a, $b) => ($b['costo_total_anual'] ?? 0) <=> ($a['costo_total_anual'] ?? 0));
}

$seo = [
    'title' => '¿Cuánto Cuesta la Clase Política en México? — Tabulador Oficial | El Pulso',
    'description' => 'Monitor interactivo y transparente del costo base de los cargos públicos en México: Diputados Federales, Senadores, Gobernadores, Congresos Locales y Alcaldías.',
    'canonical' => Router::url('/remuneraciones')
];

require __DIR__ . '/layout/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
    .counter-ticker {
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.04em;
    }
    .metric-hero-card {
        background: linear-gradient(135deg, #1C1C1E 0%, #2C2C2E 100%);
        color: #FFFFFF;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
    .card-cargo-box {
        background: #FFFFFF;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-cargo-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    }
    .nav-pills-ios .nav-link {
        color: #636366;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.5rem 1rem;
        transition: all 0.15s ease;
    }
    .nav-pills-ios .nav-link.active {
        background-color: var(--ios-blue);
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.25);
    }
    .table-ranking tr:hover {
        background-color: rgba(0, 122, 255, 0.03);
    }
    .slider-cost {
        accent-color: var(--ios-blue);
    }
    .nav-tabs-mobile-wrapper {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        width: 100%;
        padding-bottom: 4px;
    }
    .nav-tabs-mobile-wrapper::-webkit-scrollbar {
        display: none;
    }
    .nav-tabs-mobile-wrapper .nav-pills {
        flex-wrap: nowrap;
        width: max-content;
        min-width: 100%;
        margin-bottom: 0 !important;
    }
    @media (min-width: 768px) {
        .nav-tabs-mobile-wrapper {
            overflow-x: visible;
            white-space: normal;
        }
        .nav-tabs-mobile-wrapper .nav-pills {
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }
    }
    @media (max-width: 576px) {
        .metric-hero-card {
            padding: 1.5rem 1.15rem !important;
            border-radius: 18px;
        }
        .counter-ticker {
            font-size: clamp(1.8rem, 7.5vw, 3rem) !important;
        }
        .card-cargo-box {
            padding: 1.25rem 1rem !important;
            border-radius: 16px;
        }
    }
</style>

<div class="py-2">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= Router::url('/') ?>" class="text-decoration-none">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Costo Político Institucional</li>
        </ol>
    </nav>

    <!-- Header Hero -->
    <div class="text-center mb-4">
        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 mb-2 fw-semibold">
            <i class="bi bi-shield-check me-1"></i> Basado en Tabuladores Oficiales (PEF / DOF) + Piso Mínimo Conservador
        </span>
        <h1 class="display-6 fw-bold tracking-tight text-dark mb-2">
            ¿Cuánto Cuesta la Clase Política en México?
        </h1>
        <p class="text-secondary mx-auto lead fs-6" style="max-width: 780px;">
            Radiografía ciudadana del piso mínimo de dinero público destinado exclusivamente a dietas y sueldos base de <strong><?= number_format((float)($totales['total_puestos_evaluados'] ?? 4274)) ?> titulares de elección popular</strong>: Diputados Federales, Senadores, Gobernadores, Diputados Locales y Presidentes Municipales.
        </p>
    </div>

    <!-- Aviso Metodológico Destacado (Criterio de Piso Conservador) -->
    <div class="alert alert-warning border-warning-subtle rounded-4 p-3 p-md-4 mb-4 shadow-sm">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-info-circle-fill text-warning-emphasis fs-4 mt-1"></i>
            <div class="small text-dark">
                <h2 class="h6 fw-bold mb-1">Criterio de Piso Mínimo Auditable (¿Por qué el gasto real es mayor al mostrado?)</h2>
                <p class="mb-1">
                    <strong>1. Cargos Federales (Dato Exacto PEF/DOF):</strong> Para los <strong>500 Diputados Federales ($75,763.18/mes)</strong> y <strong>128 Senadores ($126,800.00/mes)</strong> se multiplica únicamente su dieta neta mensual oficial por 12 meses, <em>dejando fuera del cálculo</em> aguinaldos de 40 días y subvenciones parlamentarias de Asistencia Legislativa y Atención Ciudadana.
                </p>
                <p class="mb-0">
                    <strong>2. Cargos Estatales y Municipales (Sueldo Único y Bajo):</strong> Debido a que los sueldos no son iguales en cada entidad y municipio, con fines de mostrar realidad sin inflar cifras se consideró un <strong>sueldo base único y bajo para todos</strong>: <strong>$70,000/mes</strong> por Gobernador (32), <strong>$45,000/mes</strong> por Diputado Local (1,123 curules; el tabulador más bajo del país, Tlaxcala) y <strong>$25,000/mes</strong> por Presidente Municipal (2,491 alcaldes titulares, sin contar síndicos ni regidores). <strong>Por lo tanto, la cantidad final real que desembolsa el erario es superior a la que se muestra.</strong>
                </p>
            </div>
        </div>
    </div>

    <!-- Ticker / Contador del Erario en Tiempo Real -->
    <div class="metric-hero-card p-4 p-md-5 mb-5 text-white">
        <div class="row align-items-center g-4">
            <div class="col-lg-7 text-center text-lg-start">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white-50 small mb-3">
                    <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
                    <span>Costo Anual Base (Piso Conservador) • 4,274 Titulares de Elección</span>
                </div>
                <div class="display-4 fw-black text-warning counter-ticker mb-1" id="liveMoneyCounter">
                    $<?= number_format((float)($totales['gran_total_anual_mxn'] ?? 2029943880.00), 2) ?> MXN
                </div>
                <div class="text-white-50 small">
                    <i class="bi bi-arrow-repeat text-info me-1"></i> Equivale a un piso mínimo de <strong>$<?= number_format((float)($totales['gran_total_por_segundo_mxn'] ?? 64.37), 2) ?> pesos cada segundo</strong> ($5.56M al día) en dietas y sueldos base puros.
                </div>
            </div>

            <div class="col-lg-5">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="text-white-50 small">Gasto Mensual Base</div>
                            <div class="fs-5 fw-bold text-white">$<?= number_format((float)($totales['gran_total_mensual_mxn'] ?? 169161990)) ?></div>
                            <div class="text-white-50" style="font-size: 0.72rem;">Sueldos netos base / mes</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="text-white-50 small">Gasto Diario Base</div>
                            <div class="fs-5 fw-bold text-white">$<?= number_format((float)($totales['gran_total_por_dia_mxn'] ?? 5561490)) ?></div>
                            <div class="text-white-50" style="font-size: 0.72rem;">Costo al día (365 días)</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="text-white-50 small">Titulares Evaluados</div>
                            <div class="fs-5 fw-bold text-white"><?= number_format((float)($totales['total_puestos_evaluados'] ?? 4274)) ?></div>
                            <div class="text-white-50" style="font-size: 0.72rem;">1 titular por cargo</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="text-white-50 small">Criterio Contable</div>
                            <div class="fs-5 fw-bold text-white">Piso Bajo</div>
                            <div class="text-white-50" style="font-size: 0.72rem;">Sin apoyos ni aguinaldo</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas Interactivas con Desplazamiento Móvil -->
    <div class="nav-tabs-mobile-wrapper mb-4">
        <ul class="nav nav-pills nav-pills-ios gap-2 p-2 bg-light rounded-4 border" id="remuneracionesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2" id="tab-cargos-btn" data-bs-toggle="pill" data-bs-target="#tab-cargos" type="button" role="tab">
                <i class="bi bi-briefcase"></i>
                <span>Por Cargo / Puesto</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-bancadas-btn" data-bs-toggle="pill" data-bs-target="#tab-bancadas" type="button" role="tab">
                <i class="bi bi-pie-chart"></i>
                <span>Por Bancada / Partido</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-estados-btn" data-bs-toggle="pill" data-bs-target="#tab-estados" type="button" role="tab">
                <i class="bi bi-geo-alt"></i>
                <span>Ranking por Estado</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-simulador-btn" data-bs-toggle="pill" data-bs-target="#tab-simulador" type="button" role="tab">
                <i class="bi bi-calculator"></i>
                <span>Simulador Ciudadano</span>
            </button>
        </li>
        </ul>
    </div>

    <!-- Contenido de las Pestañas -->
    <div class="tab-content" id="remuneracionesTabContent">
        <!-- PESTAÑA 1: POR CARGO -->
        <div class="tab-pane fade show active" id="tab-cargos" role="tabpanel">
            <div class="row g-4 mb-4">
                <!-- Diputado Federal -->
                <div class="col-lg-6">
                    <div class="card-cargo-box p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill mb-1">Poder Legislativo Federal (Tabulador Único PEF)</span>
                                <h3 class="h5 fw-bold text-dark mb-0">Diputado(a) Federal</h3>
                                <div class="text-secondary small">Cámara de Diputados • San Lázaro (500 curules)</div>
                            </div>
                            <span class="fs-4 text-primary"><i class="bi bi-buildings"></i></span>
                        </div>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="text-secondary small">Dieta Mensual Neta (Contabilizada)</div>
                                    <div class="fs-5 fw-bold text-dark">$<?= number_format((float)($cargosFed['diputado_federal']['dieta_mensual_neta'] ?? 75763.18), 2) ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-secondary small">Costo Anual Base (12 meses)</div>
                                    <div class="fs-5 fw-bold text-primary">$<?= number_format((float)($cargosFed['diputado_federal']['costo_anual_puesto'] ?? 909158.16), 2) ?></div>
                                </div>
                            </div>
                        </div>
                        <ul class="list-unstyled small mb-3 text-secondary space-y-1">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span><i class="bi bi-check2-circle text-success me-1"></i> <strong>Dieta Neta Oficial PEF (× 12 meses)</strong></span>
                                <strong class="text-dark">$909,158.16 /año</strong>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span><i class="bi bi-dash-circle text-muted me-1"></i> Asistencia Legislativa <span class="badge bg-light text-secondary border">No sumada al piso</span></span>
                                <span class="text-muted">$<?= number_format((float)($cargosFed['diputado_federal']['asistencia_legislativa_mensual'] ?? 45786), 2) ?> /mes</span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span><i class="bi bi-dash-circle text-muted me-1"></i> Atención Ciudadana <span class="badge bg-light text-secondary border">No sumada al piso</span></span>
                                <span class="text-muted">$<?= number_format((float)($cargosFed['diputado_federal']['atencion_ciudadana_mensual'] ?? 28772), 2) ?> /mes</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span><i class="bi bi-dash-circle text-muted me-1"></i> Aguinaldo 40 días <span class="badge bg-light text-secondary border">No sumado al piso</span></span>
                                <span class="text-muted">$<?= number_format((float)($cargosFed['diputado_federal']['aguinaldo_anual'] ?? 140504), 2) ?> /año</span>
                            </li>
                        </ul>
                        <div class="mt-auto pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Total Base 500 Diputados / año:</span>
                                <span class="badge bg-dark fs-6">$<?= number_format((float)($cargosFed['diputado_federal']['costo_anual_total_organo'] ?? 454579080)) ?> MXN</span>
                            </div>
                            <div class="text-muted mt-2" style="font-size: 0.72rem;">
                                Cálculo exacto: $75,763.18 × 12 meses × 500 curules ($14.41/s | $1.25M al día)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Senador -->
                <div class="col-lg-6">
                    <div class="card-cargo-box p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-purple-subtle text-purple border rounded-pill mb-1">Cámara Alta Federal (Tabulador Único PEF)</span>
                                <h3 class="h5 fw-bold text-dark mb-0">Senador(a) de la República</h3>
                                <div class="text-secondary small">Senado de la República • Reforma (128 escaños)</div>
                            </div>
                            <span class="fs-4 text-purple"><i class="bi bi-bank"></i></span>
                        </div>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="text-secondary small">Dieta Mensual Neta (Contabilizada)</div>
                                    <div class="fs-5 fw-bold text-dark">$<?= number_format((float)($cargosFed['senador_republica']['dieta_mensual_neta'] ?? 126800.00), 2) ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-secondary small">Costo Anual Base (12 meses)</div>
                                    <div class="fs-5 fw-bold text-purple">$<?= number_format((float)($cargosFed['senador_republica']['costo_anual_puesto'] ?? 1521600.00), 2) ?></div>
                                </div>
                            </div>
                        </div>
                        <ul class="list-unstyled small mb-3 text-secondary space-y-1">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span><i class="bi bi-check2-circle text-success me-1"></i> <strong>Dieta Neta Oficial PEF (× 12 meses)</strong></span>
                                <strong class="text-dark">$1,521,600.00 /año</strong>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span><i class="bi bi-info-circle text-muted me-1"></i> Percepción Bruta Mensual (PEF)</span>
                                <span class="text-muted">$<?= number_format((float)($cargosFed['senador_republica']['dieta_mensual_bruta'] ?? 171444), 2) ?> /mes</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span><i class="bi bi-dash-circle text-muted me-1"></i> Aguinaldo 40 días <span class="badge bg-light text-secondary border">No sumado al piso</span></span>
                                <span class="text-muted">$<?= number_format((float)($cargosFed['senador_republica']['aguinaldo_anual'] ?? 230000), 2) ?> /año</span>
                            </li>
                        </ul>
                        <div class="mt-auto pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Total Base 128 Senadores / año:</span>
                                <span class="badge bg-dark fs-6">$<?= number_format((float)($cargosFed['senador_republica']['costo_anual_total_organo'] ?? 194764800)) ?> MXN</span>
                            </div>
                            <div class="text-muted mt-2" style="font-size: 0.72rem;">
                                Cálculo exacto: $126,800.00 × 12 meses × 128 escaños ($6.18/s | $534k al día)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gobernadores -->
                <div class="col-lg-6">
                    <div class="card-cargo-box p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill mb-1">Poder Ejecutivo Estatal (Sueldo Único y Bajo)</span>
                                <h3 class="h5 fw-bold text-dark mb-0">Gobernadores de Estado</h3>
                                <div class="text-secondary small">32 Mandatarios Ejecutivos de las Entidades Federativas</div>
                            </div>
                            <span class="fs-4 text-warning"><i class="bi bi-award"></i></span>
                        </div>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="text-secondary small">Sueldo Único Bajo Aplicado</div>
                                    <div class="fs-5 fw-bold text-dark">$70,000.00 /mes</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-secondary small">Costo Base Anual por Cargo</div>
                                    <div class="fs-5 fw-bold text-warning-emphasis">$840,000.00 /año</div>
                                </div>
                            </div>
                        </div>
                        <p class="small text-secondary mb-3">
                            Debido a que los sueldos no son iguales por estado (en estados como Chihuahua, Guanajuato o Coahuila superan los $109,000 netos mensuales), con fines de mostrar realidad sin inflar cifras se consideró un <strong>sueldo único y bajo de $70,000/mes</strong> para los 32 titulares. La cifra real al erario es superior.
                        </p>
                        <div class="mt-auto pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Total Base 32 Gobernadores / año:</span>
                                <span class="badge bg-dark fs-6">$<?= number_format((float)($totales['desglose_por_orden']['poderes_ejecutivos_estatales']['costo_anual'] ?? 26880000)) ?> MXN</span>
                            </div>
                            <div class="text-muted mt-2" style="font-size: 0.72rem;">
                                Cálculo exacto: $70,000 × 12 meses × 32 gobernadores ($0.85/s | $73.6k al día)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Congresos Locales -->
                <div class="col-lg-6">
                    <div class="card-cargo-box p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill mb-1">Poder Legislativo Estatal (Sueldo Único y Bajo)</span>
                                <h3 class="h5 fw-bold text-dark mb-0">Diputados(as) Locales</h3>
                                <div class="text-secondary small">1,123 Curules en los 32 Congresos Estatales</div>
                            </div>
                            <span class="fs-4 text-info"><i class="bi bi-people"></i></span>
                        </div>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="text-secondary small">Dieta Única Baja Aplicada</div>
                                    <div class="fs-5 fw-bold text-dark">$45,000.00 /mes</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-secondary small">Costo Base Anual por Curul</div>
                                    <div class="fs-5 fw-bold text-info-emphasis">$540,000.00 /año</div>
                                </div>
                            </div>
                        </div>
                        <p class="small text-secondary mb-3">
                            Como las dietas no son iguales por estado (en Guanajuato o Edomex llegan a $98k–$125k netos más apoyos), se aplicó para todas las 1,123 curules un <strong>sueldo único y bajo de $45,000/mes</strong> (el más bajo del país, Tlaxcala), sin sumar apoyos legislativos ni aguinaldos. El costo real es mayor.
                        </p>
                        <div class="mt-auto pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Total Base 1,123 Diputados Locales / año:</span>
                                <span class="badge bg-dark fs-6">$<?= number_format((float)($totales['desglose_por_orden']['congresos_estatales_locales']['costo_anual'] ?? 606420000)) ?> MXN</span>
                            </div>
                            <div class="text-muted mt-2" style="font-size: 0.72rem;">
                                Cálculo exacto: $45,000 × 12 meses × 1,123 curules ($19.23/s | $1.66M al día)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alcaldías -->
                <div class="col-12">
                    <div class="card-cargo-box p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mb-1">Orden de Gobierno Municipal (1 Titular por Municipio)</span>
                                <h3 class="h5 fw-bold text-dark mb-0">Presidentes Municipales y Alcaldes Titulares</h3>
                                <div class="text-secondary small">2,475 Municipios de la República y 16 Alcaldías de la CDMX (2,491 titulares; excluye síndicos y regidores)</div>
                            </div>
                            <span class="fs-4 text-success"><i class="bi bi-geo"></i></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoría de Municipio (Referencia INEGI)</th>
                                        <th class="text-center">Titulares (1 por Mpio.)</th>
                                        <th class="text-end">Sueldo Único Bajo Aplicado</th>
                                        <th class="text-end">Costo Base Anual por Alcalde</th>
                                        <th class="text-end">Costo Base Anual Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alcaldias as $catId => $cat): ?>
                                    <tr>
                                        <td>
                                            <strong><?= ViewHelper::e($cat['nombre']) ?></strong>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= ViewHelper::e($cat['ejemplos']) ?></div>
                                        </td>
                                        <td class="text-center fw-bold"><?= $cat['total_municipios'] ?></td>
                                        <td class="text-end">$<?= number_format((float)$cat['sueldo_mensual_neto_promedio'], 2) ?> /mes</td>
                                        <td class="text-end fw-semibold">$<?= number_format((float)$cat['costo_anual_por_alcaldia'], 2) ?></td>
                                        <td class="text-end fw-bold text-primary">$<?= number_format((float)$cat['costo_anual_total_categoria'], 2) ?> MXN</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>Total Presidentes Municipales y Alcaldes (Sin síndicos ni regidores)</td>
                                        <td class="text-center">2,491</td>
                                        <td class="text-end">$25,000.00 /mes</td>
                                        <td class="text-end">$300,000.00 /año</td>
                                        <td class="text-end text-success">$<?= number_format((float)($totales['desglose_por_orden']['presidencias_municipales_alcaldias']['costo_anual'] ?? 747300000), 2) ?> MXN</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="text-muted mt-2" style="font-size: 0.72rem;">
                            Cálculo exacto: $25,000 × 12 meses × 2,491 presidentes municipales = $747,300,000 MXN/año ($23.70/s | $2.05M al día). Como en ciudades medias y metrópolis el sueldo real supera los $70k–$115k mensuales, el monto real al erario es mayor al mostrado.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: POR BANCADA -->
        <div class="tab-pane fade" id="tab-bancadas" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="card-cargo-box p-4 h-100">
                        <h4 class="h6 fw-bold text-dark mb-1">Distribución del Piso Salarial por Fuerza Política</h4>
                        <p class="text-secondary small mb-4">
                            Porcentaje del piso presupuestal según los puestos titulares que cada fuerza política ostenta (Diputados Federales, Senadores, Gobernadores, Diputados Locales y Alcaldes).
                        </p>
                        <div style="height: 280px; position: relative;">
                            <canvas id="chartBancadas"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card-cargo-box p-4 h-100">
                        <h4 class="h6 fw-bold text-dark mb-3">Detalle del Gasto Base por Partido</h4>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Partido / Bancada</th>
                                        <th class="text-center">Dips Fed.</th>
                                        <th class="text-center">Senadores</th>
                                        <th class="text-center">Gobernadores</th>
                                        <th class="text-center">Dips Loc.</th>
                                        <th class="text-center">Alcaldes</th>
                                        <th class="text-end">Costo Anual Base</th>
                                        <th class="text-end">% Erario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bancadas as $pName => $b): ?>
                                    <tr>
                                        <td>
                                            <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background-color: <?= $b['color'] ?>;"></span>
                                            <strong><?= ViewHelper::e($b['partido']) ?></strong>
                                        </td>
                                        <td class="text-center"><?= $b['diputados_federales'] ?></td>
                                        <td class="text-center"><?= $b['senadores'] ?></td>
                                        <td class="text-center"><?= $b['gobernadores'] ?></td>
                                        <td class="text-center"><?= $b['curules_locales'] ?></td>
                                        <td class="text-center"><?= $b['alcaldias'] ?></td>
                                        <td class="text-end fw-bold">$<?= number_format((float)$b['costo_anual_total'], 2) ?></td>
                                        <td class="text-end"><span class="badge bg-light text-dark border"><?= $b['porcentaje_del_erario'] ?>%</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 3: RANKING POR ESTADO -->
        <div class="tab-pane fade" id="tab-estados" role="tabpanel">
            <div class="card-cargo-box p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h4 class="h6 fw-bold text-dark mb-1">Costo Base Político por Entidad Federativa</h4>
                        <p class="text-secondary small mb-0">
                            Gobernador(a) ($840k/año) + Congreso Local ($540k/curul) + Alcaldes ($300k/municipio) + Legisladores Federales de la entidad.
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <input type="text" id="filtroEstadoInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Buscar estado..." style="max-width: 200px;">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-sort-down me-1"></i> Ordenar
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item small" href="<?= Router::url('/remuneraciones?sort_by=costo_total_anual') ?>">Mayor Costo Total</a></li>
                                <li><a class="dropdown-item small" href="<?= Router::url('/remuneraciones?sort_by=costo_per_capita_anual') ?>">Mayor Costo por Habitante</a></li>
                                <li><a class="dropdown-item small" href="<?= Router::url('/remuneraciones?sort_by=poblacion') ?>">Mayor Población</a></li>
                                <li><a class="dropdown-item small" href="<?= Router::url('/remuneraciones?sort_by=nombre') ?>">Alfabético</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0 table-ranking" id="tablaEstados">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Entidad Federativa</th>
                                <th>Gobierno</th>
                                <th class="text-center">Curules Locales</th>
                                <th class="text-center">Alcaldes Mpales.</th>
                                <th class="text-end">Costo Anual Base</th>
                                <th class="text-end">Costo Base por Habitante / Año</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($estados as $est): ?>
                            <tr class="fila-estado" data-nombre="<?= strtolower($est['nombre']) ?>">
                                <td class="text-muted"><?= $i++ ?></td>
                                <td>
                                    <a href="<?= Router::url('/estados/' . $est['slug']) ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= ViewHelper::e($est['nombre']) ?>
                                    </a>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= number_format((float)$est['poblacion']) ?> hab.</div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= ViewHelper::e($est['partido_gobierno']) ?></span></td>
                                <td class="text-center"><?= $est['curules_locales'] ?></td>
                                <td class="text-center"><?= $est['total_municipios'] ?></td>
                                <td class="text-end fw-bold text-primary">$<?= number_format((float)$est['costo_total_anual'], 2) ?></td>
                                <td class="text-end"><span class="badge bg-secondary-subtle text-secondary-emphasis fw-bold">$<?= number_format((float)$est['costo_per_capita_anual'], 2) ?> MXN</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 4: SIMULADOR -->
        <div class="tab-pane fade" id="tab-simulador" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card-cargo-box p-4 h-100">
                        <h4 class="h5 fw-bold text-dark mb-2">Simulador de Costo Base (Piso Conservador)</h4>
                        <p class="text-secondary small mb-4">
                            Ajusta el número de puestos para calcular en tiempo real cuánto le cuesta como mínimo al erario público (12 meses de sueldo base neto) y a qué obras equivale:
                        </p>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span>Diputados Federales ($909,158.16 / año | $75,763/mes):</span>
                                <span class="badge bg-primary" id="valDipsFed">10</span>
                            </div>
                            <input type="range" class="form-range slider-cost" id="sliderDipsFed" min="0" max="500" value="10">
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span>Senadores de la República ($1,521,600 / año | $126,800/mes):</span>
                                <span class="badge bg-purple" id="valSens">2</span>
                            </div>
                            <input type="range" class="form-range slider-cost" id="sliderSens" min="0" max="128" value="2">
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span>Gobernadores ($840,000 / año | piso $70,000/mes):</span>
                                <span class="badge bg-warning text-dark" id="valGobs">1</span>
                            </div>
                            <input type="range" class="form-range slider-cost" id="sliderGobs" min="0" max="32" value="1">
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span>Diputados Locales ($540,000 / año | piso $45,000/mes):</span>
                                <span class="badge bg-info text-dark" id="valDipsLoc">25</span>
                            </div>
                            <input type="range" class="form-range slider-cost" id="sliderDipsLoc" min="0" max="1123" value="25">
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span>Presidentes Municipales ($300,000 / año | piso $25,000/mes):</span>
                                <span class="badge bg-success" id="valAlcaldes">5</span>
                            </div>
                            <input type="range" class="form-range slider-cost" id="sliderAlcaldes" min="0" max="2491" value="5">
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card-cargo-box p-4 h-100 bg-light d-flex flex-column">
                        <h4 class="h5 fw-bold text-dark mb-1">Resultado de la Simulación</h4>
                        <div class="text-secondary small mb-3">Impacto presupuestal anual mínimo calculado:</div>

                        <div class="p-3 bg-white rounded-3 border mb-4 text-center">
                            <div class="text-muted small">Costo Total Anual Base</div>
                            <div class="display-6 fw-bold text-primary" id="simMontoAnual">$27,974,782 MXN</div>
                            <div class="text-muted small">Costo Mensual Base: <strong id="simMontoMensual">$2,331,232 MXN</strong></div>
                        </div>

                        <h5 class="h6 fw-bold text-dark mb-2">¿A qué equivale este dinero para la ciudadanía?</h5>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-3 border">
                                    <div class="fs-4 fw-bold text-success mb-1" id="eqBecas">3,040</div>
                                    <div class="text-secondary small">Becas de estudio escolares por 1 año</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-3 border">
                                    <div class="fs-4 fw-bold text-info mb-1" id="eqSalarios">312.2</div>
                                    <div class="text-secondary small">Años de Salario Mínimo general</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-3 border">
                                    <div class="fs-4 fw-bold text-warning mb-1" id="eqPatrullas">29</div>
                                    <div class="text-secondary small">Patrullas policiacas equipadas</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-white rounded-3 border">
                                    <div class="fs-4 fw-bold text-danger mb-1" id="eqAulas">43</div>
                                    <div class="text-secondary small">Aulas escolares completas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_TOTAL_ANUAL = <?= (float)($totales['gran_total_anual_mxn'] ?? 2029943880.00) ?>;
    const GASTO_POR_SEGUNDO = <?= (float)($totales['gran_total_por_segundo_mxn'] ?? 64.37) ?>;
    let dineroAcumulado = BASE_TOTAL_ANUAL;

    const counterElement = document.getElementById('liveMoneyCounter');
    if (counterElement) {
        setInterval(() => {
            dineroAcumulado += (GASTO_POR_SEGUNDO / 10);
            counterElement.textContent = '$' + dineroAcumulado.toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' MXN';
        }, 100);
    }

    const ctxBancadas = document.getElementById('chartBancadas');
    if (ctxBancadas) {
        const bancadasData = <?= json_encode($bancadas, JSON_UNESCAPED_UNICODE) ?>;
        const labels = Object.keys(bancadasData);
        const values = labels.map(k => bancadasData[k].costo_anual_total);
        const colors = labels.map(k => bancadasData[k].color);

        new Chart(ctxBancadas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                return ' ' + context.label + ': $' + Number(val).toLocaleString('es-MX') + ' MXN';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    const filtroEstadoInput = document.getElementById('filtroEstadoInput');
    if (filtroEstadoInput) {
        filtroEstadoInput.addEventListener('input', function(e) {
            const q = e.target.value.toLowerCase().trim();
            const filas = document.querySelectorAll('.fila-estado');
            filas.forEach(fila => {
                const nombre = fila.getAttribute('data-nombre') || '';
                fila.style.display = nombre.includes(q) ? '' : 'none';
            });
        });
    }

    const sliderDipsFed = document.getElementById('sliderDipsFed');
    const sliderSens = document.getElementById('sliderSens');
    const sliderGobs = document.getElementById('sliderGobs');
    const sliderDipsLoc = document.getElementById('sliderDipsLoc');
    const sliderAlcaldes = document.getElementById('sliderAlcaldes');

    function actualizarSimulador() {
        const df = parseInt(sliderDipsFed.value) || 0;
        const sen = parseInt(sliderSens.value) || 0;
        const gob = parseInt(sliderGobs.value) || 0;
        const dl = parseInt(sliderDipsLoc.value) || 0;
        const alc = parseInt(sliderAlcaldes.value) || 0;

        document.getElementById('valDipsFed').textContent = df;
        document.getElementById('valSens').textContent = sen;
        document.getElementById('valGobs').textContent = gob;
        document.getElementById('valDipsLoc').textContent = dl;
        document.getElementById('valAlcaldes').textContent = alc;

        const costoAnual = (df * 909158.16) + (sen * 1521600.0) + (gob * 840000.0) + (dl * 540000.0) + (alc * 300000.0);
        const costoMensual = costoAnual / 12.0;

        document.getElementById('simMontoAnual').textContent = '$' + Number(costoAnual.toFixed(2)).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MXN';
        document.getElementById('simMontoMensual').textContent = '$' + Number(costoMensual.toFixed(2)).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MXN';

        const becas = Math.floor(costoAnual / 9200);
        const salarios = (costoAnual / 89615).toFixed(1);
        const patrullas = Math.floor(costoAnual / 950000);
        const aulas = Math.floor(costoAnual / 650000);

        document.getElementById('eqBecas').textContent = Number(becas).toLocaleString('es-MX');
        document.getElementById('eqSalarios').textContent = Number(salarios).toLocaleString('es-MX');
        document.getElementById('eqPatrullas').textContent = Number(patrullas).toLocaleString('es-MX');
        document.getElementById('eqAulas').textContent = Number(aulas).toLocaleString('es-MX');
    }

    [sliderDipsFed, sliderSens, sliderGobs, sliderDipsLoc, sliderAlcaldes].forEach(slider => {
        if (slider) slider.addEventListener('input', actualizarSimulador);
    });

    actualizarSimulador();
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
