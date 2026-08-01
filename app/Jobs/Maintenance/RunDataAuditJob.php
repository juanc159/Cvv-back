<?php

namespace App\Jobs\Maintenance;

use App\Models\MaintenanceAudit;
use App\Services\Maintenance\DataCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Corre la revisión de consistencia de datos en segundo plano.
 */
class RunDataAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 280;

    public $tries = 1;

    public function __construct(
        protected string $auditId,
        protected ?string $companyId = null,
    ) {}

    public function handle(DataCheckService $service): void
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
            $result = $service->run($this->companyId);

            $audit->update([
                'status' => MaintenanceAudit::STATUS_COMPLETED,
                'summary' => $result['summary'],
                'findings' => ['checks' => $result['checks']],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $th) {
            Log::error('Falló la revisión de datos', [
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
