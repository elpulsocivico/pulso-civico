<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $comision
 * @var array $seo
 */

$nombre = $comision['nombre'] ?? 'Comisión Legislativa';
$slug = $comision['slug'] ?? '';
$integrantes = $comision['integrantes'] ?? [];
$homeUrl = Router::url('/');
$canonicalUrl = Router::url('/comisiones/' . $slug);

$legNumero = (int)($comision['legislatura'] ?? (isset($_GET['leg']) ? PulsoCivico\Services\DataService::parseNumeroLegislatura((string)$_GET['leg']) : 66));

// Separar por jerarquía: Presidencia, Secretarías y Colegiados
$presidencia = [];
$secretarias = [];
$restoIntegrantes = [];

// Métricas de partidos para balance político
$partidosConteo = [];
$totalIntegrantes = count($integrantes);

foreach ($integrantes as $int) {
    $puesto = strtoupper(trim($int['puesto'] ?? ''));
    if (str_contains($puesto, 'PRESID')) {
        $presidencia[] = $int;
    } elseif (str_contains($puesto, 'SECRET')) {
        $secretarias[] = $int;
    } else {
        $restoIntegrantes[] = $int;
    }

    $partido = trim($int['partido'] ?? 'Sin Partido');
    if ($partido === '') $partido = 'Sin Partido';
    $partidosConteo[$partido] = ($partidosConteo[$partido] ?? 0) + 1;
}
arsort($partidosConteo);

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= Router::url('/comisiones') ?>">Comisiones</a></li>
        <li class="breadcrumb-item active text-truncate" style="max-width: 300px;" aria-current="page"><?= ViewHelper::e($nombre) ?></li>
    </ol>
</nav>

<!-- Header de Comisión con Balance Político -->
<div class="card-civic p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-primary rounded-pill">Comisión Ordinaria</span>
        <span class="badge bg-light text-secondary border">LXVI Legislatura</span>
    </div>
    <h1 class="h3 fw-bold text-dark mb-2"><?= ViewHelper::e($nombre) ?></h1>
    <p class="text-secondary small mb-0">
        Órgano legislativo colegiado encargado de dictaminar iniciativas, puntos de acuerdo e informes en la materia.
    </p>

    <!-- Balance y Mayoría Parlamentaria -->
    <?php if ($totalIntegrantes > 0): ?>
    <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
            <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.74rem;">
                <i class="bi bi-pie-chart-fill text-primary me-1"></i>Fuerza y Balance Político
            </span>
            <span class="badge bg-light text-dark border px-3 py-1">
                <?= $totalIntegrantes ?> Integrantes Registrados
            </span>
        </div>

        <!-- Barra de progreso segmentada de partidos -->
        <div class="progress rounded-pill mb-3" style="height: 12px; background-color: #E5E5EA; overflow: hidden;">
            <?php foreach ($partidosConteo as $partido => $qty): 
                $pct = round(($qty / $totalIntegrantes) * 100, 1);
                $color = ViewHelper::getPartyColor($partido);
            ?>
                <div class="progress-bar" role="progressbar" 
                     style="width: <?= $pct ?>%; background-color: <?= $color ?>;" 
                     title="<?= ViewHelper::e($partido) ?>: <?= $qty ?> integrantes (<?= $pct ?>%)" 
                     aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Leyenda de partidos con porcentajes -->
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php foreach ($partidosConteo as $partido => $qty): 
                $pct = round(($qty / $totalIntegrantes) * 100);
                $color = ViewHelper::getPartyColor($partido);
            ?>
                <div class="d-inline-flex align-items-center gap-2 px-2 py-1 bg-light rounded-pill border small" style="font-size: 0.75rem;">
                    <span class="rounded-circle d-inline-block flex-shrink-0" style="width: 9px; height: 9px; background-color: <?= $color ?>;"></span>
                    <span class="fw-semibold text-dark"><?= ViewHelper::e($partido) ?></span>
                    <span class="text-muted"><?= $qty ?> <span style="font-size: 0.68rem;">(<?= $pct ?>%)</span></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Presidencia de la Comisión -->
<?php if (!empty($presidencia)): ?>
<div class="mb-4">
    <h2 class="h5 fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
        <i class="bi bi-award-fill text-warning"></i>
        Presidencia de la Comisión
    </h2>
    <div class="row g-3">
        <?php foreach ($presidencia as $pres): ?>
            <?php renderPresidenciaCard($pres, $legNumero); ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Secretarías de la Mesa Directiva -->
<?php if (!empty($secretarias)): ?>
<div class="mb-4">
    <h2 class="h5 fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
        <i class="bi bi-star-fill text-primary"></i>
        Secretarías de la Mesa Directiva (<?= count($secretarias) ?>)
    </h2>
    <div class="row g-3">
        <?php foreach ($secretarias as $int): ?>
            <?php renderIntegranteCard($int, true, $legNumero); ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Resto de Integrantes -->
<div class="mb-4">
    <h2 class="h5 fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
        <i class="bi bi-people-fill text-secondary"></i>
        Integrantes Colegiados (<?= count($restoIntegrantes) ?>)
    </h2>
    <div class="row g-3">
        <?php foreach ($restoIntegrantes as $int): ?>
            <?php renderIntegranteCard($int, false, $legNumero); ?>
        <?php endforeach; ?>
    </div>
</div>

<?php
function renderIntegranteCard(array $int, bool $esDirectiva, int $legNumero = 66): void {
    $slug = $int['slug'] ?? '';
    $legParam = ($legNumero !== 66 && $legNumero !== 0) ? '?leg=' . $legNumero : '';
    $url = Router::url('/politicos/' . $slug . $legParam);
    $nombre = $int['nombre_completo'] ?? '';
    $partido = $int['partido'] ?? '';
    $camara = $int['camara'] ?? '';
    $entidad = $int['entidad'] ?? ($int['estado'] ?? '');
    $puesto = $int['puesto'] ?? 'Integrante';
    $foto = !empty($int['referencia_sil']) && file_exists(dirname(__DIR__) . "/storage/raw/photos/{$int['referencia_sil']}.jpg")
        ? Router::url("/storage/raw/photos/{$int['referencia_sil']}.jpg")
        : ($int['foto_oficial_url'] ?? 'https://via.placeholder.com/150');
    $borderClass = $esDirectiva ? 'border-primary-subtle shadow-sm' : '';
    ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
        <a href="<?= $url ?>" class="text-decoration-none text-dark d-block h-100">
            <div class="card-civic p-3 h-100 d-flex flex-column <?= $borderClass ?>">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="<?= ViewHelper::e($foto) ?>" alt="<?= ViewHelper::e($nombre) ?>" class="rounded-circle border flex-shrink-0" style="width: 48px; height: 48px; object-fit: cover;">
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-semibold text-truncate small" title="<?= ViewHelper::e($nombre) ?>">
                            <?= ViewHelper::e($nombre) ?>
                        </div>
                        <div class="text-muted d-flex align-items-center gap-1 text-truncate" style="font-size: 0.72rem;" title="<?= ViewHelper::e($camara . (!empty($entidad) ? ' • ' . $entidad : '')) ?>">
                            <span class="flex-shrink-0"><?= ViewHelper::e($camara) ?></span>
                            <?php if (!empty($entidad)): ?>
                                <span class="text-muted flex-shrink-0">&bull;</span>
                                <span class="text-secondary text-truncate">
                                    <i class="bi bi-geo-alt-fill text-danger" style="font-size: 0.65rem;"></i> <?= ViewHelper::e($entidad) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
                    <?= ViewHelper::renderPartyBadge($partido) ?>
                    <span class="badge rounded-pill <?= $esDirectiva ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>" style="font-size: 0.72rem;">
                        <?= ViewHelper::e($puesto) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
    <?php
}

function renderPresidenciaCard(array $int, int $legNumero = 66): void {
    $slug = $int['slug'] ?? '';
    $legParam = ($legNumero !== 66 && $legNumero !== 0) ? '?leg=' . $legNumero : '';
    $url = Router::url('/politicos/' . $slug . $legParam);
    $nombre = $int['nombre_completo'] ?? '';
    $partido = $int['partido'] ?? '';
    $camara = $int['camara'] ?? '';
    $entidad = $int['entidad'] ?? ($int['estado'] ?? '');
    $puesto = $int['puesto'] ?? 'Presidencia';
    $foto = !empty($int['referencia_sil']) && file_exists(dirname(__DIR__) . "/storage/raw/photos/{$int['referencia_sil']}.jpg")
        ? Router::url("/storage/raw/photos/{$int['referencia_sil']}.jpg")
        : ($int['foto_oficial_url'] ?? 'https://via.placeholder.com/150');
    ?>
    <div class="col-12 col-md-8 col-lg-6">
        <a href="<?= $url ?>" class="text-decoration-none text-dark d-block">
            <div class="card-civic p-3 p-md-4 border border-warning border-opacity-50 shadow-sm" style="background: linear-gradient(135deg, #ffffff 0%, #fffdf5 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative flex-shrink-0">
                        <img src="<?= ViewHelper::e($foto) ?>" alt="<?= ViewHelper::e($nombre) ?>" class="rounded-circle border border-2 border-warning shadow-sm" style="width: 64px; height: 64px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 badge bg-warning text-dark rounded-circle p-1 border shadow-sm" title="Presidencia" style="transform: translate(20%, 20%); font-size: 0.65rem;">
                            <i class="bi bi-star-fill"></i>
                        </span>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-warning text-dark fw-bold rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                <i class="bi bi-award-fill me-1"></i><?= ViewHelper::e($puesto) ?>
                            </span>
                            <?= ViewHelper::renderPartyBadge($partido) ?>
                        </div>
                        <h3 class="h6 fw-bold text-dark mb-1 text-truncate" title="<?= ViewHelper::e($nombre) ?>">
                            <?= ViewHelper::e($nombre) ?>
                        </h3>
                        <div class="text-muted d-flex align-items-center gap-1 text-truncate" style="font-size: 0.75rem;" title="<?= ViewHelper::e($camara . (!empty($entidad) ? ' • ' . $entidad : '')) ?>">
                            <span class="flex-shrink-0"><?= ViewHelper::e($camara) ?></span>
                            <?php if (!empty($entidad)): ?>
                                <span class="text-muted flex-shrink-0">&bull;</span>
                                <span class="text-secondary text-truncate">
                                    <i class="bi bi-geo-alt-fill text-danger" style="font-size: 0.68rem;"></i> <?= ViewHelper::e($entidad) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php
}
?>

<?php require __DIR__ . '/layout/footer.php'; ?>
