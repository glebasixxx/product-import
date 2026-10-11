<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_fill_all_tables(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('products', 30);
        $this->assertDatabaseCount('product_attributes', 120);
        $this->assertDatabaseCount('product_images', 60);
        $this->assertDatabaseHas('users', ['email' => config('auth.admin.email')]);
    }
}
