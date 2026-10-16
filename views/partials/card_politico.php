<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;
use PulsoCivico\Services\DataService;

/**
 * @var array $pol  Datos del político a renderizar
 */
$slug = $pol['slug'] ?? '';
$nombrePol = $pol['nombre_completo'] ?? 'Legislador';
$nombreMostrar = $pol['nombre_natural'] ?? ViewHelper::formatearNombreNatural($nombrePol);
$partidoPol = $pol['partido'] ?? 'Sin Partido';
$partidoSlug = $pol['partido_slug'] ?? DataService::slugify($partidoPol);
$camaraPol = $pol['camara'] ?? 'Diputados';
$entidadPol = $pol['entidad'] ?? 'México';
$entidadSlug = DataService::slugify($entidadPol);
if ($entidadSlug === 'mexico' || $entidadSlug === 'edomex') {
    $entidadSlug = 'estado-de-mexico';
}

$hasFotoLocal = isset($pol['tiene_foto_local'])
    ? (bool)$pol['tiene_foto_local']
    : (!empty($pol['referencia_sil']) && file_exists(dirname(__DIR__, 2) . "/storage/raw/photos/{$pol['referencia_sil']}.jpg"));

$fotoPol = (!empty($pol['referencia_sil']) && $hasFotoLocal)
    ? Router::url("/storage/raw/photos/{$pol['referencia_sil']}.jpg")
    : ($pol['foto_oficial_url'] ?? '');

$perfilUrl = Router::url('/politicos/' . $slug);
$esActivoPol = !empty($pol['es_activo']);
$cargosBadges = $pol['cargos_badges'] ?? [];
$cargoResumen = $pol['cargo_resumen'] ?? ($camaraPol . ' • ' . $entidadPol);
$rolesFlags = $pol['roles_flags'] ?? [];
$legsLista = $pol['legislaturas_lista'] ?? [(int)($pol['legislatura'] ?? 66)];

$munAlcalde = $pol['municipio_alcalde'] ?? '';
$dataSearch = mb_strtolower("{$nombreMostrar} {$nombrePol} {$partidoPol} {$entidadPol} {$camaraPol} {$cargoResumen} {$munAlcalde}");
$dataLegs = implode(',', $legsLista);
$dataRoles = [];
foreach ($rolesFlags as $rfKey => $rfVal) {
    if ($rfVal) {
        $dataRoles[] = $rfKey;
    }
}
$dataRolesStr = implode(',', $dataRoles);
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 politico-card-item"
     data-search="<?= ViewHelper::e($dataSearch) ?>"
     data-legs="<?= ViewHelper::e($dataLegs) ?>"
     data-roles="<?= ViewHelper::e($dataRolesStr) ?>"
     data-estado="<?= ViewHelper::e($entidadSlug) ?>"
     data-partido="<?= ViewHelper::e($partidoSlug) ?>"
     data-activo="<?= $esActivoPol ? '1' : '0' ?>">

    <div class="card card-civic h-100 border p-3 d-flex flex-column justify-content-between position-relative">
        <!-- Cabecera de la Card: Foto y Datos Clave -->
        <div>
            <div class="d-flex align-items-start gap-3 mb-2">
                <a href="<?= $perfilUrl ?>" class="text-decoration-none flex-shrink-0" aria-label="Ver perfil de <?= ViewHelper::e($nombreMostrar) ?>">
                    <?php if ($fotoPol): ?>
                        <img src="<?= ViewHelper::e($fotoPol) ?>" 
                             alt="<?= ViewHelper::e($nombreMostrar) ?>" 
                             class="rounded-circle border object-fit-cover shadow-xs" 
                             width="58" 
                             height="58" 
                             loading="lazy" 
                             decoding="async" 
                             referrerpolicy="no-referrer"
                             onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                        <div class="rounded-circle bg-light border align-items-center justify-content-center text-secondary fw-bold shadow-xs" style="width: 58px; height: 58px; font-size: 1.1rem; display: none;">
                            <?= mb_substr($nombreMostrar, 0, 1) ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-secondary fw-bold shadow-xs" style="width: 58px; height: 58px; font-size: 1.1rem;">
                            <?= mb_substr($nombreMostrar, 0, 1) ?>
                        </div>
                    <?php endif; ?>
                </a>

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                        <?php if ($esActivoPol): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.65rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>En funciones (LXVI)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-secondary border rounded-pill" style="font-size: 0.65rem;">
                                Archivo Histórico
                            </span>
                        <?php endif; ?>
                    </div>

                    <a href="<?= $perfilUrl ?>" class="text-decoration-none text-dark d-block">
                        <h2 class="h6 fw-bold mb-1 text-dark" title="<?= ViewHelper::e($nombrePol) ?>" style="font-size: 0.95rem; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.4rem;">
                            <?= ViewHelper::e($nombreMostrar) ?>
                        </h2>
                    </a>

                    <div class="text-muted small text-truncate mb-2" style="font-size: 0.75rem;" title="<?= ViewHelper::e($cargoResumen) ?>">
                        <i class="bi bi-geo-alt text-danger me-1"></i><?= ViewHelper::e($entidadPol) ?>
                    </div>
                </div>
            </div>

            <!-- Partido Político Badge -->
            <div class="mb-2">
                <?= ViewHelper::renderPartyBadge($partidoPol) ?>
            </div>

            <!-- Trayectoria Histórica y Cargos Acumulados -->
            <div class="pt-2 border-top mt-2">
                <span class="text-uppercase text-secondary d-block fw-semibold mb-1" style="font-size: 0.65rem; letter-spacing: 0.04em;">
                    Trayectoria acumulada:
                </span>
                <div class="d-flex flex-wrap gap-1">
                    <?php if (!empty($cargosBadges)): ?>
                        <?php foreach ($cargosBadges as $cb): ?>
                            <?php if (($cb['tipo'] ?? '') === 'alcalde' && !empty($cb['municipio'])): ?>
                                <a href="<?= Router::url('/alcaldes') ?>?q=<?= urlencode((string)$cb['municipio']) ?>" class="badge <?= ViewHelper::e($cb['badge_class']) ?> rounded-pill d-inline-flex align-items-center gap-1 text-decoration-none" style="font-size: 0.68rem; font-weight: 500;" title="Ver alcaldía e historial de <?= ViewHelper::e($cb['municipio']) ?>">
                                    <i class="bi <?= ViewHelper::e($cb['icono']) ?>" style="font-size: 0.65rem;"></i>
                                    <?= ViewHelper::e($cb['label']) ?>
                                </a>
                            <?php else: ?>
                                <span class="badge <?= ViewHelper::e($cb['badge_class']) ?> rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.68rem; font-weight: 500;">
                                    <i class="bi <?= ViewHelper::e($cb['icono']) ?>" style="font-size: 0.65rem;"></i>
                                    <?= ViewHelper::e($cb['label']) ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted" style="font-size: 0.7rem;">Sin cargos previos reportados</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer de la Card: Botón Ver Perfil y Reporte de Anomalía -->
        <div class="pt-3 border-top mt-3 d-flex align-items-center gap-2">
            <a href="<?= $perfilUrl ?>" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 d-flex align-items-center justify-content-center gap-1 fw-semibold" style="font-size: 0.8rem;">
                <span>Ver expediente completo</span>
                <i class="bi bi-arrow-right"></i>
            </a>
            <button type="button"
                    class="btn btn-sm btn-light border text-secondary rounded-circle btn-reportar-anomalia d-flex align-items-center justify-content-center"
                    style="width: 31px; height: 31px; flex-shrink: 0;"
                    data-origen-tipo="politico"
                    data-origen-id="<?= ViewHelper::e($slug) ?>"
                    data-origen-nombre="<?= ViewHelper::e($nombreMostrar) ?>"
                    data-origen-url="<?= $perfilUrl ?>"
                    title="Reportar anomalía o dato inconsistente en esta ficha"
                    data-bs-toggle="tooltip">
                <i class="bi bi-flag text-danger" style="font-size: 0.75rem;"></i>
            </button>
        </div>
    </div>
</div>
