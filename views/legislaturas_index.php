<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $catalog
 * @var array $disponibles
 * @var array $seo
 */

$homeUrl = Router::url('/');

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Legislaturas</li>
    </ol>
</nav>

<!-- Hero Section -->
<div class="text-center py-4 py-md-5 mb-4">
    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 small fw-semibold mb-2">
        <i class="bi bi-bank me-1"></i>H. Congreso de la Unión
    </span>
    <h1 class="display-5 fw-bold text-dark mb-3">
        Legislaturas de México
    </h1>
    <p class="lead text-secondary mx-auto mb-4" style="max-width: 680px; font-size: 1.1rem;">
        Explora la integración, balances de poder y perfiles parlamentarios por periodo constitucional en la historia democrática de México.
    </p>
</div>

<!-- Grid de Legislaturas -->
<div class="row g-4 mb-5">
    <?php foreach ($catalog as $num => $leg): ?>
        <?php 
        $esActiva = (($leg['estatus'] ?? '') === 'activa');
        $tieneDatos = in_array($num, $disponibles, true);
        $url = Router::url('/legislaturas/' . $num);
        $periodoSenado = $leg['periodo_senado'] ?? null;
        ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-civic p-4 h-100 border <?= $esActiva ? 'border-primary shadow-sm bg-primary-subtle bg-opacity-10' : '' ?> d-flex flex-column justify-content-between transition-card">
                <div>
                    <!-- Badges superiores de estatus y número -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <span class="badge bg-primary text-white rounded-pill px-3 py-2 fw-bold" style="font-size: 0.82rem;">
                            <?= ViewHelper::e($leg['romano']) ?> Legislatura
                        </span>
                        <?php if ($esActiva): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                                En Funciones
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">
                                Histórico
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Imagotipo Oficial de la Legislatura -->
                    <div class="mb-3 rounded-3 overflow-hidden border border-light-subtle shadow-xs" style="background: #FBFBFC;">
                        <img src="<?= Router::url('/assets/img/legislaturas/leg-' . $num . '.svg') ?>" 
                             alt="Imagotipo <?= ViewHelper::e($leg['romano']) ?> Legislatura" 
                             class="w-100 d-block" 
                             style="height: auto; max-height: 56px; object-fit: contain;" 
                             loading="lazy">
                    </div>

                    <!-- Periodos de Diputados y Senadores -->
                    <div class="text-secondary small fw-medium mb-2" style="font-size: 0.82rem;">
                        <span><?= ViewHelper::e($leg['periodo']) ?></span>
                        <?php if (!empty($periodoSenado)): ?>
                            <span class="mx-1">&bull;</span>
                            <span>Senado: <?= ViewHelper::e($periodoSenado) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Descripción histórica / institucional -->
                    <p class="text-secondary small mb-4" style="line-height: 1.55;">
                        <?= ViewHelper::e($leg['descripcion']) ?>
                    </p>
                </div>

                <!-- Botón de Acción Principal -->
                <div class="pt-2">
                    <a href="<?= $url ?>" class="btn <?= $esActiva ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill w-100 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1 transition-all shadow-xs">
                        <span>Explorar Radiografía y Representantes</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
