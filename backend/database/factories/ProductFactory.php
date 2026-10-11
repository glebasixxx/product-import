<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Support\DiscountCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 100, 5000);
        $purchasePrice = round($price * fake()->randomFloat(2, 0.4, 0.9), 2);

        return [
            'external_code' => Str::random(22),
            'name' => Str::ucfirst(fake()->words(3, true)),
            'description' => fake()->paragraph(),
            'price' => $price,
            'purchase_price' => $purchasePrice,
            'discount' => DiscountCalculator::calculate($price, $purchasePrice),
        ];
    }

    public function withDetails(int $attributes = 4, int $images = 2): static
    {
        return $this
            ->has(ProductAttribute::factory()->count($attributes)->sequence(
                fn ($sequence) => ['key' => ProductAttributeFactory::KEYS[$sequence->index % count(ProductAttributeFactory::KEYS)]],
            ), 'productAttributes')
            ->has(ProductImage::factory()->count($images)->sequence(
                fn ($sequence) => ['position' => $sequence->index],
            ), 'images');
    }
}
