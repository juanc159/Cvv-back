<?php

namespace App\Jobs\Maintenance;

use App\Models\MaintenanceAudit;
use App\Services\Maintenance\FileCleanupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Ejecuta la limpieza en segundo plano.
 *
 * En modo simulación ($dryRun) informa exactamente qué haría, sin tocar nada.
 */
class RunFileCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 280;

    public $tries = 1;

    protected const MAX_DETAIL_ITEMS = 2000;

    /**
     * @param  array<int, string>  $targets  orphans | missing
     */
    public function __construct(
        protected string $auditId,
        protected array $targets,
        protected bool $dryRun,
    ) {}

    public function handle(FileCleanupService $service): void
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
            $summary = ['dry_run' => $this->dryRun, 'targets' => $this->targets];
            $findings = [];

            if (in_array('orphans', $this->targets, true)) {
                $result = $service->cleanOrphans($this->dryRun);

                $summary['orphans_deleted'] = count($result['deleted']);
                $summary['orphans_bytes'] = $result['bytes'];
                $summary['orphans_skipped_recent'] = $result['skipped_recent'];
                $findings['orphans'] = array_slice($result['deleted'], 0, self::MAX_DETAIL_ITEMS);
            }

            if (in_array('missing', $this->targets, true)) {
                $result = $service->cleanBrokenReferences($this->dryRun);

                $summary['references_nullified'] = $result['nullified'];
                $summary['rows_deleted'] = $result['deleted_rows'];
                $findings['missing'] = $result['cleaned'];
            }

            $audit->update([
                'status' => MaintenanceAudit::STATUS_COMPLETED,
                'summary' => $summary,
                'findings' => $findings,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $th) {
            Log::error('Falló la limpieza de archivos', [
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

    public function failed(\Throwable $th): void
    {
        MaintenanceAudit::where('id', $this->auditId)->update([
            'status' => MaintenanceAudit::STATUS_FAILED,
            'error' => $th->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
