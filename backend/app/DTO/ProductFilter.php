<?php

namespace App\DTO;

final readonly class ProductFilter
{
    public const SORTABLE = ['id', 'name', 'price', 'discount', 'created_at'];

    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        public ?string $name = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public string $sort = 'id',
        public string $direction = 'asc',
    ) {}

    /**
     * @param  array<string, mixed>  $input  validated query parameters
     */
    public static function fromArray(array $input): self
    {
        return new self(
            page: (int) ($input['page'] ?? 1),
            limit: (int) ($input['limit'] ?? 20),
            name: isset($input['name']) && $input['name'] !== '' ? (string) $input['name'] : null,
            priceMin: isset($input['price_min']) ? (float) $input['price_min'] : null,
            priceMax: isset($input['price_max']) ? (float) $input['price_max'] : null,
            sort: (string) ($input['sort'] ?? 'id'),
            direction: (string) ($input['direction'] ?? 'asc'),
        );
    }
}
