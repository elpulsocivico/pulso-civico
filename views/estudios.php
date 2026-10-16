<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;
use PulsoCivico\Services\DataService;

/**
 * @var array $politicosConEstudios Colección de políticos con perfil académico clasificado
 * @var array $escolaridadStats Métricas globales de escolaridad
 * @var array $estadosCatalog Catálogo y estadísticas por estado
 * @var string $categoriaActiva Categoría inicial preseleccionada ('todos' | 'posgrados' | 'licenciatura' | 'media-trunca')
 * @var array $seo Metadatos SEO
 */

$totalAuditados = $escolaridadStats['total_legisladores'] ?? count($politicosConEstudios);
$posgrados = $escolaridadStats['resumen_posgrados'] ?? ['total' => 0, 'porcentaje' => 0];
$licenciaturas = $escolaridadStats['resumen_licenciaturas'] ?? ['total' => 0, 'porcentaje' => 0];
$mediaTrunca = $escolaridadStats['resumen_media_trunca'] ?? ['total' => 0, 'porcentaje' => 0];

$homeUrl = Router::url('/');
$estudiosBaseUrl = Router::url('/estudios');

// Catálogo ordenado de los 32 estados
$estadosOpciones = [];
if (!empty($estadosCatalog) && is_array($estadosCatalog)) {
    foreach ($estadosCatalog as $slug => $est) {
        if ($slug === 'mexico') {
            continue; // Evitar duplicado del alias
        }
        $estadosOpciones[$slug] = [
            'nombre' => $est['nombre'] ?? ucfirst($slug),
            'slug' => $slug,
            'total' => $est['total_representantes'] ?? 0,
            'escolaridad' => $est['escolaridad'] ?? [],
            'partido_mayoria' => $est['partido_mayoria'] ?? '',
        ];
    }
    uasort($estadosOpciones, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
}

// Conteo de partidos y disciplinas para los filtros
$partidosConteo = [];
$disciplinasConteo = [];
foreach ($politicosConEstudios as $p) {
    $canParty = ViewHelper::getCanonicalPartyName($p['partido'] ?? 'Sin Partido');
    $partidosConteo[$canParty] = ($partidosConteo[$canParty] ?? 0) + 1;

    $disc = $p['analisis_academico']['disciplina_principal'] ?? 'General / Diversa';
    $disciplinasConteo[$disc] = ($disciplinasConteo[$disc] ?? 0) + 1;
}
arsort($partidosConteo);
arsort($disciplinasConteo);

require __DIR__ . '/layout/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="breadcrumb-ios">
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= $homeUrl ?>#seccion-educacion">Radiografía Educativa</a></li>
        <li class="breadcrumb-item active" aria-current="page">Directorio por Nivel de Estudios</li>
    </ol>
</nav>

<!-- Hero de Auditoría Académica -->
<div class="card-civic p-4 p-md-5 mb-4 position-relative overflow-hidden">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary-subtle text-primary small fw-semibold mb-3">
                <i class="bi bi-mortarboard-fill"></i> Observatorio de Trayectorias y Formación
            </div>
            <h1 class="display-6 fw-bold text-dark mb-2">
                Nivel Académico de los Legisladores de México
            </h1>
            <p class="text-secondary mb-3" style="max-width: 680px;">
                ¿Qué preparación tienen quienes representan a tu estado y toman decisiones en el Congreso? Explora títulos, universidades y áreas de especialidad de los <strong><?= (int)$totalAuditados ?></strong> representantes federales.
            </p>

            <!-- Nota ciudadana / pedagógica -->
            <div class="p-3 bg-light rounded-3 border d-flex align-items-start gap-2 mb-2" style="font-size: 0.8rem; line-height: 1.45;">
                <i class="bi bi-info-circle-fill text-primary flex-shrink-0 mt-1"></i>
                <div class="text-secondary">
                    <strong class="text-dark">Principio de Representación Popular:</strong> La Constitución no exige título universitario para ser legislador, garantizando que cualquier persona pueda ser votada. En este observatorio auditamos la formación declarada ante fuentes oficiales con fines de transparencia cívica.
                </div>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <!-- Resumen Nacional de Distribución -->
            <div class="p-3 bg-white rounded-3 border shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        Promedio Nacional LXVI
                    </span>
                    <span class="badge bg-light text-dark border small"><?= (int)$totalAuditados ?> auditados</span>
                </div>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 bg-primary-subtle rounded-3 h-100">
                            <div class="h5 fw-bold text-primary mb-0"><?= $posgrados['porcentaje'] ?>%</div>
                            <div class="text-muted" style="font-size: 0.68rem;">Posgrado</div>
                            <span class="badge bg-primary text-white rounded-pill mt-1" style="font-size: 0.65rem;"><?= $posgrados['total'] ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-success-subtle rounded-3 h-100">
                            <div class="h5 fw-bold text-success mb-0"><?= $licenciaturas['porcentaje'] ?>%</div>
                            <div class="text-muted" style="font-size: 0.68rem;">Licenciatura</div>
                            <span class="badge bg-success text-white rounded-pill mt-1" style="font-size: 0.65rem;"><?= $licenciaturas['total'] ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-warning-subtle rounded-3 h-100">
                            <div class="h5 fw-bold text-warning-emphasis mb-0"><?= $mediaTrunca['porcentaje'] ?>%</div>
                            <div class="text-muted" style="font-size: 0.68rem;">Trunca / Media</div>
                            <span class="badge bg-dark-subtle text-dark rounded-pill mt-1" style="font-size: 0.65rem;"><?= $mediaTrunca['total'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Termómetro / Radiografía Dinámica del Estado Seleccionado -->
<div id="panelTermometroEstado" class="card-civic p-3 p-md-4 mb-4 border bg-gradient-light shadow-sm" style="transition: all 0.3s ease;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
        <div>
            <span class="badge bg-primary-subtle text-primary border small mb-1">
                <i class="bi bi-geo-alt-fill me-1"></i><span id="termometroBadgeTipo">Vista Nacional</span>
            </span>
            <h2 class="h5 fw-bold text-dark mb-0" id="termometroTitulo">
                Radiografía General de México
            </h2>
            <div class="text-secondary small" id="termometroSubtitulo">
                Promedio acumulado de las 32 entidades federativas. Selecciona un estado abajo para ver el detalle de tu tierra.
            </div>
        </div>
        <div id="termometroEnlaceEstado" style="display: none;">
            <a href="#" id="linkFichaEstado" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                Ver expediente del estado &rarr;
            </a>
        </div>
    </div>

    <!-- Barra de distribución visual apilada -->
    <div class="mb-3">
        <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 0.75rem;">
            <span>Distribución de estudios:</span>
            <span id="termometroLeyendaValores">
                Posgrados: <?= $posgrados['porcentaje'] ?>% | Licenciaturas: <?= $licenciaturas['porcentaje'] ?>% | Trunca/Media: <?= $mediaTrunca['porcentaje'] ?>%
            </span>
        </div>
        <div class="progress" style="height: 12px; border-radius: 6px; background-color: #e2e8f0;">
            <div id="barraPosgrado" class="progress-bar bg-primary" role="progressbar" style="width: <?= $posgrados['porcentaje'] ?>%;" title="Posgrado"></div>
            <div id="barraLicenciatura" class="progress-bar bg-success" role="progressbar" style="width: <?= $licenciaturas['porcentaje'] ?>%;" title="Licenciatura"></div>
            <div id="barraMediaTrunca" class="progress-bar bg-warning" role="progressbar" style="width: <?= $mediaTrunca['porcentaje'] ?>%;" title="Trunca / Media Superior"></div>
        </div>
    </div>

    <!-- 3 Mini Bloques Informativos -->
    <div class="row g-2 text-center" id="termometroMetricas">
        <div class="col-4">
            <div class="p-2 rounded-3 bg-white border">
                <div class="text-muted small" style="font-size: 0.7rem;">Con Posgrado</div>
                <div class="fw-bold text-primary" id="termometroValPosgrado"><?= $posgrados['total'] ?> (<?= $posgrados['porcentaje'] ?>%)</div>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 rounded-3 bg-white border">
                <div class="text-muted small" style="font-size: 0.7rem;">Con Licenciatura</div>
                <div class="fw-bold text-success" id="termometroValLicenciatura"><?= $licenciaturas['total'] ?> (<?= $licenciaturas['porcentaje'] ?>%)</div>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 rounded-3 bg-white border">
                <div class="text-muted small" style="font-size: 0.7rem;">Media / Trunca</div>
                <div class="fw-bold text-warning-emphasis" id="termometroValMediaTrunca"><?= $mediaTrunca['total'] ?> (<?= $mediaTrunca['porcentaje'] ?>%)</div>
            </div>
        </div>
    </div>
</div>

<!-- Centro de Control y Filtros Dinámicos -->
<div class="card-civic p-3 p-md-4 mb-4 border">
    <!-- Fila 1: Pestañas de Nivel Académico (Mobile Scrollable) -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
        <div class="d-flex flex-wrap gap-2" id="selectorCategorias" role="group" aria-label="Filtro de nivel de estudios">
            <button type="button" 
                    class="btn btn-sm rounded-pill px-3 fw-semibold <?= $categoriaActiva === 'todos' ? 'btn-primary' : 'btn-light border text-dark' ?>" 
                    data-categoria="todos">
                Todos <span class="badge bg-dark-subtle text-dark ms-1"><?= count($politicosConEstudios) ?></span>
            </button>
            <button type="button" 
                    class="btn btn-sm rounded-pill px-3 fw-semibold <?= $categoriaActiva === 'posgrados' ? 'btn-primary' : 'btn-light border text-dark' ?>" 
                    data-categoria="posgrados">
                <i class="bi bi-mortarboard me-1"></i> Posgrados <span class="badge bg-primary-subtle text-primary ms-1"><?= $posgrados['total'] ?></span>
            </button>
            <button type="button" 
                    class="btn btn-sm rounded-pill px-3 fw-semibold <?= $categoriaActiva === 'licenciatura' ? 'btn-primary' : 'btn-light border text-dark' ?>" 
                    data-categoria="licenciatura">
                <i class="bi bi-award me-1"></i> Licenciaturas <span class="badge bg-success-subtle text-success ms-1"><?= $licenciaturas['total'] ?></span>
            </button>
            <button type="button" 
                    class="btn btn-sm rounded-pill px-3 fw-semibold <?= $categoriaActiva === 'media-trunca' ? 'btn-primary' : 'btn-light border text-dark' ?>" 
                    data-categoria="media-trunca">
                <i class="bi bi-layers-half me-1"></i> Media Superior / Trunca <span class="badge bg-warning-subtle text-dark ms-1"><?= $mediaTrunca['total'] ?></span>
            </button>
        </div>

        <button type="button" id="btnLimpiarTodo" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
        </button>
    </div>

    <!-- Fila 2: Buscador en Tiempo Real -->
    <div class="mb-3">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" 
                   id="buscadorEnVivo" 
                   class="form-control border-start-0 border-end-0" 
                   placeholder="Buscar por nombre, estado, municipio cabecera, carrera (ej. médico, abogado), universidad..." 
                   autocomplete="off">
            <button class="btn btn-outline-secondary border-start-0 bg-white" type="button" id="btnLimpiarTexto" title="Borrar búsqueda" style="display: none;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Fila 3: Filtros Dropdown (Estado, Disciplina, Alma Máter, Cámara) -->
    <div class="row g-2 mb-3">
        <!-- Filtro por Estado / Entidad -->
        <div class="col-12 col-sm-6 col-lg-3">
            <label for="filtroEstado" class="form-label small fw-semibold text-secondary mb-1">
                <i class="bi bi-geo-alt me-1"></i> Estado / Entidad
            </label>
            <select id="filtroEstado" class="form-select form-select-sm">
                <option value="todos">🇲🇽 Todos los estados (32)</option>
                <?php foreach ($estadosOpciones as $slug => $est): ?>
                <option value="<?= ViewHelper::e($slug) ?>">
                    <?= ViewHelper::e($est['nombre']) ?> (<?= (int)$est['total'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro por Área / Disciplina -->
        <div class="col-12 col-sm-6 col-lg-3">
            <label for="filtroDisciplina" class="form-label small fw-semibold text-secondary mb-1">
                <i class="bi bi-compass me-1"></i> Área de Conocimiento
            </label>
            <select id="filtroDisciplina" class="form-select form-select-sm">
                <option value="todas">Todas las áreas (<?= count($politicosConEstudios) ?>)</option>
                <?php foreach ($disciplinasConteo as $disc => $c): ?>
                <option value="<?= ViewHelper::e($disc) ?>">
                    <?= ViewHelper::e($disc) ?> (<?= (int)$c ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro por Alma Máter (Pública vs Privada) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <label for="filtroInstitucion" class="form-label small fw-semibold text-secondary mb-1">
                <i class="bi bi-bank me-1"></i> Tipo de Alma Máter
            </label>
            <select id="filtroInstitucion" class="form-select form-select-sm">
                <option value="todas">Todas las universidades</option>
                <option value="publica">🏛️ Univ. Pública (UNAM, IPN, Estatales...)</option>
                <option value="privada">🎓 Univ. Privada (Tec, ITAM, Ibero...)</option>
                <option value="mixta">⚖️ Formación Mixta</option>
            </select>
        </div>

        <!-- Filtro por Cámara -->
        <div class="col-12 col-sm-6 col-lg-3">
            <label for="filtroCamara" class="form-label small fw-semibold text-secondary mb-1">
                <i class="bi bi-building me-1"></i> Cámara Legislativa
            </label>
            <select id="filtroCamara" class="form-select form-select-sm">
                <option value="todas">Ambas Cámaras (Diputados y Senadores)</option>
                <option value="Diputados">Cámara de Diputados (500)</option>
                <option value="Senadores">Cámara de Senadores (128)</option>
            </select>
        </div>
    </div>

    <!-- Fila 4: Píldoras Rápidas por Partido Político -->
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="small fw-semibold text-secondary" style="font-size: 0.78rem;">
                <i class="bi bi-flag me-1"></i> Filtrar por Partido:
            </span>
        </div>
        <div class="d-flex flex-wrap gap-2" id="selectorPartidos">
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-dark active" data-partido="todos">
                Todos
            </button>
            <?php 
            $partidosDestacados = ['Morena', 'PAN', 'PRI', 'PVEM', 'PT', 'Movimiento Ciudadano'];
            foreach ($partidosDestacados as $partyName): 
                $countP = $partidosConteo[$partyName] ?? 0;
                if ($countP === 0) continue;
            ?>
            <button type="button" 
                    class="btn btn-sm rounded-pill px-3 fw-semibold btn-light border text-dark d-inline-flex align-items-center gap-1 party-filter-btn" 
                    data-partido="<?= ViewHelper::e($partyName) ?>">
                <?= ViewHelper::getPartyMicroIcon($partyName) ?>
                <span><?= ViewHelper::e($partyName) ?></span>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.65rem;"><?= $countP ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Barra de Conteo y Chips de Filtros Activos -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3 px-1 small text-muted">
    <div class="d-flex align-items-center flex-wrap gap-2">
        <span>Mostrando <strong class="text-dark" id="conteoVisible"><?= count($politicosConEstudios) ?></strong> de <?= count($politicosConEstudios) ?> representantes</span>
        <div id="contenedorChipsActivos" class="d-flex flex-wrap gap-1"></div>
    </div>
    <div>
        <a href="<?= $homeUrl ?>#seccion-educacion" class="text-decoration-none small text-secondary">
            &larr; Volver al resumen nacional
        </a>
    </div>
</div>

<!-- Grid de Personas / Políticos -->
<div class="row g-3 g-md-4 mb-5" id="gridLegisladores">
    <?php foreach ($politicosConEstudios as $p): 
        $analisis = $p['analisis_academico'];
        $gradoKey = $analisis['grado_maximo'] ?? 'sin_registro';
        $gradoInfo = $analisis['grado_info'] ?? [];
        $categoriaGrupo = $p['categoria_grupo'] ?? 'media-trunca';
        
        $nombre = $p['nombre_completo'] ?? '';
        $slug = $p['slug'] ?? '';
        $urlPerfil = Router::url('/politicos/' . $slug);
        
        $foto = !empty($p['referencia_sil']) && file_exists(dirname(__DIR__) . "/storage/raw/photos/{$p['referencia_sil']}.jpg")
            ? Router::url("/storage/raw/photos/{$p['referencia_sil']}.jpg")
            : ($p['foto_oficial_url'] ?? 'https://via.placeholder.com/150');

        $camara = $p['camara'] ?? 'Diputados';
        $partido = $p['partido'] ?? 'Sin Partido';
        $partidoCan = ViewHelper::getCanonicalPartyName($partido);
        $entidad = $p['entidad'] ?? ($p['estado'] ?? 'México');
        
        // Normalización de slug del estado
        $estadoSlug = DataService::slugify($entidad);
        if ($estadoSlug === 'mexico' || $estadoSlug === 'edomex') {
            $estadoSlug = 'estado-de-mexico';
        }

        $distrito = $p['distrito'] ?? null;
        $principio = $p['principio_eleccion'] ?? null;

        $distritoTexto = '';
        if (!empty($distrito) && $distrito !== 'null') {
            $distritoTexto = 'Dto. ' . $distrito;
        } elseif (!empty($principio)) {
            $distritoTexto = ($principio === 'Representación Proporcional') ? 'Plurinominal' : $principio;
        }

        $tituloDestacado = $analisis['titulo_destacado'] ?? 'Sin registro';
        $disciplina = $analisis['disciplina_principal'] ?? 'General / Diversa';
        $tieneTrunca = $analisis['tiene_trunca'] ?? false;
        $instResumen = $analisis['institucion_resumen'] ?? [];
        $almaMater = $instResumen['alma_mater_destacada'] ?? '';
        $tipoInst = $instResumen['tipo_predominante'] ?? 'no_determinada';
        $instInfo = $instResumen['info'] ?? [];
        $todasInst = implode(' ', $instResumen['instituciones'] ?? []);

        // Corpus para búsqueda en vivo
        $searchCorpus = mb_strtolower("{$nombre} {$partido} {$partidoCan} {$entidad} {$distritoTexto} {$camara} {$tituloDestacado} {$disciplina} {$gradoInfo['label']} {$almaMater} {$todasInst}", 'UTF-8');
    ?>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-3 item-legislador" 
         data-categoria="<?= ViewHelper::e($categoriaGrupo) ?>" 
         data-estado="<?= ViewHelper::e($estadoSlug) ?>" 
         data-partido="<?= ViewHelper::e($partidoCan) ?>" 
         data-disciplina="<?= ViewHelper::e($disciplina) ?>" 
         data-camara="<?= ViewHelper::e($camara) ?>" 
         data-institucion="<?= ViewHelper::e($tipoInst) ?>" 
         data-distrito="<?= ViewHelper::e($distritoTexto) ?>" 
         data-search="<?= ViewHelper::e($searchCorpus) ?>">
        <div class="card-civic p-3 p-md-4 h-100 d-flex flex-column justify-content-between position-relative shadow-xs">
            <div>
                <!-- Encabezado de la Card: Entidad, Distrito y Partido -->
                <div class="d-flex align-items-center justify-content-between mb-3 gap-1">
                    <span class="badge bg-light text-secondary border small text-truncate" style="max-width: 60%; font-size: 0.7rem;" title="<?= ViewHelper::e($entidad) ?> <?= $distritoTexto ? '• ' . ViewHelper::e($distritoTexto) : '' ?>">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= ViewHelper::e($entidad) ?><?= $distritoTexto ? ' • ' . ViewHelper::e($distritoTexto) : '' ?>
                    </span>
                    <?= ViewHelper::renderPartyBadge($partido) ?>
                </div>

                <!-- Foto y Datos Principales -->
                <div class="text-center mb-3">
                    <div class="position-relative d-inline-block mb-2">
                        <img src="<?= $foto ?>" 
                             alt="<?= ViewHelper::e($nombre) ?>" 
                             class="rounded-circle shadow-sm border object-fit-cover" 
                             style="width: 78px; height: 78px;"
                             loading="lazy"
                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($nombre) ?>&background=0D8ABC&color=fff';">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-white text-secondary border shadow-xs" style="font-size: 0.65rem;" title="<?= ViewHelper::e($camara) ?>">
                            <?= $camara === 'Senadores' ? 'SEN' : 'DIP' ?>
                        </span>
                    </div>
                    <h2 class="h6 fw-bold text-dark mb-1 text-truncate" title="<?= ViewHelper::e($nombre) ?>">
                        <a href="<?= $urlPerfil ?>" class="text-decoration-none text-dark hover-primary">
                            <?= ViewHelper::e($nombre) ?>
                        </a>
                    </h2>
                    <div class="text-muted small" style="font-size: 0.75rem;">
                        <?= ViewHelper::e($camara) ?> &bull; <?= ViewHelper::e($distritoTexto ?: $entidad) ?>
                    </div>
                </div>

                <!-- Insignia Académica y Título -->
                <div class="p-2 px-3 rounded-3 bg-light border mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="badge <?= $gradoInfo['badge_class'] ?? 'bg-secondary-subtle text-secondary' ?>" style="font-size: 0.72rem;">
                            <i class="bi bi-<?= $gradoInfo['icono'] ?? 'mortarboard' ?> me-1"></i><?= ViewHelper::e($gradoInfo['label'] ?? 'Sin registro') ?>
                        </span>
                        <?php if ($tieneTrunca && $gradoKey !== 'trunca'): ?>
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.65rem;" title="Cuenta con título superior pero dejó otra carrera trunca">
                            +1 carrera trunca
                        </span>
                        <?php elseif ($gradoKey === 'trunca'): ?>
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.65rem;">
                            Sin título acreditado
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="fw-semibold text-dark small text-truncate" title="<?= ViewHelper::e($tituloDestacado) ?>" style="font-size: 0.8rem;">
                        <?= ViewHelper::e($tituloDestacado) ?>
                    </div>
                    <div class="text-muted" style="font-size: 0.72rem;">
                        <i class="bi bi-compass me-1"></i><?= ViewHelper::e($disciplina) ?>
                    </div>

                    <!-- Alma Máter / Institución -->
                    <?php if ($almaMater !== '' && $almaMater !== 'No especificada en ficha'): ?>
                    <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between" style="font-size: 0.72rem;">
                        <span class="badge <?= $instInfo['badge_class'] ?? 'bg-light text-secondary' ?>" style="font-size: 0.65rem;">
                            <i class="bi bi-<?= $tipoInst === 'publica' ? 'bank' : ($tipoInst === 'privada' ? 'mortarboard' : 'shuffle') ?> me-1"></i>
                            <?= $tipoInst === 'publica' ? 'Pública' : ($tipoInst === 'privada' ? 'Privada' : 'Mixta') ?>
                        </span>
                        <span class="text-secondary text-truncate ms-2 fw-medium text-end" title="<?= ViewHelper::e($almaMater) ?>" style="max-width: 140px;">
                            <?= ViewHelper::e($almaMater) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botón de acceso a Ficha Canónica -->
            <div class="pt-2 border-top text-center">
                <a href="<?= $urlPerfil ?>" class="btn btn-outline-primary btn-sm rounded-pill w-100 py-1" style="font-size: 0.78rem;">
                    Ver expediente completo &rarr;
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Estado Vacío cuando ningún resultado coincide -->
<div id="sinResultados" class="p-5 text-center bg-white rounded-4 border shadow-sm my-4" style="display: none;">
    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-muted mb-3" style="width: 60px; height: 60px;">
        <i class="bi bi-search fs-3"></i>
    </div>
    <h3 class="h5 fw-bold text-dark mb-1">No se encontraron legisladores con estos criterios</h3>
    <p class="text-muted small mb-3">Prueba cambiando el estado, partido o el término de búsqueda.</p>
    <button type="button" class="btn btn-primary rounded-pill btn-sm px-4" id="btnRestablecerVacios">
        Restablecer todos los filtros
    </button>
</div>

<!-- Lógica JavaScript Dinámica "Al Vuelo" (Vanilla JS, Zero-Latency) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Referencias al DOM
    const selectorCategorias = document.getElementById('selectorCategorias');
    const selectorPartidos = document.getElementById('selectorPartidos');
    const buscadorEnVivo = document.getElementById('buscadorEnVivo');
    const btnLimpiarTexto = document.getElementById('btnLimpiarTexto');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroDisciplina = document.getElementById('filtroDisciplina');
    const filtroInstitucion = document.getElementById('filtroInstitucion');
    const filtroCamara = document.getElementById('filtroCamara');
    const btnLimpiarTodo = document.getElementById('btnLimpiarTodo');
    const btnRestablecerVacios = document.getElementById('btnRestablecerVacios');

    const items = document.querySelectorAll('.item-legislador');
    const conteoVisible = document.getElementById('conteoVisible');
    const sinResultados = document.getElementById('sinResultados');
    const contenedorChips = document.getElementById('contenedorChipsActivos');

    // Elementos del Termómetro
    const termometroBadgeTipo = document.getElementById('termometroBadgeTipo');
    const termometroTitulo = document.getElementById('termometroTitulo');
    const termometroSubtitulo = document.getElementById('termometroSubtitulo');
    const termometroEnlaceEstado = document.getElementById('termometroEnlaceEstado');
    const linkFichaEstado = document.getElementById('linkFichaEstado');
    const termometroLeyendaValores = document.getElementById('termometroLeyendaValores');
    const barraPosgrado = document.getElementById('barraPosgrado');
    const barraLicenciatura = document.getElementById('barraLicenciatura');
    const barraMediaTrunca = document.getElementById('barraMediaTrunca');
    const termometroValPosgrado = document.getElementById('termometroValPosgrado');
    const termometroValLicenciatura = document.getElementById('termometroValLicenciatura');
    const termometroValMediaTrunca = document.getElementById('termometroValMediaTrunca');

    // Catálogo de datos estatales para el termómetro
    const estadosData = <?= json_encode($estadosOpciones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const statsNacionales = {
        total: <?= (int)$totalAuditados ?>,
        posgrados: <?= (int)$posgrados['total'] ?>,
        pct_posgrados: <?= (float)$posgrados['porcentaje'] ?>,
        licenciaturas: <?= (int)$licenciaturas['total'] ?>,
        pct_licenciaturas: <?= (float)$licenciaturas['porcentaje'] ?>,
        media_trunca: <?= (int)$mediaTrunca['total'] ?>,
        pct_media_trunca: <?= (float)$mediaTrunca['porcentaje'] ?>
    };

    const estadoBaseUrl = '<?= Router::url('/estados/') ?>';
    const estudiosBaseUrl = '<?= $estudiosBaseUrl ?>';

    // Estados de filtros activos
    let categoriaActual = '<?= ViewHelper::e($categoriaActiva) ?>';
    let partidoActual = 'todos';

    function actualizarTermometro(estadoSlug) {
        if (!estadoSlug || estadoSlug === 'todos' || !estadosData[estadoSlug]) {
            // Mostrar Vista Nacional
            termometroBadgeTipo.textContent = 'Vista Nacional';
            termometroTitulo.textContent = 'Radiografía General de México';
            termometroSubtitulo.textContent = 'Promedio acumulado de las 32 entidades federativas. Selecciona tu estado en los filtros para ver el detalle.';
            termometroEnlaceEstado.style.display = 'none';

            barraPosgrado.style.width = statsNacionales.pct_posgrados + '%';
            barraLicenciatura.style.width = statsNacionales.pct_licenciaturas + '%';
            barraMediaTrunca.style.width = statsNacionales.pct_media_trunca + '%';

            termometroLeyendaValores.textContent = 'Posgrados: ' + statsNacionales.pct_posgrados + '% | Licenciaturas: ' + statsNacionales.pct_licenciaturas + '% | Trunca/Media: ' + statsNacionales.pct_media_trunca + '%';

            termometroValPosgrado.textContent = statsNacionales.posgrados + ' (' + statsNacionales.pct_posgrados + '%)';
            termometroValLicenciatura.textContent = statsNacionales.licenciaturas + ' (' + statsNacionales.pct_licenciaturas + '%)';
            termometroValMediaTrunca.textContent = statsNacionales.media_trunca + ' (' + statsNacionales.pct_media_trunca + '%)';
            return;
        }

        const est = estadosData[estadoSlug];
        const esc = est.escolaridad || {};
        const totalEst = est.total || 1;
        const pos = esc.posgrados || 0;
        const lic = esc.licenciaturas || 0;
        const med = esc.media_trunca || 0;

        const pctPos = totalEst > 0 ? ((pos / totalEst) * 100).toFixed(1) : '0';
        const pctLic = totalEst > 0 ? ((lic / totalEst) * 100).toFixed(1) : '0';
        const pctMed = totalEst > 0 ? ((med / totalEst) * 100).toFixed(1) : '0';

        termometroBadgeTipo.textContent = 'Entidad Federativa';
        termometroTitulo.textContent = '📍 ' + est.nombre + ' (' + est.total + ' representantes)';
        
        let mensajeCívico = 'Radiografía de preparación académica de los legisladores que representan a ' + est.nombre + '.';
        if (parseFloat(pctPos) >= 40) {
            mensajeCívico = '💡 ' + est.nombre + ' destaca con una alta proporción de legisladores con estudios de posgrado (' + pctPos + '%).';
        }
        termometroSubtitulo.textContent = mensajeCívico;

        linkFichaEstado.href = estadoBaseUrl + est.slug;
        linkFichaEstado.textContent = 'Ver expediente de ' + est.nombre + ' →';
        termometroEnlaceEstado.style.display = 'block';

        barraPosgrado.style.width = pctPos + '%';
        barraLicenciatura.style.width = pctLic + '%';
        barraMediaTrunca.style.width = pctMed + '%';

        termometroLeyendaValores.textContent = 'Posgrados: ' + pctPos + '% | Licenciaturas: ' + pctLic + '% | Trunca/Media: ' + pctMed + '%';

        termometroValPosgrado.textContent = pos + ' (' + pctPos + '%)';
        termometroValLicenciatura.textContent = lic + ' (' + pctLic + '%)';
        termometroValMediaTrunca.textContent = med + ' (' + pctMed + '%)';
    }

    function renderChipsActivos(query, estado, partido, disciplina, institucion, camara) {
        let chipsHtml = '';

        if (categoriaActual !== 'todos') {
            const labels = {
                'posgrados': '🎓 Posgrados',
                'licenciatura': '📜 Licenciaturas',
                'media-trunca': '📄 Media / Trunca'
            };
            chipsHtml += '<span class="badge bg-primary text-white d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1">' + (labels[categoriaActual] || categoriaActual) + ' <i class="bi bi-x-circle cursor-pointer" onclick="window.quitarFiltro(\'categoria\')"></i></span>';
        }

        if (estado !== 'todos' && estadosData[estado]) {
            chipsHtml += '<span class="badge bg-secondary text-white d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1">📍 ' + estadosData[estado].nombre + ' <i class="bi bi-x-circle cursor-pointer" onclick="window.quitarFiltro(\'estado\')"></i></span>';
        }

        if (partido !== 'todos') {
            chipsHtml += '<span class="badge bg-dark text-white d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1">🚩 ' + partido + ' <i class="bi bi-x-circle cursor-pointer" onclick="window.quitarFiltro(\'partido\')"></i></span>';
        }

        if (disciplina !== 'todas') {
            chipsHtml += '<span class="badge bg-info-subtle text-info-emphasis border d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1">📚 ' + disciplina + ' <i class="bi bi-x-circle cursor-pointer" onclick="window.quitarFiltro(\'disciplina\')"></i></span>';
        }

        if (institucion !== 'todas') {
            const instLabels = {
                'publica': '🏛️ Pública',
                'privada': '🎓 Privada',
                'mixta': '⚖️ Mixta'
            };
            chipsHtml += '<span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1">' + (instLabels[institucion] || institucion) + ' <i class="bi bi-x-circle cursor-pointer" onclick="window.quitarFiltro(\'institucion\')"></i></span>';
        }

        if (camara !== 'todas') {
            chipsHtml += '<span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1">' + camara + ' <i class="bi bi-x-circle cursor-pointer" onclick="window.quitarFiltro(\'camara\')"></i></span>';
        }

        if (query) {
            chipsHtml += '<span class="badge bg-warning-subtle text-dark border d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1">🔍 "' + query + '" <i class="bi bi-x-circle cursor-pointer" onclick="window.quitarFiltro(\'texto\')"></i></span>';
        }

        contenedorChips.innerHTML = chipsHtml;
    }

    function filtrarAlVuelo() {
        const query = (buscadorEnVivo.value || '').trim().toLowerCase();
        const estado = filtroEstado.value;
        const partido = partidoActual;
        const disciplina = filtroDisciplina.value;
        const institucion = filtroInstitucion ? filtroInstitucion.value : 'todas';
        const camara = filtroCamara.value;

        btnLimpiarTexto.style.display = query ? 'block' : 'none';

        let visibleCount = 0;

        items.forEach(function (el) {
            const elCategoria = el.getAttribute('data-categoria');
            const elEstado = el.getAttribute('data-estado');
            const elPartido = el.getAttribute('data-partido');
            const elDisciplina = el.getAttribute('data-disciplina');
            const elCamara = el.getAttribute('data-camara');
            const elInstitucion = el.getAttribute('data-institucion');
            const elSearch = el.getAttribute('data-search') || '';

            // Criterios de filtrado
            const pasaCategoria = (categoriaActual === 'todos' || elCategoria === categoriaActual);
            const pasaEstado = (estado === 'todos' || elEstado === estado);
            const pasaPartido = (partido === 'todos' || elPartido === partido);
            const pasaDisciplina = (disciplina === 'todas' || elDisciplina === disciplina);
            const pasaInstitucion = (institucion === 'todas' || elInstitucion === institucion);
            const pasaCamara = (camara === 'todas' || elCamara === camara);
            const pasaTexto = (!query || elSearch.indexOf(query) !== -1);

            if (pasaCategoria && pasaEstado && pasaPartido && pasaDisciplina && pasaInstitucion && pasaCamara && pasaTexto) {
                el.style.display = '';
                visibleCount++;
            } else {
                el.style.display = 'none';
            }
        });

        conteoVisible.textContent = visibleCount;
        sinResultados.style.display = (visibleCount === 0) ? 'block' : 'none';

        actualizarTermometro(estado);
        renderChipsActivos(query, estado, partido, disciplina, institucion, camara);
    }

    // Interacción con Pestañas de Grado Académico
    selectorCategorias.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-categoria]');
        if (!btn) return;

        categoriaActual = btn.getAttribute('data-categoria');

        selectorCategorias.querySelectorAll('button[data-categoria]').forEach(function (b) {
            if (b === btn) {
                b.className = 'btn btn-sm rounded-pill px-3 fw-semibold btn-primary';
            } else {
                b.className = 'btn btn-sm rounded-pill px-3 fw-semibold btn-light border text-dark';
            }
        });

        // Actualizar URL limpia sin recargar
        const nuevaUrl = (categoriaActual === 'todos') 
            ? estudiosBaseUrl 
            : estudiosBaseUrl + '/' + categoriaActual;
        window.history.replaceState({ categoria: categoriaActual }, '', nuevaUrl);

        filtrarAlVuelo();
    });

    // Interacción con Píldoras de Partidos
    selectorPartidos.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-partido]');
        if (!btn) return;

        partidoActual = btn.getAttribute('data-partido');

        selectorPartidos.querySelectorAll('button[data-partido]').forEach(function (b) {
            if (b === btn) {
                b.classList.remove('btn-light', 'border');
                b.classList.add('btn-dark', 'active');
            } else {
                b.classList.remove('btn-dark', 'active');
                b.classList.add('btn-light', 'border');
            }
        });

        filtrarAlVuelo();
    });

    // Input en vivo y selects
    buscadorEnVivo.addEventListener('input', filtrarAlVuelo);
    btnLimpiarTexto.addEventListener('click', function () {
        buscadorEnVivo.value = '';
        filtrarAlVuelo();
        buscadorEnVivo.focus();
    });

    filtroEstado.addEventListener('change', filtrarAlVuelo);
    filtroDisciplina.addEventListener('change', filtrarAlVuelo);
    if (filtroInstitucion) filtroInstitucion.addEventListener('change', filtrarAlVuelo);
    filtroCamara.addEventListener('change', filtrarAlVuelo);

    // Función global para quitar chips individuales
    window.quitarFiltro = function (tipo) {
        if (tipo === 'categoria') {
            categoriaActual = 'todos';
            selectorCategorias.querySelectorAll('button[data-categoria]').forEach(function (b) {
                b.className = (b.getAttribute('data-categoria') === 'todos') 
                    ? 'btn btn-sm rounded-pill px-3 fw-semibold btn-primary' 
                    : 'btn btn-sm rounded-pill px-3 fw-semibold btn-light border text-dark';
            });
            window.history.replaceState({ categoria: 'todos' }, '', estudiosBaseUrl);
        } else if (tipo === 'estado') {
            filtroEstado.value = 'todos';
        } else if (tipo === 'partido') {
            partidoActual = 'todos';
            selectorPartidos.querySelectorAll('button[data-partido]').forEach(function (b) {
                if (b.getAttribute('data-partido') === 'todos') {
                    b.classList.remove('btn-light', 'border');
                    b.classList.add('btn-dark', 'active');
                } else {
                    b.classList.remove('btn-dark', 'active');
                    b.classList.add('btn-light', 'border');
                }
            });
        } else if (tipo === 'disciplina') {
            filtroDisciplina.value = 'todas';
        } else if (tipo === 'institucion') {
            filtroInstitucion.value = 'todas';
        } else if (tipo === 'camara') {
            filtroCamara.value = 'todas';
        } else if (tipo === 'texto') {
            buscadorEnVivo.value = '';
        }
        filtrarAlVuelo();
    };

    function resetearTodosLosFiltros() {
        categoriaActual = 'todos';
        partidoActual = 'todos';
        buscadorEnVivo.value = '';
        filtroEstado.value = 'todos';
        filtroDisciplina.value = 'todas';
        if (filtroInstitucion) filtroInstitucion.value = 'todas';
        filtroCamara.value = 'todas';

        selectorCategorias.querySelectorAll('button[data-categoria]').forEach(function (b) {
            b.className = (b.getAttribute('data-categoria') === 'todos') 
                ? 'btn btn-sm rounded-pill px-3 fw-semibold btn-primary' 
                : 'btn btn-sm rounded-pill px-3 fw-semibold btn-light border text-dark';
        });

        selectorPartidos.querySelectorAll('button[data-partido]').forEach(function (b) {
            if (b.getAttribute('data-partido') === 'todos') {
                b.classList.remove('btn-light', 'border');
                b.classList.add('btn-dark', 'active');
            } else {
                b.classList.remove('btn-dark', 'active');
                b.classList.add('btn-light', 'border');
            }
        });

        window.history.replaceState({ categoria: 'todos' }, '', estudiosBaseUrl);
        filtrarAlVuelo();
    }

    btnLimpiarTodo.addEventListener('click', resetearTodosLosFiltros);
    btnRestablecerVacios.addEventListener('click', resetearTodosLosFiltros);

    // Inicialización al cargar la vista
    filtrarAlVuelo();
});
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>
