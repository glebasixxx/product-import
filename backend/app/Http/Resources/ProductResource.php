<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_code' => $this->external_code,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'purchase_price' => $this->purchase_price !== null ? (float) $this->purchase_price : null,
            'discount' => $this->discount !== null ? (float) $this->discount : null,
            'attributes' => ProductAttributeResource::collection($this->whenLoaded('productAttributes')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
