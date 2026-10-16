<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $legData
 * @var array $seo
 * @var string $legislaturaRomano
 */

$meta = $legData['meta'] ?? [];
$numero = (int)($meta['numero'] ?? 66);
$romano = $meta['romano'] ?? 'LXVI';
$periodo = $meta['periodo'] ?? '2024–2027';
$estatus = $meta['estatus'] ?? 'activa';
$estatusLabel = $meta['estatus_label'] ?? 'Actual • En Funciones';
$descripcion = $meta['descripcion'] ?? '';
$silUrl = $meta['sil_url'] ?? 'http://sil.gobernacion.gob.mx';

$tieneDataset = (bool)($legData['tiene_dataset_local'] ?? false);
$totalLegisladores = (int)($legData['total_legisladores'] ?? 0);
$totalDiputados = (int)($legData['total_diputados'] ?? 0);
$totalSenadores = (int)($legData['total_senadores'] ?? 0);
$diputados = $legData['diputados'] ?? [];
$senadores = $legData['senadores'] ?? [];
$bancadasDip = $legData['bancadas_diputados'] ?? [];
$bancadasSen = $legData['bancadas_senadores'] ?? [];
$mayoriaDip = $legData['mayoria_diputados'] ?? null;
$mayoriaSen = $legData['mayoria_senadores'] ?? null;
$escolaridadStats = $legData['escolaridad_stats'] ?? null;
$todasLegislaturas = $legData['legislaturas_todas'] ?? [];

$homeUrl = Router::url('/');
$catalogUrl = Router::url('/legislaturas');

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= $catalogUrl ?>">Legislaturas</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= ViewHelper::e($romano) ?> Legislatura</li>
    </ol>
</nav>

<!-- Hero de la Legislatura -->
<div class="card-civic p-4 p-md-5 mb-4 position-relative overflow-hidden border">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <!-- Imagotipo Oficial -->
            <div class="mb-3 d-inline-block rounded-3 border border-light-subtle shadow-xs overflow-hidden p-1 px-2" style="background: #FBFBFC; max-width: 340px;">
                <img src="<?= Router::url('/assets/img/legislaturas/leg-' . $numero . '.svg') ?>" 
                     alt="Imagotipo oficial <?= ViewHelper::e($romano) ?> Legislatura" 
                     class="d-block img-fluid" 
                     style="max-height: 46px; width: auto; object-fit: contain;">
            </div>

            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-bold"><?= ViewHelper::e($romano) ?> Legislatura</span>
                <span class="badge bg-light text-secondary border rounded-pill px-3 py-1"><?= ViewHelper::e($periodo) ?></span>
                <?php if ($estatus === 'activa'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                        <i class="bi bi-broadcast me-1"></i><?= ViewHelper::e($estatusLabel) ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">
                        <i class="bi bi-clock-history me-1"></i><?= ViewHelper::e($estatusLabel) ?>
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="display-6 fw-bold text-dark mb-2">
                <?= ViewHelper::e($romano) ?> Legislatura del Congreso de la Unión
            </h1>
            <p class="text-secondary lead mb-3" style="font-size: 1.05rem;">
                <?= ViewHelper::e($descripcion) ?>
            </p>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if ($tieneDataset && $totalLegisladores > 0): ?>
                    <a href="#directorio-seccion" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-people-fill me-2"></i>Ver los <?= $totalLegisladores ?> Políticos de la <?= ViewHelper::e($romano) ?> &darr;
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-light border rounded-pill px-3 py-2 small text-secondary" data-bs-toggle="modal" data-bs-target="#modalLegislaturas">
                    <i class="bi bi-arrow-left-right me-1"></i>Cambiar legislatura
                </button>
                <a href="<?= ViewHelper::e($silUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 small">
                    <i class="bi bi-box-arrow-up-right me-1"></i>SIL SEGOB oficial ↗
                </a>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card-apple p-3 border bg-light bg-opacity-50">
                <div class="text-muted small fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    Equilibrio de Fuerzas
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="small text-secondary">Cámara de Diputados</span>
                    <span class="fw-bold text-dark"><?= $totalDiputados > 0 ? $totalDiputados : 500 ?> escaños</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="small text-secondary">Senado de la República</span>
                    <span class="fw-bold text-dark"><?= $totalSenadores > 0 ? $totalSenadores : 128 ?> escaños</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary">Primera fuerza política</span>
                    <span class="small fw-semibold text-primary">
                        <?= ViewHelper::e($mayoriaDip['partido'] ?? 'MORENA') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Métricas Clave de la Legislatura (Clicables para ir directo a los políticos de esa cámara) -->
<div class="row g-3 mb-4 text-center">
    <div class="col-6 col-md-3">
        <?php if ($tieneDataset): ?>
            <a href="#directorio-seccion" class="text-decoration-none text-dark d-block h-100" onclick="window.filtrarPorCamaraDirecta && window.filtrarPorCamaraDirecta('Diputados');">
                <div class="card-civic p-3 h-100 transition-card border-primary border-opacity-25 hover-shadow">
                    <div class="h3 fw-bold text-primary mb-0"><?= $totalDiputados > 0 ? $totalDiputados : 500 ?></div>
                    <div class="text-dark fw-semibold small">Diputados Federales</div>
                    <div class="text-primary mt-1 small" style="font-size: 0.72rem;">
                        <i class="bi bi-arrow-down-circle me-1"></i>Ver listado &darr;
                    </div>
                </div>
            </a>
        <?php else: ?>
            <div class="card-civic p-3 h-100">
                <div class="h3 fw-bold text-primary mb-0"><?= $totalDiputados > 0 ? $totalDiputados : 500 ?></div>
                <div class="text-muted small">Diputados Federales</div>
                <div class="text-secondary mt-1 small" style="font-size: 0.72rem;">Meta constitucional: 500</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-6 col-md-3">
        <?php if ($tieneDataset): ?>
            <a href="#directorio-seccion" class="text-decoration-none text-dark d-block h-100" onclick="window.filtrarPorCamaraDirecta && window.filtrarPorCamaraDirecta('Senadores');">
                <div class="card-civic p-3 h-100 transition-card border-primary border-opacity-25 hover-shadow">
                    <div class="h3 fw-bold text-primary mb-0"><?= $totalSenadores > 0 ? $totalSenadores : 128 ?></div>
                    <div class="text-dark fw-semibold small">Senadores de la República</div>
                    <div class="text-primary mt-1 small" style="font-size: 0.72rem;">
                        <i class="bi bi-arrow-down-circle me-1"></i>Ver listado &darr;
                    </div>
                </div>
            </a>
        <?php else: ?>
            <div class="card-civic p-3 h-100">
                <div class="h3 fw-bold text-primary mb-0"><?= $totalSenadores > 0 ? $totalSenadores : 128 ?></div>
                <div class="text-muted small">Senadores de la República</div>
                <div class="text-secondary mt-1 small" style="font-size: 0.72rem;">Meta constitucional: 128</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-civic p-3 h-100">
            <div class="h3 fw-bold text-success mb-0">32</div>
            <div class="text-muted small">Entidades Federativas</div>
            <div class="text-success mt-1 small" style="font-size: 0.72rem;">Cobertura nacional 100%</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-civic p-3 h-100">
            <div class="h3 fw-bold text-danger mb-0">
                <?= count($bancadasDip) > 0 ? count($bancadasDip) : 7 ?>
            </div>
            <div class="text-muted small">Grupos Parlamentarios</div>
            <div class="text-secondary mt-1 small" style="font-size: 0.72rem;">Representación plural</div>
        </div>
    </div>
</div>

<?php if ($tieneDataset): ?>
<!-- Barra de Acceso Rápido a Secciones -->
<div class="card-civic p-2 px-3 mb-4 border bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="text-muted small fw-semibold text-uppercase me-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
            Secciones:
        </span>
        <a href="#directorio-seccion" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold">
            <i class="bi bi-people-fill me-1"></i>Directorio de Políticos (<?= $totalLegisladores ?>)
        </a>
        <a href="#bancadas-seccion" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-secondary">
            <i class="bi bi-pie-chart me-1"></i>Bancadas y Escaños
        </a>
        <?php if ($escolaridadStats): ?>
            <a href="#estudios-seccion" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-secondary">
                <i class="bi bi-mortarboard me-1"></i>Nivel de Estudios
            </a>
        <?php endif; ?>
    </div>
    <div class="small text-muted">
        Periodo <strong class="text-dark"><?= ViewHelper::e($periodo) ?></strong>
    </div>
</div>

<!-- Composición Parlamentaria por Bancadas -->
<div class="card-civic p-4 mb-4 border" id="bancadas-seccion">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="h5 fw-bold text-dark mb-1">
                <i class="bi bi-pie-chart text-primary me-2"></i>Composición Parlamentaria (Fuerza por Partidos)
            </h2>
            <p class="text-muted small mb-0">
                Distribución de curules y escaños por grupo parlamentario en la <?= ViewHelper::e($romano) ?> Legislatura
            </p>
        </div>
        <ul class="nav nav-pills" id="pillsCamaras" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-3 py-1 small fw-semibold" id="tab-diputados" data-bs-toggle="pill" data-bs-target="#content-diputados" type="button" role="tab">
                    Diputados (<?= $totalDiputados ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-3 py-1 small fw-semibold ms-1" id="tab-senadores" data-bs-toggle="pill" data-bs-target="#content-senadores" type="button" role="tab">
                    Senadores (<?= $totalSenadores ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="pillsCamarasContent">
        <!-- Tab Diputados -->
        <div class="tab-pane fade show active" id="content-diputados" role="tabpanel">
            <!-- Barra Proporcional de Escaños -->
            <div class="progress rounded-pill mb-3" style="height: 18px; overflow: hidden;">
                <?php foreach ($bancadasDip as $b): ?>
                    <div class="progress-bar" role="progressbar" style="width: <?= $b['porcentaje'] ?>%; background-color: <?= $b['color'] ?>;" title="<?= ViewHelper::e($b['partido']) ?>: <?= $b['asientos'] ?> (<?= $b['porcentaje'] ?>%)" aria-valuenow="<?= $b['asientos'] ?>" aria-valuemin="0" aria-valuemax="<?= $totalDiputados ?>"></div>
                <?php endforeach; ?>
            </div>

            <div class="row g-2">
                <?php foreach ($bancadasDip as $b): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-apple p-3 border h-100 d-flex flex-column justify-content-between">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <?= ViewHelper::renderPartyBadge($b['partido']) ?>
                                <span class="badge bg-light text-secondary border small"><?= $b['porcentaje'] ?>%</span>
                            </div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span class="h4 fw-bold text-dark mb-0"><?= $b['asientos'] ?></span>
                                <span class="text-muted small">escaños</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex align-items-center gap-3 mt-3 pt-3 border-top text-secondary small flex-wrap" style="font-size: 0.78rem;">
                <span><i class="bi bi-info-circle text-primary me-1"></i>Mayoría Absoluta: <strong>251</strong> votos</span>
                <span><i class="bi bi-shield-check text-success me-1"></i>Mayoría Calificada (2/3): <strong>334</strong> votos</span>
            </div>
        </div>

        <!-- Tab Senadores -->
        <div class="tab-pane fade" id="content-senadores" role="tabpanel">
            <!-- Barra Proporcional de Escaños -->
            <div class="progress rounded-pill mb-3" style="height: 18px; overflow: hidden;">
                <?php foreach ($bancadasSen as $b): ?>
                    <div class="progress-bar" role="progressbar" style="width: <?= $b['porcentaje'] ?>%; background-color: <?= $b['color'] ?>;" title="<?= ViewHelper::e($b['partido']) ?>: <?= $b['asientos'] ?> (<?= $b['porcentaje'] ?>%)" aria-valuenow="<?= $b['asientos'] ?>" aria-valuemin="0" aria-valuemax="<?= $totalSenadores ?>"></div>
                <?php endforeach; ?>
            </div>

            <div class="row g-2">
                <?php foreach ($bancadasSen as $b): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-apple p-3 border h-100 d-flex flex-column justify-content-between">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <?= ViewHelper::renderPartyBadge($b['partido']) ?>
                                <span class="badge bg-light text-secondary border small"><?= $b['porcentaje'] ?>%</span>
                            </div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span class="h4 fw-bold text-dark mb-0"><?= $b['asientos'] ?></span>
                                <span class="text-muted small">escaños</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex align-items-center gap-3 mt-3 pt-3 border-top text-secondary small flex-wrap" style="font-size: 0.78rem;">
                <span><i class="bi bi-info-circle text-primary me-1"></i>Mayoría Absoluta: <strong>65</strong> votos</span>
                <span><i class="bi bi-shield-check text-success me-1"></i>Mayoría Calificada (2/3): <strong>86</strong> votos</span>
            </div>
        </div>
    </div>
</div>

<?php if ($escolaridadStats): ?>
<!-- Resumen de Preparación Académica en la Legislatura -->
<?php 
$posgrados = $escolaridadStats['resumen_posgrados'] ?? ['total' => 0, 'porcentaje' => 0];
$licenciaturas = $escolaridadStats['resumen_licenciaturas'] ?? ['total' => 0, 'porcentaje' => 0];
$mediaTrunca = $escolaridadStats['resumen_media_trunca'] ?? ['total' => 0, 'porcentaje' => 0];
$niveles = $escolaridadStats['niveles'] ?? [];
$doctoresCount = $niveles['doctorado']['cantidad'] ?? 0;
$maestriasCount = $niveles['maestria']['cantidad'] ?? 0;
?>
<div class="card-civic p-4 mb-4 border" id="estudios-seccion">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="h5 fw-bold text-dark mb-1">
                <i class="bi bi-mortarboard text-primary me-2"></i>Radiografía de Estudios (Nivel Académico)
            </h2>
            <p class="text-muted small mb-0">
                Grado máximo de estudios acreditado por los legisladores de la <?= ViewHelper::e($romano) ?> Legislatura
            </p>
        </div>
        <a href="<?= Router::url('/estudios') ?>" class="btn btn-sm btn-light border rounded-pill px-3 py-1 small text-secondary">
            Ver observatorio académico &rarr;
        </a>
    </div>

    <div class="row g-3 text-center">
        <div class="col-6 col-md-3">
            <div class="card-apple p-3 border">
                <div class="h4 fw-bold text-primary mb-0"><?= $posgrados['total'] ?></div>
                <div class="text-muted small">Posgrados (<?= $posgrados['porcentaje'] ?>%)</div>
                <div class="text-secondary mt-1 small" style="font-size: 0.72rem;">
                    <?= $doctoresCount ?> Doctorados &bull; <?= $maestriasCount ?> Maestrías
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-apple p-3 border">
                <div class="h4 fw-bold text-success mb-0"><?= $licenciaturas['total'] ?></div>
                <div class="text-muted small">Licenciaturas (<?= $licenciaturas['porcentaje'] ?>%)</div>
                <div class="text-secondary mt-1 small" style="font-size: 0.72rem;">Grado universitario concluido</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-apple p-3 border">
                <div class="h4 fw-bold text-warning-emphasis mb-0"><?= $mediaTrunca['total'] ?></div>
                <div class="text-muted small">Media Superior / Trunca</div>
                <div class="text-secondary mt-1 small" style="font-size: 0.72rem;"><?= $mediaTrunca['porcentaje'] ?>% del pleno</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-apple p-3 border">
                <div class="h4 fw-bold text-dark mb-0"><?= $totalLegisladores ?></div>
                <div class="text-muted small">Expedientes auditados</div>
                <div class="text-secondary mt-1 small" style="font-size: 0.72rem;">Curricula oficial verificada</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Directorio de Legisladores de la Legislatura -->
<div class="card-civic p-4 mb-4 border" id="directorio-seccion">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="h5 fw-bold text-dark mb-1">
                <i class="bi bi-people text-primary me-2"></i>Directorio de Legisladores (<?= $totalLegisladores ?>)
            </h2>
            <p class="text-muted small mb-0">
                Explora las fichas y expedientes oficiales de los integrantes de la <?= ViewHelper::e($romano) ?> Legislatura
            </p>
        </div>
    </div>

    <!-- Controles de Filtro Instantáneo -->
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                <input type="text" id="filtroNombre" class="form-control border-start-0 shadow-none ps-0" placeholder="Buscar por nombre, estado o partido..." autocomplete="off">
            </div>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-center gap-1 flex-wrap">
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 btn-filtro-camara active" data-camara="todas">
                Todas (<?= $totalLegisladores ?>)
            </button>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 btn-filtro-camara" data-camara="Diputados">
                Diputados (<?= $totalDiputados ?>)
            </button>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 btn-filtro-camara" data-camara="Senadores">
                Senadores (<?= $totalSenadores ?>)
            </button>
        </div>
    </div>

    <!-- Grid de Legisladores -->
    <div class="row g-3" id="gridLegisladores">
        <?php 
        $todosLegisladores = array_merge($diputados, $senadores);
        foreach ($todosLegisladores as $p):
            $pNombre = $p['nombre_completo'] ?? '';
            $pCamara = $p['camara'] ?? '';
            $pPartido = $p['partido'] ?? 'Sin Partido';
            $pEntidad = $p['entidad'] ?? '';
            $pPrincipio = $p['principio_eleccion'] ?? '';
            $pSlug = $p['slug'] ?? '';
            $pUrl = Router::url('/politicos/' . $pSlug . ($numero !== 66 ? '?leg=' . $numero : ''));
            $pRef = $p['referencia_sil'] ?? '';
            $pFoto = !empty($pRef) && file_exists(dirname(__DIR__) . "/storage/raw/photos/{$pRef}.jpg")
                ? Router::url("/storage/raw/photos/{$pRef}.jpg")
                : ($p['foto_oficial_url'] ?? 'https://via.placeholder.com/150');
        ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 item-legislador" data-nombre="<?= mb_strtolower($pNombre) ?>" data-camara="<?= $pCamara ?>" data-partido="<?= mb_strtolower($pPartido) ?>" data-entidad="<?= mb_strtolower($pEntidad) ?>">
                <a href="<?= $pUrl ?>" class="text-decoration-none text-dark d-block h-100">
                    <div class="card-apple p-3 h-100 d-flex flex-column justify-content-between transition-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="chip-neutral" style="font-size: 0.72rem;">
                                    <i class="bi bi-geo-alt text-danger me-1"></i><?= ViewHelper::e($pEntidad) ?>
                                </span>
                                <?= ViewHelper::renderPartyBadge($pPartido) ?>
                            </div>
                            <div class="text-center my-2">
                                <img src="<?= ViewHelper::e($pFoto) ?>" alt="<?= ViewHelper::e($pNombre) ?>" class="avatar-img mb-2" style="width: 72px; height: 72px; object-fit: cover; border-radius: 50%;" loading="lazy">
                                <h3 class="h6 fw-bold mb-1 text-dark text-truncate" title="<?= ViewHelper::e($pNombre) ?>">
                                    <?= ViewHelper::e($pNombre) ?>
                                </h3>
                                <div class="text-secondary small" style="font-size: 0.75rem;">
                                    <?= ViewHelper::e($pCamara) ?> &bull; <?= ViewHelper::e($pPrincipio) ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-2 pt-2 border-top">
                            <span class="text-primary small fw-semibold" style="font-size: 0.78rem;">
                                Ver perfil oficial &rarr;
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="noResults" class="text-center p-5 text-muted d-none">
        <i class="bi bi-search fs-3 d-block mb-2 text-secondary"></i>
        No se encontraron legisladores que coincidan con la búsqueda.
    </div>
</div>

<?php else: ?>

<!-- Aviso de Dataset Histórico en Digitalización (Para legislaturas sin JSON nominal local aún) -->
<div class="card-civic p-4 p-md-5 mb-4 text-center border">
    <div class="mx-auto mb-3" style="max-width: 580px;">
        <span class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
            <i class="bi bi-clock-history fs-2"></i>
        </span>
        <h2 class="h4 fw-bold text-dark mb-2">
            Expediente de la <?= ViewHelper::e($romano) ?> Legislatura (<?= ViewHelper::e($periodo) ?>)
        </h2>
        <p class="text-secondary small mb-4">
            Los perfiles nominales de este periodo constitucional se encuentran en proceso de normalización y extracción abierta. Puedes consultar la conformación oficial del Congreso de la Unión directamente en el Sistema de Información Legislativa de la SEGOB.
        </p>
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <a href="<?= ViewHelper::e($silUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary rounded-pill px-4 py-2 small fw-semibold">
                <i class="bi bi-box-arrow-up-right me-1"></i>Consultar en SIL SEGOB Oficial ↗
            </a>
            <a href="<?= Router::url('/legislaturas/66') ?>" class="btn btn-outline-secondary rounded-pill px-4 py-2 small">
                Ver LXVI Legislatura (Actual) &rarr;
            </a>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Selector de Navegación Rápida entre Legislaturas -->
<div class="card-civic p-4 mb-4 border bg-light">
    <div class="text-muted small fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
        Explorar Otros Periodos Constitucionales
    </div>
    <div class="row g-2">
        <?php foreach ($todasLegislaturas as $legNum => $legInfo): ?>
            <?php 
            $esEsta = ($legNum === $numero);
            $legRom = $legInfo['romano'] ?? (string)$legNum;
            $legPer = $legInfo['periodo'] ?? '';
            ?>
            <div class="col-6 col-md-4 col-lg">
                <a href="<?= Router::url('/legislaturas/' . $legNum) ?>" class="text-decoration-none">
                    <div class="card-apple p-3 border text-center h-100 <?= $esEsta ? 'border-primary bg-primary-subtle bg-opacity-25' : 'bg-white' ?> transition-card">
                        <span class="fw-bold <?= $esEsta ? 'text-primary' : 'text-dark' ?> d-block mb-1" style="font-size: 0.95rem;">
                            <?= ViewHelper::e($legRom) ?>
                        </span>
                        <span class="text-muted small d-block" style="font-size: 0.75rem;">
                            <?= ViewHelper::e($legPer) ?>
                        </span>
                        <?php if ($esEsta): ?>
                            <span class="badge bg-primary text-white rounded-pill mt-2" style="font-size: 0.65rem;">Actual</span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Filtro reactivo en el directorio de legisladores
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('filtroNombre');
    const items = document.querySelectorAll('.item-legislador');
    const noResults = document.getElementById('noResults');
    const botonesCamara = document.querySelectorAll('.btn-filtro-camara');

    let camaraActiva = 'todas';

    function filtrar() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        let visibleCount = 0;

        items.forEach(function(item) {
            const nombre = item.getAttribute('data-nombre') || '';
            const camara = item.getAttribute('data-camara') || '';
            const partido = item.getAttribute('data-partido') || '';
            const entidad = item.getAttribute('data-entidad') || '';

            const matchTexto = (query === '') || nombre.includes(query) || partido.includes(query) || entidad.includes(query);
            const matchCamara = (camaraActiva === 'todas') || (camara === camaraActiva);

            if (matchTexto && matchCamara) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });

        if (noResults) {
            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filtrar);
    }

    window.filtrarPorCamaraDirecta = function(camaraNombre) {
        botonesCamara.forEach(b => {
            const camAttr = b.getAttribute('data-camara');
            if (camAttr === camaraNombre) {
                b.classList.remove('btn-light', 'border');
                b.classList.add('btn-primary', 'active');
            } else {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-light', 'border');
            }
        });
        camaraActiva = camaraNombre;
        filtrar();
    };

    botonesCamara.forEach(function(btn) {
        btn.addEventListener('click', function() {
            botonesCamara.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-light', 'border');
            });
            this.classList.remove('btn-light', 'border');
            this.classList.add('btn-primary', 'active');
            camaraActiva = this.getAttribute('data-camara') || 'todas';
            filtrar();
        });
    });
});
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
