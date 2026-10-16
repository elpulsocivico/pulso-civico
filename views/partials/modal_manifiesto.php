<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;
?>

<!-- Modal de Manifiesto de Neutralidad y Metodología de Datos -->
<div class="modal fade" id="modalManifiesto" tabindex="-1" aria-labelledby="modalManifiestoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex p-2 rounded-circle bg-primary-subtle text-primary">
                        <i class="bi bi-shield-check fs-5"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalManifiestoLabel">Manifiesto de Neutralidad y Metodología</h5>
                        <small class="text-secondary">Principios rectores de Pulso Cívico para la fiscalización ciudadana</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4 text-secondary" style="font-size: 0.92rem; line-height: 1.6;">
                <!-- Declaración de Principios -->
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-compass text-primary"></i> Nuestro Compromiso Fundamental
                    </h6>
                    <p class="mb-0 small">
                        <strong>Pulso Cívico</strong> es una plataforma ciudadana independiente y sin fines de lucro. 
                        No pertenecemos a ningún partido político ni recibimos financiamiento gubernamental. 
                        Nuestra misión es hacer accesible, auditable y comprensible la información pública oficial del Estado Mexicano, sin emitir juicios de valor, opiniones editoriales ni calificativos hacia ningún representante.
                    </p>
                </div>

                <!-- 4 Pilares de Credibilidad -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary-subtle text-primary rounded-circle p-2">
                                    <i class="bi bi-file-earmark-check"></i>
                                </span>
                                <h6 class="fw-bold text-dark mb-0">1. Fuentes 100% Oficiales</h6>
                            </div>
                            <p class="small text-muted mb-0">
                                Cada dato proviene de fuentes primarias del Estado Mexicano (SEGOB, INE, Senado de la República, Cámara de Diputados y Gacetas Parlamentarias). No utilizamos trascendidos ni fuentes no confirmadas.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success-subtle text-success rounded-circle p-2">
                                    <i class="bi bi-arrow-repeat"></i>
                                </span>
                                <h6 class="fw-bold text-dark mb-0">2. Trazabilidad Clicable</h6>
                            </div>
                            <p class="small text-muted mb-0">
                                Ningún número o antecedente se muestra como un hecho aislado. Proveemos folios oficiales (como el registro SIL de SEGOB) y enlaces directos para que cualquier ciudadano contraste la información en los portales gubernamentales.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-circle p-2">
                                    <i class="bi bi-calculator"></i>
                                </span>
                                <h6 class="fw-bold text-dark mb-0">3. Metodología Abierta</h6>
                            </div>
                            <p class="small text-muted mb-0">
                                Las asistencias se computan sobre pases de lista en el pleno; la disciplina partidaria mide la coincidencia con las votaciones del grupo parlamentario; y las comisiones reflejan acuerdos vigentes de la JUCOPO publicados en la Gaceta.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-danger-subtle text-danger rounded-circle p-2">
                                    <i class="bi bi-flag"></i>
                                </span>
                                <h6 class="fw-bold text-dark mb-0">4. Corrección Ciudadana</h6>
                            </div>
                            <p class="small text-muted mb-0">
                                Si una dependencia gubernamental actualiza un registro oficial o existe alguna discrepancia de homonimia o cargo, cualquier usuario puede enviar un reporte con evidencia para su revisión inmediata.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Catálogo de Fuentes Gubernamentales -->
                <h6 class="fw-bold text-dark mb-2">Registro de Fuentes Primarias Empleadas</h6>
                <div class="table-responsive border rounded-3 mb-4">
                    <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Órgano Oficial</th>
                                <th>Dato Extraído</th>
                                <th>Dominio Oficial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">SEGOB (SIL)</td>
                                <td>Fichas curriculares, trayectoria y escolaridad</td>
                                <td><code>sil.gobernacion.gob.mx</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Senado de la República</td>
                                <td>Comisiones, escaños y mesa directiva</td>
                                <td><code>senado.gob.mx</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Cámara de Diputados (SITL)</td>
                                <td>Asistencias nominales y votaciones en pleno</td>
                                <td><code>sitl.diputados.gob.mx</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Gaceta Parlamentaria</td>
                                <td>Decretos, iniciativas y acuerdos de bancadas</td>
                                <td><code>gaceta.diputados.gob.mx</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">INE</td>
                                <td>Cómputos de votos y constancias de mayoría</td>
                                <td><code>ine.mx</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info py-2 px-3 small border-info-subtle mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0"></i>
                    <span>
                        Pulso Cívico respeta los derechos de acceso a la información pública contemplados en el Artículo 6° de la Constitución Política de los Estados Unidos Mexicanos.
                    </span>
                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>
