<?php

namespace App\Repositories\Contracts;

use App\Models\Product;

interface ProductAttributeRepositoryInterface
{
    /**
     * @param  array<string, string>  $attributes  key => value
     */
    public function replaceForProduct(Product $product, array $attributes): void;
}
