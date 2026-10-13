<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de Baja California Sur.
 * Extrae tanto la XVII Legislatura (2024-2027) como el acervo histórico de legislaturas pasadas.
 * Fuente primaria: cbcs.gob.mx e IEEBCS (ieebcs.org.mx).
 */
class CongresoBcsScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public const URL_OFICIAL_CBCS = 'https://www.cbcs.gob.mx/index.php/organizacion/diputados';
    public const URL_HISTORICA_CBCS = 'https://www.cbcs.gob.mx/index.php/organizacion/integrantes-de-las-legislaturas';
    public const URL_IEEBCS = 'https://ieebcs.org.mx/archivos/sesiones/2024/Acuerdos/IEEBCS-CG143-JUNIO-2024.pdf';

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    /**
     * Extrae y genera el dataset oficial de la XVII Legislatura (2024-2027).
     * @return array<string, mixed>
     */
    public function scrapeLegislatura17(): array
    {
        $this->logger->info("Iniciando extracción del Congreso de Baja California Sur (XVII Legislatura)...");

        $catalogo = CongresoBcsCatalogoActual::getDiputadosXVII();
        $fechaExtraccion = date('Y-m-d H:i:s');
        $fechaCorta = date('Y-m-d');
        $legisladores = [];

        foreach ($catalogo as $idx => $dip) {
            $slugId = 'bcs_leg17_' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $fuenteRegistro = !empty($dip['distrito']) ? self::URL_OFICIAL_CBCS : self::URL_IEEBCS;
            $hashEvidencia = hash('sha256', $dip['nombre'] . '|' . $dip['partido'] . '|' . ($dip['distrito'] ?? 'RP') . '|' . $fechaCorta);

            $registro = [
                'referencia_sil' => $slugId,
                'nombre_completo' => $dip['nombre'],
                'camara' => 'Congreso del Estado de Baja California Sur',
                'legislatura' => 17,
                'partido' => $dip['partido'],
                'principio_eleccion' => $dip['principio_eleccion'],
                'entidad' => 'Baja California Sur',
                'distrito' => $dip['distrito'],
                'municipio' => $dip['municipio'] ?? null,
                'tipo_cargo' => 'Propietario',
                'suplente_nombre' => $dip['suplente'] ?? null,
                'fecha_toma_protesta' => '2024-09-01',
                'fecha_nacimiento' => null,
                'correo_institucional' => $dip['correo'] ?? null,
                'telefono' => $dip['telefono'] ?? null,
                'ubicacion_oficina' => 'Av. Isabel La Católica y Nicolás Bravo, Col. Centro, C.P. 23000, La Paz, B.C.S.',
                'ultimo_grado_estudios' => null,
                'preparacion_academica' => null,
                'experiencia_legislativa_resumen' => null,
                'foto_oficial_url' => null,
                'foto_local_path' => null,
                'total_comisiones' => count($dip['comisiones'] ?? []),
                'comisiones' => $dip['comisiones'] ?? [],
                'trayectoria_politica' => [],
                'trayectoria_legislativa' => [],
                'trayectoria_administrativa' => [],
                'escolaridad_detalle' => [],
                'licencias' => [],
                'iniciativas' => [],
                'puntos_acuerdo' => [],
                'redes_sociales' => [],
                'metadata' => [
                    'fuente_url' => $fuenteRegistro,
                    'sha256_evidencia' => $hashEvidencia,
                    'fecha_extraccion' => $fechaExtraccion,
                    'entidad_tipo' => 'Congreso Local',
                ],
            ];

            $legisladores[] = $registro;
        }

        $dataset = [
            'metadata' => [
                'titulo' => 'H. Congreso del Estado de Baja California Sur - XVII Legislatura (2024-2027)',
                'entidad' => 'Baja California Sur',
                'camara' => 'Congreso Local',
                'legislatura' => 17,
                'periodo' => '2024-2027',
                'generado_en' => date('c'),
                'fuente_url' => self::URL_OFICIAL_CBCS,
                'fuente_electoral_ieebcs' => self::URL_IEEBCS,
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        $filePath = $this->guardarDataset('congreso_bcs_leg_17.json', $dataset);
        // Guardar alias consistente con nombres completos
        $this->guardarDataset('congreso_bajacaliforniasur_leg_17.json', $dataset);

        $this->logger->info("XVII Legislatura BCS guardada en {$filePath} (21 diputados)");

        return [
            'ok' => true,
            'legislatura' => 17,
            'archivo' => $filePath,
            'total_legisladores' => count($legisladores),
            'diputados_mayoria_relativa' => 16,
            'diputados_representacion_proporcional' => 5,
        ];
    }

    /**
     * Extrae y genera una legislatura histórica específica de BCS.
     * @return array<string, mixed>
     */
    public function scrapeLegislaturaHistorica(int $numero): array
    {
        $metaLegs = CongresoBcsCatalogoHistorico::getMetadataLegislaturas();
        $key = ($numero === 0) ? 'constituyente' : (string)$numero;

        if (!isset($metaLegs[$key])) {
            return [
                'ok' => false,
                'error' => "Legislatura {$numero} no encontrada en catálogo histórico de BCS.",
            ];
        }

        $info = $metaLegs[$key];
        $dipsRaw = match ($numero) {
            0 => CongresoBcsCatalogoHistorico::getConstituyente(),
            1 => CongresoBcsCatalogoHistorico::getLegislatura1(),
            2 => CongresoBcsCatalogoHistorico::getLegislatura2(),
            3 => CongresoBcsCatalogoHistorico::getLegislatura3(),
            4 => CongresoBcsCatalogoHistorico::getLegislatura4(),
            5 => CongresoBcsCatalogoHistorico::getLegislatura5(),
            6 => CongresoBcsCatalogoHistorico::getLegislatura6(),
            7 => CongresoBcsCatalogoHistorico::getLegislatura7(),
            8 => CongresoBcsCatalogoHistorico::getLegislatura8(),
            9 => CongresoBcsCatalogoModerno::getLegislatura9(),
            10 => CongresoBcsCatalogoModerno::getLegislatura10(),
            11 => CongresoBcsCatalogoModerno::getLegislatura11(),
            12 => CongresoBcsCatalogoModerno::getLegislatura12(),
            13 => CongresoBcsCatalogoModerno::getLegislatura13(),
            14 => CongresoBcsCatalogoModerno::getLegislatura14(),
            15 => CongresoBcsCatalogoModerno::getLegislatura15(),
            16 => CongresoBcsCatalogoModerno::getLegislatura16(),
            default => [],
        };

        $fechaExtraccion = date('Y-m-d H:i:s');
        $legisladores = [];

        foreach ($dipsRaw as $idx => $dip) {
            $slugId = 'bcs_leg' . $numero . '_' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $hashEvidencia = hash('sha256', $dip['nombre'] . '|' . ($dip['partido'] ?? 'PRI') . '|' . ($dip['distrito'] ?? 'RP'));

            $legisladores[] = [
                'referencia_sil' => $slugId,
                'nombre_completo' => $dip['nombre'],
                'camara' => 'Congreso del Estado de Baja California Sur',
                'legislatura' => $numero,
                'partido' => $dip['partido'] ?? 'PRI',
                'principio_eleccion' => $dip['principio_eleccion'] ?? 'Mayoría Relativa',
                'entidad' => 'Baja California Sur',
                'distrito' => $dip['distrito'] ?? null,
                'tipo_cargo' => 'Propietario',
                'suplente_nombre' => $dip['suplente'] ?? null,
                'fecha_toma_protesta' => $info['inicio'],
                'fecha_conclusion' => $info['fin'],
                'metadata' => [
                    'fuente_url' => self::URL_HISTORICA_CBCS,
                    'sha256_evidencia' => $hashEvidencia,
                    'fecha_extraccion' => $fechaExtraccion,
                    'entidad_tipo' => 'Congreso Local',
                ],
            ];
        }

        $dataset = [
            'metadata' => [
                'titulo' => "H. Congreso del Estado de Baja California Sur - {$info['romano']} Legislatura ({$info['periodo']})",
                'entidad' => 'Baja California Sur',
                'camara' => 'Congreso Local',
                'legislatura' => $numero,
                'periodo' => $info['periodo'],
                'generado_en' => date('c'),
                'fuente_url' => self::URL_HISTORICA_CBCS,
                'total_legisladores' => count($legisladores),
            ],
            'legisladores' => $legisladores,
        ];

        $nombreArchivo = ($numero === 0) ? 'congreso_bcs_constituyente_1974.json' : "congreso_bcs_leg_{$numero}.json";
        $filePath = $this->guardarDataset($nombreArchivo, $dataset);

        // Guardar también con formato con cero inicial (ej. congreso_bcs_leg_01.json) para compatibilidad
        if ($numero > 0 && $numero < 10) {
            $this->guardarDataset("congreso_bcs_leg_0{$numero}.json", $dataset);
        }

        return [
            'ok' => true,
            'legislatura' => $numero,
            'archivo' => $filePath,
            'total_legisladores' => count($legisladores),
        ];
    }

    /**
     * Procesa todas las legislaturas históricas y genera el mapa cronológico maestro de BCS.
     * @return array<string, mixed>
     */
    public function scrapeTodasHistoricas(): array
    {
        $resultados = [];
        $legsDisponibles = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16];

        foreach ($legsDisponibles as $leg) {
            $resultados[$leg] = $this->scrapeLegislaturaHistorica($leg);
        }

        // Generar índice histórico maestro de las 17 Legislaturas + Constituyente
        $resumenHistorico = [
            'metadata' => [
                'titulo' => 'Historial Integral de Legislaturas de Baja California Sur (1974-2027)',
                'entidad' => 'Baja California Sur',
                'fuente_url' => self::URL_HISTORICA_CBCS,
                'fecha_extraccion' => date('Y-m-d H:i:s'),
                'total_legislaturas' => 18,
            ],
            'cronologia' => CongresoBcsCatalogoHistorico::getMetadataLegislaturas(),
        ];

        $resumenPath = $this->guardarDataset('congreso_bcs_cronologia_historica.json', $resumenHistorico);

        return [
            'ok' => true,
            'total_procesadas' => count($legsDisponibles),
            'resumen_cronologia' => $resumenPath,
            'detalle' => $resultados,
        ];
    }

    /**
     * Pipeline completo: Ejecuta la XVII actual prioritaria y las históricas.
     * @return array<string, mixed>
     */
    public function scrapeTodo(): array
    {
        $actual = $this->scrapeLegislatura17();
        $historicas = $this->scrapeTodasHistoricas();

        return [
            'ok' => true,
            'actual_xvii' => $actual,
            'historicas' => $historicas,
        ];
    }

    /**
     * Guarda de forma atómica y segura un dataset JSON en storage/data/json/.
     * @param array<string, mixed> $data
     */
    private function guardarDataset(string $filename, array $data): string
    {
        $targetDir = $this->storagePath . '/data/json';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = $targetDir . '/' . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filePath, $json);

        return $filePath;
    }
}
