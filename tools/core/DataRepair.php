<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

class DataRepair
{
    /**
     * Repara la información de escolaridad y grados académicos en todos los JSON
     * a partir de los perfiles HTML oficiales cacheados en storage/raw/html/.
     */
    public static function repairStudies(string $rootDir): array
    {
        $rawHtmlDir = $rootDir . '/storage/raw/html';
        $jsonDir = $rootDir . '/storage/data/json';

        if (!is_dir($rawHtmlDir)) {
            return ['ok' => false, 'error' => "Directorio $rawHtmlDir no existe."];
        }

        // 1. Indexar perfiles HTML cacheados
        $htmlFiles = glob($rawHtmlDir . '/profile_*.html');
        $htmlLookup = []; // ref -> data

        foreach ($htmlFiles as $hPath) {
            if (!preg_match('/profile_(\d+)\.html$/', $hPath, $m)) {
                continue;
            }
            $ref = $m[1];
            $parsed = self::parseHtmlProfile($hPath);
            if ($parsed) {
                $htmlLookup[$ref] = $parsed;
            }
        }

        // 2. Procesar y enriquecer todos los JSON del directorio storage/data/json
        $jsonFiles = glob($jsonDir . '/*.json');
        $updatedFiles = 0;
        $totalPoliticosReparados = 0;

        foreach ($jsonFiles as $jf) {
            $baseName = basename($jf);
            // Solo procesar archivos federales y de entidad de legisladores
            if (!str_starts_with($baseName, 'congreso_union_leg_') && !str_starts_with($baseName, 'legisladores_entidad_')) {
                continue;
            }

            $content = (string)file_get_contents($jf);
            $data = json_decode($content, true);
            if (!is_array($data)) {
                continue;
            }

            $modificado = false;

            // Función recursiva o iterativa para procesar listas de legisladores
            foreach (['diputados', 'senadores', 'legisladores'] as $key) {
                if (!isset($data[$key]) || !is_array($data[$key])) {
                    continue;
                }

                foreach ($data[$key] as &$pol) {
                    $ref = (string)($pol['referencia_sil'] ?? '');
                    if (!$ref || !isset($htmlLookup[$ref])) {
                        continue;
                    }

                    $info = $htmlLookup[$ref];
                    $cambio = false;

                    // Reparar ultimo_grado_estudios
                    $gradoActual = (string)($pol['ultimo_grado_estudios'] ?? '');
                    if (str_contains($gradoActual, 'Ã') || empty($gradoActual) || in_array(mb_strtolower($gradoActual), ['no disponible', 'sin registro', 'n/a', 'ninguno'])) {
                        if (!empty($info['ultimo_grado_estudios'])) {
                            $pol['ultimo_grado_estudios'] = $info['ultimo_grado_estudios'];
                            $cambio = true;
                        }
                    }

                    // Reparar preparacion_academica
                    $prepActual = (string)($pol['preparacion_academica'] ?? '');
                    if (str_contains($prepActual, 'Ã') || empty($prepActual) || in_array(mb_strtolower($prepActual), ['no disponible', 'sin registro', 'n/a', 'ninguno'])) {
                        if (!empty($info['preparacion_academica'])) {
                            $pol['preparacion_academica'] = $info['preparacion_academica'];
                            $cambio = true;
                        }
                    }

                    // Reparar escolaridad_detalle
                    if (empty($pol['escolaridad_detalle']) || (is_array($pol['escolaridad_detalle']) && str_contains(json_encode($pol['escolaridad_detalle']), 'Ã'))) {
                        if (!empty($info['escolaridad_detalle'])) {
                            $pol['escolaridad_detalle'] = $info['escolaridad_detalle'];
                            $cambio = true;
                        }
                    }

                    // Inferencia de grado si aún está vacío pero tiene escolaridad_detalle
                    $gradoActualFinal = trim((string)($pol['ultimo_grado_estudios'] ?? ''));
                    if (empty($gradoActualFinal) && !empty($pol['escolaridad_detalle'])) {
                        $inferred = self::inferGradoFromDetalle($pol['escolaridad_detalle']);
                        if ($inferred) {
                            $pol['ultimo_grado_estudios'] = $inferred;
                            $cambio = true;
                        }
                    }

                    if ($cambio) {
                        $totalPoliticosReparados++;
                        $modificado = true;
                    }
                }
                unset($pol);
            }

            if ($modificado) {
                file_put_contents($jf, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $updatedFiles++;
            }
        }

        return [
            'ok' => true,
            'html_profiles_indexed' => count($htmlLookup),
            'files_updated' => $updatedFiles,
            'politicians_enriched' => $totalPoliticosReparados,
        ];
    }

    /**
     * Parsea un archivo de perfil HTML y extrae estudios de forma resiliente.
     */
    private static function parseHtmlProfile(string $filePath): ?array
    {
        $raw = @file_get_contents($filePath);
        if (!$raw || strlen($raw) < 500) {
            return null;
        }

        // Normalizar encoding si no es UTF-8 válido
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $raw);
        $xpath = new \DOMXPath($doc);

        $ultimoGrado = null;
        $prepAcad = null;
        $escolaridadDetalle = [];

        // 1. Extraer de la tabla de datos personales
        $filas = $xpath->query("//tr[td/b or td/font/b]");
        foreach ($filas as $fila) {
            $tds = $xpath->query("./td", $fila);
            if ($tds->length < 2) continue;
            $etiqueta = mb_strtolower(self::cleanUtf8($tds->item(0)->textContent ?? ''));
            $valor = self::cleanUtf8($tds->item(1)->textContent ?? '');

            if (!$valor || in_array(mb_strtolower($valor), ['no disponible', 'sin registro', 'n/a', 'ninguno', ''])) {
                continue;
            }

            if (str_contains($etiqueta, 'grado de estudios')) {
                $ultimoGrado = $valor;
            } elseif (str_contains($etiqueta, 'preparaci') && str_contains($etiqueta, 'acad')) {
                $prepAcad = $valor;
            }
        }

        // 2. Extraer de tablas de sección (TRAYECTORIA ACADÉMICA o ESCOLARIDAD)
        $sectionHeaders = $xpath->query("//table[contains(@class, 'datosL2')]");
        foreach ($sectionHeaders as $headerTable) {
            $headerText = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $headerTable->textContent ?? '')));

            if (!str_contains($headerText, 'ACAD') && !str_contains($headerText, 'ESCOLARIDAD') && !str_contains($headerText, 'ESTUDIOS')) {
                continue;
            }

            $dataTable = $xpath->query("./following-sibling::table[contains(@class, 'TFtable2')][1]", $headerTable)->item(0);
            if (!$dataTable) {
                continue;
            }

            $rows = $xpath->query(".//tr", $dataTable);
            foreach ($rows as $rIdx => $row) {
                if ($rIdx === 0) continue; // Cabecera Del año | Al año | Experiencia

                $parentTable = $row->parentNode;
                while ($parentTable && $parentTable->nodeName !== 'table') {
                    $parentTable = $parentTable->parentNode;
                }
                if ($parentTable !== $dataTable) {
                    continue;
                }

                $cells = $xpath->query("./td", $row);
                if ($cells->length >= 3) {
                    $del = self::cleanUtf8($cells->item(0)->textContent ?? '');
                    $al  = self::cleanUtf8($cells->item(1)->textContent ?? '');
                    $exp = self::cleanUtf8($cells->item(2)->textContent ?? '');
                    if (!empty($exp)) {
                        $escolaridadDetalle[] = [
                            'del_ano' => $del ?: null,
                            'al_ano' => $al ?: null,
                            'experiencia' => $exp,
                        ];
                    }
                }
            }
        }

        return [
            'ultimo_grado_estudios' => $ultimoGrado,
            'preparacion_academica' => $prepAcad,
            'escolaridad_detalle' => $escolaridadDetalle,
        ];
    }

    /**
     * Limpia y remueve artefactos de doble codificación UTF-8.
     */
    public static function cleanUtf8(?string $str): ?string
    {
        if ($str === null || $str === '') return $str;
        $s = trim(preg_replace('/\s+/', ' ', $str));
        if (str_contains($s, 'Ã')) {
            $try = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
            if ($try && mb_check_encoding($try, 'UTF-8')) {
                $s = $try;
            }
        }
        return $s;
    }

    /**
     * Infiere el grado académico a partir de descripciones en escolaridad_detalle.
     */
    private static function inferGradoFromDetalle(array $detalle): ?string
    {
        $maxGrado = null;
        $maxPeso = -1;

        $pesos = [
            'doctorado' => 6,
            'doctor' => 6,
            'maestr' => 5,
            'especialidad' => 5,
            'posgrado' => 5,
            'licenciatura' => 4,
            'licenciado' => 4,
            'ingenier' => 4,
            'abogado' => 4,
            'médico' => 4,
            'medico' => 4,
            'trunca' => 3,
            'pasante' => 3,
            'bachillerato' => 2,
            'preparatoria' => 2,
            'técnico' => 2,
            'tecnico' => 2,
            'secundaria' => 1,
            'primaria' => 1,
        ];

        foreach ($detalle as $item) {
            $exp = mb_strtolower($item['experiencia'] ?? '');
            foreach ($pesos as $keyword => $peso) {
                if (str_contains($exp, $keyword)) {
                    if ($peso > $maxPeso) {
                        $maxPeso = $peso;
                        if ($peso === 6) $maxGrado = 'Doctorado';
                        elseif ($peso === 5) $maxGrado = 'Maestría';
                        elseif ($peso === 4) $maxGrado = 'Licenciatura';
                        elseif ($peso === 3) $maxGrado = 'Carrera Trunca';
                        elseif ($peso === 2) $maxGrado = 'Bachillerato / Técnico';
                        elseif ($peso === 1) $maxGrado = 'Educación Básica';
                    }
                    break;
                }
            }
        }

        return $maxGrado;
    }
}
