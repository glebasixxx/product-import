<?php

namespace App\Repositories\Contracts;

use App\Models\Product;

interface ProductImageRepositoryInterface
{
    /**
     * @param  list<array{url: string, path: string|null}>  $images
     */
    public function replaceForProduct(Product $product, array $images): void;
}
