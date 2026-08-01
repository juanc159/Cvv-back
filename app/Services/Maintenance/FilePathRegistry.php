<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario de dónde el sistema guarda rutas de archivos.
 *
 * Es la pieza de la que depende toda la auditoría: un archivo se considera huérfano
 * cuando está en el disco y NO aparece en ninguna de estas columnas. Si alguien agrega
 * una carga de archivos nueva y no la registra acá, la auditoría va a reportar esos
 * archivos como basura.
 *
 * POR ESO: al agregar cualquier `->store()` o `->storeAs()` nuevo en el proyecto,
 * registrá su columna en PATH_COLUMNS.
 */
class FilePathRegistry
{
    /** Al limpiar una referencia rota se vacía la columna. */
    public const ON_MISSING_NULLIFY = 'nullify';

    /**
     * Al limpiar una referencia rota se borra la fila entera.
     * Se usa donde la columna es NOT NULL y el registro no tiene sentido sin su archivo.
     */
    public const ON_MISSING_DELETE_ROW = 'delete_row';

    /**
     * Columnas que contienen rutas de archivos del disco público.
     *
     * @var array<int, array{table: string, column: string, label: string, on_missing: string}>
     */
    public const PATH_COLUMNS = [
        ['table' => 'students', 'column' => 'photo', 'label' => 'Foto del estudiante', 'on_missing' => self::ON_MISSING_NULLIFY],
        ['table' => 'students', 'column' => 'boletin', 'label' => 'Boletín del estudiante', 'on_missing' => self::ON_MISSING_NULLIFY],
        ['table' => 'teachers', 'column' => 'photo', 'label' => 'Foto del docente', 'on_missing' => self::ON_MISSING_NULLIFY],
        ['table' => 'teacher_plannings', 'column' => 'path', 'label' => 'Planificación del docente', 'on_missing' => self::ON_MISSING_NULLIFY],
        ['table' => 'banners', 'column' => 'path', 'label' => 'Banner', 'on_missing' => self::ON_MISSING_NULLIFY],
        ['table' => 'companies', 'column' => 'image_principal', 'label' => 'Imagen del colegio', 'on_missing' => self::ON_MISSING_NULLIFY],
        ['table' => 'services', 'column' => 'image', 'label' => 'Imagen del servicio', 'on_missing' => self::ON_MISSING_NULLIFY],
        // file_path es NOT NULL: un adjunto sin archivo no tiene sentido, se borra la fila.
        ['table' => 'comment_attachments', 'column' => 'file_path', 'label' => 'Adjunto de comentario', 'on_missing' => self::ON_MISSING_DELETE_ROW],
        ['table' => 'pending_registration_files', 'column' => 'path', 'label' => 'Archivo de materia pendiente', 'on_missing' => self::ON_MISSING_NULLIFY],
    ];

    /**
     * Carpetas de trabajo que no se auditan.
     *
     * Guardan archivos temporales que por diseño no tienen fila en la base, así que
     * reportarlos como huérfanos sería ruido.
     *
     * @var array<int, string>
     */
    public const IGNORED_PREFIXES = [
        'temp/',
        'temp_imports/',
    ];

    /**
     * Columnas del registro que no existen en el esquema actual.
     *
     * @var array<int, string>
     */
    protected array $skippedSources = [];

    /**
     * Devuelve todas las rutas referenciadas en la base, normalizadas.
     *
     * Se consulta con DB::table a propósito: así se incluyen los registros con borrado
     * lógico (deleted_at). Sus archivos siguen estando referenciados y no deben tratarse
     * como huérfanos.
     *
     * @return array<string, string> ruta => etiqueta de dónde se usa
     */
    public function referencedPaths(): array
    {
        $paths = [];
        $this->skippedSources = [];

        foreach (self::PATH_COLUMNS as $source) {
            // Si el esquema todavía no tiene esa tabla o columna, se omite en vez de
            // reventar: un registro desactualizado no debe impedir auditar el resto.
            // Se deja constancia porque sus archivos van a aparecer como huérfanos.
            if (! Schema::hasColumn($source['table'], $source['column'])) {
                $this->skippedSources[] = $source['table'] . '.' . $source['column'];
                continue;
            }

            DB::table($source['table'])
                ->select($source['column'] . ' as path')
                ->whereNotNull($source['column'])
                ->where($source['column'], '<>', '')
                ->orderBy($source['column'])
                ->chunk(2000, function ($rows) use (&$paths, $source) {
                    foreach ($rows as $row) {
                        $normalized = $this->normalize($row->path);

                        if ($normalized !== '') {
                            $paths[$normalized] = $source['label'];
                        }
                    }
                });
        }

        return $paths;
    }

    /**
     * Columnas registradas que no existen en el esquema, tras la última consulta.
     *
     * Si esto trae algo, los archivos de esas columnas van a figurar como huérfanos
     * aunque estén en uso: hay que revisarlo antes de borrar nada.
     *
     * @return array<int, string>
     */
    public function skippedSources(): array
    {
        return $this->skippedSources;
    }

    /**
     * Indica si la columna existe en el esquema actual.
     *
     * @param  array{table: string, column: string}  $source
     */
    public function hasSource(array $source): bool
    {
        return Schema::hasColumn($source['table'], $source['column']);
    }

    /**
     * Columnas del registro ausentes del esquema, consultando directamente.
     *
     * A diferencia de skippedSources(), no depende de haber leído las referencias antes,
     * así que sirve para validar antes de cualquier limpieza.
     *
     * @return array<int, string>
     */
    public function missingSources(): array
    {
        $missing = [];

        foreach (self::PATH_COLUMNS as $source) {
            if (! $this->hasSource($source)) {
                $missing[] = $source['table'] . '.' . $source['column'];
            }
        }

        return $missing;
    }

    /**
     * Indica si una ruta debe quedar fuera de la auditoría.
     */
    public function isIgnored(string $path): bool
    {
        // Archivos ocultos: el propio Laravel deja .gitignore dentro de storage.
        if (str_starts_with(basename($path), '.')) {
            return true;
        }

        foreach (self::IGNORED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deja la ruta como la devuelve el disco: sin barra inicial y con separadores unix.
     *
     * Hace falta porque algunos módulos guardan con '/' adelante (ej: '/banners/...')
     * y otros no, y las dos formas apuntan al mismo archivo.
     */
    public function normalize(?string $path): string
    {
        return ltrim(trim((string) $path), '/');
    }
}
