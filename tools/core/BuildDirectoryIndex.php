<?php
declare(strict_types=1);

namespace PulsoCivico\Tools;

use PulsoCivico\Services\DataService;

/**
 * BuildDirectoryIndex: Precomputa y serializa el catálogo de políticos y directores
 * para evitar miles de operaciones de expresiones regulares y lecturas repetidas de JSON en runtime.
 */
class BuildDirectoryIndex
{
    public static function build(string $rootDir): array
    {
        @ini_set('memory_limit', '1024M');

        $cacheDir = $rootDir . '/storage/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $targetFile = $cacheDir . '/directorio_politicos_index.json';

        // Autoload PSR-4 nativo de src/ si aún no está registrado
        spl_autoload_register(function (string $class) use ($rootDir): void {
            $prefix = 'PulsoCivico\\';
            $baseDir = $rootDir . '/src/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });

        $dataService = new DataService($rootDir . '/storage');
        $directorio = $dataService->getDirectorioPoliticos(null, false);

        // Precomputar la existencia de foto local y reducir a estructura liviana (slim)
        $photosDir = $rootDir . '/storage/raw/photos';
        $slim = [];

        foreach ($directorio as $pol) {
            $ref = (string)($pol['referencia_sil'] ?? '');
            $hasLocalPhoto = ($ref !== '' && is_file($photosDir . "/{$ref}.jpg"));
            $fotoUrl = ($ref !== '' && $hasLocalPhoto)
                ? "/photos/{$ref}.jpg"
                : ($pol['foto_oficial_url'] ?? '');

            $slim[] = [
                'slug' => $pol['slug'] ?? '',
                'nombre_completo' => $pol['nombre_completo'] ?? '',
                'nombre_natural' => $pol['nombre_natural'] ?? null,
                'partido' => $pol['partido'] ?? '',
                'partido_slug' => $pol['partido_slug'] ?? '',
                'camara' => $pol['camara'] ?? '',
                'entidad' => $pol['entidad'] ?? '',
                'referencia_sil' => $ref,
                'foto_oficial_url' => $pol['foto_oficial_url'] ?? '',
                'foto_url' => $fotoUrl,
                'es_activo' => !empty($pol['es_activo']),
                'cargos_badges' => $pol['cargos_badges'] ?? [],
                'cargo_resumen' => $pol['cargo_resumen'] ?? '',
                'roles_flags' => $pol['roles_flags'] ?? [],
                'legislaturas_lista' => $pol['legislaturas_lista'] ?? [],
                'municipio_alcalde' => $pol['municipio_alcalde'] ?? '',
                'tiene_foto_local' => $hasLocalPhoto,
                'legislatura' => (int)($pol['legislatura'] ?? 66),
            ];
        }

        $payload = json_encode($slim, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tempFile = $targetFile . '.tmp.' . getmypid();

        $bytes = @file_put_contents($tempFile, (string)$payload);
        if ($bytes === false) {
            return [
                'success' => false,
                'error' => 'No se pudo escribir el archivo temporal del índice.',
            ];
        }

        $renamed = @rename($tempFile, $targetFile);
        if (!$renamed) {
            @unlink($tempFile);
            return [
                'success' => false,
                'error' => 'No se pudo renombrar el archivo final del índice.',
            ];
        }

        return [
            'success' => true,
            'total_politicos' => count($slim),
            'file' => str_replace('\\', '/', $targetFile),
            'size_kb' => round($bytes / 1024, 2),
        ];
    }
}
