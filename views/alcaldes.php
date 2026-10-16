<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array<string, array> $alcaldes
 * @var array<string, array> $estadosCatalog
 * @var array<string, int> $partidosConteo
 * @var int $totalUsosCostumbres
 * @var array $seo
 */

$homeUrl = Router::url('/');
$canonicalUrl = $seo['canonical'] ?? Router::url('/alcaldes');
$totalMunicipios = count($alcaldes);

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Alcaldías y Municipios</li>
    </ol>
</nav>

<!-- Hero del Directorio Nacional de Alcaldes -->
<div class="card-civic p-4 p-md-5 mb-4 position-relative overflow-hidden">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-primary rounded-pill">Gobernanza Local</span>
                <span class="badge bg-light text-secondary border">
                    <i class="bi bi-buildings me-1"></i><?= number_format($totalMunicipios) ?> Municipios y Alcaldías
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                    <i class="bi bi-shield-check me-1"></i>32 Entidades Federativas
                </span>
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill">
                    <i class="bi bi-clock-history me-1"></i>54 Años de Historia (1970–2027)
                </span>
            </div>
            <h1 class="display-6 fw-bold text-dark mb-2">
                Directorio Nacional de Alcaldes y Presidentes Municipales
            </h1>
            <p class="text-secondary mb-0">
                Consulta a los titulares de los 2,474 gobiernos municipales de México, su filiación partidista o régimen por Sistemas Normativos Indígenas y su historial político de alternancia.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <div class="d-inline-flex flex-column align-items-lg-end gap-1 p-3 bg-light rounded-4 border w-100">
                <span class="text-muted small fw-semibold">Fuerzas Políticas en Municipios:</span>
                <div class="d-flex flex-wrap gap-1 justify-content-lg-end mt-1">
                    <?php 
                    $topP = array_slice($partidosConteo, 0, 6, true);
                    foreach ($topP as $pNombre => $pCant): 
                    ?>
                        <span class="badge bg-white text-dark border px-2 py-1" style="font-size: 0.75rem;">
                            <strong><?= ViewHelper::e($pNombre) ?>:</strong> <?= number_format($pCant) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda Reactiva -->
<div class="card-civic p-3 mb-4 sticky-top bg-white shadow-xs" style="top: 15px; z-index: 99;">
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="filtroTexto" class="form-control bg-light border-start-0" placeholder="Buscar por municipio, alcalde o clave INEGI..." autocomplete="off">
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <select id="filtroEstado" class="form-select bg-light">
                <option value="">Todas las Entidades (32 estados)</option>
                <?php foreach ($estadosCatalog as $est): ?>
                    <option value="<?= ViewHelper::e($est['nombre']) ?>"><?= ViewHelper::e($est['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 col-sm-6">
            <select id="filtroPartido" class="form-select bg-light">
                <option value="">Todos los Partidos / Regímenes</option>
                <option value="Sistemas Normativos Indígenas">Sistemas Normativos Indígenas (<?= $totalUsosCostumbres ?>)</option>
                <?php foreach ($partidosConteo as $partido => $cant): 
                    if ($partido === 'Sistemas Normativos Indígenas') continue;
                ?>
                    <option value="<?= ViewHelper::e($partido) ?>"><?= ViewHelper::e($partido) ?> (<?= $cant ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted small">
        <span>Mostrando <strong id="conteoVisibles"><?= number_format($totalMunicipios) ?></strong> municipios</span>
        <button id="btnLimpiarFiltros" class="btn btn-link btn-sm text-decoration-none p-0 text-secondary d-none">
            <i class="bi bi-x-circle me-1"></i>Limpiar filtros
        </button>
    </div>
</div>

<!-- Grid de Municipios y Alcaldes -->
<div class="row g-3" id="gridMunicipios">
    <?php foreach ($alcaldes as $cvegeo => $item): 
        $munUrl = Router::url('/municipios/' . $cvegeo);
        $esSni = !empty($item['sistema']) && str_contains(strtolower((string)$item['sistema']), 'normativos');
        $partidoLabel = $esSni ? 'Usos y Costumbres' : ($item['partido'] ?? 'Sin Partido');
        $nombreEdil = $item['nombre_completo'] ?? 'Autoridad Municipal';
        $periodo = $item['periodo'] ?? 'Periodo Actual';
    ?>
        <div class="col-lg-4 col-md-6 item-municipio" 
             data-municipio="<?= ViewHelper::e(mb_strtolower($item['municipio'] ?? '')) ?>"
             data-estado="<?= ViewHelper::e($item['entidad'] ?? '') ?>"
             data-alcalde="<?= ViewHelper::e(mb_strtolower($nombreEdil)) ?>"
             data-partido="<?= ViewHelper::e($esSni ? 'Sistemas Normativos Indígenas' : ($item['partido'] ?? '')) ?>"
             data-cvegeo="<?= ViewHelper::e($cvegeo) ?>">
            <div class="card-civic h-100 p-3 d-flex flex-column justify-content-between transition-hover">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">
                                INEGI <?= ViewHelper::e($cvegeo) ?>
                            </span>
                            <span class="badge bg-light text-dark ms-1" style="font-size: 0.7rem;">
                                <?= ViewHelper::e($item['entidad'] ?? '') ?>
                            </span>
                        </div>
                        <?php if ($esSni): ?>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill" style="font-size: 0.68rem;">
                                <i class="bi bi-people me-1"></i>SNI
                            </span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.68rem;">
                                <?= ViewHelper::e($partidoLabel) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= ViewHelper::e($item['municipio'] ?? '') ?>">
                        <?= ViewHelper::e($item['municipio'] ?? '') ?>
                    </h5>

                    <div class="d-flex align-items-center gap-2 mb-2 mt-2">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-secondary border" style="width: 36px; height: 36px;">
                            <i class="bi bi-person text-secondary fs-5"></i>
                        </div>
                        <div class="overflow-hidden">
                            <div class="small text-muted" style="font-size: 0.72rem;">Presidente(a) Municipal:</div>
                            <div class="fw-semibold text-dark text-truncate" style="font-size: 0.9rem;" title="<?= ViewHelper::e($nombreEdil) ?>">
                                <?= ViewHelper::e($nombreEdil) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-top mt-2 d-flex justify-content-between align-items-center">
                    <span class="text-muted small" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar3 me-1"></i><?= ViewHelper::e($periodo) ?>
                    </span>
                    <a href="<?= $munUrl ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                        <span>Historial</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="sinResultados" class="card-civic p-5 text-center my-4 d-none">
    <i class="bi bi-search text-muted fs-1 mb-2 d-block"></i>
    <h5 class="fw-bold text-dark">No se encontraron municipios</h5>
    <p class="text-secondary small mb-0">Prueba con otro término de búsqueda o limpia los filtros de estado y partido.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputTexto = document.getElementById('filtroTexto');
    const selectEstado = document.getElementById('filtroEstado');
    const selectPartido = document.getElementById('filtroPartido');
    const conteoVisibles = document.getElementById('conteoVisibles');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const sinResultados = document.getElementById('sinResultados');
    const items = Array.from(document.querySelectorAll('.item-municipio'));

    function filtrar() {
        const texto = inputTexto.value.toLowerCase().trim();
        const estado = selectEstado.value;
        const partido = selectPartido.value;

        let visibles = 0;
        items.forEach(el => {
            const mun = el.dataset.municipio || '';
            const est = el.dataset.estado || '';
            const alc = el.dataset.alcalde || '';
            const part = el.dataset.partido || '';
            const cve = el.dataset.cvegeo || '';

            const matchTexto = !texto || mun.includes(texto) || alc.includes(texto) || cve.includes(texto);
            const matchEstado = !estado || est === estado;
            const matchPartido = !partido || part.includes(partido);

            if (matchTexto && matchEstado && matchPartido) {
                el.classList.remove('d-none');
                visibles++;
            } else {
                el.classList.add('d-none');
            }
        });

        conteoVisibles.textContent = visibles.toLocaleString('es-MX');
        sinResultados.classList.toggle('d-none', visibles > 0);
        btnLimpiar.classList.toggle('d-none', !texto && !estado && !partido);
    }

    inputTexto.addEventListener('input', filtrar);
    selectEstado.addEventListener('change', filtrar);
    selectPartido.addEventListener('change', filtrar);

    btnLimpiar.addEventListener('click', () => {
        inputTexto.value = '';
        selectEstado.value = '';
        selectPartido.value = '';
        filtrar();
    });
});
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
