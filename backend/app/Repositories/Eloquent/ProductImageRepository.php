<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\Contracts\ProductImageRepositoryInterface;

class ProductImageRepository implements ProductImageRepositoryInterface
{
    public function replaceForProduct(Product $product, array $images): void
    {
        ProductImage::query()->where('product_id', $product->id)->delete();

        if ($images === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($images as $position => $image) {
            $rows[] = [
                'product_id' => $product->id,
                'url' => $image['url'],
                'path' => $image['path'],
                'position' => $position,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ProductImage::query()->insert($rows);
        $product->unsetRelation('images');
    }
}
