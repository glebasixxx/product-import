<?php

namespace App\Services;

use App\DTO\ProductFilter;
use App\Models\Product;
use App\Repositories\Contracts\ProductAttributeRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\DiscountCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductAttributeRepositoryInterface $attributes,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function list(ProductFilter $filter): LengthAwarePaginator
    {
        return $this->products->paginate($filter);
    }

    public function find(int $id): ?Product
    {
        return $this->products->findWithRelations($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $product = DB::transaction(function () use ($data): Product {
            $product = $this->products->create($this->withDiscount($data));
            $this->syncAttributes($product, $data);

            return $product;
        });

        return $this->products->findWithRelations($product->id) ?? $product;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        DB::transaction(function () use ($product, $data): void {
            $merged = [
                'price' => $product->price,
                'purchase_price' => $product->purchase_price,
                ...$data,
            ];
            $this->products->update($product, $this->withDiscount($merged));
            $this->syncAttributes($product, $data);
        });

        return $this->products->findWithRelations($product->id) ?? $product;
    }

    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withDiscount(array $data): array
    {
        $price = (float) $data['price'];
        $purchasePrice = isset($data['purchase_price']) ? (float) $data['purchase_price'] : null;

        unset($data['attributes']);
        $data['discount'] = DiscountCalculator::calculate($price, $purchasePrice);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncAttributes(Product $product, array $data): void
    {
        if (! array_key_exists('attributes', $data) || ! is_array($data['attributes'])) {
            return;
        }

        $attributes = [];
        foreach ($data['attributes'] as $attribute) {
            $attributes[(string) $attribute['key']] = (string) $attribute['value'];
        }

        $this->attributes->replaceForProduct($product, $attributes);
    }
}
