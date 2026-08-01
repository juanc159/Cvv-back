<?php

namespace App\Services\Maintenance;

use App\Helpers\Constants;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Limpieza de lo que reporta la auditoría.
 *
 * Dos operaciones, con criterios distintos:
 *   - Huérfanos: archivo en disco sin referencia → se borra del disco.
 *   - Referencias rotas: fila que apunta a un archivo inexistente → se vacía la columna
 *     (o se borra la fila donde la columna es NOT NULL).
 *
 * Todo vuelve a verificarse en el momento de ejecutar: NO se confía en el resultado de
 * una auditoría anterior, que pudo quedar viejo.
 */
class FileCleanupService
{
    /**
     * Un archivo recién subido puede aparecer como huérfano si entró entre la lectura de
     * la base y el recorrido del disco. Con este margen esa carrera no puede pasar.
     */
    public const MIN_AGE_DAYS = 7;

    public function __construct(
        protected FilePathRegistry $registry,
    ) {}

    /**
     * Borra del disco los archivos huérfanos.
     *
     * @param  bool  $dryRun  Si es true solo informa qué haría, sin tocar nada
     * @return array{deleted: array, skipped_recent: int, bytes: int}
     */
    public function cleanOrphans(bool $dryRun, int $minAgeDays = self::MIN_AGE_DAYS): array
    {
        $this->guardTrustworthy();

        $disk = Storage::disk(Constants::DISK_FILES);

        // Se recalcula: no se usa el detalle de una auditoría previa.
        $referenced = $this->registry->referencedPaths();

        $cutoff = now()->subDays($minAgeDays)->getTimestamp();

        $deleted = [];
        $skippedRecent = 0;
        $bytes = 0;

        foreach ($disk->allFiles() as $path) {
            if ($this->registry->isIgnored($path) || isset($referenced[$path])) {
                continue;
            }

            if ($this->lastModified($disk, $path) > $cutoff) {
                $skippedRecent++;
                continue;
            }

            $size = $this->size($disk, $path);

            if (! $dryRun) {
                $disk->delete($path);
            }

            $bytes += $size;
            $deleted[] = ['path' => $path, 'size' => $size];
        }

        return [
            'deleted' => $deleted,
            'skipped_recent' => $skippedRecent,
            'bytes' => $bytes,
        ];
    }

    /**
     * Limpia las filas que apuntan a archivos que ya no existen.
     *
     * @return array{cleaned: array, nullified: int, deleted_rows: int}
     */
    public function cleanBrokenReferences(bool $dryRun): array
    {
        $this->guardTrustworthy();

        $disk = Storage::disk(Constants::DISK_FILES);

        // Índice de lo que sí está en el disco, para no hacer un exists() por fila.
        $onDisk = array_flip($disk->allFiles());

        $cleaned = [];
        $nullified = 0;
        $deletedRows = 0;

        foreach (FilePathRegistry::PATH_COLUMNS as $source) {
            $table = $source['table'];
            $column = $source['column'];

            if (! $this->registry->hasSource($source)) {
                continue;
            }

            $broken = [];

            DB::table($table)
                ->select('id', $column . ' as path')
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->orderBy('id')
                ->chunk(1000, function ($rows) use (&$broken, $onDisk) {
                    foreach ($rows as $row) {
                        $normalized = $this->registry->normalize($row->path);

                        if ($normalized !== '' && ! isset($onDisk[$normalized])) {
                            $broken[] = ['id' => $row->id, 'path' => $normalized];
                        }
                    }
                });

            if (empty($broken)) {
                continue;
            }

            $ids = array_column($broken, 'id');

            if (! $dryRun) {
                foreach (array_chunk($ids, 500) as $chunk) {
                    if ($source['on_missing'] === FilePathRegistry::ON_MISSING_DELETE_ROW) {
                        DB::table($table)->whereIn('id', $chunk)->delete();
                    } else {
                        DB::table($table)->whereIn('id', $chunk)->update([$column => null]);
                    }
                }
            }

            if ($source['on_missing'] === FilePathRegistry::ON_MISSING_DELETE_ROW) {
                $deletedRows += count($ids);
            } else {
                $nullified += count($ids);
            }

            $cleaned[] = [
                'source' => $table . '.' . $column,
                'label' => $source['label'],
                'action' => $source['on_missing'],
                'count' => count($ids),
                'samples' => array_slice(array_column($broken, 'path'), 0, 5),
            ];
        }

        return [
            'cleaned' => $cleaned,
            'nullified' => $nullified,
            'deleted_rows' => $deletedRows,
        ];
    }

    /**
     * Impide limpiar cuando el registro no está completo.
     *
     * Si falta una columna, sus archivos se clasifican como huérfanos y sus filas como
     * referencias rotas: limpiar en ese estado destruye datos buenos.
     */
    protected function guardTrustworthy(): void
    {
        $skipped = $this->registry->missingSources();

        if (! empty($skipped)) {
            throw new \RuntimeException(
                'No se puede limpiar: el registro tiene columnas que no existen en la base ('
                . implode(', ', $skipped)
                . '). Sus archivos figurarían como huérfanos sin serlo.'
            );
        }
    }

    protected function size(Filesystem $disk, string $path): int
    {
        try {
            return (int) $disk->size($path);
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function lastModified(Filesystem $disk, string $path): int
    {
        try {
            return (int) $disk->lastModified($path);
        } catch (\Throwable) {
            // Si no se puede leer la fecha se trata como reciente: no se toca.
            return PHP_INT_MAX;
        }
    }
}
