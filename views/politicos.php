<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;
use PulsoCivico\Services\DataService;

/**
 * @var array<int, array> $politicos
 * @var array<int, array> $catalogLegislaturas
 * @var array<string, array> $estadosCatalog
 * @var array<string, array> $partidosCatalog
 * @var int $totalPoliticos
 * @var array $seo
 */

$homeUrl = Router::url('/');
$canonicalUrl = $seo['canonical'] ?? Router::url('/politicos');
$selectedLeg = isset($_GET['leg']) && trim((string)$_GET['leg']) !== '' ? trim((string)$_GET['leg']) : 'todas';

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Directorio de Políticos</li>
    </ol>
</nav>

<!-- Hero del Directorio -->
<div class="card-civic p-4 p-md-5 mb-4 position-relative overflow-hidden">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-primary rounded-pill">Directorio Ciudadano</span>
                <span class="badge bg-light text-secondary border">
                    <i class="bi bi-person-badge me-1"></i><?= number_format($totalPoliticos) ?> Registros Auditados
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                    <i class="bi bi-shield-check me-1"></i>Fuentes Oficiales
                </span>
            </div>
            <h1 class="display-6 fw-bold text-dark mb-2">
                Directorio de Políticos y Representantes
            </h1>
            <p class="text-secondary mb-0">
                Consulta el expediente público, cargos actuales y trayectoria acumulada de legisladores, gobernadores y ediles de la República Mexicana.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <div class="d-inline-flex flex-column align-items-lg-end gap-1 p-3 bg-light rounded-4 border">
                <span class="text-muted small">Cargos históricos rastreados:</span>
                <div class="d-flex flex-wrap gap-1 justify-content-lg-end">
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Senadores</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Dip. Federales</span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Dip. Locales</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Alcaldes</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Gobernadores</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barra de Búsqueda y Filtros Reactivos -->
<div class="card-civic p-3 p-md-4 mb-4 bg-white shadow-sm border">
    <div class="row g-2 align-items-center">
        <!-- Buscador de Texto Libre -->
        <div class="col-12 col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="filtroTexto" class="form-control bg-light border-start-0 rounded-end-pill py-2 shadow-none" placeholder="Buscar por nombre o apodo..." autocomplete="off">
            </div>
        </div>

        <!-- Filtro por Legislatura -->
        <div class="col-6 col-md-2">
            <select id="filtroLegislatura" class="form-select bg-light rounded-pill py-2 shadow-none text-secondary">
                <option value="todas">Todas las Legislaturas</option>
                <?php foreach ($catalogLegislaturas as $numL => $infoL): ?>
                    <option value="<?= $numL ?>" <?= ($selectedLeg === (string)$numL) ? 'selected' : '' ?>>
                        <?= ViewHelper::e($infoL['romano']) ?> (<?= ViewHelper::e($infoL['periodo']) ?>) <?= ($numL === 66 ? '• Actual' : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro por Cargo / Trayectoria Acumulada -->
        <div class="col-6 col-md-2">
            <select id="filtroCargo" class="form-select bg-light rounded-pill py-2 shadow-none text-secondary">
                <option value="todos">Todos los Cargos</option>
                <option value="senador">🏛️ Senadores</option>
                <option value="diputado_federal">🏛️ Diputados Federales</option>
                <option value="diputado_local">🏛️ Diputados Locales</option>
                <option value="alcalde">🏢 Alcaldes</option>
                <option value="edil">👥 Regidores / Síndicos (Cabildo)</option>
                <option value="gobernador">🏛️ Gobernadores</option>
                <option value="cambio_partido">🔀 Transición Partidista (Múltiples Partidos)</option>
            </select>
        </div>

        <!-- Filtro por Entidad Federativa -->
        <div class="col-6 col-md-2">
            <select id="filtroEstado" class="form-select bg-light rounded-pill py-2 shadow-none text-secondary">
                <option value="todos">Todas las Entidades</option>
                <?php foreach ($estadosCatalog as $estItem): ?>
                    <option value="<?= ViewHelper::e($estItem['slug']) ?>">
                        <?= ViewHelper::e($estItem['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro por Partido Político -->
        <div class="col-6 col-md-2">
            <select id="filtroPartido" class="form-select bg-light rounded-pill py-2 shadow-none text-secondary">
                <option value="todos">Todos los Partidos</option>
                <option value="morena">Morena</option>
                <option value="pan">PAN</option>
                <option value="pri">PRI</option>
                <option value="pvem">PVEM (Verde)</option>
                <option value="pt">PT (Trabajo)</option>
                <option value="mc">Movimiento Ciudadano</option>
                <option value="prd">PRD</option>
                <option value="independiente">Independiente / Sin Partido</option>
            </select>
        </div>
    </div>

    <!-- Barra Secundaria: Conmutador Solo Activos y Contador de Resultados -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="form-check form-switch m-0">
                <input class="form-check-input shadow-none" type="checkbox" role="switch" id="filtroSoloActivos">
                <label class="form-check-label small text-secondary fw-semibold" for="filtroSoloActivos">
                    Solo en funciones actualmente (LXVI)
                </label>
            </div>
            <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted p-0 d-none" id="btnLimpiarFiltros">
                <i class="bi bi-x-circle me-1"></i>Limpiar filtros
            </button>
        </div>
        <div class="text-secondary small fw-medium" id="contadorResultados">
            Mostrando <strong class="text-dark" id="numMostrados"><?= min(32, $totalPoliticos) ?></strong> de <span id="numTotales"><?= number_format($totalPoliticos) ?></span> políticos
        </div>
    </div>
</div>

<!-- Grid de Políticos (Lote Inicial SSR para carga instantánea y SEO) -->
<div class="row g-3" id="gridPoliticos">
    <?php 
    $loteInicial = array_slice($politicos, 0, 32);
    $cardPartialPath = __DIR__ . '/partials/card_politico.php';
    foreach ($loteInicial as $pol):
        require $cardPartialPath;
    endforeach; 
    ?>
</div>

<!-- Centinela y Loader de Infinite Scroll Progresivo -->
<div id="infiniteScrollSentinel" class="text-center py-4 my-3 <?= $totalPoliticos <= 32 ? 'd-none' : '' ?>">
    <div id="loaderSpinner" class="d-none mb-2">
        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
        <span class="text-secondary small fw-medium">Cargando más representantes...</span>
    </div>
    <button type="button" id="btnCargarMas" class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-semibold shadow-xs">
        <i class="bi bi-arrow-down-circle me-1"></i>Cargar más representantes
    </button>
</div>

<!-- Estado Vacío (Sin Resultados) -->
<div id="noResultados" class="card-civic p-5 text-center my-4 d-none">
    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
        <i class="bi bi-search text-muted fs-3"></i>
    </div>
    <h3 class="h5 fw-bold text-dark mb-1">No se encontraron políticos</h3>
    <p class="text-secondary small mb-3" style="max-width: 420px; margin: 0 auto;">
        No hay registros que coincidan con los filtros aplicados. Intenta restablecer los filtros o buscar con otro término.
    </p>
    <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" id="btnResetTodos">
        Restablecer todos los filtros
    </button>
</div>

<!-- Script Reactivo de Lazy Loading e Infinite Scroll Progresivo -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const apiUrl = '<?= Router::url('/api/politicos') ?>';
    const inputTexto = document.getElementById('filtroTexto');
    const selectLeg = document.getElementById('filtroLegislatura');
    const selectCargo = document.getElementById('filtroCargo');
    const selectEstado = document.getElementById('filtroEstado');
    const selectPartido = document.getElementById('filtroPartido');
    const switchActivos = document.getElementById('filtroSoloActivos');
    const numMostrados = document.getElementById('numMostrados');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const btnReset = document.getElementById('btnResetTodos');
    const noResultados = document.getElementById('noResultados');
    const grid = document.getElementById('gridPoliticos');
    const sentinel = document.getElementById('infiniteScrollSentinel');

    const btnCargarMas = document.getElementById('btnCargarMas');
    const loaderSpinner = document.getElementById('loaderSpinner');
    const numTotales = document.getElementById('numTotales');

    let currentPage = 1;
    let isLoading = false;
    let hasMore = <?= $totalPoliticos > 32 ? 'true' : 'false' ?>;
    let currentAbortController = null;

    function getParametros(pageNumber = 1) {
        const params = new URLSearchParams();
        params.set('page', pageNumber);
        params.set('limit', '32');

        const texto = (inputTexto.value || '').trim();
        if (texto !== '') params.set('q', texto);

        const leg = selectLeg.value;
        if (leg !== 'todas') params.set('leg', leg);

        const cargo = selectCargo.value;
        if (cargo !== 'todos') params.set('cargo', cargo);

        const estado = selectEstado.value;
        if (estado !== 'todos') params.set('estado', estado);

        const partido = selectPartido.value;
        if (partido !== 'todos') params.set('partido', partido);

        if (switchActivos.checked) params.set('activo', '1');

        return params;
    }

    function hayFiltrosActivos() {
        return (inputTexto.value || '').trim() !== ''
            || selectLeg.value !== 'todas'
            || selectCargo.value !== 'todos'
            || selectEstado.value !== 'todos'
            || selectPartido.value !== 'todos'
            || switchActivos.checked;
    }

    async function cargarLote(reset = false) {
        if (isLoading) return;
        if (!reset && !hasMore) return;

        if (currentAbortController) {
            currentAbortController.abort();
        }
        currentAbortController = new AbortController();

        isLoading = true;
        const targetPage = reset ? 1 : (currentPage + 1);

        if (reset) {
            grid.innerHTML = '';
            if (noResultados) noResultados.classList.add('d-none');
            grid.classList.remove('d-none');
        }

        if (sentinel) {
            sentinel.classList.remove('d-none');
            if (loaderSpinner) loaderSpinner.classList.remove('d-none');
            if (btnCargarMas) btnCargarMas.classList.add('d-none');
        }

        if (btnLimpiar) {
            btnLimpiar.classList.toggle('d-none', !hayFiltrosActivos());
        }

        try {
            const params = getParametros(targetPage);
            const response = await fetch(`${apiUrl}?${params.toString()}`, {
                signal: currentAbortController.signal,
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('Error al consultar políticos');

            const data = await response.json();
            if (data.ok) {
                // Actualizar opciones de droplists dinámicamente según resultados disponibles (Faceted Filtering)
                if (data.facetas) {
                    if (data.facetas.partidos) actualizarOpcionesDropdown(selectPartido, data.facetas.partidos, 'todos');
                    if (data.facetas.estados) actualizarOpcionesDropdown(selectEstado, data.facetas.estados, 'todos');
                    if (data.facetas.cargos) actualizarOpcionesDropdown(selectCargo, data.facetas.cargos, 'todos');
                    if (data.facetas.legislaturas) actualizarOpcionesDropdown(selectLeg, data.facetas.legislaturas, 'todas');
                }

                if (data.html && data.html.trim() !== '') {
                    grid.insertAdjacentHTML('beforeend', data.html);
                    if (noResultados) noResultados.classList.add('d-none');
                    grid.classList.remove('d-none');
                } else if (reset) {
                    if (noResultados) noResultados.classList.remove('d-none');
                    grid.classList.add('d-none');
                }

                const totalEnDOM = grid.querySelectorAll('.politico-card-item').length;
                if (numMostrados) {
                    numMostrados.textContent = totalEnDOM.toLocaleString();
                }
                if (numTotales && data.total_filtrados !== undefined) {
                    numTotales.textContent = Number(data.total_filtrados).toLocaleString();
                }

                currentPage = targetPage;
                hasMore = Boolean(data.has_more);

                if (!hasMore && sentinel) {
                    sentinel.classList.add('d-none');
                }
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Error cargando representantes:', err);
            }
        } finally {
            isLoading = false;
            if (loaderSpinner) loaderSpinner.classList.add('d-none');
            if (hasMore && btnCargarMas) {
                btnCargarMas.classList.remove('d-none');
            }
        }
    }

    function actualizarOpcionesDropdown(selectElement, valoresValidos, neutroValue = 'todos') {
        if (!selectElement || !Array.isArray(valoresValidos)) return;
        const setValidos = new Set(valoresValidos.map(v => String(v).toLowerCase()));
        
        let seleccionSigueValida = false;
        for (const opt of selectElement.options) {
            const val = opt.value;
            if (val === neutroValue || val === '') {
                opt.hidden = false;
                opt.disabled = false;
                opt.style.display = '';
                continue;
            }

            const esValida = setValidos.has(String(val).toLowerCase());
            opt.hidden = !esValida;
            opt.disabled = !esValida;
            opt.style.display = esValida ? '' : 'none';

            if (esValida && selectElement.value === val) {
                seleccionSigueValida = true;
            }
        }

        // Si el valor previamente seleccionado ya no tiene coincidencias en la combinación, volver a neutral
        if (selectElement.value !== neutroValue && !seleccionSigueValida) {
            selectElement.value = neutroValue;
        }
    }

    function resetearOpcionesDropdown(selectElement) {
        if (!selectElement) return;
        for (const opt of selectElement.options) {
            opt.hidden = false;
            opt.disabled = false;
            opt.style.display = '';
        }
    }

    function resetearFiltros() {
        inputTexto.value = '';
        selectLeg.value = 'todas';
        selectCargo.value = 'todos';
        selectEstado.value = 'todos';
        selectPartido.value = 'todos';
        switchActivos.checked = false;
        resetearOpcionesDropdown(selectLeg);
        resetearOpcionesDropdown(selectCargo);
        resetearOpcionesDropdown(selectEstado);
        resetearOpcionesDropdown(selectPartido);
        cargarLote(true);
    }

    let debounceTimer = null;
    function debounce(fn, delay) {
        return function(...args) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    let userHasScrolled = false;
    window.addEventListener('scroll', () => { userHasScrolled = true; }, { once: true, passive: true });

    // Botón manual Cargar Más
    if (btnCargarMas) {
        btnCargarMas.addEventListener('click', () => {
            userHasScrolled = true;
            cargarLote(false);
        });
    }

    // Configurar IntersectionObserver para Infinite Scroll progresivo tras scroll
    if (sentinel && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            const first = entries[0];
            if (first && first.isIntersecting && hasMore && !isLoading && userHasScrolled) {
                cargarLote(false);
            }
        }, {
            rootMargin: '100px 0px'
        });

        observer.observe(sentinel);
    }

    // Event Listeners
    inputTexto.addEventListener('input', debounce(() => cargarLote(true), 220));
    selectLeg.addEventListener('change', () => cargarLote(true));
    selectCargo.addEventListener('change', () => cargarLote(true));
    selectEstado.addEventListener('change', () => cargarLote(true));
    selectPartido.addEventListener('change', () => cargarLote(true));
    switchActivos.addEventListener('change', () => cargarLote(true));

    if (btnLimpiar) btnLimpiar.addEventListener('click', resetearFiltros);
    if (btnReset) btnReset.addEventListener('click', resetearFiltros);
});
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
