<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $comisiones
 * @var array $seo
 */

$homeUrl = Router::url('/');
$comisionesUrl = Router::url('/comisiones');

// Contar comisiones por cámara
$totalComisiones = count($comisiones);
$totalDiputados = 0;
$totalSenado = 0;

foreach ($comisiones as $c) {
    $cam = $c['camara'] ?? '';
    if (str_contains(strtoupper($c['nombre']), 'DIPUTADOS') || $cam === 'Diputados') {
        $totalDiputados++;
    } elseif (str_contains(strtoupper($c['nombre']), 'SENAD') || $cam === 'Senadores') {
        $totalSenado++;
    }
}

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Comisiones Legislativas</li>
    </ol>
</nav>

<!-- Hero de Comisiones -->
<div class="card-civic p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-primary rounded-pill">LXVI Legislatura</span>
        <span class="badge bg-light text-secondary border">Órganos Dictaminadores</span>
    </div>
    <h1 class="h2 fw-bold text-dark mb-2">Comisiones del Congreso de la Unión</h1>
    <p class="text-secondary small mb-3" style="max-width: 720px;">
        Directorio y observatorio ciudadano de los órganos técnicos encargados de estudiar, dictaminar y debatir iniciativas de ley, reformas constitucionales y puntos de acuerdo en ambas cámaras.
    </p>

    <!-- Métricas Rápidas -->
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-white text-dark border px-3 py-2 fs-6 shadow-2xs">
            <i class="bi bi-briefcase-fill text-primary me-1"></i>
            <strong><?= $totalComisiones ?></strong> Comisiones Registradas
        </span>
        <span class="badge bg-white text-secondary border px-3 py-2 fs-6 shadow-2xs">
            <i class="bi bi-building text-info me-1"></i>
            <strong><?= $totalDiputados ?></strong> C. Diputados
        </span>
        <span class="badge bg-white text-secondary border px-3 py-2 fs-6 shadow-2xs">
            <i class="bi bi-bank text-warning me-1"></i>
            <strong><?= $totalSenado ?></strong> Senado
        </span>
    </div>
</div>

<!-- Barra de Control: Buscador en Vivo y Filtros de Cámara -->
<div class="card-civic p-3 p-md-4 mb-4">
    <div class="row g-3 align-items-center">
        <!-- Buscador Reactivo -->
        <div class="col-lg-6">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="busquedaComision" class="form-control border-start-0 ps-1 shadow-none" 
                       placeholder="Buscar comisión por materia (ej. Energía, Presupuesto, Salud)..." 
                       autocomplete="off">
                <button type="button" class="btn btn-outline-secondary border-start-0 d-none" id="btnLimpiarBusqueda" title="Limpiar búsqueda">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>

        <!-- Filtros por Cámara -->
        <div class="col-lg-6 text-lg-end">
            <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border" role="group" id="filtroCamara">
                <button type="button" class="btn btn-sm rounded-pill btn-primary px-3 fw-medium active" data-filtro="todos">
                    Todas (<?= $totalComisiones ?>)
                </button>
                <button type="button" class="btn btn-sm rounded-pill btn-light text-secondary px-3 fw-medium" data-filtro="diputados">
                    Diputados (<?= $totalDiputados ?>)
                </button>
                <button type="button" class="btn btn-sm rounded-pill btn-light text-secondary px-3 fw-medium" data-filtro="senadores">
                    Senadores (<?= $totalSenado ?>)
                </button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top text-muted small">
        <span id="contadorResultados">Mostrando <strong><?= $totalComisiones ?></strong> comisiones</span>
        <span class="d-none d-md-inline">Toca cualquier comisión para ver su mesa directiva, integrantes y balance político.</span>
    </div>
</div>

<!-- Listado Grid de Comisiones -->
<div class="row g-3 g-md-4" id="contenedorComisiones">
    <?php foreach ($comisiones as $c): 
        $slug = $c['slug'] ?? '';
        $nombreCom = $c['nombre'] ?? '';
        $url = Router::url('/comisiones/' . $slug);
        $integrantes = $c['integrantes'] ?? [];
        $totalInt = count($integrantes);
        
        // Detectar cámara
        $camaraTag = 'Congreso';
        if (str_contains(strtoupper($nombreCom), 'DIPUTADOS') || ($c['camara'] ?? '') === 'Diputados') {
            $camaraTag = 'Diputados';
        } elseif (str_contains(strtoupper($nombreCom), 'SENAD') || ($c['camara'] ?? '') === 'Senadores') {
            $camaraTag = 'Senadores';
        }

        // Detectar Presidencia si existe
        $presidencia = $c['presidente'] ?? null;
        if (!$presidencia) {
            foreach ($integrantes as $it) {
                if (str_contains(strtoupper(trim($it['puesto'] ?? '')), 'PRESID')) {
                    $presidencia = $it;
                    break;
                }
            }
        }

        $fotoPresidencia = 'data:image/svg+xml;utf8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27150%27 height=%27150%27 viewBox=%270 0 150 150%27%3E%3Crect width=%27150%27 height=%27150%27 fill=%27%23F1F5F9%27/%3E%3Ccircle cx=%2775%27 cy=%2758%27 r=%2726%27 fill=%27%2394A3B8%27/%3E%3Cpath d=%27M25 138 C25 103 125 103 125 138%27 fill=%27%2394A3B8%27/%3E%3C/svg%3E';
        if ($presidencia) {
            $refSil = (string)($presidencia['referencia_sil'] ?? '');
            if ($refSil !== '' && file_exists(dirname(__DIR__) . "/storage/raw/photos/{$refSil}.jpg")) {
                $fotoPresidencia = Router::url("/storage/raw/photos/{$refSil}.jpg");
            } elseif (!empty($presidencia['foto_oficial_url'])) {
                $fotoPresidencia = $presidencia['foto_oficial_url'];
            }
        }

        // Conteo rápido de partidos para minibar
        $partidosPills = [];
        foreach ($integrantes as $it) {
            $pty = trim($it['partido'] ?? '');
            if ($pty !== '') {
                $partidosPills[$pty] = ($partidosPills[$pty] ?? 0) + 1;
            }
        }
        arsort($partidosPills);
        $topPartidos = array_slice($partidosPills, 0, 4, true);
    ?>
    <div class="col-12 col-md-6 col-xl-4 tarjeta-comision-wrapper" 
         data-nombre="<?= mb_strtolower(ViewHelper::e($nombreCom)) ?>"
         data-camara="<?= mb_strtolower($camaraTag) ?>">
        <a href="<?= $url ?>" class="text-decoration-none text-dark d-block h-100">
            <div class="card-civic p-3 p-md-4 h-100 d-flex flex-column">
                <!-- Barra superior: Cámara y Total integrantes -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge rounded-pill <?= ($camaraTag === 'Diputados') ? 'bg-primary-subtle text-primary border' : (($camaraTag === 'Senadores') ? 'bg-warning-subtle text-dark border' : 'bg-light text-secondary border') ?>" style="font-size: 0.72rem;">
                        <i class="bi <?= ($camaraTag === 'Diputados') ? 'bi-building' : 'bi-bank' ?> me-1"></i>C. <?= $camaraTag ?>
                    </span>
                    <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">
                        <i class="bi bi-people-fill me-1"></i><?= $totalInt ?> integrantes
                    </span>
                </div>

                <!-- Título de la Comisión -->
                <h2 class="h6 fw-bold text-dark mb-2 text-truncate-2" style="min-height: 2.6rem; line-height: 1.3;" title="<?= ViewHelper::e($nombreCom) ?>">
                    <?= ViewHelper::e($nombreCom) ?>
                </h2>

                <!-- Presidencia destacada con fotografía -->
                <div class="mb-3 mt-1">
                    <?php if ($presidencia): 
                        $nomPres = $presidencia['nombre_completo'] ?? ($presidencia['nombre'] ?? '');
                        $ptyPres = $presidencia['partido'] ?? '';
                    ?>
                        <div class="p-2 bg-light rounded-3 border d-flex align-items-center gap-2">
                            <img src="<?= ViewHelper::e($fotoPresidencia) ?>" alt="<?= ViewHelper::e($nomPres) ?>" class="rounded-circle border flex-shrink-0 shadow-2xs" style="width: 38px; height: 38px; object-fit: cover;">
                            <div class="min-w-0 flex-grow-1">
                                <div class="small fw-semibold text-truncate text-dark" style="font-size: 0.78rem;" title="<?= ViewHelper::e($nomPres) ?>">
                                    <?= ViewHelper::e($nomPres) ?>
                                </div>
                                <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.68rem;">
                                    <span class="badge bg-warning text-dark border px-1 py-0 shadow-2xs" style="font-size: 0.62rem;">Presidencia</span>
                                    <?php if (!empty($ptyPres)): ?>
                                        <span>&bull;</span>
                                        <span class="fw-medium text-secondary text-truncate"><?= ViewHelper::e($ptyPres) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-2 bg-light-subtle rounded-3 border border-dashed text-muted small text-center" style="font-size: 0.72rem; min-height: 52px; display: flex; align-items: center; justify-content: center;">
                            Mesa Directiva Colegiada
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Composición por Bancadas en el footer -->
                <div class="mt-auto pt-2 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-secondary" style="font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;">
                            Bancadas:
                        </span>
                        <span class="text-primary small fw-semibold" style="font-size: 0.73rem;">
                            Ver comisión <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <?php foreach ($topPartidos as $ptyName => $ptyCount): 
                            $pColor = ViewHelper::getPartyColor($ptyName);
                        ?>
                            <span class="badge rounded-pill px-2 py-1 border shadow-2xs d-inline-flex align-items-center gap-1" 
                                  style="font-size: 0.68rem; background-color: <?= $pColor ?>15; color: <?= $pColor ?>; border-color: <?= $pColor ?>40 !important;" 
                                  title="<?= ViewHelper::e($ptyName) ?>: <?= $ptyCount ?> legisladores">
                                <span class="rounded-circle d-inline-block" style="width: 6px; height: 6px; background-color: <?= $pColor ?>;"></span>
                                <strong style="font-weight: 700;"><?= ViewHelper::e($ptyName) ?></strong> <?= $ptyCount ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Estado vacío si la búsqueda no arroja resultados -->
<div id="estadoVacio" class="text-center py-5 d-none">
    <div class="p-4 bg-white rounded-4 border shadow-sm mx-auto" style="max-width: 420px;">
        <i class="bi bi-search text-muted display-4 d-block mb-3"></i>
        <h3 class="h5 fw-bold text-dark mb-1">Sin coincidencias</h3>
        <p class="text-muted small mb-3">No localizamos ninguna comisión con ese término. Intenta con otra palabra como <em>Educación</em>, <em>Hacienda</em> o <em>Justicia</em>.</p>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" id="btnRestablecerFiltros">
            Ver todas las comisiones
        </button>
    </div>
</div>

<script>
// Buscador en Vivo y Filtrado Reactivo Native-First
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('busquedaComision');
    const btnLimpiar = document.getElementById('btnLimpiarBusqueda');
    const btnRestablecer = document.getElementById('btnRestablecerFiltros');
    const tarjetas = document.querySelectorAll('.tarjeta-comision-wrapper');
    const contador = document.getElementById('contadorResultados');
    const vacio = document.getElementById('estadoVacio');
    const botonesCamara = document.querySelectorAll('#filtroCamara button');
    
    let camaraActiva = 'todos';

    function filtrar() {
        const query = (input.value || '').trim().toLowerCase();
        let visibles = 0;

        if (query.length > 0) {
            btnLimpiar.classList.remove('d-none');
        } else {
            btnLimpiar.classList.add('d-none');
        }

        tarjetas.forEach(card => {
            const nombre = card.getAttribute('data-nombre') || '';
            const camara = card.getAttribute('data-camara') || '';

            const coincideTexto = (query === '') || nombre.includes(query);
            const coincideCamara = (camaraActiva === 'todos') || (camara.includes(camaraActiva));

            if (coincideTexto && coincideCamara) {
                card.classList.remove('d-none');
                visibles++;
            } else {
                card.classList.add('d-none');
            }
        });

        contador.innerHTML = `Mostrando <strong>${visibles}</strong> de <strong>${tarjetas.length}</strong> comisiones`;

        if (visibles === 0) {
            vacio.classList.remove('d-none');
        } else {
            vacio.classList.add('d-none');
        }
    }

    input.addEventListener('input', filtrar);

    btnLimpiar.addEventListener('click', () => {
        input.value = '';
        filtrar();
        input.focus();
    });

    btnRestablecer.addEventListener('click', () => {
        input.value = '';
        camaraActiva = 'todos';
        botonesCamara.forEach(b => {
            if (b.getAttribute('data-filtro') === 'todos') {
                b.classList.add('btn-primary', 'active');
                b.classList.remove('btn-light', 'text-secondary');
            } else {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-light', 'text-secondary');
            }
        });
        filtrar();
    });

    botonesCamara.forEach(btn => {
        btn.addEventListener('click', () => {
            botonesCamara.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-light', 'text-secondary');
            });
            btn.classList.add('btn-primary', 'active');
            btn.classList.remove('btn-light', 'text-secondary');

            camaraActiva = btn.getAttribute('data-filtro') || 'todos';
            filtrar();
        });
    });
});
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
