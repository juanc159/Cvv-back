<?php
 
namespace App\Jobs;

use App\Services\ExcelDataProcessor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExcelDataJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $filePath,
        protected int $companyId,
        protected int $typeEducationId,
        protected ?int $teacherId,
        protected array $chunkData,
        protected int $sheetIndex
    ) {}

    public function handle(ExcelDataProcessor $processor)
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        try {
            $processor->processChunk(
                $this->filePath,
                $this->companyId,
                $this->typeEducationId,
                $this->teacherId,
                $this->chunkData,
                $this->sheetIndex
            );
        } catch (\Exception $e) {
            Log::error("Error processing chunk: " . $e->getMessage());
            $this->fail($e);
        }
    }
}