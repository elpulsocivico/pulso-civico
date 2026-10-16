<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $estados
 * @var array $comisiones
 * @var array $politicos
 * @var array $seo
 */

$homeUrl = Router::url('/');
$bcUrl = Router::url('/estados/baja-california');

require __DIR__ . '/layout/header.php';
?>

<!-- Hero Section -->
<div class="text-center py-4 py-md-5 mb-4">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary-subtle text-primary small fw-semibold mb-3">
        <i class="bi bi-shield-check"></i> El Pulso de la Política &bull; <?= ViewHelper::e($currentLegRomano ?? ($romano ?? 'LXVI')) ?> Legislatura (<?= ViewHelper::e($periodo ?? '2024–2027') ?>)
    </div>
    <h1 class="display-5 fw-bold text-dark mb-3">
        El Pulso de la Política
    </h1>
    <p class="lead text-secondary mx-auto mb-4" style="max-width: 680px; font-size: 1.1rem;">
        Monitorea la composición del Congreso, la fuerza de los partidos y el perfil de cada legislador en los 32 estados de la República en tiempo real.
    </p>

    <!-- Barra de Búsqueda Spotlight Front & Center -->
    <div class="mx-auto" style="max-width: 580px;">
        <div class="card-civic p-2 d-flex align-items-center gap-2 shadow-sm border" style="cursor: pointer;" onclick="document.getElementById('btnOpenSpotlight').click();">
            <i class="bi bi-search text-primary fs-5 ms-2"></i>
            <span class="text-secondary small flex-grow-1 text-start">
                Buscar legislador, comisión, estado o partido...
            </span>
            <kbd class="bg-light border text-muted px-2 py-1 small me-2">Ctrl+K</kbd>
        </div>

        <!-- Accesos Rápidos Populares -->
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mt-3" style="font-size: 0.8rem;">
            <span class="text-muted small">Explorar directo:</span>
            <a href="<?= Router::url('/politicos') ?>" class="badge bg-primary text-white text-decoration-none py-1 px-2 hover-bg shadow-xs">
                <i class="bi bi-people-fill me-1"></i>Directorio de Políticos
            </a>
            <a href="<?= Router::url('/estados/sinaloa') ?>" class="badge bg-white text-secondary border text-decoration-none py-1 px-2 hover-bg">Sinaloa</a>
            <a href="<?= Router::url('/estados/ciudad-de-mexico') ?>" class="badge bg-white text-secondary border text-decoration-none py-1 px-2 hover-bg">CDMX</a>
            <a href="<?= Router::url('/estados/estado-de-mexico') ?>" class="badge bg-white text-secondary border text-decoration-none py-1 px-2 hover-bg">EdoMex</a>
            <a href="<?= Router::url('/estudios/posgrados') ?>" class="badge bg-white text-secondary border text-decoration-none py-1 px-2 hover-bg">Posgrados</a>
            <a href="<?= Router::url('/estudios/media-trunca') ?>" class="badge bg-white text-secondary border text-decoration-none py-1 px-2 hover-bg">Media Trunca</a>
        </div>
    </div>
</div>

<!-- Métricas Rápidas del Congreso -->
<div class="row g-3 mb-5 text-center">
    <div class="col-6 col-md-3">
        <a href="<?= Router::url('/politicos') ?>" class="text-decoration-none text-dark d-block h-100">
            <div class="card-civic p-3 h-100">
                <div class="h3 fw-bold text-primary mb-0">500</div>
                <div class="text-muted small">Diputados Federales</div>
                <div class="text-secondary mt-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 0.72rem;">
                    <span class="abbr-tip" data-bs-toggle="tooltip" data-bs-title="Mayoría Relativa: 300 diputados elegidos por voto popular directo en sus distritos electorales.">300 MR</span>
                    <span>&bull;</span>
                    <span class="abbr-tip" data-bs-toggle="tooltip" data-bs-title="Representación Proporcional: 200 diputados plurinominales asignados por listas de partido según votación nacional.">200 RP</span>
                    <span class="text-primary ms-1">Ver todos &rarr;</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= Router::url('/politicos') ?>" class="text-decoration-none text-dark d-block h-100">
            <div class="card-civic p-3 h-100">
                <div class="h3 fw-bold text-primary mb-0">128</div>
                <div class="text-muted small">Senadores de la República</div>
                <div class="text-secondary mt-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 0.72rem;">
                    <span class="abbr-tip" data-bs-toggle="tooltip" data-bs-title="Mayoría Relativa: 64 senadores (fórmula ganadora de 2 por cada entidad federativa).">64 MR</span>
                    <span>&bull;</span>
                    <span class="abbr-tip" data-bs-toggle="tooltip" data-bs-title="Primera Minoría: 32 senadores asignados a la fórmula que obtuvo el 2do lugar en cada estado.">32 PM</span>
                    <span>&bull;</span>
                    <span class="abbr-tip" data-bs-toggle="tooltip" data-bs-title="Representación Proporcional: 32 senadores plurinominales por lista nacional de partidos.">32 RP</span>
                    <span class="text-primary ms-1">Ver todos &rarr;</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="#seccion-mapa" class="text-decoration-none text-dark">
            <div class="card-civic p-3 h-100">
                <div class="h3 fw-bold text-success mb-0">32</div>
                <div class="text-muted small">Entidades Federativas</div>
                <div class="text-success mt-1" style="font-size: 0.72rem;">Ver mapa interactivo &darr;</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= Router::url('/comisiones') ?>" class="text-decoration-none text-dark">
            <div class="card-civic p-3 h-100">
                <div class="h3 fw-bold text-danger mb-0"><?= count($comisiones) ?></div>
                <div class="text-muted small">Comisiones Ordinarias</div>
                <div class="text-secondary mt-1" style="font-size: 0.72rem;">Ver directorio completo ↗</div>
            </div>
        </a>
    </div>
</div>

<!-- Mapa Político de México (32 Estados Interactivos) -->
<?php require __DIR__ . '/partials/mexico_map.php'; ?>

<!-- Radiografía Educativa y Nivel de Estudios del Congreso -->
<?php require __DIR__ . '/partials/card_educacion.php'; ?>

<!-- Comisiones Destacadas y Accesos Directos -->
<div class="row g-4 mb-5">
    <div class="col-lg-6">
        <div class="card-civic p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-briefcase-fill text-primary"></i>
                    Comisiones Clave del Congreso
                </h2>
                <a href="<?= Router::url('/comisiones') ?>" class="badge bg-light text-primary border text-decoration-none small">Ver todas (<?= count($comisiones) ?>)</a>
            </div>
            <p class="text-secondary small mb-3">
                Órganos donde se debaten y aprueban las reformas constitucionales y el presupuesto federal.
            </p>
            <div class="list-group list-group-flush mb-3">
                <?php 
                $topComisiones = array_slice($comisiones, 0, 5, true);
                foreach ($topComisiones as $c): 
                    $cUrl = Router::url('/comisiones/' . $c['slug']);
                ?>
                <a href="<?= $cUrl ?>" class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between text-decoration-none text-dark hover-bg">
                    <div class="fw-medium small text-truncate pe-2"><?= ViewHelper::e($c['nombre']) ?></div>
                    <span class="badge bg-light text-secondary border small"><?= count($c['integrantes']) ?> int.</span>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="mt-auto pt-2 text-end border-top">
                <a href="<?= Router::url('/comisiones') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium">
                    Explorar todas las comisiones &rarr;
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-civic p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-danger"></i>
                    Radiografía por Entidad Federativa
                </h2>
                <span class="badge bg-success-subtle text-success border border-success-subtle small">100% Cobertura</span>
            </div>
            <p class="text-secondary small mb-3">
                Auditoría ciudadana de los 583 representantes federales en funciones en las 32 entidades federativas.
            </p>

            <!-- Acceso Rápido Destacado -->
            <div class="p-3 bg-light rounded-3 border mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <strong class="text-dark d-block">Baja California &amp; República Mexicana</strong>
                        <span class="text-muted small">583 legisladores federales auditados en funciones</span>
                    </div>
                    <span class="badge bg-primary text-white">32 Estados</span>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <a href="#seccion-mapa" class="btn btn-primary btn-sm rounded-pill px-3">
                        Explorar en el Mapa &rarr;
                    </a>
                    <a href="<?= $bcUrl ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        Ver Baja California
                    </a>
                </div>
            </div>

            <div class="text-success small mt-auto">
                <i class="bi bi-check-circle-fill me-1"></i>
                Catálogo completo de las 32 entidades sincronizado con el Sistema de Información Legislativa (SIL - SEGOB).
            </div>
        </div>
    </div>
</div>

<!-- Modal Pedagógico: ¿Cómo se eligen los legisladores? (MR, RP, PM) -->
<div class="modal fade" id="modalGlosarioEleccion" tabindex="-1" aria-labelledby="modalGlosarioEleccionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-1">
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small mb-1">
                        Pedagogía Cívica
                    </span>
                    <h5 class="modal-title fw-bold text-dark" id="modalGlosarioEleccionLabel">
                        ¿Cómo llegan al Congreso?
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-secondary small mb-3">
                    En el Poder Legislativo Federal existen tres principios de elección constitucional para ocupar una curul o escaño:
                </p>

                <div class="d-flex flex-column gap-3">
                    <!-- MR -->
                    <div class="p-3 rounded-3 bg-light border">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary text-white fw-bold px-2 py-1">MR</span>
                            <span class="fw-bold text-dark small">Mayoría Relativa (Voto Directo en Urnas)</span>
                        </div>
                        <p class="text-secondary small mb-1" style="font-size: 0.82rem; line-height: 1.45;">
                            Hicieron campaña territorial y <strong>ganaron la elección directamente</strong> al recibir la mayor cantidad de votos en su distrito electoral (300 Diputados) o estado (64 Senadores).
                        </p>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>Elección directa por boleta ciudadana.
                        </div>
                    </div>

                    <!-- RP -->
                    <div class="p-3 rounded-3 bg-light border">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-dark text-white fw-bold px-2 py-1">RP</span>
                            <span class="fw-bold text-dark small">Representación Proporcional (Plurinominales)</span>
                        </div>
                        <p class="text-secondary small mb-1" style="font-size: 0.82rem; line-height: 1.45;">
                            Asignados mediante <strong>listas predefinidas por cada partido</strong> en proporción al porcentaje nacional de votos obtenidos (200 Diputados y 32 Senadores).
                        </p>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-pie-chart-fill text-primary me-1"></i>Garantiza representación para minorías y equilibrio de fuerzas.
                        </div>
                    </div>

                    <!-- PM -->
                    <div class="p-3 rounded-3 bg-light border">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">PM</span>
                            <span class="fw-bold text-dark small">Primera Minoría (Exclusivo del Senado)</span>
                        </div>
                        <p class="text-secondary small mb-1" style="font-size: 0.82rem; line-height: 1.45;">
                            Cada estado tiene 3 senadores: 2 de la fórmula ganadora y 1 escaño reservado a la fórmula del <strong>partido que quedó en 2º lugar de votos</strong> (32 Senadores en total).
                        </p>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-award-fill text-warning me-1"></i>Asegura contrapeso político en cada entidad federativa.
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-2 bg-light rounded-3 text-center border text-secondary" style="font-size: 0.78rem;">
                    <strong>Total en funciones:</strong> 500 Diputados Federales &bull; 128 Senadores de la República.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary rounded-pill w-100 py-2 small fw-semibold" data-bs-dismiss="modal">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.abbr-tip {
    border-bottom: 1px dotted #8E8E93;
    cursor: help;
    transition: color 0.15s ease-in-out;
}
.abbr-tip:hover {
    color: var(--apple-blue, #0071E3);
}
</style>

<?php require __DIR__ . '/layout/footer.php'; ?>
