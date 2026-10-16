<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array<string, mixed> $municipio
 * @var array $seo
 */

$homeUrl = Router::url('/');
$alcaldesUrl = Router::url('/alcaldes');
$nombreMun = $municipio['municipio'] ?? 'Municipio';
$entidad = $municipio['entidad'] ?? 'México';
$cvegeo = $municipio['cvegeo'] ?? '00000';
$cveMun = $municipio['cve_mun'] ?? 0;
$cveEnt = $municipio['cve_ent'] ?? 0;
$regimen = $municipio['regimen_electoral'] ?? 'Partidos Políticos';
$esSni = !empty($municipio['es_usos_costumbres']) || str_contains(strtolower($regimen), 'normativos');
$alcaldeActual = $municipio['alcalde_actual'] ?? [];
$cronologia = $municipio['cronologia'] ?? [];
$cabildo = $municipio['cabildo'] ?? [];
$totalMandatos = count($cronologia);

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= $alcaldesUrl ?>">Alcaldías y Municipios</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= ViewHelper::e($nombreMun) ?></li>
    </ol>
</nav>

<!-- Encabezado del Municipio -->
<div class="card-civic p-4 p-md-5 mb-4 position-relative overflow-hidden">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-light text-secondary border font-monospace">
                    Clave INEGI <?= ViewHelper::e($cvegeo) ?>
                </span>
                <span class="badge bg-primary rounded-pill"><?= ViewHelper::e($entidad) ?></span>
                <?php if ($esSni): ?>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill">
                        <i class="bi bi-people me-1"></i>Sistemas Normativos Indígenas
                    </span>
                <?php else: ?>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-diagram-3 me-1"></i>Régimen de Partidos Políticos
                    </span>
                <?php endif; ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                    <i class="bi bi-calendar-check me-1"></i><?= $totalMandatos ?> Periodos Históricos Registrados
                </span>
            </div>
            <h1 class="display-6 fw-bold text-dark mb-2">
                Municipio de <?= ViewHelper::e($nombreMun) ?>
            </h1>
            <p class="text-secondary mb-0">
                Historial de alternancia, presidentes municipales y gobernanza local en <?= ViewHelper::e($nombreMun) ?>, <?= ViewHelper::e($entidad) ?> (1970–2027).
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="<?= $alcaldesUrl ?>" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Volver al Directorio Nacional</span>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Columna Izquierda: Alcalde en Funciones -->
    <div class="col-lg-4">
        <div class="card-civic p-4 sticky-top shadow-xs" style="top: 20px;">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>En Funciones
                </span>
                <span class="text-muted small"><?= ViewHelper::e($alcaldeActual['periodo'] ?? 'Periodo Actual') ?></span>
            </div>

            <div class="text-center my-3">
                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center border border-primary-subtle shadow-xs mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-person fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">
                    <?= ViewHelper::e($alcaldeActual['nombre_completo'] ?? 'Autoridad Municipal') ?>
                </h4>
                <div class="text-muted small mb-2">
                    <?= ViewHelper::e($alcaldeActual['tipo_cargo'] ?? 'Presidente(a) Municipal') ?>
                </div>

                <?php if ($esSni): ?>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1">
                        <i class="bi bi-people"></i>
                        <span>Elección por Asamblea Comunitaria</span>
                    </span>
                <?php elseif (!empty($alcaldeActual['partido'])): ?>
                    <span class="badge bg-primary text-white px-3 py-2 rounded-pill fs-6">
                        <?= ViewHelper::e($alcaldeActual['partido']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <ul class="list-group list-group-flush small border-top pt-2">
                <?php if (!empty($alcaldeActual['segundo_lugar'])): ?>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">2° Lugar Electoral:</span>
                        <span class="fw-semibold text-end text-dark"><?= ViewHelper::e($alcaldeActual['segundo_lugar']) ?></span>
                    </li>
                <?php endif; ?>
                <?php if (isset($alcaldeActual['margen_victoria']) && $alcaldeActual['margen_victoria'] !== null): ?>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Margen de Victoria:</span>
                        <span class="fw-semibold text-dark"><?= number_format((float)$alcaldeActual['margen_victoria'] * 100, 2) ?>%</span>
                    </li>
                <?php endif; ?>
                <?php if (!empty($alcaldeActual['reeleccion'])): ?>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Estatus:</span>
                        <span class="badge bg-info-subtle text-info-emphasis border">Reelección Consecutiva</span>
                    </li>
                <?php endif; ?>
                <?php if (!empty($alcaldeActual['notas'])): ?>
                    <li class="list-group-item px-0 py-2">
                        <span class="text-muted d-block mb-1">Notas Relevantes:</span>
                        <span class="text-dark bg-light p-2 rounded d-block border"><?= ViewHelper::e($alcaldeActual['notas']) ?></span>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="mt-3 pt-3 border-top text-muted" style="font-size: 0.72rem;">
                <i class="bi bi-shield-check text-success me-1"></i>
                Fuente oficial: <?= ViewHelper::e($alcaldeActual['metadata']['fuente_url'] ?? 'Bases Electorales Oficiales / OPLEs') ?>
            </div>
        </div>

        <?php if (!empty($cabildo)): ?>
            <div class="card-civic p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-people text-primary"></i>
                        <span>Cabildo Municipal</span>
                    </h5>
                    <span class="badge bg-light text-secondary border rounded-pill">
                        <?= count($cabildo) ?> Ediles
                    </span>
                </div>
                <p class="text-muted small mb-3">Órgano de gobierno colegiado y deliberativo del Ayuntamiento.</p>
                <div class="list-group list-group-flush small">
                    <?php foreach ($cabildo as $ed): ?>
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border" style="font-size: 0.72rem;">
                                    <?= ViewHelper::e($ed['rol_cabildo'] ?? 'Edil') ?>
                                </span>
                                <?php if (!empty($ed['partido'])): ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                        <?= ViewHelper::e($ed['partido']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="fw-semibold text-dark"><?= ViewHelper::e($ed['nombre_completo']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= ViewHelper::e($ed['cargo']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Columna Derecha: Cronología Histórica (1970–2027) -->
    <div class="col-lg-8">
        <div class="card-civic p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Línea de Tiempo y Alternancia Histórica</h4>
                    <p class="text-secondary small mb-0">Evolución de autoridades y elecciones municipales desde 1970</p>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                    <?= $totalMandatos ?> Registros
                </span>
            </div>

            <?php if (empty($cronologia)): ?>
                <div class="text-center p-4 text-muted">
                    <i class="bi bi-clock-history fs-2 mb-2 d-block"></i>
                    <p class="mb-0">No se encontraron registros cronológicos anteriores para este municipio.</p>
                </div>
            <?php else: ?>
                <div class="timeline-container position-relative">
                    <?php 
                    // Mostramos del más reciente al más antiguo para lectura natural
                    $cronoReversa = array_reverse($cronologia);
                    foreach ($cronoReversa as $idx => $mItem): 
                        $esActual = !empty($mItem['es_vigente']);
                        $partidoItem = $mItem['partido'] ?? 'Sin Partido';
                        $nombreTitular = $mItem['nombre_completo'] ?? 'Presidente Municipal';
                        $slugPolitico = ViewHelper::slugify($nombreTitular);
                        $politicoUrl = Router::url('/politicos/' . $slugPolitico);
                    ?>
                        <div class="d-flex gap-3 mb-4 position-relative">
                            <!-- Nodo visual -->
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center border <?= $esActual ? 'bg-success text-white border-success shadow-xs' : 'bg-light text-secondary border-secondary-subtle' ?>" style="width: 38px; height: 38px; z-index: 2;">
                                    <i class="bi <?= $esActual ? 'bi-star-fill' : 'bi-calendar' ?> small"></i>
                                </div>
                                <?php if ($idx < count($cronoReversa) - 1): ?>
                                    <div class="border-start border-2 h-100 my-1" style="border-color: #dee2e6 !important;"></div>
                                <?php endif; ?>
                            </div>

                            <!-- Contenido del periodo -->
                            <div class="card-civic p-3 flex-grow-1 border <?= $esActual ? 'border-primary bg-primary-subtle bg-opacity-10' : 'bg-white' ?>">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <div>
                                        <span class="badge <?= $esActual ? 'bg-primary' : 'bg-light text-dark border' ?> rounded-pill px-2 py-1">
                                            <?= ViewHelper::e($mItem['periodo'] ?? '') ?>
                                        </span>
                                        <?php if ($esActual): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Actual</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($mItem['partido'])): ?>
                                        <span class="badge bg-light text-primary border fw-semibold">
                                            <?= ViewHelper::e($mItem['partido']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    <?= ViewHelper::e($nombreTitular) ?>
                                </h6>

                                <div class="d-flex flex-wrap gap-3 text-muted small mt-2">
                                    <?php if (!empty($mItem['total_votos'])): ?>
                                        <span><i class="bi bi-people me-1"></i><?= number_format((int)$mItem['total_votos']) ?> votos</span>
                                    <?php endif; ?>
                                    <?php if (isset($mItem['margen_victoria']) && $mItem['margen_victoria'] !== null): ?>
                                        <span><i class="bi bi-graph-up me-1"></i>Margen: <?= number_format((float)$mItem['margen_victoria'] * 100, 1) ?>%</span>
                                    <?php endif; ?>
                                    <?php if (!empty($mItem['segundo_lugar'])): ?>
                                        <span><i class="bi bi-person-dash me-1"></i>2°: <?= ViewHelper::e($mItem['segundo_lugar']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($mItem['notas'])): ?>
                                    <div class="alert alert-light border small py-1 px-2 mt-2 mb-0 text-secondary">
                                        <i class="bi bi-info-circle me-1"></i><?= ViewHelper::e($mItem['notas']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
