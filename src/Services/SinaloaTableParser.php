<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * SinaloaTableParser: Parser especializado en estructuras de tablas legislativas de Sinaloa.
 */
class SinaloaTableParser
{
    private const ROMANOS = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
        'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10,
        'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15,
        'XVI' => 16, 'XVII' => 17, 'XVIII' => 18, 'XIX' => 19, 'XX' => 20,
        'XXI' => 21, 'XXII' => 22, 'XXIII' => 23, 'XXIV' => 24
    ];

    public static function parseHtml(string $html, array $fotosOficiales = []): array
    {
        $items = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);
        $tables = $xpath->query('//table[contains(@class, "wikitable")]');

        if ($tables->length >= 2) {
            // T1 = Mayoría Relativa
            $items = array_merge($items, self::parseTableRows($tables->item(1), $xpath, true, $fotosOficiales));
        }

        if ($tables->length >= 3) {
            // T2 = Representación Proporcional
            $items = array_merge($items, self::parseTableRows($tables->item(2), $xpath, false, $fotosOficiales));
        }

        return $items;
    }

    private static function parseTableRows(\DOMNode $table, \DOMXPath $xpath, bool $esMR, array $fotosOficiales): array
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
            elseif (str_contains($txt, 'diputad') || str_contains($txt, 'nombre')) $colMap['nombre'] = $cIdx;
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
            } elseif ($esMR && count($vals) >= 3) {
                $nombreRaw = $vals[2];
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
                $distRaw = (isset($colMap['distrito']) && isset($vals[$colMap['distrito']])) ? $vals[$colMap['distrito']] : ($vals[0] ?? '');
                $distrito = self::normalizarDistrito($distRaw);
                $cabecera = (isset($colMap['cabecera']) && isset($vals[$colMap['cabecera']])) ? self::limpiarTexto($vals[$colMap['cabecera']]) : null;
            }

            $suplente = (isset($colMap['suplente']) && isset($vals[$colMap['suplente']])) ? self::limpiarTexto($vals[$colMap['suplente']]) : null;

            $extra = self::buscarFotoOficial($nombre, $fotosOficiales);

            $items[] = [
                'nombre_completo' => $nombre,
                'principio_eleccion' => $esMR ? 'Mayoría Relativa' : 'Representación Proporcional',
                'distrito' => $distrito,
                'cabecera' => $cabecera,
                'partido' => $partido,
                'suplente' => $suplente,
                'foto_url' => $extra['foto_url'] ?? null,
                'perfil_url' => $extra['perfil_url'] ?? null,
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
        if (stripos($raw, 'Sinaloense') !== false || stripos($raw, 'PAS') !== false) return 'PAS';
        if (stripos($raw, 'Nueva Alianza') !== false || stripos($raw, 'PANAL') !== false) return 'NUEVA ALIANZA';
        if (stripos($raw, 'Encuentro') !== false || stripos($raw, 'PES') !== false) return 'PES';
        return $raw !== '' ? $raw : 'Sin Partido';
    }

    public static function normalizarClave(string $str): string
    {
        $str = mb_strtolower(trim($str));
        $str = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $str);
        return preg_replace('/[^a-z]/', '', $str) ?? '';
    }

    private static function normalizarDistrito(string $raw): ?string
    {
        $raw = self::limpiarTexto($raw);
        if (preg_match('/^([IVXLCDM]+|\d+)$/i', $raw, $m)) {
            $val = trim($m[1]);
            return is_numeric($val) ? (string)(int)$val : (string)(self::ROMANOS[strtoupper($val)] ?? $val);
        }
        return null;
    }

    private static function buscarFotoOficial(string $nombre, array $fotosOficiales): ?array
    {
        if (empty($fotosOficiales)) return null;
        $key = self::normalizarClave($nombre);
        if (isset($fotosOficiales[$key])) return $fotosOficiales[$key];
        foreach ($fotosOficiales as $k => $info) {
            if (str_contains($k, $key) || str_contains($key, $k)) return $info;
        }
        return null;
    }
}
