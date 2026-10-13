<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * SonoraTableParser: Parser especializado en estructuras de tablas legislativas de Sonora (Wikipedia/Gacetas).
 */
class SonoraTableParser
{
    private const ROMANOS = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
        'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10,
        'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15,
        'XVI' => 16, 'XVII' => 17, 'XVIII' => 18, 'XIX' => 19, 'XX' => 20,
        'XXI' => 21, 'XXII' => 22
    ];

    public static function parseHtml(string $html): array
    {
        $items = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table[contains(@class, "wikitable")]');

        if ($tables->length >= 3) {
            // T1 = Mayoría Relativa
            $items = array_merge($items, self::parseTableRows($tables->item(1), $xpath, true));
            // T2 = Representación Proporcional
            $items = array_merge($items, self::parseTableRows($tables->item(2), $xpath, false));
        }

        return $items;
    }

    private static function parseTableRows(\DOMNode $table, \DOMXPath $xpath, bool $esMR): array
    {
        $rows = $xpath->query('.//tr', $table);
        if ($rows->length < 2) return [];

        $ths = $xpath->query('.//th', $rows->item(0));
        $colMap = [];
        $cIdx = 0;
        foreach ($ths as $th) {
            $colspan = (int)($th->getAttribute('colspan') ?: 1);
            $txt = mb_strtolower(trim($th->textContent));
            if (str_contains($txt, 'distrito') || str_contains($txt, 'cabecera')) $colMap['distrito'] = $cIdx;
            elseif (str_contains($txt, 'suplente')) $colMap['suplente'] = $cIdx;
            elseif (str_contains($txt, 'propietario') || str_contains($txt, 'diputad') || str_contains($txt, 'nombre')) $colMap['nombre'] = $cIdx;
            elseif (str_contains($txt, 'partido')) $colMap['partido'] = $cIdx;
            $cIdx += $colspan;
        }

        $items = [];
        for ($i = 1; $i < $rows->length; $i++) {
            $r = $rows->item($i);
            $tds = $xpath->query('.//td', $r);
            if ($tds->length === 0) continue;

            $vals = [];
            foreach ($tds as $td) {
                $vals[] = trim($td->textContent);
            }

            $nombreRaw = '';
            if (isset($colMap['nombre']) && isset($vals[$colMap['nombre']])) {
                $nombreRaw = $vals[$colMap['nombre']];
            } elseif ($esMR && count($vals) >= 2) {
                $nombreRaw = $vals[0];
            } elseif (!$esMR && count($vals) >= 1) {
                $nombreRaw = $vals[0];
            }

            $nombre = self::limpiarTexto($nombreRaw);
            if ($nombre === '' || mb_strtolower($nombre) === 'total' || is_numeric($nombre)) continue;

            $partidoRaw = (isset($colMap['partido']) && isset($vals[$colMap['partido']])) ? $vals[$colMap['partido']] : end($vals);
            $partido = self::normalizarPartido($partidoRaw);

            $distrito = null;
            $cabecera = null;
            if ($esMR) {
                $distRaw = (isset($colMap['distrito']) && isset($vals[$colMap['distrito']])) ? $vals[$colMap['distrito']] : ($vals[1] ?? $vals[0]);
                $distInfo = self::extraerDistritoCabecera($distRaw);
                $distrito = $distInfo['distrito'];
                $cabecera = $distInfo['cabecera'];
            }

            $suplente = (isset($colMap['suplente']) && isset($vals[$colMap['suplente']])) ? self::limpiarTexto($vals[$colMap['suplente']]) : null;
            if ($suplente !== null && (mb_strtolower($suplente) === 'ninguno' || $suplente === '')) {
                $suplente = null;
            }

            $items[] = [
                'nombre_completo' => $nombre,
                'principio_eleccion' => $esMR ? 'Mayoría Relativa' : 'Representación Proporcional',
                'distrito' => $distrito,
                'cabecera' => $cabecera,
                'partido' => $partido,
                'suplente' => $suplente,
            ];
        }

        return $items;
    }

    public static function limpiarTexto(string $str): string
    {
        $str = preg_replace('/\[\[([^|\]]+\|)?([^\]]+)\]\]/', '$2', $str);
        $str = preg_replace('/<[^>]*>/', '', $str);
        $str = preg_replace('/\[\d+\]/', '', $str);
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    public static function normalizarPartido(string $raw): string
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw));
        if (stripos($raw, 'Regeneración') !== false || stripos($raw, 'Morena') !== false) return 'MORENA';
        if (stripos($raw, 'Acción Nacional') !== false || stripos($raw, 'PAN') !== false) return 'PAN';
        if (stripos($raw, 'Revolucionario Institucional') !== false || stripos($raw, 'PRI') !== false) return 'PRI';
        if (stripos($raw, 'del Trabajo') !== false || $raw === 'PT') return 'PT';
        if (stripos($raw, 'Verde') !== false || stripos($raw, 'PVEM') !== false) return 'PVEM';
        if (stripos($raw, 'Revolución Democrática') !== false || stripos($raw, 'PRD') !== false) return 'PRD';
        if (stripos($raw, 'Ciudadano') !== false || stripos($raw, 'MC') !== false) return 'MC';
        if (stripos($raw, 'Nueva Alianza') !== false || stripos($raw, 'PANAL') !== false) return 'NUEVA ALIANZA';
        if (stripos($raw, 'Encuentro') !== false || stripos($raw, 'PES') !== false) return 'PES';
        return $raw !== '' ? $raw : 'Sin Partido';
    }

    private static function extraerDistritoCabecera(string $raw): array
    {
        $raw = self::limpiarTexto($raw);
        $distrito = null;
        $cabecera = null;

        if (preg_match('/^(Distrito\s+)?([IVXLCDM]+|\d+)(\s*[-–—:]\s*|\s+)(.*)$/iu', $raw, $m)) {
            $val = trim($m[2]);
            $distrito = is_numeric($val) ? (string)(int)$val : (string)(self::ROMANOS[strtoupper($val)] ?? $val);
            $cabecera = trim($m[4]);
        } elseif (preg_match('/^([IVXLCDM]+|\d+)$/iu', $raw, $m)) {
            $val = trim($m[1]);
            $distrito = is_numeric($val) ? (string)(int)$val : (string)(self::ROMANOS[strtoupper($val)] ?? $val);
        } else {
            $cabecera = $raw;
        }

        return ['distrito' => $distrito, 'cabecera' => $cabecera];
    }
}
