<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(ProductIndexRequest $request): ProductCollection
    {
        return new ProductCollection($this->products->list($request->toFilter()));
    }

    public function show(int $product): ProductResource
    {
        return new ProductResource($this->findOrFail($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateProductRequest $request, int $product): ProductResource
    {
        return new ProductResource($this->products->update($this->findOrFail($product), $request->validated()));
    }

    public function destroy(int $product): Response
    {
        $this->products->delete($this->findOrFail($product));

        return response()->noContent();
    }

    private function findOrFail(int $id): Product
    {
        return $this->products->find($id) ?? abort(Response::HTTP_NOT_FOUND, 'Товар не найден.');
    }
}
