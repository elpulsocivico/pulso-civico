<?php
declare(strict_types=1);

namespace PulsoCivico\Core;

/**
 * PageCache: Caché de salida HTML en disco para rutas de alta demanda.
 * Elimina la necesidad de re-ejecutar DataService (125 MB de JSON) en cada request.
 *
 * Uso:
 *   $cache = new PageCache('politicos');
 *   if ($html = $cache->get()) { echo $html; exit; }
 *   ob_start();
 *   // ... render de la página ...
 *   $html = ob_get_clean();
 *   $cache->set($html);
 *   echo $html;
 */
class PageCache
{
    private const CACHE_DIR_NAME = 'pages';
    private string $cacheDir;
    private string $cacheFile;
    private int $ttl;

    /**
     * @param string $key  Clave única para esta variante de página (ej. 'politicos', 'politicos_leg65')
     * @param int    $ttl  Tiempo de vida en segundos (por defecto 6 horas)
     */
    public function __construct(string $key, int $ttl = 21600)
    {
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache/' . self::CACHE_DIR_NAME;
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        $this->cacheFile = $this->cacheDir . '/' . $safeKey . '.html';
        $this->ttl = $ttl;
    }

    /**
     * Intenta devolver HTML desde caché. Retorna null si no existe o expiró.
     */
    public function get(): ?string
    {
        if (!is_file($this->cacheFile)) {
            return null;
        }

        $mtime = @filemtime($this->cacheFile);
        if ($mtime === false || (time() - $mtime) > $this->ttl) {
            @unlink($this->cacheFile);
            return null;
        }

        $html = @file_get_contents($this->cacheFile);
        return ($html === false || strlen($html) < 100) ? null : $html;
    }

    /**
     * Guarda el HTML generado en disco.
     */
    public function set(string $html): bool
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        // Escritura atómica: temp → rename para evitar páginas parciales
        $tmp = $this->cacheFile . '.tmp.' . getmypid();
        if (@file_put_contents($tmp, $html) === false) {
            return false;
        }

        return @rename($tmp, $this->cacheFile);
    }

    /**
     * Invalida esta entrada de caché.
     */
    public function clear(): bool
    {
        if (is_file($this->cacheFile)) {
            return @unlink($this->cacheFile);
        }
        return true;
    }

    /**
     * Invalida todas las entradas del directorio de caché de páginas.
     * @return int Número de archivos eliminados
     */
    public static function clearAll(): int
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/' . self::CACHE_DIR_NAME;
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/*.html') ?: [] as $f) {
            if (@unlink($f)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Devuelve información de diagnóstico sobre el estado del caché.
     * @return array<string, mixed>
     */
    public static function status(): array
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/' . self::CACHE_DIR_NAME;
        $files = glob($dir . '/*.html') ?: [];
        $entries = [];
        $totalBytes = 0;

        foreach ($files as $f) {
            $mtime = (int)@filemtime($f);
            $size = (int)@filesize($f);
            $totalBytes += $size;
            $entries[] = [
                'key'      => basename($f, '.html'),
                'size_kb'  => round($size / 1024, 1),
                'age_min'  => round((time() - $mtime) / 60, 1),
                'path'     => str_replace('\\', '/', $f),
            ];
        }

        return [
            'cache_dir'    => str_replace('\\', '/', $dir),
            'entries'      => count($entries),
            'total_size_kb' => round($totalBytes / 1024, 1),
            'items'        => $entries,
        ];
    }
}
