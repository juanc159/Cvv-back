<?php

namespace App\Services\Maintenance;

use App\Helpers\Constants;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Compara los archivos del disco contra las rutas registradas en la base.
 *
 * Es de SOLO LECTURA: reporta, nunca borra ni mueve nada. La limpieza es un paso
 * aparte y explícito, para no destruir archivos por una clasificación equivocada.
 */
class FileAuditService
{
    public function __construct(
        protected FilePathRegistry $registry,
    ) {}

    /**
     * Ejecuta la auditoría completa.
     *
     * @param  callable|null  $onProgress  Recibe (procesados, total) para reportar avance
     * @return array{summary: array, orphans: array, missing: array, byFolder: array}
     */
    public function run(?callable $onProgress = null): array
    {
        $disk = Storage::disk(Constants::DISK_FILES);

        $referenced = $this->registry->referencedPaths();
        $diskFiles = $disk->allFiles();

        $total = count($diskFiles);
        $processed = 0;

        $orphans = [];
        $ignored = 0;
        $orphanBytes = 0;

        foreach ($diskFiles as $path) {
            $processed++;

            if ($onProgress && $processed % 250 === 0) {
                $onProgress($processed, $total);
            }

            if ($this->registry->isIgnored($path)) {
                $ignored++;
                continue;
            }

            if (isset($referenced[$path])) {
                continue;
            }

            // En el disco pero sin ninguna fila que lo apunte.
            $size = $this->safeSize($disk, $path);
            $orphanBytes += $size;

            $orphans[] = [
                'path' => $path,
                'size' => $size,
                'folder' => $this->topFolder($path),
                'modified_at' => $this->safeModified($disk, $path),
            ];
        }

        if ($onProgress) {
            $onProgress($total, $total);
        }

        // El caso inverso: la base apunta a un archivo que ya no está en el disco.
        $onDisk = array_flip($diskFiles);
        $missing = [];

        foreach ($referenced as $path => $label) {
            if (! isset($onDisk[$path])) {
                $missing[] = ['path' => $path, 'source' => $label];
            }
        }

        return [
            'summary' => [
                'disk_files' => $total,
                'referenced' => count($referenced),
                'ignored' => $ignored,
                'orphans' => count($orphans),
                'orphan_bytes' => $orphanBytes,
                'missing' => count($missing),
            ],
            'skippedSources' => $this->registry->skippedSources(),
            'orphans' => $orphans,
            'missing' => $missing,
            'byFolder' => $this->groupByFolder($orphans),
        ];
    }

    /**
     * Agrupa los huérfanos por carpeta de primer nivel.
     *
     * Sirve de alarma: si aparece una carpeta entera con muchos huérfanos, lo más
     * probable no es que sean basura sino que falta registrar esa columna en
     * FilePathRegistry.
     *
     * @return array<int, array{folder: string, count: int, bytes: int}>
     */
    protected function groupByFolder(array $orphans): array
    {
        $grouped = [];

        foreach ($orphans as $orphan) {
            $folder = $orphan['folder'];

            if (! isset($grouped[$folder])) {
                $grouped[$folder] = ['folder' => $folder, 'count' => 0, 'bytes' => 0];
            }

            $grouped[$folder]['count']++;
            $grouped[$folder]['bytes'] += $orphan['size'];
        }

        usort($grouped, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_values($grouped);
    }

    protected function topFolder(string $path): string
    {
        $position = strpos($path, '/');

        return $position === false ? '(raíz)' : substr($path, 0, $position);
    }

    protected function safeSize(Filesystem $disk, string $path): int
    {
        try {
            return (int) $disk->size($path);
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function safeModified(Filesystem $disk, string $path): ?string
    {
        try {
            return date('Y-m-d H:i:s', $disk->lastModified($path));
        } catch (\Throwable) {
            return null;
        }
    }
}
