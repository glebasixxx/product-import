<?php

namespace Tests\Feature;

use App\Jobs\ProcessProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Bus::fake();
        $this->signIn();
    }

    private function exampleFile(string $name = 'import.xlsx'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/import_example.xlsx'), $name, null, null, true);
    }

    public function test_upload_queues_import_and_returns_202(): void
    {
        $response = $this->postJson('/api/imports', ['file' => $this->exampleFile()]);

        $response->assertAccepted()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('original_filename', 'import.xlsx')
            ->assertJsonStructure(['id', 'status', 'progress', 'errors', 'warnings']);

        $this->assertDatabaseHas('imports', ['id' => $response->json('id'), 'status' => 'pending']);
        Bus::assertDispatched(ProcessProductImport::class, fn (ProcessProductImport $job) => $job->importId === $response->json('id')
            && $job->queue === 'imports');
    }

    public function test_status_endpoint_returns_import(): void
    {
        $id = $this->postJson('/api/imports', ['file' => $this->exampleFile()])->json('id');

        $this->getJson("/api/imports/{$id}")->assertOk()->assertJsonPath('id', $id);
        $this->getJson('/api/imports')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/imports/'.fake()->uuid())->assertNotFound();
    }

    public function test_file_is_required(): void
    {
        $this->postJson('/api/imports')->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_rejects_wrong_extension(): void
    {
        $this->postJson('/api/imports', ['file' => $this->exampleFile('import.csv')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_rejects_wrong_mime_type(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($path, 'just some text');
        $file = new UploadedFile($path, 'fake.xlsx', null, null, true);

        $this->postJson('/api/imports', ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_rejects_too_large_file(): void
    {
        config(['import.max_file_size_kb' => 10]);

        $this->postJson('/api/imports', ['file' => $this->exampleFile()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        Bus::assertNothingDispatched();
    }

    public function test_import_endpoint_is_rate_limited(): void
    {
        config(['import.rate_limit_per_minute' => 2]);

        $this->postJson('/api/imports', ['file' => $this->exampleFile()])->assertAccepted();
        $this->postJson('/api/imports', ['file' => $this->exampleFile()])->assertAccepted();
        $this->postJson('/api/imports', ['file' => $this->exampleFile()])->assertTooManyRequests();
    }
}
