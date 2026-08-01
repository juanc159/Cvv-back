<?php

namespace App\Jobs\Maintenance;

use App\Models\MaintenanceAudit;
use App\Services\Maintenance\FileAuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Corre la auditoría de archivos en segundo plano.
 *
 * Va en cola porque recorrer el disco y cruzarlo contra la base no entra en el tiempo
 * de una petición HTTP.
 */
class RunFileAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Debe ser menor al timeout del worker (300s) para poder marcar el fallo. */
    public $timeout = 280;

    /** Sin reintentos: si falló, que quede registrado y el usuario decida. */
    public $tries = 1;

    /**
     * Tope de items guardados en el detalle. Los totales reales van en el resumen,
     * así no se infla la fila cuando hay decenas de miles de archivos.
     */
    protected const MAX_DETAIL_ITEMS = 2000;

    public function __construct(
        protected string $auditId,
    ) {}

    public function handle(FileAuditService $service): void
    {
        $audit = MaintenanceAudit::find($this->auditId);

        if (! $audit) {
            return;
        }

        $audit->update([
            'status' => MaintenanceAudit::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $result = $service->run();

            $summary = $result['summary'];
            $summary['skipped_sources'] = $result['skippedSources'];
            $summary['detail_truncated'] = count($result['orphans']) > self::MAX_DETAIL_ITEMS
                || count($result['missing']) > self::MAX_DETAIL_ITEMS;

            $audit->update([
                'status' => MaintenanceAudit::STATUS_COMPLETED,
                'summary' => $summary,
                'findings' => [
                    'orphans' => array_slice($result['orphans'], 0, self::MAX_DETAIL_ITEMS),
                    'missing' => array_slice($result['missing'], 0, self::MAX_DETAIL_ITEMS),
                    'byFolder' => $result['byFolder'],
                ],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $th) {
            Log::error('Falló la auditoría de archivos', [
                'audit_id' => $this->auditId,
                'error' => $th->getMessage(),
            ]);

            $audit->update([
                'status' => MaintenanceAudit::STATUS_FAILED,
                'error' => $th->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }

    /** Si el job muere por timeout o error fatal, que la fila no quede en "running" para siempre. */
    public function failed(\Throwable $th): void
    {
        MaintenanceAudit::where('id', $this->auditId)->update([
            'status' => MaintenanceAudit::STATUS_FAILED,
            'error' => $th->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
