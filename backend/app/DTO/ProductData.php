<?php

namespace App\DTO;

use App\Support\DiscountCalculator;

final readonly class ProductData
{
    /**
     * @param  array<string, string>  $attributes  key => value
     * @param  list<string>  $imageUrls
     */
    public function __construct(
        public string $externalCode,
        public string $name,
        public ?string $description,
        public float $price,
        public ?float $purchasePrice,
        public array $attributes = [],
        public array $imageUrls = [],
    ) {}

    public function discount(): ?float
    {
        return DiscountCalculator::calculate($this->price, $this->purchasePrice);
    }

    /**
     * @return array<string, mixed>
     */
    public function toProductAttributes(): array
    {
        return [
            'external_code' => $this->externalCode,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'purchase_price' => $this->purchasePrice,
            'discount' => $this->discount(),
        ];
    }
}
