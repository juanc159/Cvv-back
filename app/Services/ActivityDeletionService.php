<?php

namespace App\Services;

use App\Helpers\Constants;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\CommentAttachment;
use App\Models\ActivitySubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Borra actividades con todo lo que cuelga de ellas.
 *
 * El colegio decidió que eliminar una actividad elimina también el trabajo entregado por los
 * alumnos. No hay vuelta atrás, así que todo pasa por aquí en lugar de repartirse entre
 * controladores: es el único sitio donde hay que mirar para saber qué se lleva un borrado.
 *
 * Las claves foráneas de activity_submissions, submissions y activity_assignments ya están en
 * CASCADE, así que la base se encarga de esas. Lo que la base no cubre y hay que hacer a mano:
 *
 *   - comments es polimórfica (commentable_type + commentable_id) y no tiene clave foránea,
 *     así que nadie la limpia sola.
 *   - los archivos adjuntos viven en disco; borrar la fila no borra el archivo.
 */
class ActivityDeletionService
{
    /**
     * Qué se llevaría por delante el borrado, sin borrar nada.
     *
     * Sirve para que la confirmación diga cuántas entregas de alumnos se van a destruir en vez
     * de un "¿está seguro?" que nadie lee.
     *
     * @param  array  $activityIds
     */
    public function preview(array $activityIds, string $companyId): array
    {
        // withTrashed para alcanzar también las que quedaron en borrado lógico de antes: si
        // no, serían invisibles para este servicio y nunca se podrían terminar de borrar.
        $activities = Activity::withTrashed()->whereIn('id', $activityIds)->where('company_id', $companyId)->get();

        if ($activities->isEmpty()) {
            return ['activities' => 0, 'submissions' => 0, 'comments' => 0, 'files' => 0];
        }

        $submissionIds = ActivitySubmission::whereIn('activity_id', $activities->pluck('id'))->pluck('id');
        $commentIds = $this->commentIdsOf($submissionIds);

        return [
            'activities' => $activities->count(),
            'submissions' => $submissionIds->count(),
            'comments' => $commentIds->count(),
            'files' => CommentAttachment::whereIn('comment_id', $commentIds)->count(),
        ];
    }

    /**
     * Borra las actividades indicadas y todo lo que depende de ellas.
     *
     * @return array Lo que se eliminó, para informarlo al usuario.
     */
    public function delete(array $activityIds, string $companyId): array
    {
        $resumen = ['activities' => 0, 'submissions' => 0, 'comments' => 0, 'files' => 0];

        DB::transaction(function () use ($activityIds, $companyId, &$resumen) {
            // El filtro por colegio va aquí y no en el controlador: así ninguna vía de borrado
            // puede saltárselo y alcanzar actividades de otro colegio.
            $activities = Activity::withTrashed()->whereIn('id', $activityIds)->where('company_id', $companyId)->get();

            if ($activities->isEmpty()) {
                return;
            }

            $submissionIds = ActivitySubmission::whereIn('activity_id', $activities->pluck('id'))->pluck('id');
            $commentIds = $this->commentIdsOf($submissionIds);

            $resumen['files'] = $this->deleteAttachments($commentIds);

            // Los comentarios usan borrado lógico; aquí hace falta que desaparezcan de verdad,
            // porque su entrega deja de existir y quedarían apuntando al vacío.
            $resumen['comments'] = Comment::whereIn('id', $commentIds)->forceDelete();

            $resumen['submissions'] = $submissionIds->count();

            foreach ($activities as $activity) {
                // forceDelete porque Activity usa SoftDeletes para el resto del sistema: sin
                // esto la fila se marca pero no se borra, y las cascadas de la base nunca se
                // disparan, que es justo lo que dejaba vivas las entregas de actividades
                // "eliminadas".
                $activity->forceDelete();
                $resumen['activities']++;
            }
        });

        return $resumen;
    }

    /**
     * Ids de los comentarios que cuelgan de esas entregas.
     */
    private function commentIdsOf($submissionIds)
    {
        if ($submissionIds->isEmpty()) {
            return collect();
        }

        return Comment::withTrashed()
            ->where('commentable_type', ActivitySubmission::class)
            ->whereIn('commentable_id', $submissionIds)
            ->pluck('id');
    }

    /**
     * Borra los archivos adjuntos del disco.
     *
     * Las filas se van solas por la cascada de comment_attachments, pero el archivo físico no:
     * sin esto el disco acumula adjuntos que ya no están referenciados por nadie.
     */
    private function deleteAttachments($commentIds): int
    {
        if ($commentIds->isEmpty()) {
            return 0;
        }

        $borrados = 0;

        foreach (CommentAttachment::whereIn('comment_id', $commentIds)->get() as $attachment) {
            if (empty($attachment->file_path)) {
                continue;
            }

            try {
                Storage::disk(Constants::DISK_FILES)->delete($attachment->file_path);
                $borrados++;
            } catch (\Throwable $th) {
                // Que un archivo no se pueda borrar no debe abortar la operación: la fila se va
                // igual y el huérfano lo recoge después la auditoría de archivos.
                Log::warning('No se pudo borrar el adjunto de una actividad eliminada', [
                    'attachment_id' => $attachment->id,
                    'path' => $attachment->file_path,
                    'error' => $th->getMessage(),
                ]);
            }
        }

        return $borrados;
    }
}
