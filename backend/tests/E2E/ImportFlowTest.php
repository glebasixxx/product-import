<?php

namespace Tests\E2E;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\XlsxBuilder;
use Tests\TestCase;

class ImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_imports_file_and_browses_products(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Http::fake(['*' => Http::response(XlsxBuilder::png(), 200)]);
        $this->seed(AdminUserSeeder::class);

        $token = $this->postJson('/api/auth/login', [
            'email' => config('auth.admin.email'),
            'password' => config('auth.admin.password'),
        ])->assertOk()->json('token');

        $file = new UploadedFile(base_path('tests/Fixtures/import_example.xlsx'), 'import example.xlsx', null, null, true);
        $importId = $this->withToken($token)
            ->post('/api/imports', ['file' => $file], ['Accept' => 'application/json'])
            ->assertAccepted()
            ->json('id');

        $this->withToken($token)->getJson("/api/imports/{$importId}")
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('progress', 100)
            ->assertJsonPath('created_count', 40)
            ->assertJsonPath('failed_count', 0);

        $list = $this->withToken($token)->getJson('/api/products?page=1&limit=20&name=Бермуды')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $productId = $list->json('data.0.id');
        $this->assertNotNull($list->json('data.0.thumbnail_url'));

        $this->withToken($token)->getJson("/api/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('external_code', '3UHfAid1jaMiwgBuNvnsf3')
            ->assertJsonPath('discount', 33.33)
            ->assertJsonCount(4, 'images')
            ->assertJsonFragment(['key' => 'Бренд', 'value' => 'OMSA']);

        $file = new UploadedFile(base_path('tests/Fixtures/import_example.xlsx'), 'again.xlsx', null, null, true);
        $this->withToken($token)
            ->post('/api/imports', ['file' => $file], ['Accept' => 'application/json'])
            ->assertAccepted()
            ->assertJsonPath('updated_count', 40)
            ->assertJsonPath('created_count', 0);

        $this->withToken($token)->getJson('/api/products')->assertJsonPath('meta.total', 40);
    }
}
