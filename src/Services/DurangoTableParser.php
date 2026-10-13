<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * DurangoTableParser: Parser especializado en estructuras de tablas legislativas de Durango.
 */
class DurangoTableParser
{
    private const ROMANOS = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
        'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10,
        'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15,
        'XVI' => 16, 'XVII' => 17, 'XVIII' => 18, 'XIX' => 19, 'XX' => 20
    ];

    public static function parseHtml(string $html, array $datosWeb): array
    {
        $items = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table[contains(@class, "wikitable")]');

        foreach ($tables as $t) {
            $firstTh = $xpath->query('.//th', $t);
            $thText = '';
            foreach ($firstTh as $th) {
                $thText .= ' ' . mb_strtolower(trim($th->textContent));
            }

            if (str_contains($thText, 'distrito') && str_contains($thText, 'diputad')) {
                $items = array_merge($items, self::parseRows($t, $xpath, true, $datosWeb));
            } elseif (!str_contains($thText, 'distrito') && str_contains($thText, 'diputad') && !str_contains($thText, 'total')) {
                $items = array_merge($items, self::parseRows($t, $xpath, false, $datosWeb));
            }
        }

        return $items;
    }

    private static function parseRows(\DOMNode $table, \DOMXPath $xpath, bool $esMR, array $datosWeb): array
    {
        $rows = $xpath->query('.//tr', $table);
        if ($rows->length < 2) return [];

        $ths = $xpath->query('.//th', $rows->item(0));
        $colMap = [];
        $cIdx = 0;
        foreach ($ths as $th) {
            $colspan = (int)($th->getAttribute('colspan') ?: 1);
            $txt = mb_strtolower(trim($th->textContent));
            if (str_contains($txt, 'distrito')) $colMap['distrito'] = $cIdx;
            elseif (str_contains($txt, 'cabecera')) $colMap['cabecera'] = $cIdx;
            elseif (str_contains($txt, 'suplente')) $colMap['suplente'] = $cIdx;
            elseif (str_contains($txt, 'diputad')) $colMap['diputado'] = $cIdx;
            elseif (str_contains($txt, 'partido')) $colMap['partido'] = $cIdx;
            $cIdx += $colspan;
        }

        $items = [];
        for ($i = 1; $i < $rows->length; $i++) {
            $tds = $xpath->query('.//td', $rows->item($i));
            if ($tds->length === 0) continue;

            $vals = [];
            foreach ($tds as $td) {
                $vals[] = trim($td->textContent);
            }

            $nombreRaw = '';
            if (isset($colMap['diputado']) && isset($vals[$colMap['diputado']])) {
                $nombreRaw = $vals[$colMap['diputado']];
            } elseif ($esMR && count($vals) >= 2) {
                $nombreRaw = $vals[1];
            } elseif (!$esMR && count($vals) >= 1) {
                $nombreRaw = $vals[0];
            }

            $nombre = self::limpiarNombre($nombreRaw);
            if ($nombre === '' || mb_strtolower($nombre) === 'total') continue;

            $partidoRaw = (isset($colMap['partido']) && isset($vals[$colMap['partido']])) ? $vals[$colMap['partido']] : end($vals);
            $partido = self::normalizarPartido($partidoRaw);

            $distVal = ($esMR && isset($colMap['distrito']) && isset($vals[$colMap['distrito']])) ? $vals[$colMap['distrito']] : ($esMR ? ($vals[0] ?? '') : null);
            $distrito = $distVal !== null ? (is_numeric($distVal) ? (string)(int)$distVal : (string)(self::ROMANOS[strtoupper(trim($distVal))] ?? $distVal)) : null;

            $cabecera = (isset($colMap['cabecera']) && isset($vals[$colMap['cabecera']])) ? self::limpiarNombre($vals[$colMap['cabecera']]) : null;
            $suplente = (isset($colMap['suplente']) && isset($vals[$colMap['suplente']])) ? self::limpiarNombre($vals[$colMap['suplente']]) : null;

            $extra = self::buscarCoincidenciaWeb($nombre, $datosWeb);

            $items[] = [
                'nombre_completo' => $nombre,
                'principio_eleccion' => $esMR ? 'Mayoría Relativa' : 'Representación Proporcional',
                'distrito' => $distrito,
                'cabecera' => $cabecera,
                'partido' => $partido,
                'suplente' => $suplente,
                'foto_url' => $extra['foto_url'] ?? null,
                'email' => $extra['email'] ?? null,
                'fuente_url' => $extra['distrito_url'] ?? null,
            ];
        }

        return $items;
    }

    public static function limpiarNombre(string $nombre): string
    {
        $nombre = preg_replace('/^DIP\.\s*/iu', '', $nombre);
        $nombre = preg_replace('/\[\[([^|\]]+\|)?([^\]]+)\]\]/', '$2', $nombre);
        $nombre = preg_replace('/<[^>]*>/', '', $nombre);
        $nombre = preg_replace('/\[\d+\]/', '', $nombre);
        return trim(preg_replace('/\s+/', ' ', $nombre));
    }

    public static function normalizarClave(string $str): string
    {
        $str = mb_strtolower(trim($str));
        $str = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $str);
        return preg_replace('/[^a-z]/', '', $str) ?? '';
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
        if (stripos($raw, 'Duranguense') !== false || stripos($raw, 'PD') !== false) return 'PD';
        if (stripos($raw, 'Nueva Alianza') !== false || stripos($raw, 'PANAL') !== false) return 'NUEVA ALIANZA';
        return $raw !== '' ? $raw : 'Sin Partido';
    }

    private static function buscarCoincidenciaWeb(string $nombre, array $datosWeb): ?array
    {
        if (empty($datosWeb)) return null;
        $key = self::normalizarClave($nombre);
        if (isset($datosWeb[$key])) return $datosWeb[$key];
        foreach ($datosWeb as $k => $info) {
            if (str_contains($k, $key) || str_contains($key, $k)) return $info;
        }
        return null;
    }
}
