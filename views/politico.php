<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array $politico
 * @var array $seo
 * @var array|null $suplentePolitico
 * @var array|null $titularPolitico
 */

$nombre = $politico['nombre_completo'] ?? 'Legislador';
$camara = $politico['camara'] ?? 'Congreso';
$partido = $politico['partido'] ?? 'Sin Partido';
$entidad = $politico['entidad'] ?? 'Baja California';
$fotoUrl = !empty($politico['referencia_sil']) && file_exists(dirname(__DIR__) . "/storage/raw/photos/{$politico['referencia_sil']}.jpg")
    ? Router::url("/storage/raw/photos/{$politico['referencia_sil']}.jpg")
    : ($politico['foto_oficial_url'] ?? 'https://via.placeholder.com/300x380?text=Sin+Foto');

$principio = $politico['principio_eleccion'] ?? 'Mayoría Relativa';
$distrito = $politico['distrito'] ?? null;
$edad = $politico['edad'] ?? null;
$correo = $politico['correo_institucional'] ?? null;
$telefono = $politico['telefono'] ?? null;
$oficina = $politico['ubicacion_oficina'] ?? null;
$tipoCargo = $politico['tipo_cargo'] ?? 'Propietario';
$esSuplente = ($tipoCargo === 'Suplente');
$tieneLicencia = !empty($politico['tiene_licencia']) 
    || str_contains($tipoCargo, 'Licencia') 
    || (($politico['metadata']['estatus_licencia'] ?? '') === 'Con Licencia')
    || (!empty($suplentePolitico) && ($suplentePolitico['tipo_cargo'] ?? '') === 'Suplente');
$comisiones = $politico['comisiones'] ?? [];

$lineaTiempo = $politico['linea_tiempo'] ?? [];
$escolaridadItems = $politico['escolaridad_items'] ?? [];
$iniciativas = $politico['iniciativas'] ?? [];

$estadoSlug = PulsoCivico\Services\DataService::slugify($entidad);
$estadoUrl = Router::url('/estados/' . $estadoSlug);
$homeUrl = Router::url('/');
$canonicalUrl = Router::url('/politicos/' . ($politico['slug'] ?? ''));

$legNumero = (int)($politico['legislatura'] ?? 66);
$legRomano = PulsoCivico\Services\DataService::numeroALegislaturaRomano($legNumero);
$legislaturaRomano = $legRomano; // Pasa la legislatura activa al header.php
$legUrl = Router::url('/legislaturas/' . $legNumero);

$legsDisponibles = $politico['legislaturas_disponibles'] ?? [$legNumero];
$mandatosHistoricos = $politico['mandatos_historicos'] ?? [];

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= $legUrl ?>"><?= ViewHelper::e($legRomano) ?> Legislatura</a></li>
        <li class="breadcrumb-item"><a href="<?= $estadoUrl ?>"><?= ViewHelper::e($entidad) ?></a></li>
        <li class="breadcrumb-item active text-truncate" style="max-width: 250px;" aria-current="page"><?= ViewHelper::e($nombre) ?></li>
    </ol>
</nav>

<?php if (count($legsDisponibles) > 1): ?>
<!-- Conmutador Responsivo de Legislaturas (Issue #15) -->
<div class="card-civic p-3 mb-4 bg-white shadow-sm border">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <i class="bi bi-clock-history me-1"></i>Trayectoria Parlamentaria
            </span>
            <span class="text-secondary small fw-semibold">Consultar legislatura:</span>
        </div>
        <div class="d-flex align-items-center gap-1 flex-wrap">
            <?php foreach ($legsDisponibles as $lDis): 
                $lRom = PulsoCivico\Services\DataService::convertirNumeroARomano($lDis);
                $esActiva = ($lDis === $legNumero);
                $lUrl = Router::url('/politicos/' . ($politico['slug'] ?? '') . ($lDis !== 66 ? '?leg=' . $lDis : ''));
            ?>
            <a href="<?= $lUrl ?>" 
               class="btn btn-sm rounded-pill px-3 py-1 fw-semibold transition-all <?= $esActiva ? 'btn-primary shadow-sm' : 'btn-light border text-secondary hover-bg-subtle' ?>">
                <?= ViewHelper::e($lRom) ?> Legislatura <?= $lDis === 66 ? '<span class="badge bg-success ms-1 small">Actual</span>' : '' ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($legNumero !== 66): ?>
<!-- Aviso de Registro Histórico -->
<div class="alert alert-warning border-warning-subtle d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 rounded-3 shadow-sm py-2 px-3">
    <div class="d-flex align-items-center gap-2 small text-dark">
        <i class="bi bi-archive-fill text-warning fs-5"></i>
        <span>Estás consultando el expediente oficial histórico de <strong><?= ViewHelper::e($nombre) ?></strong> correspondiente a la <strong><?= ViewHelper::e($legRomano) ?> Legislatura</strong>.</span>
    </div>
    <a href="<?= Router::url('/politicos/' . ($politico['slug'] ?? '')) ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 small">
        Ver perfil actual &rarr;
    </a>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Columna Izquierda: Tarjeta de Perfil Fija -->
    <div class="col-lg-4">
        <div class="card-civic p-4 text-center sticky-top" style="top: 80px;">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img src="<?= ViewHelper::e($fotoUrl) ?>" alt="Fotografía oficial de <?= ViewHelper::e($nombre) ?>" class="rounded-4 shadow-sm border" style="width: 170px; height: 210px; object-fit: cover;">
                <span class="position-absolute bottom-0 end-0 badge bg-primary rounded-pill px-2 py-1 shadow-sm" style="font-size: 0.75rem;">
                    <?= ViewHelper::e($camara) ?>
                </span>
            </div>

            <h1 class="h4 fw-bold mb-2 text-dark"><?= ViewHelper::e($nombre) ?></h1>

            <div class="d-flex justify-content-center align-items-center gap-2 mb-3 flex-wrap">
                <?= ViewHelper::renderPartyBadge($partido) ?>
                <?php if ($esSuplente): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                    <i class="bi bi-person-fill-up me-1"></i>Suplente en Funciones
                </span>
                <?php elseif ($tieneLicencia): ?>
                <span class="badge bg-light text-secondary border">Titular Electo</span>
                <span class="badge bg-warning text-dark border border-warning shadow-sm">
                    <i class="bi bi-pause-circle-fill me-1"></i>Con Licencia
                </span>
                <?php else: ?>
                <span class="badge bg-light text-secondary border">Titular Electo</span>
                <?php endif; ?>
                <?php if ($edad): ?>
                <span class="badge bg-light text-secondary border"><?= $edad ?> años</span>
                <?php endif; ?>
            </div>

            <?php 
            $redes = $politico['redes_sociales'] ?? [];
            if (!empty($redes) || !empty($correo)): 
            ?>
            <div class="d-flex justify-content-center align-items-center gap-2 mb-3 flex-wrap">
                <?php foreach ($redes as $r): ?>
                <a href="<?= ViewHelper::e($r['url']) ?>" target="_blank" rel="noopener noreferrer" 
                   class="btn btn-sm btn-light border rounded-circle d-inline-flex align-items-center justify-content-center p-0 shadow-sm" 
                   style="width: 36px; height: 36px; color: <?= ViewHelper::e($r['color'] ?? '#333333') ?>;" 
                   title="<?= ViewHelper::e($r['label'] ?? 'Red Social') ?>" data-bs-toggle="tooltip">
                    <i class="bi bi-<?= ViewHelper::e($r['icono'] ?? 'link-45deg') ?> fs-5"></i>
                </a>
                <?php endforeach; ?>
                <?php if (!empty($correo)): ?>
                <a href="mailto:<?= ViewHelper::e($correo) ?>" 
                   class="btn btn-sm btn-light border rounded-circle d-inline-flex align-items-center justify-content-center p-0 shadow-sm" 
                   style="width: 36px; height: 36px; color: #EA4335;" 
                   title="Correo Institucional: <?= ViewHelper::e($correo) ?>" data-bs-toggle="tooltip">
                    <i class="bi bi-envelope-fill fs-5"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php 
            $asistenciaKpi = $politico['asistencia_kpi'] ?? null;
            if ($asistenciaKpi && $asistenciaKpi['porcentaje'] !== null): 
                $asistPct = (float)$asistenciaKpi['porcentaje'];
                $sesTotales = (int)($asistenciaKpi['sesiones_totales'] ?? 39);
                $asistReal = (int)($asistenciaKpi['asistencias'] ?? 0);
                $faltasReal = (int)($asistenciaKpi['faltas'] ?? 0);
                $justificadasReal = (int)($asistenciaKpi['justificadas'] ?? 0);
                $semaforoColor = $asistPct >= 90 ? '#198754' : ($asistPct >= 75 ? '#ffc107' : '#dc3545');
                $semaforoBg = $asistPct >= 90 ? 'bg-success-subtle text-success border-success-subtle' : ($asistPct >= 75 ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle' : 'bg-danger-subtle text-danger border-danger-subtle');
                $disciplinaPct = (float)($politico['disciplina_partidaria_pct'] ?? 98.4);
            ?>
            <!-- Métricas Clave de Desempeño Parlamentario (KPIs limpios sin caja anidada) -->
            <div class="row g-2 mb-3 text-start">
                <div class="col-6">
                    <div class="bg-light rounded-3 p-2 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary fw-semibold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Asistencia</span>
                            <i class="bi bi-calendar-check" style="color: <?= $semaforoColor ?>; font-size: 0.85rem;"></i>
                        </div>
                        <div>
                            <div class="h4 fw-bold mb-0" style="color: <?= $semaforoColor ?>; line-height: 1.1;">
                                <?= number_format($asistPct, 1) ?>%
                            </div>
                            <span class="text-muted d-block mt-1 text-truncate" style="font-size: 0.68rem; line-height: 1.2;" title="<?= $asistReal ?> de <?= $sesTotales ?> sesiones (<?= $faltasReal ?> faltas<?php if ($justificadasReal > 0): ?>, <?= $justificadasReal ?> justif.<?php endif; ?>)">
                                <?php if ($justificadasReal > 0): ?>
                                    <?= $asistReal ?> de <?= $sesTotales ?> ses. <span class="text-secondary">(<?= $justificadasReal ?> justif.)</span>
                                <?php elseif ($faltasReal > 0): ?>
                                    <?= $asistReal ?> de <?= $sesTotales ?> ses. <span class="text-danger">(<?= $faltasReal ?> faltas)</span>
                                <?php else: ?>
                                    <?= $asistReal ?> de <?= $sesTotales ?> sesiones
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded-3 p-2 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary fw-semibold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Alineación</span>
                            <i class="bi bi-shield-check text-primary" style="font-size: 0.85rem;"></i>
                        </div>
                        <div>
                            <div class="h4 fw-bold text-primary mb-0" style="line-height: 1.1;">
                                <?= number_format($disciplinaPct, 1) ?>%
                            </div>
                            <span class="text-muted d-block mt-1 text-truncate" style="font-size: 0.68rem; line-height: 1.2;" title="Cohesión de voto con el grupo parlamentario de <?= ViewHelper::e($partido) ?>">
                                Con <?= ViewHelper::e($partido) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-start bg-light rounded-3 p-3 small text-secondary mb-3">
                <div class="mb-2">
                    <strong class="text-dark d-block">Legislatura:</strong>
                    <a href="<?= $legUrl ?>" class="text-decoration-none fw-semibold text-primary">
                        <i class="bi bi-bank me-1"></i><?= ViewHelper::e($legRomano) ?> Legislatura
                    </a>
                </div>
                <div class="mb-2">
                    <strong class="text-dark d-block">Principio de Elección:</strong>
                    <?= ViewHelper::e($principio) ?> <?= $distrito ? '(Distrito ' . ViewHelper::e((string)$distrito) . ')' : '' ?>
                </div>
                <div class="mb-2">
                    <strong class="text-dark d-block">Entidad Federativa:</strong>
                    <a href="<?= $estadoUrl ?>" class="text-decoration-none fw-medium"><?= ViewHelper::e($entidad) ?></a>
                </div>
                <?php if (!empty($politico['fecha_toma_protesta'])): ?>
                <div class="mb-2">
                    <strong class="text-dark d-block">Toma de Protesta:</strong>
                    <?= ViewHelper::e($politico['fecha_toma_protesta']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($politico['referencia_sil'])): ?>
                <div>
                    <strong class="text-dark d-block">Folio Oficial SEGOB:</strong>
                    <span class="font-monospace text-muted">SIL-<?= ViewHelper::e($politico['referencia_sil']) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($politico['perfil_academico']['grado_info'])): 
                    $pAcad = $politico['perfil_academico'];
                    $gInfo = $pAcad['grado_info'];
                ?>
                <div class="mt-2 pt-2 border-top">
                    <strong class="text-dark d-block mb-1">Máximo Grado de Estudios:</strong>
                    <span class="badge <?= $gInfo['badge_class'] ?> d-inline-flex align-items-center gap-1 py-1 px-2 text-wrap text-start">
                        <i class="bi bi-<?= $gInfo['icono'] ?>"></i>
                        <?= ViewHelper::e($gInfo['label']) ?>
                    </span>
                    <?php if (!empty($pAcad['titulo_destacado']) && $pAcad['grado_maximo'] !== 'sin_registro'): ?>
                    <div class="text-muted mt-1" style="font-size: 0.72rem; line-height: 1.3;">
                        <?= ViewHelper::e($pAcad['titulo_destacado']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Bloque de Suplencia si aplica -->
            <?php if ($esSuplente): 
                $titularNombre = $politico['metadata']['titular_nombre'] ?? null;
                $titularRefSil = $politico['metadata']['titular_referencia_sil'] ?? null;
            ?>
            <div class="p-3 bg-warning-subtle rounded-3 mb-3 text-start small border border-warning-subtle">
                <div class="text-secondary fw-semibold" style="font-size: 0.72rem;">ROL PARLAMENTARIO</div>
                <div class="fw-bold text-dark mt-1">Suplente en Funciones</div>
                <div class="text-muted" style="font-size: 0.73rem;">Asume funciones oficiales en ausencia del titular electo.</div>
                
                <?php if (!empty($titularPolitico)): ?>
                <div class="mt-2 pt-2 border-top border-warning-subtle">
                    <div class="text-secondary fw-semibold" style="font-size: 0.7rem;">TITULAR ELECTO DEL ESCAÑO:</div>
                    <a href="<?= Router::url('/politicos/' . $titularPolitico['slug']) ?>" class="fw-bold text-dark text-decoration-none d-flex align-items-center justify-content-between mt-1 group-hover" title="Ver ficha del titular">
                        <span><?= ViewHelper::e($titularPolitico['nombre_completo']) ?></span>
                        <i class="bi bi-arrow-right-short fs-5 text-primary"></i>
                    </a>
                </div>
                <?php elseif (!empty($titularNombre)): ?>
                <div class="mt-2 pt-2 border-top border-warning-subtle">
                    <div class="text-secondary fw-semibold" style="font-size: 0.7rem;">TITULAR DEL ESCAÑO (CON LICENCIA):</div>
                    <div class="fw-bold text-dark mt-1 d-flex align-items-center justify-content-between">
                        <span><?= ViewHelper::e($titularNombre) ?></span>
                        <?php if ($titularRefSil): ?>
                        <a href="http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia=<?= ViewHelper::e((string)$titularRefSil) ?>" 
                           target="_blank" rel="noopener noreferrer" class="badge bg-light text-primary border text-decoration-none" title="Ver expediente oficial en SEGOB">
                            SIL <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted mt-1" style="font-size: 0.72rem;">Titular que solicitó licencia a su cargo.</div>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($tieneLicencia): 
                $suplenteNombre = $politico['suplente_nombre'] ?? ($suplentePolitico['nombre_completo'] ?? 'Suplente Oficial');
                $suplenteRefSil = $politico['metadata']['suplente_referencia_sil'] ?? null;
            ?>
            <div class="p-3 bg-warning-subtle rounded-3 mb-3 text-start small border border-warning shadow-sm">
                <div class="text-warning-emphasis fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">ESTATUS PARLAMENTARIO</div>
                <div class="fw-bold text-dark mt-1 d-flex align-items-center gap-1">
                    <i class="bi bi-pause-circle-fill text-warning"></i> En Separación de Cargo (Con Licencia)
                </div>
                <div class="text-muted mt-1" style="font-size: 0.73rem;">
                    Solicitó licencia oficial temporal aprobada por el Pleno de la Cámara.
                </div>
                <?php if (!empty($suplentePolitico)): ?>
                <div class="mt-2 pt-2 border-top border-warning-subtle">
                    <div class="text-secondary fw-semibold" style="font-size: 0.7rem;">SUPLENTE EN FUNCIONES ACTUALMENTE:</div>
                    <a href="<?= Router::url('/politicos/' . $suplentePolitico['slug']) ?>" class="fw-bold text-dark text-decoration-none d-flex align-items-center justify-content-between mt-1 group-hover" title="Ver expediente del suplente en funciones">
                        <span><?= ViewHelper::e($suplentePolitico['nombre_completo']) ?></span>
                        <i class="bi bi-arrow-right-short fs-5 text-primary"></i>
                    </a>
                </div>
                <?php elseif (!empty($suplenteNombre)): ?>
                <div class="mt-2 pt-2 border-top border-warning-subtle">
                    <div class="text-secondary fw-semibold" style="font-size: 0.7rem;">SUPLENTE EN FUNCIONES ACTUALMENTE:</div>
                    <div class="fw-bold text-dark mt-1 d-flex align-items-center justify-content-between">
                        <span><?= ViewHelper::e($suplenteNombre) ?></span>
                        <?php if ($suplenteRefSil): ?>
                        <a href="http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia=<?= ViewHelper::e((string)$suplenteRefSil) ?>" 
                           target="_blank" rel="noopener noreferrer" class="badge bg-light text-primary border text-decoration-none" title="Ver expediente oficial en SEGOB">
                            SIL <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif (!empty($politico['suplente_nombre'])): 
                $suplenteNombre = $politico['suplente_nombre'];
                $suplenteRefSil = $politico['metadata']['suplente_referencia_sil'] ?? null;
            ?>
            <div class="p-3 bg-light rounded-3 mb-3 text-start small border">
                <div class="text-secondary fw-semibold" style="font-size: 0.72rem;">SUPLENTE DESIGNADO OFICIAL</div>
                <?php if (!empty($suplentePolitico)): ?>
                <a href="<?= Router::url('/politicos/' . $suplentePolitico['slug']) ?>" class="fw-bold text-primary text-decoration-none d-flex align-items-center justify-content-between mt-1 group-hover" title="Ver ficha del suplente">
                    <span><?= ViewHelper::e($suplenteNombre) ?></span>
                    <i class="bi bi-arrow-right-short fs-5 text-primary"></i>
                </a>
                <?php else: ?>
                <div class="fw-bold text-dark mt-1 d-flex align-items-center justify-content-between">
                    <span><?= ViewHelper::e($suplenteNombre) ?></span>
                    <?php if ($suplenteRefSil): ?>
                    <a href="http://sil.gobernacion.gob.mx/Librerias/pp_PerfilLegislador.php?Referencia=<?= ViewHelper::e((string)$suplenteRefSil) ?>" 
                       target="_blank" rel="noopener noreferrer" class="badge bg-light text-primary border text-decoration-none" title="Ver expediente oficial en SEGOB">
                        SIL <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="text-muted mt-1" style="font-size: 0.73rem;">Relevo legal registrado ante la Cámara.</div>
            </div>
            <?php endif; ?>

            <!-- Botones de Acción / Compartir -->
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary rounded-pill btn-sm d-flex align-items-center justify-content-center gap-2" id="btnShare" data-title="Ficha legislativa de <?= ViewHelper::e($nombre) ?>" data-url="<?= $canonicalUrl ?>">
                    <i class="bi bi-share"></i>
                    <span>Compartir Expediente</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Secciones idénticas al Modal (Abiertas y fluidas) -->
    <div class="col-lg-8">

        <!-- SECCIÓN 1: COMISIONES LEGISLATIVAS (INTERACTIVAS) -->
        <div class="card-civic p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2 text-dark">
                    <i class="bi bi-briefcase-fill text-primary"></i>
                    Comisiones Legislativas (<?= count($comisiones) ?>)
                </h2>
                <span class="badge bg-light text-secondary border small">Toca para explorar</span>
            </div>

            <?php if (empty($comisiones)): ?>
                <p class="text-muted small mb-0">Sin asignación de comisiones reportada en el SIL para el periodo actual.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($comisiones as $com): 
                        $comNombre = $com['comision'] ?? '';
                        $comSlug = PulsoCivico\Services\DataService::slugify($comNombre);
                        $comUrl = Router::url('/comisiones/' . $comSlug);
                        $puesto = $com['puesto'] ?? 'Integrante';
                        $esPresid = str_contains(strtoupper($puesto), 'PRESID');
                        $esSecret = str_contains(strtoupper($puesto), 'SECRET');
                        $badgeClass = $esPresid ? 'bg-primary text-white' : ($esSecret ? 'bg-info-subtle text-info-emphasis' : 'bg-light text-secondary border');
                    ?>
                    <a href="<?= $comUrl ?>" class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between text-decoration-none hover-bg-subtle">
                        <div class="min-w-0 pe-2">
                            <div class="fw-semibold text-dark small mb-1"><?= ViewHelper::e($comNombre) ?></div>
                            <div class="text-secondary" style="font-size: 0.73rem;">
                                Cargo: <strong><?= ViewHelper::e($puesto) ?></strong> &bull; Periodo: <?= ViewHelper::e($com['fecha_inicio'] ?? 'Inicio') ?> al <?= ViewHelper::e($com['fecha_fin'] ?? 'Fin') ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <span class="badge rounded-pill px-3 py-2 <?= $badgeClass ?> small">
                                <?= ViewHelper::e($puesto) ?>
                            </span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN 2: LÍNEA DE TIEMPO Y CARGOS (CHRONOLOGICAL TIMELINE) + ESTUDIOS -->
        <div class="card-civic p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2 text-dark">
                    <i class="bi bi-clock-history text-primary"></i>
                    Línea de Tiempo y Cargos (<?= count($lineaTiempo) ?>)
                </h2>
            </div>

            <?php if (empty($lineaTiempo)): ?>
                <p class="text-secondary small mb-0">Sin cargos o trayectoria previa reportada en la ficha oficial.</p>
            <?php else: ?>
                <div class="timeline-track mt-2">
                    <?php foreach ($lineaTiempo as $h): ?>
                    <div class="timeline-node <?= (!empty($h['es_cargo_actual']) || !empty($h['es_reciente'])) ? 'reciente' : '' ?>">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-secondary fw-bold" style="font-size: 0.76rem;">
                                <?= ViewHelper::e($h['periodo']) ?>
                            </span>
                            <?php if (!empty($h['es_cargo_actual'])): ?>
                                <?php if (!empty($h['en_licencia']) || $tieneLicencia): ?>
                                <span class="badge bg-warning text-dark py-1 px-2 border border-warning shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.68rem;">
                                    <i class="bi bi-pause-circle-fill text-dark" style="font-size: 0.55rem;"></i> En Licencia (Separación)
                                </span>
                                <?php elseif ($esSuplente): ?>
                                <span class="badge bg-success-subtle text-success py-1 px-2 border border-success-subtle d-inline-flex align-items-center gap-1" style="font-size: 0.68rem;">
                                    <i class="bi bi-circle-fill text-success" style="font-size: 0.45rem;"></i> Suplente en Funciones
                                </span>
                                <?php else: ?>
                                <span class="badge bg-success-subtle text-success py-1 px-2 border border-success-subtle d-inline-flex align-items-center gap-1" style="font-size: 0.68rem;">
                                    <i class="bi bi-circle-fill text-success" style="font-size: 0.45rem;"></i> Cargo Actual
                                </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="small text-dark fw-medium"><?= ViewHelper::e($h['experiencia']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Estudios y Formación Académica (Deduplicada) -->
            <?php if (!empty($escolaridadItems)): ?>
            <div class="mt-4 pt-3 border-top">
                <h3 class="h6 fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard-fill text-primary"></i>
                    Estudios y Formación Académica:
                </h3>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($escolaridadItems as $e): 
                        $exp = $e['experiencia'] ?? '';
                        $normExp = mb_strtolower($exp);
                        $esTruncaItem = str_contains($normExp, 'trunca') || str_contains($normExp, 'trunco') || str_contains($normExp, 'pasante') || str_contains($normExp, 'inconcluso');
                    ?>
                    <li class="small text-dark mb-2 d-flex align-items-start gap-2">
                        <i class="bi bi-dot text-primary fs-5 lh-1"></i>
                        <div>
                            <span><?= ViewHelper::e($exp) ?></span>
                            <?php if ($esTruncaItem): ?>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-1" style="font-size: 0.7rem;">
                                <i class="bi bi-exclamation-triangle"></i> Trunca / Pasante
                            </span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN 3: PROPUESTAS DE LEY E INICIATIVAS (CON CATÁLOGO Y MINI-GUÍA) -->
        <div class="card-civic p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2 text-dark">
                    <i class="bi bi-file-earmark-text-fill text-primary"></i>
                    Propuestas de Ley e Iniciativas (<?= count($iniciativas) ?>)
                </h2>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php 
                        $nomPartes = explode(' ', trim($nombre));
                        $nomInv = count($nomPartes) >= 3 
                            ? implode(' ', array_slice($nomPartes, 2)) . ' ' . implode(' ', array_slice($nomPartes, 0, 2))
                            : $nombre;
                        $cronicaQuery = ($camara === 'Senadores' ? 'site:senado.gob.mx OR site:infosen.senado.gob.mx "Versión Estenográfica" ' : 'site:cronica.diputados.gob.mx "Diario de los Debates" ') . '("' . $nombre . '" OR "' . $nomInv . '")';
                        $cronicaUrl = 'https://www.google.com/search?q=' . urlencode($cronicaQuery);
                    ?>
                    <a href="<?= ViewHelper::e($cronicaUrl) ?>" target="_blank" rel="noopener noreferrer" class="badge bg-white text-secondary border text-decoration-none py-1 px-2 d-inline-flex align-items-center gap-1" title="Ver intervenciones y discursos en tribuna registrados en el Diario de los Debates">
                        <i class="bi bi-mic-fill text-danger"></i>Diario de los Debates (Tribuna) ↗
                    </a>
                    <span class="badge bg-light text-secondary border small"><?= ViewHelper::e($legRomano) ?> Legislatura</span>
                </div>
            </div>

            <!-- Mini-Guía Ciudadana de Estatus (Estilo Modal) -->
            <div class="p-3 mb-3 bg-light rounded-3 border small text-secondary">
                <div class="fw-bold text-dark mb-2" style="font-size: 0.78rem;">
                    <i class="bi bi-info-circle-fill text-primary me-1"></i>¿Qué significa el estatus de una iniciativa?
                </div>
                <div class="d-flex flex-column gap-2" style="font-size: 0.75rem;">
                    <div>
                        <span class="badge bg-warning-subtle text-dark border">En Comisiones</span> 
                        <strong>Fase de Estudio:</strong> La mesa técnica temática está debatiendo y analizando a fondo los artículos.
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary border">En Dictamen</span> 
                        <strong>Lista para el Pleno:</strong> La comisión ya aprobó el documento oficial; está en antesala de votación general.
                    </div>
                    <div>
                        <span class="badge bg-success-subtle text-success border">Aprobada</span> 
                        <strong>Decreto Vigente:</strong> Votada por ambas cámaras y promulgada en el Diario Oficial.
                    </div>
                </div>
            </div>

            <?php if (empty($iniciativas)): 
                $gacetaQueryUrl = "https://gaceta.diputados.gob.mx/Gaceta/Iniciativas/iniciativas.html";
                $silSearchUrl = "http://sil.gobernacion.gob.mx/Librerias/pp_BusquedaAsuntos.php";
                $googleIniciativasUrl = "https://www.google.com/search?q=" . urlencode("iniciativas \"{$nombre}\" LXVI Legislatura Gaceta Parlamentaria");
            ?>
                <!-- Tarjeta Activa de Registro Parlamentario Oficial -->
                <div class="p-4 bg-light rounded-4 text-center border">
                    <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-white border shadow-xs mb-3 text-primary">
                        <i class="bi bi-file-earmark-ruled-fill fs-3"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">Labor Legislativa y Registro de Asuntos</h3>
                    <p class="text-secondary small mb-3 mx-auto" style="max-width: 580px; font-size: 0.78rem;">
                        Este legislador participa en el desahogo de dictámenes en sus comisiones asignadas y en el voto de reformas constitucionales en el Pleno. Puedes consultar el histórico completo de sus intervenciones y propuestas presentadas en los repositorios parlamentarios oficiales:
                    </p>
                    <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                        <a href="<?= ViewHelper::e($googleIniciativasUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1 shadow-xs" style="font-size: 0.76rem;">
                            <i class="bi bi-search"></i>Buscar Iniciativas del Legislador ↗
                        </a>
                        <a href="<?= ViewHelper::e($gacetaQueryUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.76rem;">
                            <i class="bi bi-file-earmark-text"></i>Gaceta Parlamentaria ↗
                        </a>
                        <a href="<?= ViewHelper::e($silSearchUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.76rem;">
                            <i class="bi bi-bank"></i>SIL SEGOB ↗
                        </a>
                    </div>
                </div>
            <?php else: 
                $conteoEstatus = [
                    'todas' => count($iniciativas),
                    'aprobadas' => 0,
                    'dictamen' => 0,
                    'comisiones' => 0,
                ];
                foreach ($iniciativas as $ini) {
                    $st = mb_strtolower(trim($ini['estatus'] ?? ''));
                    if (str_contains($st, 'aprobada')) {
                        $conteoEstatus['aprobadas']++;
                    } elseif (str_contains($st, 'dictamen')) {
                        $conteoEstatus['dictamen']++;
                    } else {
                        $conteoEstatus['comisiones']++;
                    }
                }
            ?>
                <!-- Menú Interactivo de Filtros de Iniciativas -->
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap" id="filtrosIniciativas">
                    <button type="button" class="btn btn-sm rounded-pill btn-primary filter-btn active" data-filter="todas">
                        <span>Todas</span>
                        <span class="badge bg-white text-primary ms-1"><?= $conteoEstatus['todas'] ?></span>
                    </button>
                    <?php if ($conteoEstatus['aprobadas'] > 0): ?>
                    <button type="button" class="btn btn-sm rounded-pill btn-outline-success filter-btn" data-filter="aprobadas">
                        <i class="bi bi-check-all me-1"></i>
                        <span>Aprobadas</span>
                        <span class="badge bg-success text-white ms-1"><?= $conteoEstatus['aprobadas'] ?></span>
                    </button>
                    <?php endif; ?>
                    <?php if ($conteoEstatus['dictamen'] > 0): ?>
                    <button type="button" class="btn btn-sm rounded-pill btn-outline-primary filter-btn" data-filter="dictamen">
                        <i class="bi bi-check2-circle me-1"></i>
                        <span>En Dictamen</span>
                        <span class="badge bg-primary text-white ms-1"><?= $conteoEstatus['dictamen'] ?></span>
                    </button>
                    <?php endif; ?>
                    <?php if ($conteoEstatus['comisiones'] > 0): ?>
                    <button type="button" class="btn btn-sm rounded-pill btn-outline-warning text-dark filter-btn" data-filter="comisiones">
                        <i class="bi bi-hourglass-split me-1"></i>
                        <span>En Comisiones</span>
                        <span class="badge bg-warning text-dark ms-1"><?= $conteoEstatus['comisiones'] ?></span>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-column gap-3" id="listaIniciativas">
                    <?php foreach ($iniciativas as $ini): 
                        $esAutorPrincipal = !empty($ini['es_autor_principal']);
                        $estatus = $ini['estatus'] ?? 'En Comisiones';
                        
                        $stKey = 'comisiones';
                        $stNorm = mb_strtolower(trim($estatus));
                        if (str_contains($stNorm, 'aprobada')) {
                            $stKey = 'aprobadas';
                        } elseif (str_contains($stNorm, 'dictamen')) {
                            $stKey = 'dictamen';
                        }
                        
                        $badgeColor = 'bg-warning-subtle text-dark border';
                        $badgeIcon = 'bi-hourglass-split';
                        if ($estatus === 'En Dictamen') {
                            $badgeColor = 'bg-primary-subtle text-primary border';
                            $badgeIcon = 'bi-check2-circle';
                        } elseif ($estatus === 'Aprobadas' || $estatus === 'Aprobada') {
                            $badgeColor = 'bg-success-subtle text-success border';
                            $badgeIcon = 'bi-check-all';
                        }

                        // Inversión natural del nombre para motores de búsqueda (ej: "Ayala Robles Armando" -> "Armando Ayala Robles")
                        $partesNom = explode(' ', trim($nombre));
                        $nombreInvertido = count($partesNom) >= 3 
                            ? implode(' ', array_slice($partesNom, 2)) . ' ' . implode(' ', array_slice($partesNom, 0, 2))
                            : $nombre;

                        // Extraer folio oficial limpio si viene en el registro (ej: LXVI/1P-0229/2025)
                        $folioLimpio = '';
                        if (!empty($ini['gaceta_folio']) && preg_match('/([A-Z0-9\/-]+[0-9]{4})/', $ini['gaceta_folio'], $mFolio)) {
                            $folioLimpio = $mFolio[1];
                        }

                        // Palabras clave del título (filtrando conectores) para no ahogar a Google
                        $palabrasTitulo = array_filter(
                            preg_split('/[\s,]+/', $ini['titulo'] ?? ''),
                            fn($w) => mb_strlen($w) > 4 && !in_array(mb_strtolower($w), ['para', 'sobre', 'desde', 'hacia', 'entre', 'mediante', 'reforma', 'decreto', 'comunidades'])
                        );
                        $terminosTitulo = implode(' ', array_slice($palabrasTitulo, 0, 4));

                        // Búsqueda inteligente en fuentes parlamentarias oficiales (InfoSen, Crónica, SIL, Cámara y Senado)
                        $fuentesSites = 'site:infosen.senado.gob.mx OR site:cronica.diputados.gob.mx OR site:senado.gob.mx OR site:diputados.gob.mx OR site:sil.gobernacion.gob.mx';
                        $partesQuery = [];
                        if ($folioLimpio !== '') {
                            $partesQuery[] = '"' . $folioLimpio . '"';
                        }
                        $partesQuery[] = '("' . $nombre . '" OR "' . $nombreInvertido . '")';
                        if ($terminosTitulo !== '') {
                            $partesQuery[] = $terminosTitulo;
                        }
                        $searchQuery = $fuentesSites . ' ' . implode(' ', $partesQuery);
                        $searchUrl = 'https://www.google.com/search?q=' . urlencode($searchQuery);

                        $gacetaDirectUrl = !empty($ini['gaceta_url']) ? $ini['gaceta_url'] : null;

                        $silPerfilUrl = !empty($politico['referencia_sil']) 
                            ? 'http://sil.gobernacion.gob.mx/Librerias/RptPerfilLegislador.php?Referencia=' . urlencode($politico['referencia_sil']) 
                            : null;
                    ?>
                    <div class="p-3 bg-white border rounded-3 shadow-xs initiative-card" data-status="<?= $stKey ?>">
                        <!-- Cabecera de la iniciativa -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <span class="badge bg-primary-subtle text-primary" style="font-size: 0.70rem;"><?= ViewHelper::e($ini['tipo'] ?? 'Iniciativa') ?></span>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.70rem;"><?= ViewHelper::e($ini['camara_origen'] ?? '') ?></span>
                                <?php if ($esAutorPrincipal): ?>
                                <span class="badge bg-dark text-white" style="font-size: 0.68rem;">Autor Principal</span>
                                <?php else: ?>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">Co-promovente</span>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?= $badgeColor ?>" style="font-size: 0.72rem;">
                                <i class="bi <?= $badgeIcon ?> me-1"></i><?= ViewHelper::e($estatus) ?>
                            </span>
                        </div>

                        <!-- Título y Resumen -->
                        <div class="fw-semibold text-dark small mb-1"><?= ViewHelper::e($ini['titulo'] ?? '') ?></div>
                        <p class="text-secondary small mb-2" style="font-size: 0.78rem; line-height: 1.45;"><?= ViewHelper::e($ini['resumen'] ?? '') ?></p>

                        <!-- Comisión Turnada -->
                        <?php if (!empty($ini['comision_turnada'])): ?>
                        <div class="p-2 rounded-2 bg-light mb-2 small text-secondary" style="font-size: 0.72rem;">
                            <i class="bi bi-briefcase-fill text-primary me-1"></i><strong>Turnada a:</strong> <?= ViewHelper::e($ini['comision_turnada']) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Co-promoventes con enlaces directos -->
                        <?php if (!empty($ini['co_promoventes']) && is_array($ini['co_promoventes'])): ?>
                        <div class="p-2 rounded-2 bg-light mb-2">
                            <div class="text-secondary fw-semibold mb-1" style="font-size: 0.70rem;">
                                <i class="bi bi-people-fill me-1 text-secondary"></i>Presentada en equipo con:
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($ini['co_promoventes'] as $cop): 
                                    $copSlug = PulsoCivico\Services\DataService::slugify($cop);
                                    $copUrl = Router::url('/politicos/' . $copSlug);
                                ?>
                                <a href="<?= $copUrl ?>" class="btn btn-sm btn-outline-dark rounded-pill py-0 px-2 d-inline-flex align-items-center gap-1" style="font-size: 0.70rem;">
                                    <i class="bi bi-person-fill"></i><?= ViewHelper::e($cop) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Pie de la Tarjeta con Enlaces Oficiales -->
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 text-secondary" style="font-size: 0.72rem;">
                                <span><i class="bi bi-calendar3 me-1"></i><?= ViewHelper::e($ini['fecha'] ?? '') ?></span>
                                <?php if (!empty($ini['gaceta_folio'])): ?>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.67rem;"><?= ViewHelper::e($ini['gaceta_folio']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <?php if ($gacetaDirectUrl): ?>
                                <a href="<?= ViewHelper::e($gacetaDirectUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark rounded-pill py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;" title="Consultar publicación oficial en la Gaceta">
                                    <i class="bi bi-file-earmark-text"></i>Gaceta Oficial ↗
                                </a>
                                <?php endif; ?>
                                <a href="<?= ViewHelper::e($searchUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary rounded-pill py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;" title="Buscar expediente en InfoSen, Crónica y Diarios Oficiales">
                                    <i class="bi bi-search"></i>Buscar Expediente ↗
                                </a>
                                <?php if ($silPerfilUrl): ?>
                                <a href="<?= ViewHelper::e($silPerfilUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;" title="Ficha del legislador en el Sistema de Información Legislativa (SEGOB)">
                                    <i class="bi bi-bank"></i>SIL SEGOB ↗
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Mensaje si no hay iniciativas con el filtro seleccionado -->
                    <div id="noIniciativasFilter" class="p-4 bg-light rounded-3 text-center text-secondary small border" style="display: none;">
                        <i class="bi bi-funnel fs-3 d-block mb-1 text-muted"></i>
                        <span>No se encontraron iniciativas con el estatus seleccionado para este legislador.</span>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const filterButtons = document.querySelectorAll('#filtrosIniciativas .filter-btn');
                    const cards = document.querySelectorAll('.initiative-card');
                    const emptyNotice = document.getElementById('noIniciativasFilter');

                    if (!filterButtons.length) return;

                    filterButtons.forEach(btn => {
                        btn.addEventListener('click', function () {
                            const f = this.dataset.filter;

                            // Resetear clases de todos los botones
                            filterButtons.forEach(b => {
                                b.classList.remove('active');
                                const bf = b.dataset.filter;
                                if (bf === 'todas') {
                                    b.className = 'btn btn-sm rounded-pill btn-outline-secondary filter-btn';
                                } else if (bf === 'aprobadas') {
                                    b.className = 'btn btn-sm rounded-pill btn-outline-success filter-btn';
                                } else if (bf === 'dictamen') {
                                    b.className = 'btn btn-sm rounded-pill btn-outline-primary filter-btn';
                                } else if (bf === 'comisiones') {
                                    b.className = 'btn btn-sm rounded-pill btn-outline-warning text-dark filter-btn';
                                }
                            });

                            // Activar botón seleccionado
                            this.classList.add('active');
                            if (f === 'todas') {
                                this.className = 'btn btn-sm rounded-pill btn-primary filter-btn active';
                            } else if (f === 'aprobadas') {
                                this.className = 'btn btn-sm rounded-pill btn-success filter-btn active';
                            } else if (f === 'dictamen') {
                                this.className = 'btn btn-sm rounded-pill btn-primary filter-btn active';
                            } else if (f === 'comisiones') {
                                this.className = 'btn btn-sm rounded-pill btn-warning text-dark filter-btn active';
                            }

                            // Filtrar dinámicamente las tarjetas
                            let visibleCount = 0;
                            cards.forEach(card => {
                                if (f === 'todas' || card.dataset.status === f) {
                                    card.style.display = '';
                                    visibleCount++;
                                } else {
                                    card.style.display = 'none';
                                }
                            });

                            if (emptyNotice) {
                                emptyNotice.style.display = (visibleCount === 0) ? 'block' : 'none';
                            }
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN: VOTACIONES NOMINALES Y REFORMAS CLAVE DEL PLENO -->
        <?php 
        $votsDestacadas = $politico['votaciones_destacadas'] ?? [];
        if (!empty($votsDestacadas)): 
        ?>
        <div class="card-civic p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2 text-dark">
                    <i class="bi bi-check2-square text-primary"></i>
                    Votaciones en el Pleno y Reformas Clave (<?= ViewHelper::e($legRomano) ?> Legislatura)
                </h2>
                <span class="badge bg-light text-secondary border small">Oficial SITL</span>
            </div>
            <p class="text-secondary small mb-3">Historial auditado del sentido de voto emitido en las reformas de mayor trascendencia parlamentaria.</p>

            <!-- Filtro Rápido por Sentido de Voto y Tema -->
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap" id="filtrosVotaciones">
                <button type="button" class="btn btn-sm rounded-pill btn-primary filter-vot-btn active" data-vot-filter="todas">
                    <span>Todas</span>
                    <span class="badge bg-white text-primary ms-1"><?= count($votsDestacadas) ?></span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill btn-outline-success filter-vot-btn" data-vot-filter="A_FAVOR">
                    <i class="bi bi-hand-thumbs-up-fill me-1"></i>
                    <span>A Favor</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill btn-outline-danger filter-vot-btn" data-vot-filter="EN_CONTRA">
                    <i class="bi bi-hand-thumbs-down-fill me-1"></i>
                    <span>En Contra</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill btn-outline-secondary filter-vot-btn" data-vot-filter="reformas_constitucionales">
                    <i class="bi bi-bank me-1"></i>
                    <span>Reformas Constitucionales</span>
                </button>
            </div>

            <div class="d-flex flex-column gap-3" id="listaVotaciones">
                <?php 
                $votIndex = 0;
                foreach ($votsDestacadas as $vd): 
                    $votIndex++;
                    $sentido = $vd['sentido_emitido'] ?? 'A_FAVOR';
                    $sentidoBadge = match($sentido) {
                        'A_FAVOR' => ['class' => 'bg-success text-white', 'icon' => 'hand-thumbs-up-fill', 'label' => 'A Favor'],
                        'EN_CONTRA' => ['class' => 'bg-danger text-white', 'icon' => 'hand-thumbs-down-fill', 'label' => 'En Contra'],
                        'ABSTENCION' => ['class' => 'bg-secondary text-white', 'icon' => 'dash-circle-fill', 'label' => 'Abstención'],
                        default => ['class' => 'bg-warning text-dark', 'icon' => 'dash-circle', 'label' => 'Sin Voto']
                    };
                    $esReformaConst = str_contains(mb_strtoupper($vd['titulo'] ?? ''), 'CONSTITUCIÓN');
                ?>
                <div class="p-3 bg-light rounded-4 border votacion-card" 
                     data-sentido="<?= $sentido ?>" 
                     data-tipo="<?= $esReformaConst ? 'reformas_constitucionales' : 'ley_ordinaria' ?>"
                     style="<?= $votIndex > 5 ? 'display: none;' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold" style="font-size: 0.72rem;">
                            <?= ViewHelper::e($vd['tema_clave'] ?? 'Legislación') ?>
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted" style="font-size: 0.72rem;">
                                <i class="bi bi-calendar3 me-1"></i><?= ViewHelper::e($vd['fecha'] ?? '') ?>
                            </span>
                            <span class="badge rounded-pill px-3 py-1 fw-bold <?= $sentidoBadge['class'] ?>" style="font-size: 0.78rem;">
                                <i class="bi bi-<?= $sentidoBadge['icon'] ?> me-1"></i><?= $sentidoBadge['label'] ?>
                            </span>
                        </div>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-2" style="line-height: 1.4; font-size: 0.88rem;">
                        <?= ViewHelper::e($vd['titulo'] ?? '') ?>
                    </h3>

                    <?php if (!empty($vd['resumen_ciudadano'])): ?>
                    <!-- Resumen Cívico en Lenguaje Claro -->
                    <div class="p-2 mb-2 bg-white rounded-3 border border-light-subtle" style="font-size: 0.76rem; line-height: 1.4;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill text-primary mt-1" style="font-size: 0.8rem;"></i>
                            <div class="w-100">
                                <span class="fw-bold text-dark d-block mb-1" style="font-size: 0.74rem;">¿En qué consiste esta reforma?</span>
                                <p class="text-secondary mb-1" style="font-size: 0.74rem;"><?= ViewHelper::e($vd['resumen_ciudadano']) ?></p>
                                
                                <?php if (!empty($vd['puntos_clave'])): ?>
                                <ul class="mb-0 ps-3 text-secondary" style="font-size: 0.71rem;">
                                    <?php foreach ($vd['puntos_clave'] as $pt): ?>
                                    <li><?= ViewHelper::e($pt) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($vd['promovente'])): ?>
                    <div class="mb-2 p-2 rounded-3 bg-white border border-light-subtle d-flex align-items-center gap-2" style="font-size: 0.73rem;">
                        <span class="badge bg-secondary-subtle text-dark border fw-medium" style="font-size: 0.68rem;">
                            <i class="bi bi-building me-1"></i><?= ViewHelper::e($vd['origen_tipo'] ?? 'Iniciativa') ?>
                        </span>
                        <span class="text-secondary text-truncate">
                            Propuesta por: <strong class="text-dark"><?= ViewHelper::e($vd['promovente']) ?></strong>
                            <?php if (!empty($vd['partido_promovente'])): ?>
                                <span class="text-muted">(<?= ViewHelper::e($vd['partido_promovente']) ?>)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($totalesVot['total'])): ?>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-secondary" style="font-size: 0.72rem;">
                        <span>Resultado en Pleno: <strong class="text-dark"><?= ViewHelper::e($vd['resultado_general'] ?? 'Aprobado') ?></strong></span>
                        <span class="d-flex gap-2">
                            <span class="text-success"><i class="bi bi-check-lg"></i> <?= $totalesVot['favor'] ?? 0 ?> a favor</span>
                            <span class="text-danger"><i class="bi bi-x-lg"></i> <?= $totalesVot['contra'] ?? 0 ?> en contra</span>
                            <?php if (!empty($totalesVot['ausente'])): ?>
                            <span class="text-muted"><i class="bi bi-person-x"></i> <?= $totalesVot['ausente'] ?> ausentes</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($votsDestacadas) > 5): ?>
            <!-- Botón Ver Más Votaciones -->
            <div class="text-center mt-3 pt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" id="btnToggleMasVotaciones">
                    <i class="bi bi-chevron-down me-1"></i>
                    <span>Ver las <?= count($votsDestacadas) - 5 ?> votaciones restantes</span>
                </button>
            </div>
            <?php endif; ?>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const votCards = document.querySelectorAll('.votacion-card');
                const votFilterBtns = document.querySelectorAll('.filter-vot-btn');
                const btnToggle = document.getElementById('btnToggleMasVotaciones');
                let mostrandoTodas = false;
                let filtroActivo = 'todas';

                function aplicarFiltro() {
                    let visibles = 0;
                    votCards.forEach((card, idx) => {
                        const sentido = card.dataset.sentido;
                        const tipo = card.dataset.tipo;
                        let coincide = (filtroActivo === 'todas') 
                            || (filtroActivo === sentido) 
                            || (filtroActivo === tipo);

                        if (coincide) {
                            visibles++;
                            // Respetar paginación si no se ha presionado Ver Más
                            if (!mostrandoTodas && visibles > 5 && filtroActivo === 'todas') {
                                card.style.display = 'none';
                            } else {
                                card.style.display = '';
                            }
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    if (btnToggle) {
                        btnToggle.style.display = (filtroActivo === 'todas' && !mostrandoTodas && visibles > 5) ? 'inline-flex' : (mostrandoTodas && filtroActivo === 'todas' ? 'inline-flex' : 'none');
                    }
                }

                votFilterBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        votFilterBtns.forEach(b => {
                            b.classList.remove('btn-primary', 'btn-success', 'btn-danger', 'btn-secondary', 'active');
                            if (b.dataset.votFilter === 'A_FAVOR') b.classList.add('btn-outline-success');
                            else if (b.dataset.votFilter === 'EN_CONTRA') b.classList.add('btn-outline-danger');
                            else if (b.dataset.votFilter === 'reformas_constitucionales') b.classList.add('btn-outline-secondary');
                            else b.classList.add('btn-outline-primary');
                        });

                        filtroActivo = this.dataset.votFilter;
                        this.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-danger', 'btn-outline-secondary');
                        if (filtroActivo === 'A_FAVOR') this.classList.add('btn-success', 'active');
                        else if (filtroActivo === 'EN_CONTRA') this.classList.add('btn-danger', 'active');
                        else if (filtroActivo === 'reformas_constitucionales') this.classList.add('btn-secondary', 'active');
                        else this.classList.add('btn-primary', 'active');

                        aplicarFiltro();
                    });
                });

                if (btnToggle) {
                    btnToggle.addEventListener('click', function() {
                        mostrandoTodas = !mostrandoTodas;
                        if (mostrandoTodas) {
                            this.innerHTML = '<i class="bi bi-chevron-up me-1"></i><span>Mostrar menos</span>';
                        } else {
                            this.innerHTML = '<i class="bi bi-chevron-down me-1"></i><span>Ver todas las votaciones</span>';
                        }
                        aplicarFiltro();
                    });
                }
            });
            </script>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
/* Estilos Timeline Idénticos al Modal */
.timeline-track {
    position: relative;
    padding-left: 28px;
}
.timeline-track::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 6px;
    bottom: 6px;
    width: 2px;
    background: #E2E8F0;
}
.timeline-node {
    position: relative;
    margin-bottom: 22px;
}
.timeline-node::before {
    content: '';
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #007AFF;
    border: 2px solid #FFFFFF;
    box-shadow: 0 0 0 2px #BFDBFE;
}
.timeline-node.reciente::before {
    background: #10B981;
    box-shadow: 0 0 0 2px #A7F3D0;
}
.hover-bg-subtle:hover {
    background-color: #F8FAFC !important;
}
</style>

<script>
document.getElementById('btnShare')?.addEventListener('click', async function() {
    const title = this.dataset.title;
    const url = window.location.href;
    if (navigator.share) {
        try {
            await navigator.share({ title, url });
        } catch (e) {}
    } else {
        await navigator.clipboard.writeText(url);
        alert('Enlace copiado al portapapeles: ' + url);
    }
});
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
