<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $external_code
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property string|null $purchase_price
 * @property string|null $discount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductAttribute> $productAttributes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductImage> $images
 */
#[Fillable(['external_code', 'name', 'description', 'price', 'purchase_price', 'discount'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return HasMany<ProductAttribute, $this>
     */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('id');
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'discount' => 'decimal:2',
        ];
    }
}
