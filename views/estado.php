<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $estado
 * @var array $seo
 */

$nombre = $estado['nombre'] ?? 'Estado';
$abbr = $estado['abbr'] ?? 'MEX';
$totalRep = $estado['total_representantes'] ?? 0;
$partidoMayor = $estado['partido_mayoria'] ?? 'Sin Mayoría';
$conteoPartidos = $estado['conteo_partidos'] ?? [];
$diputados = $estado['diputados'] ?? [];
$senadores = $estado['senadores'] ?? [];
$todos = array_merge($diputados, $senadores);

$homeUrl = Router::url('/');
$canonicalUrl = Router::url('/estados/' . ($estado['slug'] ?? ''));
$escolaridadStats = $estado['escolaridad_stats'] ?? [];
$posgrados = $escolaridadStats['resumen_posgrados'] ?? ['total' => 0, 'porcentaje' => 0];
$licenciaturas = $escolaridadStats['resumen_licenciaturas'] ?? ['total' => 0, 'porcentaje' => 0];
$mediaTrunca = $escolaridadStats['resumen_media_trunca'] ?? ['total' => 0, 'porcentaje' => 0];
$niveles = $escolaridadStats['niveles'] ?? [];
$doctoresCount = $niveles['doctorado']['cantidad'] ?? 0;
$maestriasCount = $niveles['maestria']['cantidad'] ?? 0;

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Estados</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= ViewHelper::e($nombre) ?></li>
    </ol>
</nav>

<!-- Hero de la Entidad Federativa -->
<div class="card-civic p-4 p-md-5 mb-4 position-relative overflow-hidden">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-primary rounded-pill"><?= ViewHelper::e($abbr) ?></span>
                <span class="badge bg-light text-secondary border"><?= ViewHelper::e($romano ?? 'LXVI') ?> Legislatura</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalLegislaturas">
                    <i class="bi bi-clock-history me-1"></i>Cambiar legislatura
                </button>
            </div>
            <h1 class="display-6 fw-bold text-dark mb-2">
                Radiografía Legislativa de <?= ViewHelper::e($nombre) ?>
            </h1>
            <p class="text-secondary mb-3">
                Conoce a los <?= $totalRep ?> representantes de la entidad en el H. Congreso de la Unión durante la <?= ViewHelper::e($romano ?? 'LXVI') ?> Legislatura: sus bancadas, comisiones, grado escolar e iniciativas.
            </p>

            <!-- Resumen de Mayoría y Curules -->
            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <span class="small text-muted me-1">Fuerza dominante:</span>
                <?= ViewHelper::renderPartyBadge($partidoMayor) ?>
                <span class="badge bg-light text-secondary border"><?= count($diputados) ?> Diputados</span>
                <span class="badge bg-light text-secondary border"><?= count($senadores) ?> Senadores</span>
            </div>

            <!-- Barra de Distribución Porcentual Partidista -->
            <?php if (!empty($conteoPartidos) && $totalRep > 0): ?>
            <div class="progress" style="height: 10px; border-radius: 8px;">
                <?php foreach ($conteoPartidos as $pNombre => $pCount): 
                    $pct = round(($pCount / $totalRep) * 100, 1);
                    $color = ViewHelper::getPartyColor($pNombre);
                ?>
                <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%; background-color: <?= $color ?>;" title="<?= ViewHelper::e($pNombre) ?>: <?= $pCount ?> (<?= $pct ?>%)"></div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex flex-wrap gap-3 mt-2 small text-muted mb-3">
                <?php foreach ($conteoPartidos as $pNombre => $pCount): 
                    $color = ViewHelper::getPartyColor($pNombre);
                ?>
                <span class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: <?= $color ?>;"></span>
                    <?= ViewHelper::e($pNombre) ?>: <strong><?= $pCount ?></strong>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Columna Lateral: Tarjeta de Legisladores Totales y Mini Radiografía Educativa del Estado -->
        <div class="col-lg-5 mt-4 mt-lg-0">
            <div class="p-3 p-md-4 bg-light rounded-4 border">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small fw-bold text-dark d-flex align-items-center gap-1">
                        <i class="bi bi-mortarboard-fill text-primary"></i> Perfil Académico del Estado
                    </span>
                    <a href="<?= Router::url('/estudios') ?>" class="small text-decoration-none text-primary fw-medium" title="Ver radiografía educativa nacional">
                        Nacional &rarr;
                    </a>
                </div>

                <div class="row g-2 text-center mb-3">
                    <!-- Posgrados / Doctores -->
                    <div class="col-4">
                        <div class="p-2 bg-white rounded-3 border h-100 shadow-2xs">
                            <div class="h5 fw-bold text-primary mb-0"><?= $posgrados['porcentaje'] ?>%</div>
                            <div class="text-secondary fw-semibold" style="font-size: 0.68rem;">Posgrados</div>
                            <div class="text-muted" style="font-size: 0.66rem;">
                                <?= $doctoresCount ?> Dr. &bull; <?= $maestriasCount ?> Mtr.
                            </div>
                        </div>
                    </div>
                    <!-- Licenciaturas -->
                    <div class="col-4">
                        <div class="p-2 bg-white rounded-3 border h-100 shadow-2xs">
                            <div class="h5 fw-bold text-success mb-0"><?= $licenciaturas['porcentaje'] ?>%</div>
                            <div class="text-secondary fw-semibold" style="font-size: 0.68rem;">Licenciaturas</div>
                            <div class="text-muted" style="font-size: 0.66rem;">
                                <?= $licenciaturas['total'] ?> titulados
                            </div>
                        </div>
                    </div>
                    <!-- Media / Trunca -->
                    <div class="col-4">
                        <div class="p-2 bg-white rounded-3 border h-100 shadow-2xs">
                            <div class="h5 fw-bold text-warning-emphasis mb-0"><?= $mediaTrunca['porcentaje'] ?>%</div>
                            <div class="text-secondary fw-semibold" style="font-size: 0.68rem;">Media / Trunca</div>
                            <div class="text-muted" style="font-size: 0.66rem;">
                                <?= $mediaTrunca['total'] ?> legisladores
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-2 border-top small text-secondary" style="font-size: 0.74rem;">
                    <span><i class="bi bi-people-fill text-primary me-1"></i><strong><?= $totalRep ?></strong> Legisladores auditados</span>
                    <span class="badge bg-white text-secondary border">Fuente: SIL SEGOB</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Cámara y Nivel Escolar -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <ul class="nav nav-pills" id="filtroCamara" role="tablist">
            <li class="nav-item">
                <button class="nav-link active rounded-pill px-3 px-md-4 btn-sm" id="tab-todos" data-bs-toggle="pill" data-bs-target="#panel-todos" type="button">
                    Todos (<?= count($todos) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-3 px-md-4 btn-sm" id="tab-diputados" data-bs-toggle="pill" data-bs-target="#panel-diputados" type="button">
                    Diputados Federales (<?= count($diputados) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-3 px-md-4 btn-sm" id="tab-senadores" data-bs-toggle="pill" data-bs-target="#panel-senadores" type="button">
                    Senadores (<?= count($senadores) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="text-muted small">
        Toca cualquier tarjeta para consultar el expediente completo con sus iniciativas.
    </div>
</div>

<!-- Contenido de Pestañas con Cards Amplias y Elegantes -->
<div class="tab-content mb-5" id="filtroCamaraContent">
    <!-- Tab Todos -->
    <div class="tab-pane fade show active" id="panel-todos" role="tabpanel">
        <div class="row g-3 g-md-4">
            <?php foreach ($todos as $p): ?>
                <?php renderPoliticoCardFull($p, $numLeg); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tab Diputados -->
    <div class="tab-pane fade" id="panel-diputados" role="tabpanel">
        <div class="row g-3 g-md-4">
            <?php foreach ($diputados as $p): ?>
                <?php renderPoliticoCardFull($p, $numLeg); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tab Senadores -->
    <div class="tab-pane fade" id="panel-senadores" role="tabpanel">
        <div class="row g-3 g-md-4">
            <?php foreach ($senadores as $p): ?>
                <?php renderPoliticoCardFull($p, $numLeg); ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
/**
 * Helper para renderizar cada Card de Político con proporciones perfectas para que todos quepan sin recortarse.
 */
function renderPoliticoCardFull(array $p, int $numLeg = 66): void {
    $nombre = $p['nombre_completo'] ?? '';
    $slug = $p['slug'] ?? '';
    $url = Router::url('/politicos/' . $slug . ($numLeg !== 66 ? '?leg=' . $numLeg : ''));
    
    $foto = !empty($p['referencia_sil']) && file_exists(dirname(__DIR__) . "/storage/raw/photos/{$p['referencia_sil']}.jpg")
        ? Router::url("/storage/raw/photos/{$p['referencia_sil']}.jpg")
        : ($p['foto_oficial_url'] ?? 'https://via.placeholder.com/150');

    $camara = $p['camara'] ?? '';
    $partido = $p['partido'] ?? '';
    $principio = $p['principio_eleccion'] ?? 'Plurinominal';
    $distrito = $p['distrito'] ?? null;
    $edad = $p['edad'] ?? null;
    $esSuplente = (($p['tipo_cargo'] ?? '') === 'Suplente');
    
    $totalComisiones = $p['total_comisiones'] ?? count($p['comisiones'] ?? []);
    $totalTrayectoria = count($p['linea_tiempo'] ?? ($p['trayectoria_politica'] ?? []));
    $totalIniciativas = count($p['iniciativas'] ?? []);

    // Perfil académico del político
    $perfilAcademico = $p['perfil_academico'] ?? null;
    $gradoInfo = $perfilAcademico['grado_info'] ?? null;
    $tituloDestacado = $perfilAcademico['titulo_destacado'] ?? null;
    $almaMater = $perfilAcademico['institucion_resumen']['alma_mater_destacada'] ?? null;
    ?>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
        <a href="<?= $url ?>" class="text-decoration-none text-dark d-block h-100">
            <div class="card-apple p-3 p-md-4 h-100 d-flex flex-column">
                <!-- Barra Superior de la Card: Estado y Partido Político -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="chip-neutral">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= ViewHelper::e($p['entidad'] ?? 'Baja California') ?>
                    </span>
                    <?= ViewHelper::renderPartyBadge($partido) ?>
                </div>

                <!-- Avatar y Nombre Centrados -->
                <div class="text-center my-1 flex-grow-1">
                    <div class="avatar-container mb-2 position-relative d-inline-block">
                        <img src="<?= ViewHelper::e($foto) ?>" alt="<?= ViewHelper::e($nombre) ?>" class="avatar-img" loading="lazy">
                    </div>

                    <h2 class="h6 fw-bold mb-1 text-dark name-clamp" title="<?= ViewHelper::e($nombre) ?>">
                        <?= ViewHelper::e($nombre) ?>
                    </h2>

                    <div class="text-secondary small mb-1">
                        <?= ViewHelper::e($camara) ?> &bull; <?= $esSuplente ? 'Suplente' : 'Titular Electo' ?>
                    </div>

                    <div class="text-secondary small mb-2" style="font-size: 0.74rem;">
                        <?= ViewHelper::e($principio) ?> <?= $distrito ? '(Distrito ' . ViewHelper::e((string)$distrito) . ')' : '' ?>
                    </div>

                    <!-- Chips de Resumen: Rol, Edad y Asistencia -->
                    <div class="d-flex justify-content-center align-items-center gap-1 flex-wrap mb-2">
                        <?php if ($esSuplente): ?>
                            <span class="chip-suplente"><i class="bi bi-arrow-repeat me-1"></i>Suplente</span>
                        <?php else: ?>
                            <span class="chip-titular"><i class="bi bi-check-circle me-1"></i>Titular</span>
                        <?php endif; ?>

                        <?php if ($edad !== null): ?>
                            <span class="chip-age"><i class="bi bi-calendar3 me-1"></i><?= $edad ?> años</span>
                        <?php endif; ?>

                        <?php 
                        $asistCard = $p['asistencia_kpi'] ?? null;
                        if ($asistCard && ($asistCard['porcentaje'] !== null)): 
                            $pctCard = (float)$asistCard['porcentaje'];
                            $semBadge = $pctCard >= 90 ? 'bg-success-subtle text-success border-success-subtle' : ($pctCard >= 75 ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle' : 'bg-danger-subtle text-danger border-danger-subtle');
                            $asistRealCard = (int)($asistCard['asistencias'] ?? 0);
                            $faltasRealCard = (int)($asistCard['faltas'] ?? 0);
                            $sesTotalesCard = (int)($asistCard['sesiones_totales'] ?? 0);
                        ?>
                            <span class="badge border <?= $semBadge ?> py-1 px-2" style="font-size: 0.70rem;" title="<?= number_format($pctCard, 1) ?>% asistencia (<?= $asistRealCard ?> asist., <?= $faltasRealCard ?> faltas de <?= $sesTotalesCard ?> sesiones)">
                                <i class="bi bi-calendar-check me-1"></i><?= number_format($pctCard, 1) ?>% Asist.
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Insignia de Escolaridad y Grado Académico -->
                    <?php if ($gradoInfo): ?>
                    <div class="mt-2">
                        <span class="badge <?= $gradoInfo['badge_class'] ?? 'bg-light text-secondary border' ?> py-1 px-2 text-truncate d-inline-block mw-100" style="font-size: 0.70rem;" title="<?= ViewHelper::e($tituloDestacado ?? $gradoInfo['label']) ?>">
                            <i class="bi bi-<?= $gradoInfo['icono'] ?? 'mortarboard' ?> me-1"></i><?= ViewHelper::e($gradoInfo['label']) ?>
                        </span>
                        <?php if ($almaMater && $almaMater !== 'No especificada en ficha'): ?>
                        <div class="text-muted text-truncate mt-1" style="font-size: 0.68rem;" title="<?= ViewHelper::e($almaMater) ?>">
                            <i class="bi bi-bank me-1"></i><?= ViewHelper::e($almaMater) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Métricas al Pie de la Card -->
                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center small text-secondary" style="font-size: 0.74rem;">
                    <span><i class="bi bi-briefcase me-1 text-primary"></i><?= $totalComisiones ?> comisiones</span>
                    <?php if ($totalIniciativas > 0): ?>
                    <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-file-earmark-text me-1"></i><?= $totalIniciativas ?> inic.</span>
                    <?php else: ?>
                    <span><i class="bi bi-clock-history me-1 text-muted"></i><?= $totalTrayectoria ?> cargos</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>
    <?php
}
?>

<style>
.card-apple {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-height: 290px;
}
.card-apple:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}
.avatar-img {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #FFFFFF;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.name-clamp {
    font-size: 0.92rem;
    line-height: 1.25;
    min-height: 2.3rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.chip-neutral {
    background: #F2F2F7;
    color: #1C1C1E;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 8px;
}
.chip-titular {
    background: #E8F5E9;
    color: #2E7D32;
    font-size: 0.70rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
}
.chip-suplente {
    background: #FFF3E0;
    color: #E65100;
    font-size: 0.70rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
}
.chip-age {
    background: #F3E8FF;
    color: #6B21A8;
    font-size: 0.70rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
}
</style>

<?php require __DIR__ . '/layout/footer.php'; ?>
