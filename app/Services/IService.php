<?php

namespace App\Services;

use App\ValueObjects\QueryOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface IService
{
    public function query(array $with = []): Builder;

    /**
     * @return Collection<int, Model>
     */
    public function getAll(?QueryOption $options = null): Collection;

    /**
     * @param  array<string>  $eagers
     */
    public function getById(int $id, array $eagers = []): ?Model;

    /**
     * @return Collection<int, Model>
     */
    public function getBy(array $criteria, ?QueryOption $options = null): Collection;

    public function create(array $data): Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id, bool $force = false): bool;

    public function count(array $criteria = []): int;

    /**
     * @return Collection<int, Model>
     */
    public function autoComplete(
        string $term,
        ?string $column = 'name',
        array $selectedColumns = ['*'],
        ?QueryOption $options = null
    ): Collection;

    public function paginateAll(?QueryOption $options = null): LengthAwarePaginator;

    public function paginateBy(array $criteria, ?QueryOption $options = null): LengthAwarePaginator;
}
