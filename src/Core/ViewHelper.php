<?php
declare(strict_types=1);

namespace PulsoCivico\Core;

/**
 * ViewHelper: Funciones de renderizado reutilizables para la UI y SEO.
 */
class ViewHelper
{
    /**
     * Renderiza badges oficiales de partidos políticos con logos vectoriales SVG.
     */
    public static function renderPartyBadge(string $partido): string
    {
        $p = trim($partido);
        $upper = strtoupper($p);

        if (str_contains($upper, 'MORENA')) {
            $icon = '<svg class="party-icon" viewBox="0 0 30 20" width="24" height="16" fill="none"><rect width="30" height="20" rx="4" fill="#8B1C3F"/><text x="15" y="14" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.3">morena</text></svg>';
            $label = 'Morena';
            $bg = '#8B1C3F';
            $color = '#FFFFFF';
        } elseif (str_contains($upper, 'PAN')) {
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#003893"/><circle cx="10" cy="10" r="7" fill="#FFFFFF"/><text x="10" y="12.5" fill="#003893" font-size="6.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.4">PAN</text></svg>';
            $label = 'PAN';
            $bg = '#003893';
            $color = '#FFFFFF';
        } elseif (str_contains($upper, 'VERDE') || str_contains($upper, 'PVEM')) {
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#009E49"/><path d="M10 3 C13 6 16 9 16 13 C16 16.5 13 18.5 10 18.5 C7 18.5 4 16.5 4 13 C4 9 7 6 10 3 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="12.5" fill="#FFFFFF" font-size="5.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PVEM</text></svg>';
            $label = 'PVEM';
            $bg = '#009E49';
            $color = '#FFFFFF';
        } elseif (str_contains($upper, 'PRI')) {
            $hash = substr(md5($p), 0, 6);
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><clipPath id="pri-h-' . $hash . '"><rect width="20" height="20" rx="4"/></clipPath><g clip-path="url(#pri-h-' . $hash . ')"><rect x="0" y="0" width="6.6" height="20" fill="#008037"/><rect x="6.6" y="0" width="6.8" height="20" fill="#FFFFFF"/><rect x="13.4" y="0" width="6.6" height="20" fill="#C8102E"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRI</text></g></svg>';
            $label = 'PRI';
            $bg = '#C8102E';
            $color = '#FFFFFF';
        } elseif (str_contains($upper, 'PT') || str_contains($upper, 'TRABAJO')) {
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#DA291C"/><polygon points="10,2 11.5,6.5 16,6.5 12.5,9.5 14,14 10,11.5 6,14 7.5,9.5 4,6.5 8.5,6.5" fill="#FFCC00" fill-opacity="0.4"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PT</text></svg>';
            $label = 'PT';
            $bg = '#DA291C';
            $color = '#FFFFFF';
        } elseif (str_contains($upper, 'MC') || str_contains($upper, 'CIUDADANO')) {
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#FF8300"/><path d="M3 10 Q10 5 17 10 Q10 14 3 10 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">MC</text></svg>';
            $label = 'Movimiento Ciudadano';
            $bg = '#FF8300';
            $color = '#FFFFFF';
        } elseif (str_contains($upper, 'PES') || str_contains($upper, 'ENCUENTRO')) {
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#582C83"/><text x="10" y="12.5" fill="#FFFFFF" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PES</text></svg>';
            $label = 'PES';
            $bg = '#582C83';
            $color = '#FFFFFF';
        } elseif (str_contains($upper, 'PRD')) {
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#FFD100"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRD</text></svg>';
            $label = 'PRD';
            $bg = '#FFD100';
            $color = '#1C1C1E';
        } else {
            $icon = '<svg class="party-icon" viewBox="0 0 20 20" width="16" height="16" fill="none"><rect width="20" height="20" rx="4" fill="#6C757D"/><circle cx="10" cy="8" r="3.5" fill="#FFFFFF"/><path d="M5 18 C5 14.5 7.5 13 10 13 C12.5 13 15 14.5 15 18 Z" fill="#FFFFFF"/></svg>';
            $label = $p !== '' ? $p : 'Independiente';
            $bg = '#6C757D';
            $color = '#FFFFFF';
        }

        return sprintf(
            '<span class="party-badge" title="%s" data-party-color="%s">%s<span>%s</span></span>',
            htmlspecialchars($label),
            $bg,
            $icon,
            htmlspecialchars($label)
        );
    }

    /**
     * Devuelve el color hexadecimal oficial de un partido.
     */
    public static function getPartyColor(string $partido): string
    {
        $upper = strtoupper(trim($partido));
        if (str_contains($upper, 'MORENA')) return '#8B1C3F';
        if (str_contains($upper, 'PAN') && !str_contains($upper, 'PANAL')) return '#003893';
        if (str_contains($upper, 'VERDE') || str_contains($upper, 'PVEM')) return '#009E49';
        if (str_contains($upper, 'PRI')) return '#C8102E';
        if (str_contains($upper, 'PT') || str_contains($upper, 'TRABAJO')) return '#DA291C';
        if (str_contains($upper, 'MC') || str_contains($upper, 'CIUDADANO') || str_contains($upper, 'CONVERGENCIA')) return '#FF8300';
        if (str_contains($upper, 'PES') || str_contains($upper, 'ENCUENTRO')) return '#582C83';
        if (str_contains($upper, 'PRD')) return '#FFD100';
        if (str_contains($upper, 'PANAL') || str_contains($upper, 'NUEVA ALIANZA')) return '#00A896';
        if (str_contains($upper, 'INDEPENDIENTE') || str_contains($upper, 'SIN PARTIDO')) return '#64748B';
        return '#6C757D';
    }

    /**
     * Devuelve el nombre canónico estandarizado de un partido político.
     */
    public static function getCanonicalPartyName(string $partido): string
    {
        $p = trim($partido);
        $upper = strtoupper($p);

        if (str_contains($upper, 'MORENA')) return 'Morena';
        if (str_contains($upper, 'PAN') && !str_contains($upper, 'PANAL')) return 'PAN';
        if (str_contains($upper, 'VERDE') || str_contains($upper, 'PVEM')) return 'PVEM';
        if (str_contains($upper, 'PRI')) return 'PRI';
        if (str_contains($upper, 'PT') || str_contains($upper, 'TRABAJO')) return 'PT';
        if (str_contains($upper, 'MC') || str_contains($upper, 'CIUDADANO') || str_contains($upper, 'CONVERGENCIA')) return 'Movimiento Ciudadano';
        if (str_contains($upper, 'PES') || str_contains($upper, 'ENCUENTRO')) return 'PES';
        if (str_contains($upper, 'PRD')) return 'PRD';
        if (str_contains($upper, 'PANAL') || str_contains($upper, 'NUEVA ALIANZA')) return 'Nueva Alianza';
        if (str_contains($upper, 'INDEPENDIENTE') || str_contains($upper, 'SIN PARTIDO')) return 'Independiente';

        return $p !== '' ? $p : 'Independiente';
    }

    /**
     * Devuelve un micro-icono vectorial SVG del partido político.
     */
    public static function getPartyMicroIcon(string $partido): string
    {
        $canonical = self::getCanonicalPartyName($partido);

        return match ($canonical) {
            'Morena' => '<svg class="party-icon" viewBox="0 0 30 20" width="16" height="11" fill="none"><rect width="30" height="20" rx="3" fill="#8B1C3F"/><text x="15" y="14" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.3">morena</text></svg>',
            'PAN' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#003893"/><circle cx="10" cy="10" r="7" fill="#FFFFFF"/><text x="10" y="12.5" fill="#003893" font-size="6.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle" letter-spacing="-0.4">PAN</text></svg>',
            'PRI' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect x="0" y="0" width="6.6" height="20" rx="2" fill="#008037"/><rect x="6.6" y="0" width="6.8" height="20" fill="#FFFFFF"/><rect x="13.4" y="0" width="6.6" height="20" rx="2" fill="#C8102E"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRI</text></svg>',
            'PRD' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#FFD100"/><text x="10" y="12.5" fill="#1C1C1E" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PRD</text></svg>',
            'PVEM' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#009E49"/><path d="M10 3 C13 6 16 9 16 13 C16 16.5 13 18.5 10 18.5 C7 18.5 4 16.5 4 13 C4 9 7 6 10 3 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="12.5" fill="#FFFFFF" font-size="5.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PVEM</text></svg>',
            'PT' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#DA291C"/><polygon points="10,2 11.5,6.5 16,6.5 12.5,9.5 14,14 10,11.5 6,14 7.5,9.5 4,6.5 8.5,6.5" fill="#FFCC00" fill-opacity="0.4"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PT</text></svg>',
            'Movimiento Ciudadano' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#FF8300"/><path d="M3 10 Q10 5 17 10 Q10 14 3 10 Z" fill="#FFFFFF" fill-opacity="0.3"/><text x="10" y="13" fill="#FFFFFF" font-size="7" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">MC</text></svg>',
            'PES' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#582C83"/><text x="10" y="12.5" fill="#FFFFFF" font-size="6" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">PES</text></svg>',
            'Nueva Alianza' => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#00A896"/><text x="10" y="12.5" fill="#FFFFFF" font-size="5.5" font-weight="900" font-family="system-ui, -apple-system, sans-serif" text-anchor="middle">NA</text></svg>',
            default => '<svg class="party-icon" viewBox="0 0 20 20" width="12" height="12" fill="none"><rect width="20" height="20" rx="3" fill="#6C757D"/><circle cx="10" cy="8" r="3.5" fill="#FFFFFF"/><path d="M5 18 C5 14.5 7.5 13 10 13 C12.5 13 15 14.5 15 18 Z" fill="#FFFFFF"/></svg>',
        };
    }

    /**
     * Detecta el partido político referenciado en un texto oficial de trayectoria o cargo público.
     * Retorna el nombre canónico del partido o null si es cargo institucional o sin mención partidista.
     */
    public static function detectPartyFromText(string $text): ?string
    {
        $t = trim($text);
        if ($t === '') {
            return null;
        }

        // 1. Morena
        if (preg_match('/\b(MORENA|Movimiento\s+de\s+Regeneraci[oó]n\s+Nacional|Regeneraci[oó]n\s+Nacional)\b/ui', $t)) {
            return 'Morena';
        }

        // 2. PRI (Partido Revolucionario Institucional y organizaciones históricas sectoriales)
        if (preg_match('/\b(P\.?R\.?I\.?|Partido\s+Revolucionario\s+Institucional|Revolucionario\s+Institucional|C\.?N\.?O\.?P\.?|Confederaci[oó]n\s+Nacional\s+de\s+Organizaciones\s+Populares|C\.?T\.?M\.?|C\.?N\.?C\.?)\b/u', $t)
            || preg_match('/\b(comit[eé]\s+directivo\s+(estatal|municipal|nacional)\s+del\s+pri|consejero\s+pol[ií]tico\s+.*del\s+pri|candidat[oa]\s+del\s+pri|militante\s+del\s+pri|bancada\s+del\s+pri|fracci[oó]n\s+.*del\s+pri)\b/ui', $t)) {
            return 'PRI';
        }

        // 3. PAN (Partido Acción Nacional)
        if (preg_match('/\b(Partido\s+Acci[oó]n\s+Nacional|Acci[oó]n\s+Nacional)\b/ui', $t)
            || preg_match('/\b(P\.?A\.?N\.?)\b/u', $t)
            || preg_match('/\b(comit[eé]\s+directivo\s+.*del\s+pan|consejero\s+pol[ií]tico\s+.*del\s+pan|candidat[oa]\s+del\s+pan|militante\s+del\s+pan|bancada\s+del\s+pan|fracci[oó]n\s+.*del\s+pan)\b/ui', $t)) {
            return 'PAN';
        }

        // 4. PRD (Partido de la Revolución Democrática)
        if (preg_match('/\b(P\.?R\.?D\.?|Partido\s+de\s+la\s+Revoluci[oó]n\s+Democr[aá]tica|Revoluci[oó]n\s+Democr[aá]tica)\b/ui', $t)) {
            return 'PRD';
        }

        // 5. PVEM (Partido Verde)
        if (preg_match('/\b(P\.?V\.?E\.?M\.?|Partido\s+Verde\s+Ecologista(\s+de\s+M[eé]xico)?|Partido\s+Verde|Verde\s+Ecologista)\b/ui', $t)) {
            return 'PVEM';
        }

        // 6. PT (Partido del Trabajo)
        if (preg_match('/\b(Partido\s+del\s+Trabajo)\b/ui', $t)
            || preg_match('/\b(P\.?T\.?)\b/u', $t)) {
            return 'PT';
        }

        // 7. Movimiento Ciudadano (MC) / Convergencia
        if (preg_match('/\b(Movimiento\s+Ciudadano|Partido\s+Convergencia|Convergencia\s+por\s+la\s+Democracia|\bConvergencia\b)\b/ui', $t)
            || preg_match('/\b(M\.?C\.?)\b/u', $t)) {
            return 'Movimiento Ciudadano';
        }

        // 8. PES (Partido Encuentro Social / Encuentro Solidario)
        if (preg_match('/\b(P\.?E\.?S\.?|Partido\s+Encuentro\s+Social|Partido\s+Encuentro\s+Solidario|Encuentro\s+Social|Encuentro\s+Solidario)\b/ui', $t)) {
            return 'PES';
        }

        // 9. Nueva Alianza (PANAL)
        if (preg_match('/\b(Nueva\s+Alianza|P\.?A\.?N\.?A\.?L\.?)\b/ui', $t)) {
            return 'Nueva Alianza';
        }

        // 10. Independiente / Sin Partido
        if (preg_match('/\b(Candidatura\s+Independiente|Candidato\s+Independiente|Sin\s+Partido)\b/ui', $t)) {
            return 'Independiente';
        }

        return null;
    }

    /**
     * Renderiza un badge visual de partido político optimizado para la línea de tiempo.
     */
    public static function renderPartyTimelineBadge(string $partido, bool $esActual = false): string
    {
        $p = trim($partido);
        $canonical = self::getCanonicalPartyName($p);
        $color = self::getPartyColor($canonical);
        $icon = self::getPartyMicroIcon($canonical);

        $tagActual = $esActual ? ' <span class="badge bg-success text-white ms-1" style="font-size: 0.58rem; padding: 1px 4px; font-weight: 700;">Actual</span>' : '';

        $bgStyle = match ($canonical) {
            'PRD' => 'background-color: #FFF9DB; color: #856404; border-color: #FFE066 !important;',
            default => "background-color: {$color}14; color: {$color}; border-color: {$color}40 !important;",
        };

        return sprintf(
            '<span class="party-timeline-badge d-inline-flex align-items-center gap-1 py-0 px-2 rounded-pill border" style="font-size: 0.68rem; font-weight: 600; line-height: 1.6; %s" title="Filiación política en este periodo: %s">%s<span>%s</span>%s</span>',
            $bgStyle,
            htmlspecialchars($canonical),
            $icon,
            htmlspecialchars($canonical),
            $tagActual
        );
    }

    /**
     * Convierte nombres del formato oficial SIL (ApellidoPaterno ApellidoMaterno Nombre(s))
     * al orden natural ciudadano (Nombre(s) ApellidoPaterno ApellidoMaterno).
     */
    public static function formatearNombreNatural(?string $nombreRaw): string
    {
        if ($nombreRaw === null || trim($nombreRaw) === '') {
            return '';
        }
        $texto = preg_replace('/\s+/', ' ', trim($nombreRaw));
        $tokens = explode(' ', (string)$texto);
        if (count($tokens) <= 2) {
            return $texto;
        }

        $particulas = ['de', 'del', 'de la', 'de los', 'de las', 'san', 'santa'];
        $palabras = [];
        $i = 0;
        $total = count($tokens);

        while ($i < $total) {
            if ($i + 2 < $total && in_array(mb_strtolower($tokens[$i] . ' ' . $tokens[$i+1]), $particulas, true)) {
                $palabras[] = $tokens[$i] . ' ' . $tokens[$i+1] . ' ' . $tokens[$i+2];
                $i += 3;
            } elseif ($i + 1 < $total && in_array(mb_strtolower($tokens[$i]), $particulas, true)) {
                $palabras[] = $tokens[$i] . ' ' . $tokens[$i+1];
                $i += 2;
            } else {
                $palabras[] = $tokens[$i];
                $i++;
            }
        }

        $cnt = count($palabras);
        if ($cnt === 3) {
            return "{$palabras[2]} {$palabras[0]} {$palabras[1]}";
        } elseif ($cnt === 4) {
            return "{$palabras[2]} {$palabras[3]} {$palabras[0]} {$palabras[1]}";
        } elseif ($cnt >= 5) {
            $nombres = implode(' ', array_slice($palabras, 2));
            return "{$nombres} {$palabras[0]} {$palabras[1]}";
        }

        return $texto;
    }

    /**
     * Sanitiza texto contra XSS.
     */
    public static function e(?string $text): string
    {
        return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
