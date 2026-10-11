<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    public function test_index_is_paginated_on_the_server(): void
    {
        Product::factory()->count(25)->create();

        $this->getJson('/api/products?page=2&limit=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.limit', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.total_pages', 3)
            ->assertJsonStructure(['data' => [['id', 'external_code', 'name', 'price', 'discount', 'thumbnail_url']]]);

        $this->getJson('/api/products?page=3&limit=10')->assertJsonCount(5, 'data');
    }

    public function test_index_filters_by_name_and_price_range(): void
    {
        Product::factory()->create(['name' => 'Red shirt', 'price' => 100]);
        Product::factory()->create(['name' => 'Blue shirt', 'price' => 500]);
        Product::factory()->create(['name' => 'Red hat', 'price' => 900]);

        $this->getJson('/api/products?name=shirt')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/products?name=red&price_min=200')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Red hat');

        $this->getJson('/api/products?price_min=100&price_max=500&sort=price&direction=desc')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Blue shirt');
    }

    public function test_index_validates_query(): void
    {
        $this->getJson('/api/products?limit=1000')->assertUnprocessable()->assertJsonValidationErrors('limit');
        $this->getJson('/api/products?price_min=500&price_max=100')->assertJsonValidationErrors('price_max');
        $this->getJson('/api/products?sort=password')->assertJsonValidationErrors('sort');
    }

    public function test_show_returns_attributes_and_images(): void
    {
        $product = Product::factory()->withDetails(attributes: 3, images: 2)->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonCount(3, 'attributes')
            ->assertJsonCount(2, 'images')
            ->assertJsonStructure([
                'id', 'external_code', 'name', 'description', 'price', 'purchase_price', 'discount',
                'attributes' => [['id', 'key', 'value']],
                'images' => [['id', 'url', 'path', 'public_url', 'position']],
            ]);
    }

    public function test_show_returns_404_for_unknown_product(): void
    {
        $this->getJson('/api/products/999999')->assertNotFound();
    }

    public function test_store_creates_product_and_calculates_discount(): void
    {
        $response = $this->postJson('/api/products', [
            'external_code' => 'EXT-1',
            'name' => 'Бермуды',
            'price' => 1320,
            'purchase_price' => 880,
            'attributes' => [
                ['key' => 'Цвет', 'value' => 'Grigio'],
                ['key' => 'Размер', 'value' => '46(M)'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('discount', 33.33)
            ->assertJsonCount(2, 'attributes');

        $this->assertDatabaseHas('products', ['external_code' => 'EXT-1', 'discount' => 33.33]);
        $this->assertDatabaseCount('product_attributes', 2);
    }

    public function test_store_validates_payload_and_unique_external_code(): void
    {
        Product::factory()->create(['external_code' => 'DUP']);

        $this->postJson('/api/products', ['external_code' => 'DUP', 'price' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['external_code', 'name', 'price']);
    }

    public function test_update_recalculates_discount(): void
    {
        $product = Product::factory()->create(['price' => 100, 'purchase_price' => 50, 'discount' => 50]);

        $this->patchJson("/api/products/{$product->id}", ['price' => 200])
            ->assertOk()
            ->assertJsonPath('price', 200)
            ->assertJsonPath('discount', 75);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'discount' => 75]);
    }

    public function test_update_replaces_attributes(): void
    {
        $product = Product::factory()->withDetails(attributes: 3, images: 0)->create();

        $this->putJson("/api/products/{$product->id}", [
            'attributes' => [['key' => 'Бренд', 'value' => 'OMSA']],
        ])->assertOk()->assertJsonCount(1, 'attributes')->assertJsonPath('attributes.0.key', 'Бренд');
    }

    public function test_destroy_deletes_product_with_relations(): void
    {
        $product = Product::factory()->withDetails()->create();

        $this->deleteJson("/api/products/{$product->id}")->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseCount('product_attributes', 0);
        $this->assertDatabaseCount('product_images', 0);
    }
}
