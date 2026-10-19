<?php
declare(strict_types=1);

use PulsoCivico\Core\Router;

$homeUrl = Router::url('/');
$searchApiUrl = Router::url('/api/buscar');
?>
    </div>
</main>

<footer class="bg-white border-top py-4 mt-auto">
    <div class="container text-center text-md-start">
        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <span class="fw-bold text-dark">El Pulso de la Política</span> &bull;
                <span class="text-secondary small">Observatorio y Radiografía del H. Congreso de la Unión (LXVI Legislatura).</span>
                <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                    Datos oficiales extraídos del Sistema de Información Legislativa (SIL - SEGOB).
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?= $homeUrl ?>" class="text-secondary text-decoration-none small me-3">Inicio</a>
                <a href="<?= Router::url('/legislaturas') ?>" class="text-secondary text-decoration-none small me-3">Legislaturas</a>
                <a href="<?= $homeUrl ?>#seccion-mapa" class="text-secondary text-decoration-none small me-3">Mapa Político</a>
                <a href="<?= Router::url('/estudios') ?>" class="text-secondary text-decoration-none small me-3">Nivel de Estudios</a>
                <a href="<?= Router::url('/comisiones') ?>" class="text-secondary text-decoration-none small me-3">Comisiones</a>
                <button type="button" class="btn btn-link p-0 text-secondary text-decoration-none small me-3" data-bs-toggle="modal" data-bs-target="#modalManifiesto" title="Conoce nuestro Manifiesto de Neutralidad"><i class="bi bi-shield-check text-primary me-1"></i>Manifiesto</button>
                <a href="<?= Router::url('/sitemap.xml') ?>" class="text-secondary text-decoration-none small me-3" target="_blank">Sitemap</a>
                <a href="<?= Router::url('/llms.txt') ?>" class="text-secondary text-decoration-none small me-3" target="_blank" title="Guía para modelos de IA">LLMs.txt</a>
                <button type="button" class="btn btn-link p-0 text-secondary text-decoration-none small btn-reportar-anomalia" data-origen-tipo="general" data-origen-id="sitio-web" data-origen-nombre="Portal Pulso Cívico" title="Reportar anomalía o dato inconsistente"><i class="bi bi-flag text-danger me-1"></i>Reportar error</button>
            </div>
        </div>
    </div>
</footer>

<?php require dirname(__DIR__) . '/partials/modal_reporte.php'; ?>
<?php require dirname(__DIR__) . '/partials/modal_manifiesto.php'; ?>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Spotlight Search Controller
(function() {
    const modal = document.getElementById('spotlightModal');
    const input = document.getElementById('spotlightInput');
    const resultsContainer = document.getElementById('spotlightResults');
    const btnOpen = document.getElementById('btnOpenSpotlight');
    const btnClose = document.getElementById('btnCloseSpotlight');
    const searchUrl = '<?= $searchApiUrl ?>';
    const baseUrl = '<?= Router::initBasePath() ?>';

    function openSpotlight() {
        if (!modal) return;
        modal.style.display = 'flex';
        input.value = '';
        resultsContainer.innerHTML = '<div class="text-center text-muted p-4 small">Comienza a escribir para buscar...</div>';
        setTimeout(() => input.focus(), 50);
    }

    function closeSpotlight() {
        if (!modal) return;
        modal.style.display = 'none';
    }

    if (btnOpen) btnOpen.addEventListener('click', openSpotlight);
    if (btnClose) btnClose.addEventListener('click', closeSpotlight);

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeSpotlight();
        });
    }

    // Atajo de teclado Ctrl+K o Cmd+K
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (modal.style.display === 'flex') {
                closeSpotlight();
            } else {
                openSpotlight();
            }
        }
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeSpotlight();
        }
    });

    // Búsqueda debounce en tiempo real
    let timeout = null;
    if (input) {
        input.addEventListener('input', () => {
            clearTimeout(timeout);
            const val = input.value.trim();
            if (val.length < 2) {
                resultsContainer.innerHTML = '<div class="text-center text-muted p-4 small">Escribe al menos 2 caracteres...</div>';
                return;
            }

            timeout = setTimeout(async () => {
                resultsContainer.innerHTML = '<div class="text-center text-muted p-3 small"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Buscando...</div>';
                try {
                    const res = await fetch(`${searchUrl}?q=${encodeURIComponent(val)}`);
                    const data = await res.json();
                    renderResults(data);
                } catch (err) {
                    resultsContainer.innerHTML = '<div class="text-center text-danger p-3 small">Error al consultar datos.</div>';
                }
            }, 250);
        });
    }

    function renderResults(data) {
        const politicos = data.politicos || [];
        const comisiones = data.comisiones || [];

        if (politicos.length === 0 && comisiones.length === 0) {
            resultsContainer.innerHTML = '<div class="text-center text-muted p-4 small">No se encontraron resultados.</div>';
            return;
        }

        let html = '';
        if (politicos.length > 0) {
            html += '<div class="px-3 py-1 text-muted fw-bold" style="font-size: 0.72rem; text-transform: uppercase;">Servidores Públicos y Representantes</div>';
            politicos.forEach(p => {
                const targetUrl = baseUrl + p.url;
                const cargoDisplay = p.cargo || p.camara || 'Servidor Público';
                const entidadDisplay = p.entidad ? ` &bull; ${escapeHtml(p.entidad)}` : '';
                html += `
                <a href="${targetUrl}" class="d-flex align-items-center gap-3 p-2 rounded text-decoration-none text-dark hover-bg">
                    <img src="${p.foto_url || 'https://via.placeholder.com/40'}" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate small">${escapeHtml(p.nombre)}</div>
                        <div class="text-muted text-truncate" style="font-size: 0.75rem;">${escapeHtml(p.partido)} &bull; ${escapeHtml(cargoDisplay)}${entidadDisplay}</div>
                    </div>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>`;
            });
        }

        if (comisiones.length > 0) {
            html += '<div class="px-3 py-1 mt-2 text-muted fw-bold" style="font-size: 0.72rem; text-transform: uppercase;">Comisiones</div>';
            comisiones.forEach(c => {
                const targetUrl = baseUrl + c.url;
                html += `
                <a href="${targetUrl}" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none text-dark hover-bg">
                    <div class="small fw-medium text-truncate">${escapeHtml(c.nombre)}</div>
                    <span class="badge bg-light text-secondary border small">${c.total_integrantes} int.</span>
                </a>`;
            });
        }

        resultsContainer.innerHTML = html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.toString().replace(/[&<>"']/g, m => map[m]);
    }
})();

// Inicializar Tooltips interactivos de Bootstrap
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
.hover-bg:hover {
    background-color: #F2F2F7;
}
</style>
</body>
</html>
