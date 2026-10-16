<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;

/**
 * @var array{banned: bool, expires_at: ?int, reason: ?string, remaining_seconds: int} $banStatus
 * @var string $ip
 * @var array $seo
 */

$isBanned = !empty($banStatus['banned']);
$remainingSeconds = (int)($banStatus['remaining_seconds'] ?? 0);
$remainingHours = max(1, (int)ceil($remainingSeconds / 3600));
$expiresDate = !empty($banStatus['expires_at']) ? date('d/m/Y H:i:s', (int)$banStatus['expires_at']) : 'En 24 horas';
$reason = $banStatus['reason'] ?? 'Exceso de solicitudes automatizadas en poco tiempo';

require __DIR__ . '/layout/header.php';
?>

<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7">
            <div class="card card-civic shadow-sm border-0 text-center p-4 p-md-5">
                <div class="mb-4">
                    <span class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle p-4 mb-3" style="width: 88px; height: 88px;">
                        <i class="bi bi-shield-slash fs-1"></i>
                    </span>
                    <h1 class="h3 fw-bold text-dark mb-2">Acceso a Reportes Restringido</h1>
                    <p class="text-secondary small mb-0">Protección activa de integridad y prevención de tráfico automatizado</p>
                </div>

                <?php if ($isBanned): ?>
                    <div class="alert alert-warning border text-start p-3 mb-4 rounded-3">
                        <div class="d-flex gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Tu dirección IP se encuentra en periodo de enfriamiento (24 horas)</strong>
                                <span class="small text-muted d-block mb-2">
                                    Se detectaron más de <strong>5 envíos por minuto</strong> desde tu conexión, lo cual excede el ritmo normal de un usuario humano y activa las defensas anti-spam del observatorio cívico.
                                </span>
                                <ul class="list-unstyled small mb-0 text-secondary border-top pt-2 mt-2">
                                    <li><i class="bi bi-hdd-network me-1"></i> <strong>IP registrada:</strong> <code><?= ViewHelper::e($ip) ?></code></li>
                                    <li><i class="bi bi-clock me-1"></i> <strong>Tiempo restante aprox:</strong> <?= $remainingHours ?> hora(s)</li>
                                    <li><i class="bi bi-calendar-check me-1"></i> <strong>Reanudación automática:</strong> <?= ViewHelper::e($expiresDate) ?></li>
                                    <li><i class="bi bi-info-circle me-1"></i> <strong>Motivo:</strong> <?= ViewHelper::e($reason) ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info border text-start p-3 mb-4 rounded-3">
                        <div class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block mb-1">Estado de tu conexión: Habilitada</strong>
                                <span class="small text-muted">
                                    Tu dirección IP (<code><?= ViewHelper::e($ip) ?></code>) no cuenta con restricciones activas en este momento. Recuerda que el sistema admite hasta 5 reportes por minuto por conexión.
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="p-3 bg-light rounded-3 mb-4 text-start">
                    <h6 class="fw-semibold text-dark mb-2 small"><i class="bi bi-question-circle me-1"></i> ¿Por qué existe esta medida de seguridad?</h6>
                    <p class="text-secondary small mb-0">
                        Pulso Cívico es una iniciativa ciudadana de neutralidad y datos abiertos. Para evitar saturación maliciosa, inyección de bots y garantizar que cada ticket de inconsistencia sea auditado responsablemente por un equipo humano, limitamos la velocidad de envío a 5 reportes/minuto.
                    </p>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                    <a href="<?= Router::url('/') ?>" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-house-door me-1"></i> Volver al Inicio
                    </a>
                    <a href="<?= Router::url('/politicos') ?>" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-people me-1"></i> Explorar Políticos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/layout/footer.php';
