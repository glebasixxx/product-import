<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Repositories\Contracts\ImportRepositoryInterface;
use App\Services\Import\ProductImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessProductImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public readonly string $importId)
    {
        $this->onQueue((string) config('import.queue'));
    }

    public function handle(ImportRepositoryInterface $imports, ProductImportService $service): void
    {
        $import = $imports->find($this->importId);

        if ($import === null || $import->status->isFinished()) {
            return;
        }

        $service->process($import);
    }

    public function failed(?\Throwable $exception): void
    {
        $imports = app(ImportRepositoryInterface::class);
        $import = $imports->find($this->importId);

        if ($import !== null && ! $import->status->isFinished()) {
            $imports->update($import, [
                'status' => ImportStatus::Failed,
                'failure_reason' => $exception?->getMessage() ?? 'Задача импорта завершилась с ошибкой.',
                'finished_at' => now(),
            ]);
        }
    }
}
