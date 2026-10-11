<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Product;
use App\Services\Import\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\XlsxBuilder;
use Tests\TestCase;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Http::preventStrayRequests();
    }

    private function fakeImages(): void
    {
        Http::fake(['*' => Http::response(XlsxBuilder::png(), 200, ['Content-Type' => 'image/png'])]);
    }

    private function importFile(string $sourcePath): Import
    {
        Storage::disk('local')->put('imports/test.xlsx', (string) file_get_contents($sourcePath));

        return Import::query()->create([
            'original_filename' => 'test.xlsx',
            'file_path' => 'imports/test.xlsx',
        ]);
    }

    private function process(Import $import): Import
    {
        return app(ProductImportService::class)->process($import)->refresh();
    }

    public function test_imports_example_file(): void
    {
        $this->fakeImages();
        $import = $this->process($this->importFile(base_path('tests/Fixtures/import_example.xlsx')));

        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertSame(40, $import->total_rows);
        $this->assertSame(40, $import->processed_rows);
        $this->assertSame(40, $import->created_count);
        $this->assertSame(0, $import->failed_count);
        $this->assertSame(100, $import->progressPercent());

        $this->assertDatabaseCount('products', 40);

        $product = Product::query()->where('external_code', '3UHfAid1jaMiwgBuNvnsf3')->firstOrFail();
        $this->assertSame('Бермуды мужские, Grigio/Verde, OMSA, 46(M), РОССИЯ', $product->name);
        $this->assertSame('1320.00', $product->price);
        $this->assertSame('33.33', $product->discount);
        $this->assertStringStartsWith('Мужские трикотажные домашние бермуды', (string) $product->description);

        $attributes = $product->productAttributes()->pluck('value', 'key')->all();
        $this->assertSame('46(M)', $attributes['Размер']);
        $this->assertSame('OMSA', $attributes['Бренд']);
        $this->assertSame('Женщинам_Белье', $attributes['Категория товара']);
        $this->assertArrayNotHasKey('Ссылки на фото', $attributes);

        $images = $product->images()->get();
        $this->assertCount(4, $images);
        $this->assertSame('http://catalog.collant.ru/pics/SNL-504038_m.jpg', $images[0]->url);
        $this->assertNotNull($images[0]->path);
        Storage::disk('public')->assertExists((string) $images[0]->path);
    }

    public function test_shared_images_are_downloaded_once(): void
    {
        $this->fakeImages();
        $this->process($this->importFile(base_path('tests/Fixtures/import_example.xlsx')));

        $urls = Http::recorded()->map(fn (array $pair) => $pair[0]->url());
        $this->assertSame($urls->count(), $urls->unique()->count());
    }

    public function test_reimport_updates_instead_of_duplicating(): void
    {
        $this->fakeImages();
        $path = XlsxBuilder::make([['A-1', 'Old name', '100', '', '50', 'Red', 'M', '']]);
        $this->process($this->importFile($path));

        $path = XlsxBuilder::make([
            ['A-1', 'New name', '200', 'Desc', '50', 'Blue', '', ''],
            ['A-2', 'Second', '10', '', '', '', '', ''],
        ]);
        $import = $this->process($this->importFile($path));

        $this->assertSame(1, $import->created_count);
        $this->assertSame(1, $import->updated_count);
        $this->assertDatabaseCount('products', 2);

        $product = Product::query()->where('external_code', 'A-1')->firstOrFail();
        $this->assertSame('New name', $product->name);
        $this->assertSame('75.00', $product->discount);
        $this->assertSame(['Цвет' => 'Blue'], $product->productAttributes()->pluck('value', 'key')->all());
    }

    public function test_invalid_rows_are_reported_and_do_not_stop_import(): void
    {
        $this->fakeImages();
        $path = XlsxBuilder::make([
            ['OK-1', 'Valid product', '100', '', '', '', '', ''],
            ['', 'No code', '100', '', '', '', '', ''],
            ['BAD-PRICE', 'Bad price', 'дорого', '', '', '', '', ''],
            ['OK-2', 'Another valid', '1 500,50', '', '1000', '', '', ''],
        ]);

        $import = $this->process($this->importFile($path));

        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertSame(4, $import->processed_rows);
        $this->assertSame(2, $import->created_count);
        $this->assertSame(2, $import->failed_count);
        $this->assertDatabaseCount('products', 2);

        $errors = $import->errors ?? [];
        $this->assertCount(2, $errors);
        $this->assertSame(3, $errors[0]['row']);
        $this->assertNull($errors[0]['external_code']);
        $this->assertSame(4, $errors[1]['row']);
        $this->assertSame('BAD-PRICE', $errors[1]['external_code']);
        $this->assertStringContainsString('не является числом', $errors[1]['messages'][0]);
    }

    public function test_image_download_failure_is_a_warning(): void
    {
        Http::fake([
            'good.test/*' => Http::response(XlsxBuilder::png(), 200),
            'broken.test/*' => Http::response('Not found', 404),
            'html.test/*' => Http::response('<html></html>', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $path = XlsxBuilder::make([[
            'IMG-1', 'With images', '100', '', '', '', '',
            'http://good.test/a.png, http://broken.test/b.jpg, http://html.test/c.jpg, not-a-url',
        ]]);

        $import = $this->process($this->importFile($path));

        $this->assertSame(1, $import->created_count);
        $this->assertCount(3, $import->warnings ?? []);

        $images = Product::query()->firstOrFail()->images()->get();
        $this->assertCount(4, $images);
        $this->assertNotNull($images[0]->path);
        $this->assertNull($images[1]->path);
        $this->assertSame('http://broken.test/b.jpg', $images[1]->publicUrl());
        $this->assertNull($images[2]->path);
    }

    public function test_unreachable_host_is_skipped_after_repeated_failures(): void
    {
        Http::fake(['dead.test/*' => Http::failedConnection()]);

        $urls = implode(', ', array_map(fn (int $i) => "http://dead.test/{$i}.jpg", range(1, 5)));
        $path = XlsxBuilder::make([['DEAD', 'Dead images', '100', '', '', '', '', $urls]]);

        $import = $this->process($this->importFile($path));

        $this->assertSame(1, $import->created_count);
        $messages = array_column($import->warnings ?? [], 'message');
        $this->assertCount(5, $messages);
        $this->assertStringContainsString('Не удалось скачать', $messages[2]);
        $this->assertStringContainsString('недоступен', $messages[3]);
        $this->assertStringContainsString('недоступен', $messages[4]);
    }

    public function test_duplicate_codes_inside_one_file_produce_warning(): void
    {
        $this->fakeImages();
        $path = XlsxBuilder::make([
            ['DUP', 'First', '100', '', '', '', '', ''],
            ['DUP', 'Second', '200', '', '', '', '', ''],
        ]);

        $import = $this->process($this->importFile($path));

        $this->assertDatabaseCount('products', 1);
        $this->assertSame('Second', Product::query()->firstOrFail()->name);
        $this->assertSame(3, ($import->warnings ?? [])[0]['row']);
    }

    public function test_file_without_required_columns_fails(): void
    {
        $this->fakeImages();
        $path = XlsxBuilder::make([['x', 'y']], headers: ['Код', 'Название']);

        $import = $this->process($this->importFile($path));

        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertStringContainsString('Внешний код', (string) $import->failure_reason);
        $this->assertDatabaseCount('products', 0);
    }
}
