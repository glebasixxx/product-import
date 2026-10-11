<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seed = fake()->unique()->numberBetween(1, 100000);

        return [
            'product_id' => Product::factory(),
            'url' => "https://picsum.photos/seed/{$seed}/600/600",
            'path' => null,
            'position' => 0,
        ];
    }
}
