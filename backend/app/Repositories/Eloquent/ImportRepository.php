<?php

namespace App\Repositories\Eloquent;

use App\Models\Import;
use App\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Support\Collection;

class ImportRepository implements ImportRepositoryInterface
{
    public function create(array $attributes): Import
    {
        return Import::query()->create($attributes);
    }

    public function find(string $id): ?Import
    {
        return Import::query()->find($id);
    }

    public function latest(int $limit = 10): Collection
    {
        return Import::query()->latest()->limit($limit)->get();
    }

    public function update(Import $import, array $attributes): Import
    {
        $import->fill($attributes)->save();

        return $import;
    }
}
