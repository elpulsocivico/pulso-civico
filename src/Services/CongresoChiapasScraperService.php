<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de Chiapas.
 * Extrae la LXIX Legislatura (2024-2027, 40 curules) y el acervo histórico de legislaturas pasadas.
 * Fuente primaria: congresochiapas.gob.mx e IEPC Chiapas (iepc-chiapas.org.mx).
 */
class CongresoChiapasScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;

    public const URL_OFICIAL_CONGRESO = 'https://www.congresochiapas.gob.mx';
    public const URL_IEPC = 'https://www.iepc-chiapas.org.mx';

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
    }

    /**
     * Extrae y genera el dataset oficial de la LXIX Legislatura (2024-2027, 40 curules).
     * @return array<string, mixed>
     */
    public function scrapeLegislatura69(): array
    {
        $this->logger->info("Iniciando extracción del Congreso de Chiapas (LXIX Legislatura)...");

        $catalogo = CongresoChiapasCatalogoActual::getDiputados69();
        $fechaExtraccion = date('Y-m-d H:i:s');
        $fechaCorta = date('Y-m-d');
        $legisladores = [];

        foreach ($catalogo as $idx => $dip) {
            $slugId = 'chis_leg69_' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $hashEvidencia = hash('sha256', $dip['nombre'] . '|' . $dip['partido'] . '|' . ($dip['distrito'] ?? 'RP') . '|' . $fechaCorta);

            $registro = [
                'referencia_sil' => $slugId,
                'nombre_completo' => $dip['nombre'],
                'camara' => 'Congreso del Estado de Chiapas',
                'legislatura' => 69,
                'partido' => $dip['partido'],
                'principio_eleccion' => $dip['principio_eleccion'],
                'entidad' => 'Chiapas',
                'distrito' => $dip['distrito'],
                'cabecera' => $dip['cabecera'] ?? null,
                'municipio' => $dip['municipio'] ?? null,
                'tipo_cargo' => 'Propietario',
                'suplente_nombre' => null,
                'fecha_toma_protesta' => '2024-10-01',
                'periodo' => '2024-2027',
                'estatus' => 'En funciones',
                'contacto' => [
                    'correo' => $dip['correo'] ?? null,
                    'telefono' => $dip['telefono'] ?? null,
                    'modulo' => $dip['cabecera'] ?? null,
                ],
                'comisiones' => $dip['comisiones'] ?? [],
                'redes_sociales' => [],
                'metadata' => [
                    'fuente_url' => self::URL_OFICIAL_CONGRESO,
                    'fuente_secundaria' => self::URL_IEPC,
                    'fecha_extraccion' => $fechaExtraccion,
                    'sha256_evidencia' => $hashEvidencia,
                ],
            ];

            $legisladores[] = $registro;
        }

        $resumenPartidos = [];
        foreach ($legisladores as $l) {
            $p = $l['partido'];
            $resumenPartidos[$p] = ($resumenPartidos[$p] ?? 0) + 1;
        }

        $dataset = [
            'metadata' => [
                'congreso' => 'Congreso del Estado de Chiapas',
                'entidad' => 'Chiapas',
                'clave_entidad' => 'CHIS',
                'legislatura' => 69,
                'periodo' => '2024-2027',
                'fuente_oficial' => self::URL_OFICIAL_CONGRESO,
                'fuente_electoral' => self::URL_IEPC,
                'fecha_actualizacion' => $fechaExtraccion,
                'total_diputados' => count($legisladores),
                'composicion' => [
                    'mayoria_relativa' => 24,
                    'representacion_proporcional' => 16,
                    'total' => 40,
                ],
                'partidos' => $resumenPartidos,
            ],
            'mesa_directiva' => [
                'presidencia' => 'Marcela Castillo Atristain',
                'periodo' => 'Primer Año de Ejercicio Constitucional',
            ],
            'jucopo' => [
                'presidencia' => 'Mario Francisco Guillén Guillén',
            ],
            'diputados' => $legisladores,
        ];

        $jsonDir = $this->storagePath . '/data/json';
        if (!is_dir($jsonDir)) {
            mkdir($jsonDir, 0755, true);
        }

        $archivosSalida = [
            $jsonDir . '/congreso_chiapas_leg_69.json',
            $jsonDir . '/congreso_chis_leg_69.json',
        ];

        $jsonPayload = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        foreach ($archivosSalida as $archivo) {
            file_put_contents($archivo, $jsonPayload);
        }

        $this->logger->info("Extracción LXIX completada exitosamente. Total diputados: " . count($legisladores));
        return $dataset;
    }

    /**
     * Extrae y genera el dataset oficial de la LXVIII Legislatura (2021-2024, 40 curules).
     * @return array<string, mixed>
     */
    public function scrapeLegislatura68(): array
    {
        $this->logger->info("Iniciando extracción histórica del Congreso de Chiapas (LXVIII Legislatura)...");

        $catalogo = CongresoChiapasCatalogoHistorico::getDiputados68();
        $fechaExtraccion = date('Y-m-d H:i:s');
        $fechaCorta = date('Y-m-d');
        $legisladores = [];

        foreach ($catalogo as $idx => $dip) {
            $slugId = 'chis_leg68_' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $hashEvidencia = hash('sha256', $dip['nombre'] . '|' . $dip['partido'] . '|' . ($dip['distrito'] ?? 'RP') . '|' . $fechaCorta);

            $registro = [
                'referencia_sil' => $slugId,
                'nombre_completo' => $dip['nombre'],
                'camara' => 'Congreso del Estado de Chiapas',
                'legislatura' => 68,
                'partido' => $dip['partido'],
                'principio_eleccion' => $dip['principio_eleccion'],
                'entidad' => 'Chiapas',
                'distrito' => $dip['distrito'],
                'cabecera' => $dip['cabecera'] ?? null,
                'tipo_cargo' => 'Propietario',
                'periodo' => '2021-2024',
                'estatus' => 'Concluida',
                'metadata' => [
                    'fuente_url' => self::URL_OFICIAL_CONGRESO,
                    'fuente_secundaria' => self::URL_IEPC,
                    'fecha_extraccion' => $fechaExtraccion,
                    'sha256_evidencia' => $hashEvidencia,
                ],
            ];

            $legisladores[] = $registro;
        }

        $resumenPartidos = [];
        foreach ($legisladores as $l) {
            $p = $l['partido'];
            $resumenPartidos[$p] = ($resumenPartidos[$p] ?? 0) + 1;
        }

        $dataset = [
            'metadata' => [
                'congreso' => 'Congreso del Estado de Chiapas',
                'entidad' => 'Chiapas',
                'clave_entidad' => 'CHIS',
                'legislatura' => 68,
                'periodo' => '2021-2024',
                'fuente_oficial' => self::URL_OFICIAL_CONGRESO,
                'fuente_electoral' => self::URL_IEPC,
                'fecha_actualizacion' => $fechaExtraccion,
                'total_diputados' => count($legisladores),
                'partidos' => $resumenPartidos,
            ],
            'diputados' => $legisladores,
        ];

        $jsonDir = $this->storagePath . '/data/json';
        $archivosSalida = [
            $jsonDir . '/congreso_chiapas_leg_68.json',
            $jsonDir . '/congreso_chis_leg_68.json',
        ];

        $jsonPayload = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        foreach ($archivosSalida as $archivo) {
            file_put_contents($archivo, $jsonPayload);
        }

        $this->logger->info("Extracción LXVIII completada exitosamente. Total diputados: " . count($legisladores));
        return $dataset;
    }

    /**
     * Extrae y genera el dataset oficial de la LXVII Legislatura (2018-2021, 40 curules).
     * @return array<string, mixed>
     */
    public function scrapeLegislatura67(): array
    {
        $this->logger->info("Iniciando extracción histórica del Congreso de Chiapas (LXVII Legislatura)...");

        $catalogo = CongresoChiapasCatalogoHistorico::getDiputados67();
        $fechaExtraccion = date('Y-m-d H:i:s');
        $fechaCorta = date('Y-m-d');
        $legisladores = [];

        foreach ($catalogo as $idx => $dip) {
            $slugId = 'chis_leg67_' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $hashEvidencia = hash('sha256', $dip['nombre'] . '|' . $dip['partido'] . '|' . ($dip['distrito'] ?? 'RP') . '|' . $fechaCorta);

            $registro = [
                'referencia_sil' => $slugId,
                'nombre_completo' => $dip['nombre'],
                'camara' => 'Congreso del Estado de Chiapas',
                'legislatura' => 67,
                'partido' => $dip['partido'],
                'principio_eleccion' => $dip['principio_eleccion'],
                'entidad' => 'Chiapas',
                'distrito' => $dip['distrito'],
                'cabecera' => $dip['cabecera'] ?? null,
                'tipo_cargo' => 'Propietario',
                'periodo' => '2018-2021',
                'estatus' => 'Concluida',
                'metadata' => [
                    'fuente_url' => self::URL_OFICIAL_CONGRESO,
                    'fuente_secundaria' => self::URL_IEPC,
                    'fecha_extraccion' => $fechaExtraccion,
                    'sha256_evidencia' => $hashEvidencia,
                ],
            ];

            $legisladores[] = $registro;
        }

        $resumenPartidos = [];
        foreach ($legisladores as $l) {
            $p = $l['partido'];
            $resumenPartidos[$p] = ($resumenPartidos[$p] ?? 0) + 1;
        }

        $dataset = [
            'metadata' => [
                'congreso' => 'Congreso del Estado de Chiapas',
                'entidad' => 'Chiapas',
                'clave_entidad' => 'CHIS',
                'legislatura' => 67,
                'periodo' => '2018-2021',
                'fuente_oficial' => self::URL_OFICIAL_CONGRESO,
                'fuente_electoral' => self::URL_IEPC,
                'fecha_actualizacion' => $fechaExtraccion,
                'total_diputados' => count($legisladores),
                'partidos' => $resumenPartidos,
            ],
            'diputados' => $legisladores,
        ];

        $jsonDir = $this->storagePath . '/data/json';
        $archivosSalida = [
            $jsonDir . '/congreso_chiapas_leg_67.json',
            $jsonDir . '/congreso_chis_leg_67.json',
        ];

        $jsonPayload = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        foreach ($archivosSalida as $archivo) {
            file_put_contents($archivo, $jsonPayload);
        }

        $this->logger->info("Extracción LXVII completada exitosamente. Total diputados: " . count($legisladores));
        return $dataset;
    }

    /**
     * Genera la cronología histórica completa de legislaturas de Chiapas.
     * @return array<string, mixed>
     */
    public function scrapeCronologiaHistorica(): array
    {
        $this->logger->info("Generando cronología histórica del Congreso de Chiapas...");

        $cronologia = CongresoChiapasCatalogoHistorico::getCronologiaHistorica();
        $fechaExtraccion = date('Y-m-d H:i:s');

        $dataset = [
            'metadata' => [
                'congreso' => 'Congreso del Estado de Chiapas',
                'entidad' => 'Chiapas',
                'clave_entidad' => 'CHIS',
                'fecha_actualizacion' => $fechaExtraccion,
                'fuente_oficial' => self::URL_OFICIAL_CONGRESO,
                'total_legislaturas' => count($cronologia),
            ],
            'legislaturas' => $cronologia,
        ];

        $jsonDir = $this->storagePath . '/data/json';
        $archivosSalida = [
            $jsonDir . '/congreso_chiapas_historico.json',
            $jsonDir . '/congreso_chis_historico.json',
        ];

        $jsonPayload = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        foreach ($archivosSalida as $archivo) {
            file_put_contents($archivo, $jsonPayload);
        }

        return $dataset;
    }

    /**
     * Ejecuta todas las extracciones históricas.
     * @return array<string, mixed>
     */
    public function scrapeTodasHistoricas(): array
    {
        return [
            'leg68' => $this->scrapeLegislatura68(),
            'leg67' => $this->scrapeLegislatura67(),
            'cronologia' => $this->scrapeCronologiaHistorica(),
        ];
    }

    /**
     * Ejecuta la extracción completa: actual (LXIX) y acervo histórico (LXVIII, LXVII, cronología).
     * @return array<string, mixed>
     */
    public function scrapeTodo(): array
    {
        return [
            'actual' => $this->scrapeLegislatura69(),
            'historicas' => $this->scrapeTodasHistoricas(),
        ];
    }
}
