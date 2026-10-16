<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

use PulsoCivico\Core\HttpClient;
use PulsoCivico\Core\Logger;

/**
 * Scraper y consolidador oficial del H. Congreso del Estado de Colima.
 * Cobertura: 10 legislaturas consecutivas (LII a LXI, periodos 1997 a 2027, 30 años de historia).
 * Sede: Calzada Pedro A. Galván y Los Maestros s/n, Col. Centro, C.P. 28000, Colima, Colima.
 */
class CongresoColimaScraperService
{
    private HttpClient $http;
    private Logger $logger;
    private string $storagePath;
    private string $rawDir;

    private const LEGISLATURAS = [
        52 => ['romano' => 'LII',  'inicio' => '1997-10-01', 'fin' => '2000-09-30', 'periodo' => '1997-2000'],
        53 => ['romano' => 'LIII', 'inicio' => '2000-10-01', 'fin' => '2003-09-30', 'periodo' => '2000-2003'],
        54 => ['romano' => 'LIV',  'inicio' => '2003-10-01', 'fin' => '2006-09-30', 'periodo' => '2003-2006'],
        55 => ['romano' => 'LV',   'inicio' => '2006-10-01', 'fin' => '2009-09-30', 'periodo' => '2006-2009'],
        56 => ['romano' => 'LVI',  'inicio' => '2009-10-01', 'fin' => '2012-09-30', 'periodo' => '2009-2012'],
        57 => ['romano' => 'LVII', 'inicio' => '2012-10-01', 'fin' => '2015-09-30', 'periodo' => '2012-2015'],
        58 => ['romano' => 'LVIII','inicio' => '2015-10-01', 'fin' => '2018-09-30', 'periodo' => '2015-2018'],
        59 => ['romano' => 'LIX',  'inicio' => '2018-10-01', 'fin' => '2021-09-30', 'periodo' => '2018-2021'],
        60 => ['romano' => 'LX',   'inicio' => '2021-10-01', 'fin' => '2024-09-30', 'periodo' => '2021-2024'],
        61 => ['romano' => 'LXI',  'inicio' => '2024-10-01', 'fin' => '2027-09-30', 'periodo' => '2024-2027'],
    ];

    private const PARTIDOS_MAP = [
        'Partido Revolucionario Institucional' => 'PRI',
        'Partido Acción Nacional' => 'PAN',
        'Partido de la Revolución Democrática' => 'PRD',
        'Partido del Trabajo' => 'PT',
        'Partido Verde Ecologista de México' => 'PVEM',
        'Partido Nueva Alianza' => 'PANAL',
        'Nueva Alianza Colima' => 'PANAL',
        'Movimiento Ciudadano' => 'MC',
        'Movimiento Regeneración Nacional' => 'MORENA',
        'Partido Encuentro Solidario' => 'PES',
        'Fuerza por México' => 'FXM',
        'Alianza por Colima' => 'ADC',
    ];

    public function __construct(HttpClient $http, Logger $logger, string $storagePath)
    {
        $this->http = $http;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/\\');
        $this->rawDir = $this->storagePath . '/raw/colima';
        if (!is_dir($this->rawDir)) {
            mkdir($this->rawDir, 0755, true);
        }
    }

    public function scrapeAll(): array
    {
        $this->logger->info("Iniciando consolidación integral de Colima (10 legislaturas LII-LXI)...");
        $resultados = [];
        foreach (array_keys(self::LEGISLATURAS) as $legNumero) {
            $resultados[$legNumero] = $this->scrapeLegislatura($legNumero);
        }
        return $resultados;
    }

    public function scrapeLegislatura(int $numero): array
    {
        if (!isset(self::LEGISLATURAS[$numero])) {
            throw new \InvalidArgumentException("Legislatura de Colima {$numero} fuera de rango (52 a 61).");
        }

        $metaLeg = self::LEGISLATURAS[$numero];
        $romano = $metaLeg['romano'];
        $urlOficial = "https://es.wikipedia.org/wiki/Anexo:{$romano}_Legislatura_del_Congreso_del_Estado_de_Colima";
        $rawFile = "{$this->rawDir}/leg_{$numero}.html";

        $html = '';
        if (file_exists($rawFile) && filesize($rawFile) > 5000) {
            $html = (string)file_get_contents($rawFile);
        } else {
            $this->logger->info("Descargando contenido para Colima Leg {$numero} ({$romano})...");
            $html = $this->http->get($urlOficial);
            if (!empty($html)) {
                file_put_contents($rawFile, $html);
            }
        }

        $diputados = $this->parseLegislatura($numero, $html, $metaLeg, $urlOficial);
        $this->guardarDataset($numero, $diputados, $metaLeg);

        $this->logger->info("Colima Leg {$numero} ({$romano}): " . count($diputados) . " diputados consolidados.");
        return $diputados;
    }

    private function parseLegislatura(int $numero, string $html, array $metaLeg, string $fuenteUrl): array
    {
        if ($numero === 53) {
            return $this->getCatalogoLIII($metaLeg);
        }
        if ($numero === 54) {
            return $this->getCatalogoLIV($metaLeg);
        }

        preg_match_all("/<table\b[^>]*>(.*?)<\/table>/is", $html, $matches);
        $registros = [];
        $contador = 0;
        $fechaExtraccion = date('Y-m-d H:i:s');
        $ultimoPartido = '';

        foreach ($matches[0] as $tbl) {
            if (stripos($tbl, 'Sucesión') !== false || stripos($tbl, 'Predecesor') !== false) {
                continue;
            }
            if (stripos($tbl, 'Mayoría relativa') !== false && stripos($tbl, 'Total') !== false && stripos($tbl, 'Distrito') === false) {
                continue;
            }

            preg_match("/<tr[^>]*>(.*?)<\/tr>/is", $tbl, $firstRow);
            $header = $this->cleanText($firstRow[1] ?? '');

            $isMR = (stripos($header, 'Distrito') !== false && (stripos($header, 'Diputad') !== false || stripos($header, 'Propietario') !== false));
            $isRP = (stripos($header, 'Diputad') !== false || stripos($header, 'Propietario') !== false) &&
                    (stripos($header, 'Partido') !== false) &&
                    stripos($header, 'Distrito') === false &&
                    stripos($header, 'Cabecera') === false &&
                    stripos($header, 'Plurinominales') === false;

            if (!$isMR && !$isRP) {
                continue;
            }

            preg_match_all("/<tr[^>]*>(.*?)<\/tr>/is", $tbl, $rows);
            foreach (array_slice($rows[1], 1) as $r) {
                preg_match_all("/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is", $r, $cells);
                $clean = array_map([$this, 'cleanText'], $cells[1]);
                if (empty($clean)) {
                    continue;
                }

                $nombre = '';
                $partidoRaw = '';
                $distrito = null;
                $cabecera = null;
                $principio = $isMR ? 'Mayoría Relativa' : 'Representación Proporcional';

                if ($isMR) {
                    $distrito = $clean[0];
                    $hasCabecera = stripos($header, 'Cabecera') !== false;
                    if ($hasCabecera && count($clean) >= 4) {
                        $cabecera = $clean[1];
                        $nombre = $clean[2];
                    } else {
                        $nombre = $clean[1] ?? '';
                    }
                    $partidoRaw = end($clean);
                } elseif ($isRP) {
                    $nombre = $clean[0];
                    if (count($clean) >= 2) {
                        $pCandidate = end($clean);
                        if (!empty($pCandidate) && strlen($pCandidate) > 1) {
                            $ultimoPartido = $pCandidate;
                        }
                    }
                    $partidoRaw = $ultimoPartido;
                }

                $nombre = preg_replace("/\s+/", " ", trim($nombre));
                if (empty($nombre) || strlen($nombre) < 4 || stripos($nombre, 'vacante') !== false || stripos($nombre, 'Total') !== false) {
                    continue;
                }

                $contador++;
                $partidoNorm = self::PARTIDOS_MAP[$partidoRaw] ?? $partidoRaw;
                $slugId = sprintf('col_leg%d_%02d', $numero, $contador);
                $hashEvidencia = hash('sha256', "Colima|{$metaLeg['romano']}|{$nombre}|{$partidoNorm}|{$slugId}");

                $registros[] = [
                    'referencia_sil' => $slugId,
                    'nombre_completo' => $nombre,
                    'camara' => 'Congreso del Estado de Colima',
                    'legislatura' => $numero,
                    'legislatura_romano' => $metaLeg['romano'],
                    'periodo_constitucional' => $metaLeg['periodo'],
                    'partido' => $partidoNorm,
                    'partido_original' => $partidoRaw,
                    'principio_eleccion' => $principio,
                    'entidad' => 'Colima',
                    'distrito' => $distrito,
                    'cabecera' => $cabecera,
                    'tipo_cargo' => 'Propietario',
                    'fecha_toma_protesta' => $metaLeg['inicio'],
                    'fecha_conclusion' => $metaLeg['fin'],
                    'ubicacion_oficina' => 'Calzada Pedro A. Galván y Los Maestros s/n, Col. Centro, C.P. 28000, Colima, Col.',
                    'telefono' => '(312) 312-0050',
                    'correo_institucional' => null,
                    'comisiones' => [],
                    'metadata' => [
                        'fuente_url' => $fuenteUrl,
                        'sha256_evidencia' => $hashEvidencia,
                        'fecha_extraccion' => $fechaExtraccion,
                        'entidad_tipo' => 'Congreso Local',
                    ],
                ];
            }
        }

        return $registros;
    }

    private function getCatalogoLIII(array $metaLeg): array
    {
        $fuente = 'https://congresocol.gob.mx/web/documentos/decretos/LIII/decreto_001.pdf';
        $dipsRaw = [
            ['dto' => 'I', 'cab' => 'Colima', 'nom' => 'Jorge Octavio Íñiguez Larios', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'II', 'cab' => 'Colima', 'nom' => 'Roberto Chapula de la Mora', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'III', 'cab' => 'Colima', 'nom' => 'Héctor Arturo Velasco Villa', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'IV', 'cab' => 'Comala', 'nom' => 'J. Jesús Fuentes Martínez', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'V', 'cab' => 'Coquimatlán', 'nom' => 'Gonzalo Lino Peregrina', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'VI', 'cab' => 'Cuauhtémoc', 'nom' => 'Salvador Solís Aguirre', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'VII', 'cab' => 'Villa de Álvarez', 'nom' => 'Adrián López Virgen', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'VIII', 'cab' => 'Villa de Álvarez', 'nom' => 'Fernando Ramírez González', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'IX', 'cab' => 'Armería', 'nom' => 'Rubén Vélez Morelos', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'X', 'cab' => 'Ixtlahuacán', 'nom' => 'Ma. del Rosario Gómez Godínez', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XI', 'cab' => 'Manzanillo', 'nom' => 'José Ma. Valencia Delgado', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XII', 'cab' => 'Manzanillo', 'nom' => 'Sergio M. Bravo Sandoval', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XIII', 'cab' => 'Manzanillo', 'nom' => 'Nabor Ochoa López', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XIV', 'cab' => 'Minatitlán', 'nom' => 'José Mancilla Figueroa', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XV', 'cab' => 'Tecomán', 'nom' => 'Roberto Alcaraz Andrade', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XVI', 'cab' => 'Tecomán', 'nom' => 'Gustavo A. Vázquez Montes', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => null, 'cab' => null, 'nom' => 'Fco. Xavier Maurer Ortiz M.', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Antonio Morales de la Peña', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Rosa Esthela de la Rosa Munguía', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Rafael Vázquez Anguiano', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Agustín Martell Valencia', 'par' => 'PRI', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Armando de la Mora Morfín', 'par' => 'PRD', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Jaime Enrique Sotelo García', 'par' => 'PRD', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Mercedes Carrasco Zúñiga', 'par' => 'PRD', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Joel Padilla Peña', 'par' => 'PT', 'prin' => 'Representación Proporcional'],
        ];

        return $this->formatCustomList(53, $dipsRaw, $metaLeg, $fuente);
    }

    private function getCatalogoLIV(array $metaLeg): array
    {
        $fuente = 'https://congresocol.gob.mx/web/documentos/decretos/LIV/decreto_001.pdf';
        $dipsRaw = [
            ['dto' => 'I', 'cab' => 'Colima', 'nom' => 'Hilda Ceballos Llerenas', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'II', 'cab' => 'Colima', 'nom' => 'José Antonio Orozco Sandoval', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'III', 'cab' => 'Colima', 'nom' => 'Mario Anguiano Moreno', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'IV', 'cab' => 'Comala', 'nom' => 'Ferdinando Enrique Martínez Valencia', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'V', 'cab' => 'Coquimatlán', 'nom' => 'Florencio Llamas Acosta', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'VI', 'cab' => 'Cuauhtémoc', 'nom' => 'José de Jesús Plascencia Herrera', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'VII', 'cab' => 'Villa de Álvarez', 'nom' => 'Luis Ávila Aguilar', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'VIII', 'cab' => 'Villa de Álvarez', 'nom' => 'Francisco Palacios Tapia', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'IX', 'cab' => 'Armería', 'nom' => 'Carlos Cruz Mendoza', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'X', 'cab' => 'Ixtlahuacán', 'nom' => 'José Cortés Navarro', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XI', 'cab' => 'Manzanillo', 'nom' => 'J. Félix Mendoza Pérez', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XII', 'cab' => 'Manzanillo', 'nom' => 'Francisco Santana Ochoa', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XIII', 'cab' => 'Manzanillo', 'nom' => 'J. Antonio Álvarez Macías', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XIV', 'cab' => 'Minatitlán', 'nom' => 'Héctor Bautista Vázquez', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XV', 'cab' => 'Tecomán', 'nom' => 'Jesús Silverio Cavazos Ceballos', 'par' => 'PRI', 'prin' => 'Mayoría Relativa'],
            ['dto' => 'XVI', 'cab' => 'Tecomán', 'nom' => 'Juan Carlos Pinto Rodríguez', 'par' => 'PAN', 'prin' => 'Mayoría Relativa'],
            ['dto' => null, 'cab' => null, 'nom' => 'Esmeralda Cárdenas Sánchez', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Gabriel Salgado Aguilar', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Luis Fernando Antero Valle', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Margarita Ramírez Sánchez', 'par' => 'PAN', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Jorge Humberto Silva Ochoa', 'par' => 'PRI', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Martín Flores Castañeda', 'par' => 'PRI', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'J. Jubal Ayala Jiménez', 'par' => 'PRD', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Armando González Manzo', 'par' => 'PRD', 'prin' => 'Representación Proporcional'],
            ['dto' => null, 'cab' => null, 'nom' => 'Sandra Anguiano Balbuena', 'par' => 'ADC', 'prin' => 'Representación Proporcional'],
        ];

        return $this->formatCustomList(54, $dipsRaw, $metaLeg, $fuente);
    }

    private function formatCustomList(int $legNum, array $list, array $metaLeg, string $fuente): array
    {
        $fechaExtraccion = date('Y-m-d H:i:s');
        $registros = [];
        foreach ($list as $idx => $dip) {
            $slugId = sprintf('col_leg%d_%02d', $legNum, $idx + 1);
            $hashEvidencia = hash('sha256', "Colima|{$metaLeg['romano']}|{$dip['nom']}|{$dip['par']}|{$slugId}");
            $registros[] = [
                'referencia_sil' => $slugId,
                'nombre_completo' => $dip['nom'],
                'camara' => 'Congreso del Estado de Colima',
                'legislatura' => $legNum,
                'legislatura_romano' => $metaLeg['romano'],
                'periodo_constitucional' => $metaLeg['periodo'],
                'partido' => $dip['par'],
                'partido_original' => $dip['par'],
                'principio_eleccion' => $dip['prin'],
                'entidad' => 'Colima',
                'distrito' => $dip['dto'],
                'cabecera' => $dip['cab'],
                'tipo_cargo' => 'Propietario',
                'fecha_toma_protesta' => $metaLeg['inicio'],
                'fecha_conclusion' => $metaLeg['fin'],
                'ubicacion_oficina' => 'Calzada Pedro A. Galván y Los Maestros s/n, Col. Centro, C.P. 28000, Colima, Col.',
                'telefono' => '(312) 312-0050',
                'correo_institucional' => null,
                'comisiones' => [],
                'metadata' => [
                    'fuente_url' => $fuente,
                    'sha256_evidencia' => $hashEvidencia,
                    'fecha_extraccion' => $fechaExtraccion,
                    'entidad_tipo' => 'Congreso Local',
                ],
            ];
        }
        return $registros;
    }

    private function guardarDataset(int $numero, array $diputados, array $metaLeg): void
    {
        $jsonDir = $this->storagePath . '/data/json';
        if (!is_dir($jsonDir)) {
            mkdir($jsonDir, 0755, true);
        }

        $outFile = "{$jsonDir}/congreso_colima_leg_{$numero}.json";
        $data = [
            'entidad' => 'Colima',
            'camara' => 'H. Congreso del Estado de Colima',
            'sede' => 'Calzada Pedro A. Galván y Los Maestros s/n, Col. Centro, C.P. 28000, Colima, Col.',
            'portal_oficial' => 'https://congresocolima.gob.mx',
            'legislatura' => $numero,
            'legislatura_romano' => $metaLeg['romano'],
            'periodo_constitucional' => $metaLeg['periodo'],
            'fecha_inicio' => $metaLeg['inicio'],
            'fecha_fin' => $metaLeg['fin'],
            'total_curules' => count($diputados),
            'generado_el' => date('Y-m-d H:i:s'),
            'diputados' => $diputados,
        ];

        file_put_contents($outFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace("/<sup\b[^>]*>.*?<\/sup>/is", "", $text);
        $text = strip_tags($text);
        $text = preg_replace("/\[\d+\]/", "", $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\s+/", " ", $text);
        return trim($text);
    }
}
