<?php

namespace App\Http\Controllers;

use App\Jobs\Maintenance\RunFileAuditJob;
use App\Jobs\Maintenance\RunDataAuditJob;
use App\Jobs\Maintenance\RunFileCleanupJob;
use App\Models\MaintenanceAudit;
use App\Services\Maintenance\FilePathRegistry;
use Illuminate\Http\Request;

/**
 * Módulo de Mantenimiento: auditorías técnicas del sistema y su limpieza.
 *
 * La auditoría es de solo lectura. La limpieza es una acción aparte y explícita, con
 * modo simulación por defecto.
 */
class MaintenanceController extends Controller
{
    /** Cuántas corridas se listan en el historial. */
    protected const HISTORY_LIMIT = 10;

    /**
     * Lanza una auditoría de archivos.
     */
    public function runFileAudit(Request $request)
    {
        // Si ya hay una corriendo, se devuelve esa en vez de encolar otra igual.
        $running = MaintenanceAudit::where('type', MaintenanceAudit::TYPE_FILES)
            ->whereIn('status', [MaintenanceAudit::STATUS_PENDING, MaintenanceAudit::STATUS_RUNNING])
            ->latest()
            ->first();

        if ($running) {
            return response()->json([
                'code' => 200,
                'message' => 'Ya hay una auditoría en curso',
                'audit' => $this->asPayload($running),
            ]);
        }

        $audit = MaintenanceAudit::create([
            'type' => MaintenanceAudit::TYPE_FILES,
            'company_id' => $request->input('company_id'),
            'user_id' => $request->user()?->id,
            'status' => MaintenanceAudit::STATUS_PENDING,
        ]);

        RunFileAuditJob::dispatch($audit->id);

        return response()->json([
            'code' => 200,
            'message' => 'Auditoría iniciada',
            'audit' => $this->asPayload($audit),
        ]);
    }

    /**
     * Lanza una limpieza.
     *
     * Objetivos posibles: orphans (borrar archivos huérfanos del disco),
     * missing (limpiar filas que apuntan a archivos inexistentes).
     *
     * Con dry_run en true solo informa qué haría, sin modificar nada.
     */
    public function runCleanup(Request $request)
    {
        $allowed = ['orphans', 'missing'];
        $targets = array_values(array_intersect((array) $request->input('targets', []), $allowed));

        if (empty($targets)) {
            return response()->json([
                'code' => 422,
                'message' => 'Indicá al menos qué limpiar',
            ], 422);
        }

        $running = MaintenanceAudit::whereIn('status', [MaintenanceAudit::STATUS_PENDING, MaintenanceAudit::STATUS_RUNNING])
            ->latest()
            ->first();

        if ($running) {
            return response()->json([
                'code' => 200,
                'message' => 'Ya hay una operación en curso',
                'audit' => $this->asPayload($running),
            ]);
        }

        $audit = MaintenanceAudit::create([
            'type' => MaintenanceAudit::TYPE_CLEANUP,
            'company_id' => $request->input('company_id'),
            'user_id' => $request->user()?->id,
            'status' => MaintenanceAudit::STATUS_PENDING,
        ]);

        RunFileCleanupJob::dispatch(
            $audit->id,
            $targets,
            (bool) $request->input('dry_run', true),
        );

        return response()->json([
            'code' => 200,
            'message' => $request->input('dry_run', true) ? 'Simulación iniciada' : 'Limpieza iniciada',
            'audit' => $this->asPayload($audit),
        ]);
    }

    /**
     * Estado y resumen de una corrida. El frontend consulta esto para saber si terminó.
     */
    public function showAudit(string $id)
    {
        $audit = MaintenanceAudit::find($id);

        if (! $audit) {
            return response()->json(['code' => 404, 'message' => 'Auditoría no encontrada'], 404);
        }

        return response()->json([
            'code' => 200,
            'audit' => $this->asPayload($audit, withFindings: true),
        ]);
    }

    /**
     * Última corrida (para mostrar algo al entrar al módulo) e historial reciente.
     */
    public function fileAuditData()
    {
        $last = MaintenanceAudit::where('type', MaintenanceAudit::TYPE_FILES)
            ->latest()
            ->first();

        $history = MaintenanceAudit::latest()
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn ($audit) => $this->asPayload($audit));

        return response()->json([
            'code' => 200,
            'last' => $last ? $this->asPayload($last, withFindings: true) : null,
            'history' => $history,
            // Se expone el registro para que se vea desde la interfaz qué columnas
            // conoce la auditoría: es lo que determina qué se considera huérfano.
            'registry' => FilePathRegistry::PATH_COLUMNS,
            'ignoredPrefixes' => FilePathRegistry::IGNORED_PREFIXES,
        ]);
    }

    /**
     * Lanza la revisión de consistencia de datos.
     */
    public function runDataAudit(Request $request)
    {
        $running = MaintenanceAudit::where('type', MaintenanceAudit::TYPE_DATA)
            ->whereIn('status', [MaintenanceAudit::STATUS_PENDING, MaintenanceAudit::STATUS_RUNNING])
            ->latest()
            ->first();

        if ($running) {
            return response()->json([
                'code' => 200,
                'message' => 'Ya hay una revisión en curso',
                'audit' => $this->asPayload($running),
            ]);
        }

        $audit = MaintenanceAudit::create([
            'type' => MaintenanceAudit::TYPE_DATA,
            'company_id' => $request->input('company_id'),
            'user_id' => $request->user()?->id,
            'status' => MaintenanceAudit::STATUS_PENDING,
        ]);

        RunDataAuditJob::dispatch($audit->id, $request->input('company_id'));

        return response()->json([
            'code' => 200,
            'message' => 'Revisión iniciada',
            'audit' => $this->asPayload($audit),
        ]);
    }

    /**
     * Última revisión de datos e historial.
     */
    public function dataAuditData()
    {
        $last = MaintenanceAudit::where('type', MaintenanceAudit::TYPE_DATA)
            ->latest()
            ->first();

        $history = MaintenanceAudit::where('type', MaintenanceAudit::TYPE_DATA)
            ->latest()
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn ($audit) => $this->asPayload($audit));

        return response()->json([
            'code' => 200,
            'last' => $last ? $this->asPayload($last, withFindings: true) : null,
            'history' => $history,
        ]);
    }

    /**
     * Da forma a la fila para el frontend.
     */
    protected function asPayload(MaintenanceAudit $audit, bool $withFindings = false): array
    {
        $payload = [
            'id' => $audit->id,
            'type' => $audit->type,
            'status' => $audit->status,
            'summary' => $audit->summary,
            'error' => $audit->error,
            'started_at' => $audit->started_at?->format('Y-m-d H:i:s'),
            'finished_at' => $audit->finished_at?->format('Y-m-d H:i:s'),
            'created_at' => $audit->created_at?->format('Y-m-d H:i:s'),
            'is_finished' => $audit->isFinished(),
        ];

        if ($withFindings) {
            $payload['findings'] = $audit->findings;
        }

        return $payload;
    }
}
