<?php

namespace App\Repositories\Eloquent;

use App\DTO\ProductFilter;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginate(ProductFilter $filter): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['images' => fn ($q) => $q->limit(1)]);

        if ($filter->name !== null) {
            $query->whereLike('name', '%'.$filter->name.'%');
        }

        if ($filter->priceMin !== null) {
            $query->where('price', '>=', $filter->priceMin);
        }

        if ($filter->priceMax !== null) {
            $query->where('price', '<=', $filter->priceMax);
        }

        $sort = in_array($filter->sort, ProductFilter::SORTABLE, true) ? $filter->sort : 'id';
        $direction = $filter->direction === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(perPage: $filter->limit, page: $filter->page);
    }

    public function findWithRelations(int $id): ?Product
    {
        return Product::query()
            ->with(['productAttributes', 'images'])
            ->find($id);
    }

    public function findByExternalCode(string $externalCode): ?Product
    {
        return Product::query()->where('external_code', $externalCode)->first();
    }

    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->fill($attributes)->save();

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
