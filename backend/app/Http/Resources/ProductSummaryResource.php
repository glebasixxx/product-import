<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductSummaryResource extends JsonResource
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
            'price' => (float) $this->price,
            'purchase_price' => $this->purchase_price !== null ? (float) $this->purchase_price : null,
            'discount' => $this->discount !== null ? (float) $this->discount : null,
            'thumbnail_url' => $this->whenLoaded('images', fn () => $this->images->first()?->publicUrl()),
        ];
    }
}
