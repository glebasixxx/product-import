<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Repositories\Contracts\ProductAttributeRepositoryInterface;

class ProductAttributeRepository implements ProductAttributeRepositoryInterface
{
    public function replaceForProduct(Product $product, array $attributes): void
    {
        ProductAttribute::query()->where('product_id', $product->id)->delete();

        if ($attributes === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($attributes as $key => $value) {
            $rows[] = [
                'product_id' => $product->id,
                'key' => (string) $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ProductAttribute::query()->insert($rows);
        $product->unsetRelation('productAttributes');
    }
}
