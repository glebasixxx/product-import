<?php

namespace App\Services\Import;

use App\DTO\ProductData;
use App\Enums\ImportStatus;
use App\Exceptions\ImageDownloadException;
use App\Exceptions\InvalidSpreadsheetException;
use App\Exceptions\RowValidationException;
use App\Models\Import;
use App\Repositories\Contracts\ImportRepositoryInterface;
use App\Repositories\Contracts\ProductAttributeRepositoryInterface;
use App\Repositories\Contracts\ProductImageRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductImportService
{
    private const PROGRESS_EVERY_ROWS = 10;

    private const PROGRESS_EVERY_SECONDS = 1.0;

    private const MAX_REPORT_ENTRIES = 1000;

    /** @var array<string, string> */
    private array $failedImageUrls = [];

    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly ProductRowParser $parser,
        private readonly ImageDownloader $imageDownloader,
        private readonly ImportRepositoryInterface $imports,
        private readonly ProductRepositoryInterface $products,
        private readonly ProductAttributeRepositoryInterface $attributes,
        private readonly ProductImageRepositoryInterface $images,
    ) {}

    public function process(Import $import): Import
    {
        $this->failedImageUrls = [];
        $path = Storage::disk('local')->path($import->file_path);

        $this->imports->update($import, [
            'status' => ImportStatus::Processing,
            'started_at' => now(),
            'finished_at' => null,
            'failure_reason' => null,
        ]);

        try {
            $this->parser->assertHeaders($this->reader->readHeaders($path));
            $this->imports->update($import, ['total_rows' => $this->reader->countRows($path)]);

            $report = $this->processRows($import, $path);
        } catch (InvalidSpreadsheetException $e) {
            return $this->fail($import, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Product import crashed', ['import_id' => $import->id, 'exception' => $e]);
            $this->fail($import, 'Внутренняя ошибка импорта: '.$e->getMessage());

            throw $e;
        }

        return $this->imports->update($import, [
            ...$report,
            'status' => ImportStatus::Completed,
            'finished_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function processRows(Import $import, string $path): array
    {
        $state = [
            'processed_rows' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'failed_count' => 0,
            'errors' => [],
            'warnings' => [],
        ];
        /** @var array<string, int> $seenCodes */
        $seenCodes = [];
        $lastFlush = microtime(true);

        foreach ($this->reader->rows($path) as $rowNumber => $row) {
            try {
                $data = $this->parser->parse($row);

                if (isset($seenCodes[$data->externalCode])) {
                    $this->addEntry($state['warnings'], [
                        'row' => $rowNumber,
                        'external_code' => $data->externalCode,
                        'message' => sprintf('Внешний код уже встречался в строке %d, данные перезаписаны.', $seenCodes[$data->externalCode]),
                    ]);
                }
                $seenCodes[$data->externalCode] ??= $rowNumber;

                $images = $this->downloadImages($data, $rowNumber, $state['warnings']);
                $this->persist($data, $images) ? $state['created_count']++ : $state['updated_count']++;
            } catch (RowValidationException $e) {
                $state['failed_count']++;
                $this->addEntry($state['errors'], [
                    'row' => $rowNumber,
                    'external_code' => $e->externalCode,
                    'messages' => $e->messages,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Import row failed', ['import_id' => $import->id, 'row' => $rowNumber, 'exception' => $e]);
                $state['failed_count']++;
                $this->addEntry($state['errors'], [
                    'row' => $rowNumber,
                    'external_code' => $row[(string) config('import.columns.external_code')] ?? null,
                    'messages' => ['Не удалось сохранить товар: '.$e->getMessage()],
                ]);
            }

            $state['processed_rows']++;

            if ($state['processed_rows'] % self::PROGRESS_EVERY_ROWS === 0
                || microtime(true) - $lastFlush >= self::PROGRESS_EVERY_SECONDS
            ) {
                $this->imports->update($import, $state);
                $lastFlush = microtime(true);
            }
        }

        return $state;
    }

    /**
     * @param  list<array<string, mixed>>  $warnings
     * @return list<array{url: string, path: string|null}>
     */
    private function downloadImages(ProductData $data, int $rowNumber, array &$warnings): array
    {
        $images = [];

        foreach ($data->imageUrls as $url) {
            $path = null;

            if (! isset($this->failedImageUrls[$url])) {
                try {
                    $path = $this->imageDownloader->download($url);
                } catch (ImageDownloadException $e) {
                    $this->failedImageUrls[$url] = $e->getMessage();
                }
            }

            if ($path === null) {
                $this->addEntry($warnings, [
                    'row' => $rowNumber,
                    'external_code' => $data->externalCode,
                    'message' => $this->failedImageUrls[$url],
                ]);
            }

            $images[] = ['url' => $url, 'path' => $path];
        }

        return $images;
    }

    /**
     * @param  list<array{url: string, path: string|null}>  $images
     */
    private function persist(ProductData $data, array $images): bool
    {
        return DB::transaction(function () use ($data, $images): bool {
            $product = $this->products->findByExternalCode($data->externalCode);
            $created = $product === null;

            $product = $created
                ? $this->products->create($data->toProductAttributes())
                : $this->products->update($product, $data->toProductAttributes());

            $this->attributes->replaceForProduct($product, $data->attributes);
            $this->images->replaceForProduct($product, $images);

            return $created;
        });
    }

    private function fail(Import $import, string $reason): Import
    {
        return $this->imports->update($import, [
            'status' => ImportStatus::Failed,
            'failure_reason' => $reason,
            'finished_at' => now(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, mixed>  $entry
     */
    private function addEntry(array &$entries, array $entry): void
    {
        if (count($entries) < self::MAX_REPORT_ENTRIES) {
            $entries[] = $entry;
        }
    }
}
