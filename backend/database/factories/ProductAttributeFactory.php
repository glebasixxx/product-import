<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    public const KEYS = ['Размер', 'Цвет', 'Бренд', 'Состав', 'Кол-во в упаковке', 'Категория товара'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'key' => fake()->unique()->word(),
            'value' => fake()->words(2, true),
        ];
    }
}
