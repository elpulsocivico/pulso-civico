<?php
declare(strict_types=1);

namespace PulsoCivico\Controllers;

use PulsoCivico\Core\Router;
use PulsoCivico\Core\ViewHelper;
use PulsoCivico\Services\DataService;
use PulsoCivico\Services\MunicipioService;

/**
 * MunicipioController: Controla las vistas canónicas del Directorio Nacional de Alcaldes
 * y las páginas de historia cronológica municipal de México.
 */
class MunicipioController
{
    private MunicipioService $municipioService;
    private DataService $dataService;

    public function __construct(
        ?MunicipioService $municipioService = null,
        ?DataService $dataService = null
    ) {
        $this->municipioService = $municipioService ?? new MunicipioService();
        $this->dataService = $dataService ?? new DataService();
    }

    /**
     * Directorio Nacional de Alcaldes: Vista interactiva y filtrable de los 2,474 municipios.
     */
    public function alcaldes(): void
    {
        $alcaldes = $this->municipioService->getDirectorioVigente();
        $estadosCatalog = $this->dataService->getEstadosCatalog();

        // Conteo de gobernanza por partido y estado
        $partidosConteo = [];
        $totalUsosCostumbres = 0;
        foreach ($alcaldes as $item) {
            if (!empty($item['sistema']) && str_contains(strtolower((string)$item['sistema']), 'normativos')) {
                $totalUsosCostumbres++;
                $partidosConteo['Sistemas Normativos Indígenas'] = ($partidosConteo['Sistemas Normativos Indígenas'] ?? 0) + 1;
            } else {
                $p = $item['partido'] ?? 'Sin Partido';
                $partidosConteo[$p] = ($partidosConteo[$p] ?? 0) + 1;
            }
        }
        arsort($partidosConteo);

        $canonical = Router::url('/alcaldes');

        $seo = [
            'title' => 'Directorio Nacional de Presidentas y Presidentes Municipales de México — Pulso Cívico',
            'description' => 'Radiografía de gobernanza local en los 2,474 municipios y alcaldías de México. Directorio oficial de alcaldes vigentes y partidos políticos.',
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'GovernmentOrganization',
                'name' => 'Directorio Nacional de Alcaldes de México',
                'description' => 'Directorio de autoridades municipales y alcaldías de las 32 entidades federativas.',
                'url' => $canonical,
            ],
        ];

        require dirname(__DIR__, 2) . '/views/alcaldes.php';
    }

    /**
     * Detalle e Historia de un Municipio específico (Línea de tiempo de 54 años).
     */
    public function municipio(string $cvegeo): void
    {
        $cvegeo = sprintf('%05d', (int)$cvegeo);
        $municipio = $this->municipioService->getHistorialMunicipio($cvegeo);

        if ($municipio === null) {
            http_response_code(404);
            $seo = [
                'title' => 'Municipio No Encontrado — Pulso Cívico',
                'description' => "No se encontró el municipio con clave {$cvegeo}.",
                'canonical' => Router::url('/alcaldes'),
            ];
            require dirname(__DIR__, 2) . '/views/404.php';
            return;
        }

        $nombreMun = $municipio['municipio'] ?? "Municipio {$cvegeo}";
        $entidad = $municipio['entidad'] ?? 'México';
        $canonical = Router::url('/municipios/' . $cvegeo);

        $seo = [
            'title' => "Alcalde e Historia Política de {$nombreMun}, {$entidad} — Pulso Cívico",
            'description' => "Cronología histórica de alcaldes, partidos gobernantes y alternancia política en {$nombreMun}, {$entidad} desde 1970 hasta la fecha.",
            'canonical' => $canonical,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'AdministrativeArea',
                'name' => $nombreMun,
                'containedInPlace' => $entidad,
                'url' => $canonical,
            ],
        ];

        require dirname(__DIR__, 2) . '/views/municipio_detalle.php';
    }

    /**
     * API Interna: Búsqueda rápida de alcaldes y municipios en JSON.
     */
    public function apiAlcaldes(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $entidad = isset($_GET['entidad']) ? (int)$_GET['entidad'] : null;

        $alcaldes = $this->municipioService->getDirectorioVigente($entidad);

        if ($q !== '') {
            $filtrados = [];
            foreach ($alcaldes as $cvegeo => $item) {
                $nombre = mb_strtolower($item['nombre_completo'] ?? '');
                $mun = mb_strtolower($item['municipio'] ?? '');
                $partido = mb_strtolower($item['partido'] ?? '');
                if (str_contains($nombre, $q) || str_contains($mun, $q) || str_contains($partido, $q) || str_contains($cvegeo, $q)) {
                    $filtrados[$cvegeo] = $item;
                }
            }
            $alcaldes = $filtrados;
        }

        echo json_encode([
            'total' => count($alcaldes),
            'alcaldes' => array_values($alcaldes),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
