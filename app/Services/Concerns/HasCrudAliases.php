<?php

namespace App\Services\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait HasCrudAliases
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->list($filters);
    }

    public function find(int $id): mixed
    {
        return $this->get($id);
    }
}
