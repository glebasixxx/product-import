<?php

namespace App\Repositories\Contracts;

use App\DTO\ProductFilter;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(ProductFilter $filter): LengthAwarePaginator;

    public function findWithRelations(int $id): ?Product;

    public function findByExternalCode(string $externalCode): ?Product;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product;

    public function delete(Product $product): void;
}
