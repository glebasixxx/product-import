<?php

namespace App\Services\Import;

use App\Enums\ImportStatus;
use App\Jobs\ProcessProductImport;
use App\Models\Import;
use App\Models\User;
use App\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImportService
{
    public function __construct(
        private readonly ImportRepositoryInterface $imports,
        private readonly Dispatcher $bus,
    ) {}

    public function start(UploadedFile $file, ?User $user = null): Import
    {
        $path = $file->storeAs('imports', Str::uuid()->toString().'.xlsx', 'local');

        if ($path === false) {
            throw new \RuntimeException('Не удалось сохранить загруженный файл.');
        }

        $import = $this->imports->create([
            'user_id' => $user?->id,
            'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'file_path' => $path,
        ]);

        try {
            $this->bus->dispatch(new ProcessProductImport($import->id));
        } catch (\Throwable $e) {
            $this->imports->update($import, [
                'status' => ImportStatus::Failed,
                'failure_reason' => 'Не удалось поставить импорт в очередь.',
                'finished_at' => now(),
            ]);

            throw $e;
        }

        return $this->imports->find($import->id) ?? $import;
    }

    public function find(string $id): ?Import
    {
        return $this->imports->find($id);
    }

    /**
     * @return Collection<int, Import>
     */
    public function latest(int $limit = 10): Collection
    {
        return $this->imports->latest($limit);
    }
}
