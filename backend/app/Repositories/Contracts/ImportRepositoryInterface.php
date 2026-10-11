<?php

namespace App\Repositories\Contracts;

use App\Models\Import;
use Illuminate\Support\Collection;

interface ImportRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Import;

    public function find(string $id): ?Import;

    /**
     * @return Collection<int, Import>
     */
    public function latest(int $limit = 10): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Import $import, array $attributes): Import;
}
