<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $estados
 */

$pathsFile = dirname(__DIR__, 2) . '/storage/data/mexico_paths.json';
$paths = [];
if (file_exists($pathsFile)) {
    $paths = json_decode((string)file_get_contents($pathsFile), true) ?? [];
}

$defaultEstado = $estados['baja-california'] ?? [
    'nombre' => 'Baja California',
    'slug' => 'baja-california',
    'abbr' => 'BC',
    'total_representantes' => 24,
    'partido_mayoria' => 'Morena',
    'conteo_partidos' => ['Morena' => 14, 'PAN' => 5, 'PVEM' => 4, 'MC' => 1],
    'disponible' => true
];

// Cálculo dinámico de fuerzas mayoritarias en el mapa para la legislatura activa
$partidosEnMapa = [];
$totalEstadosConDatos = 0;
foreach ($estados as $slug => $estData) {
    if (!empty($estData['disponible'])) {
        $totalEstadosConDatos++;
        if (!empty($estData['partido_mayoria'])) {
            $pNom = ViewHelper::getCanonicalPartyName((string)$estData['partido_mayoria']);
            $partidosEnMapa[$pNom] = ($partidosEnMapa[$pNom] ?? 0) + 1;
        }
    }
}
arsort($partidosEnMapa);
?>

<div class="mapa-mexico-container card-civic p-3 p-md-4 mb-5 position-relative" id="seccion-mapa">
    <!-- Encabezado de la Sección -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 text-start">
        <div>
            <h2 class="h5 fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-danger"></i>
                Mapa Político de la República Mexicana
            </h2>
            <p class="text-secondary small mb-0">
                Selecciona cualquier entidad en el mapa para ver la distribución de bancadas partidistas en tiempo real (Morena, PAN, PRI, PVEM, PT, MC, PRD).
            </p>
        </div>

        <!-- Leyenda Dinámica de Colores de Partidos y Status -->
        <div class="d-flex align-items-center gap-2 flex-wrap small">
            <span class="text-secondary fw-semibold me-1 d-none d-sm-inline" style="font-size: 0.78rem;">
                Fuerza mayoritaria:
            </span>
            <?php if (!empty($partidosEnMapa)): ?>
                <?php foreach ($partidosEnMapa as $pName => $stCount): 
                    $pColor = ViewHelper::getPartyColor($pName);
                ?>
                <span class="d-flex align-items-center gap-1 me-2" title="<?= ViewHelper::e($pName) ?>: primera fuerza en <?= $stCount ?> entidad<?= $stCount !== 1 ? 'es' : '' ?>">
                    <span class="rounded-circle d-inline-block shadow-2xs" style="width: 10px; height: 10px; background-color: <?= $pColor ?>;"></span>
                    <span class="fw-medium text-dark"><?= ViewHelper::e($pName) ?></span>
                    <span class="text-secondary" style="font-size: 0.72rem;">(<?= $stCount ?>)</span>
                </span>
                <?php endforeach; ?>
            <?php else: ?>
                <?php 
                $partidosNacionales = ['Morena', 'PAN', 'PRI', 'PVEM', 'PT', 'MC', 'PRD'];
                foreach ($partidosNacionales as $pName):
                    $pColor = ViewHelper::getPartyColor($pName);
                ?>
                <span class="d-flex align-items-center gap-1 me-2">
                    <span class="rounded-circle d-inline-block shadow-2xs" style="width: 10px; height: 10px; background-color: <?= $pColor ?>;"></span>
                    <span class="fw-medium text-dark"><?= $pName ?></span>
                </span>
                <?php endforeach; ?>
            <?php endif; ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 ms-sm-2 d-inline-flex align-items-center gap-1">
                <i class="bi bi-patch-check-fill"></i> <?= $totalEstadosConDatos ?>/32 Cobertura
            </span>
        </div>
    </div>

    <!-- Layout Split-View: Mapa (Izq) + Overview Card de Partido (Der) -->
    <div class="row g-4 align-items-center">
        <!-- Columna del Mapa SVG -->
        <div class="col-lg-8">
            <div class="map-svg-wrapper position-relative mx-auto">
                <svg viewBox="0 0 960 600" class="w-100 h-auto" id="mexicoSvgMap" style="filter: drop-shadow(0 4px 12px rgba(0,0,0,0.06));">
                    <?php foreach ($paths as $st): 
                        $slug = $st['slug'] ?? '';
                        $name = $st['name'] ?? '';
                        $estadoData = $estados[$slug] ?? null;

                        $color = '#CBD5E1'; // Neutral para los que aún se integran
                        $disponible = false;

                        if (!empty($estadoData['disponible'])) {
                            $disponible = true;
                            $mayoria = $estadoData['partido_mayoria'] ?? 'Morena';
                            $color = ViewHelper::getPartyColor($mayoria);
                        }
                    ?>
                    <path d="<?= $st['d'] ?>" 
                          id="state-<?= $slug ?>" 
                          class="state-path <?= $disponible ? 'state-active' : 'state-pending' ?> <?= ($slug === 'baja-california') ? 'state-selected' : '' ?>"
                          data-slug="<?= ViewHelper::e($slug) ?>"
                          data-name="<?= ViewHelper::e($name) ?>"
                          data-abbr="<?= ViewHelper::e($estadoData['abbr'] ?? 'MEX') ?>"
                          data-party="<?= ViewHelper::e($estadoData['partido_mayoria'] ?? 'En proceso') ?>"
                          data-reps="<?= ViewHelper::e((string)($estadoData['total_representantes'] ?? 0)) ?>"
                          data-url="<?= Router::url('/estados/' . $slug . ((isset($currentLegNum) && $currentLegNum !== 66) ? '?leg=' . $currentLegNum : '')) ?>"
                          data-conteo='<?= json_encode($estadoData['conteo_partidos'] ?? []) ?>'
                          fill="<?= $color ?>"
                          stroke="#FFFFFF"
                          stroke-width="1.2"
                          stroke-linejoin="round">
                    </path>
                    <?php endforeach; ?>
                </svg>

                <!-- Tooltip Ligero al pasar el cursor -->
                <div id="mapTooltip" class="map-tooltip shadow-sm"></div>
            </div>
        </div>

        <!-- Columna del Overview Card (Resumen partidista que incentiva la navegación) -->
        <div class="col-lg-4">
            <div class="card-civic p-4 border bg-white shadow-xs text-start" id="estadoOverviewCard" style="border-radius: 18px;">
                <!-- Encabezado del Estado -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-light text-secondary border small" id="badgeLegislaturaCard"><?= ViewHelper::e($currentLegRomano ?? 'LXVI') ?> Legislatura</span>
                </div>

                <h3 class="h5 fw-bold text-dark mb-1" id="cardEstadoNombre">
                    <?= ViewHelper::e($defaultEstado['nombre']) ?>
                </h3>
                <div class="text-secondary small mb-3" id="cardEstadoSubtitulo">
                    <span id="cardEstadoTotalReps"><?= $defaultEstado['total_representantes'] ?></span> representantes federales
                </div>

                <!-- Mayoría del Partido -->
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="text-secondary small mb-1 fw-semibold" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em;">
                        Fuerza Mayoritaria en el Congreso:
                    </div>
                    <div class="d-flex align-items-center gap-2" id="cardEstadoMayoriaBadge">
                        <?= ViewHelper::renderPartyBadge($defaultEstado['partido_mayoria']) ?>
                        <?php 
                        $mayoriaCurules = ($defaultEstado['conteo_partidos'] ?? [])[$defaultEstado['partido_mayoria']] ?? null;
                        if ($mayoriaCurules !== null): 
                        ?>
                            <span class="badge bg-light text-dark border small"><?= $mayoriaCurules ?> de <?= $defaultEstado['total_representantes'] ?> curules</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Barra de Distribución Partidista -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center small text-secondary mb-1">
                        <span class="fw-semibold text-dark" style="font-size: 0.75rem;">Composición por Bancadas:</span>
                    </div>

                    <div class="progress mb-2" style="height: 8px; border-radius: 6px;" id="cardEstadoProgressBar">
                        <?php 
                        $total = $defaultEstado['total_representantes'] ?: 1;
                        foreach (($defaultEstado['conteo_partidos'] ?? []) as $pName => $pCount): 
                            $pct = round(($pCount / $total) * 100, 1);
                            $color = ViewHelper::getPartyColor($pName);
                        ?>
                        <div class="progress-bar" style="width: <?= $pct ?>%; background-color: <?= $color ?>;" title="<?= ViewHelper::e($pName) ?>: <?= $pCount ?>"></div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Lista Resumida de Curules por Partido -->
                    <div class="d-flex flex-column gap-2 small text-secondary" id="cardEstadoPartidosList">
                        <?php foreach (($defaultEstado['conteo_partidos'] ?? []) as $pName => $pCount): 
                            $color = ViewHelper::getPartyColor($pName);
                        ?>
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                            <span class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: <?= $color ?>;"></span>
                                <span class="text-dark fw-medium"><?= ViewHelper::e($pName) ?></span>
                            </span>
                            <span class="badge bg-light text-dark border font-monospace">
                                <strong><?= $pCount ?></strong> curules
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Botón de Llamado a la Acción para Explorar (Incentiva Navegación Profunda) -->
                <div class="pt-2">
                    <a href="<?= Router::url('/estados/' . $defaultEstado['slug'] . (isset($currentLegNum) && $currentLegNum !== 66 ? '?leg=' . $currentLegNum : '')) ?>" 
                       class="btn btn-primary rounded-pill w-100 py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold shadow-xs" 
                       id="btnExplorarEstado">
                        <span>Ver los <?= $defaultEstado['total_representantes'] ?> legisladores</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Selector móvil accesible (Evita fat-finger en estados diminutos) -->
    <div class="mt-4 pt-3 border-top d-flex flex-column flex-sm-row align-items-center justify-content-center gap-2">
        <label for="selectEstadoMobile" class="small fw-semibold text-secondary">
            <i class="bi bi-phone me-1"></i>O selecciona tu entidad federativa:
        </label>
        <select id="selectEstadoMobile" class="form-select form-select-sm w-auto rounded-pill border shadow-none">
            <option value="">-- Elige un estado --</option>
            <?php foreach ($estados as $slug => $est): ?>
                <option value="<?= ViewHelper::e($slug) ?>" <?= ($slug === 'baja-california') ? 'selected' : '' ?>>
                    <?= ViewHelper::e($est['nombre']) ?> <?= !empty($est['disponible']) ? '★ (Activo)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<style>
.state-path {
    cursor: pointer;
    transition: fill 0.2s ease, filter 0.2s ease, stroke-width 0.2s ease;
}
.state-path:hover {
    filter: brightness(1.12);
    stroke: #1C1C1E;
    stroke-width: 1.8;
}
.state-selected {
    stroke: #1C1C1E !important;
    stroke-width: 2.2 !important;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.25)) !important;
}
.map-tooltip {
    position: absolute;
    display: none;
    background: rgba(28, 28, 30, 0.94);
    color: #FFFFFF;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.78rem;
    pointer-events: none;
    z-index: 100;
    white-space: nowrap;
    backdrop-filter: blur(4px);
}
</style>

<script>
(function() {
    const map = document.getElementById('mexicoSvgMap');
    const tooltip = document.getElementById('mapTooltip');
    const selectMobile = document.getElementById('selectEstadoMobile');
    const baseUrl = '<?= Router::initBasePath() ?>';

    // Elementos del Overview Card lateral
    const cardNombre = document.getElementById('cardEstadoNombre');
    const cardTotalReps = document.getElementById('cardEstadoTotalReps');
    const cardMayoriaBadge = document.getElementById('cardEstadoMayoriaBadge');
    const cardProgressBar = document.getElementById('cardEstadoProgressBar');
    const cardPartidosList = document.getElementById('cardEstadoPartidosList');
    const btnExplorar = document.getElementById('btnExplorarEstado');

    const urlParams = new URLSearchParams(window.location.search);
    const pathLegMatch = window.location.pathname.match(/\/legislaturas\/(\d+)/);
    const activeLeg = pathLegMatch ? parseInt(pathLegMatch[1], 10) : (urlParams.get('leg') ? parseInt(urlParams.get('leg'), 10) : <?= (int)($currentLegNum ?? 66) ?>);

    if (btnExplorar && activeLeg !== 66 && !btnExplorar.href.includes('?leg=')) {
        btnExplorar.href += (btnExplorar.href.includes('?') ? '&' : '?') + 'leg=' + activeLeg;
    }

    // Función resiliente de resolución de color de partido en JS (alineada con ViewHelper::getPartyColor)
    function getPartyColorJs(partido) {
        if (!partido) return '#6C757D';
        const upper = partido.toUpperCase().trim();
        if (upper.includes('MORENA')) return '#8B1C3F';
        if (upper.includes('PAN') && !upper.includes('PANAL')) return '#003893';
        if (upper.includes('VERDE') || upper.includes('PVEM')) return '#009E49';
        if (upper.includes('PRI') || upper.includes('REVOLUCIONARIO')) return '#C8102E';
        if (upper.includes('PT') || upper.includes('TRABAJO')) return '#DA291C';
        if (upper.includes('MC') || upper.includes('CIUDADANO') || upper.includes('CONVERGENCIA')) return '#FF8300';
        if (upper.includes('PES') || upper.includes('ENCUENTRO')) return '#582C83';
        if (upper.includes('PRD')) return '#FFD100';
        if (upper.includes('PANAL') || upper.includes('NUEVA ALIANZA')) return '#00A896';
        if (upper.includes('INDEPENDIENTE') || upper.includes('SIN PARTIDO')) return '#64748B';
        return '#6C757D';
    }

    function actualizarCardLateral(name, slug, abbr, party, reps, conteo) {
        // Remover clase seleccionada previa
        map.querySelectorAll('.state-path').forEach(p => p.classList.remove('state-selected'));
        const activePath = document.getElementById('state-' + slug);
        if (activePath) activePath.classList.add('state-selected');

        cardNombre.textContent = name;
        cardTotalReps.textContent = reps > 0 ? reps : 'En integración';
        const currentLeg = activeLeg;
        btnExplorar.href = baseUrl + '/estados/' + slug + (currentLeg !== 66 ? '?leg=' + currentLeg : '');

        if (reps > 0 && Object.keys(conteo).length > 0) {
            // Renderizar Badge de mayoría
            const colorMayoria = getPartyColorJs(party);
            const mayoriaCount = conteo[party] || '';
            cardMayoriaBadge.innerHTML = `
                <span class="badge px-3 py-2 text-white" style="background-color: ${colorMayoria}; font-size: 0.82rem;">
                    ${party}
                </span>
                ${mayoriaCount ? `<span class="badge bg-light text-dark border small">${mayoriaCount} de ${reps} curules</span>` : ''}
            `;

            // Renderizar Barra de Progreso
            let progressHtml = '';
            for (const [pName, pCount] of Object.entries(conteo)) {
                const pct = ((pCount / reps) * 100).toFixed(1);
                const col = getPartyColorJs(pName);
                progressHtml += `<div class="progress-bar" style="width: ${pct}%; background-color: ${col};" title="${pName}: ${pCount}"></div>`;
            }
            cardProgressBar.innerHTML = progressHtml;

            // Renderizar Lista de Curules
            let listHtml = '';
            for (const [pName, pCount] of Object.entries(conteo)) {
                const col = getPartyColorJs(pName);
                listHtml += `
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                        <span class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: ${col};"></span>
                            <span class="text-dark fw-medium">${pName}</span>
                        </span>
                        <span class="badge bg-light text-dark border font-monospace">
                            <strong>${pCount}</strong> curules
                        </span>
                    </div>
                `;
            }
            cardPartidosList.innerHTML = listHtml;
            btnExplorar.innerHTML = `<span>Ver los ${reps} legisladores</span> <i class="bi bi-arrow-right"></i>`;
            btnExplorar.classList.remove('btn-secondary');
            btnExplorar.classList.add('btn-primary');
        } else {
            // Estado en proceso de integración
            cardMayoriaBadge.innerHTML = `<span class="badge bg-secondary-subtle text-secondary px-3 py-2 border">Datos en proceso SIL</span>`;
            cardProgressBar.innerHTML = `<div class="progress-bar bg-secondary" style="width: 100%;"></div>`;
            cardPartidosList.innerHTML = `
                <div class="text-muted small py-2">
                    <i class="bi bi-clock-history me-1"></i>Los legisladores de este estado se están procesando actualmente en el Sistema de Información Legislativa.
                </div>
            `;
            btnExplorar.innerHTML = `<span>Ver expediente en proceso</span> <i class="bi bi-arrow-right"></i>`;
            btnExplorar.classList.remove('btn-primary');
            btnExplorar.classList.add('btn-secondary');
        }
    }

    if (map) {
        map.querySelectorAll('.state-path').forEach(el => {
            // Tooltip rápido en mousemove
            el.addEventListener('mousemove', (e) => {
                const name = el.dataset.name;
                const party = el.dataset.party;
                const reps = parseInt(el.dataset.reps) || 0;

                let text = `<strong>${name}</strong>`;
                if (reps > 0) {
                    text += `<br><span style="color: #93C5FD;">Mayoría: ${party}</span> &bull; ${reps} legisladores`;
                } else {
                    text += `<br><span style="color: #CBD5E1;">Próximamente</span>`;
                }

                tooltip.innerHTML = text;
                tooltip.style.display = 'block';

                const rect = map.getBoundingClientRect();
                tooltip.style.left = (e.clientX - rect.left + 15) + 'px';
                tooltip.style.top = (e.clientY - rect.top - 10) + 'px';
            });

            el.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });

            // Clic actualiza el Overview Card lateral al instante
            el.addEventListener('click', () => {
                const name = el.dataset.name;
                const slug = el.dataset.slug;
                const abbr = el.dataset.abbr;
                const party = el.dataset.party;
                const reps = parseInt(el.dataset.reps) || 0;
                let conteo = {};
                try {
                    conteo = JSON.parse(el.dataset.conteo || '{}');
                } catch (e) {}

                actualizarCardLateral(name, slug, abbr, party, reps, conteo);

                // En móvil hacer scroll suave hacia la tarjeta
                if (window.innerWidth < 992) {
                    document.getElementById('estadoOverviewCard')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    }

    if (selectMobile) {
        selectMobile.addEventListener('change', function() {
            const slug = this.value;
            if (!slug) return;
            const targetPath = document.getElementById('state-' + slug);
            if (targetPath) {
                targetPath.dispatchEvent(new Event('click'));
            }
        });
    }
})();
</script>
