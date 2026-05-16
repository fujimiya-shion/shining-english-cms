<?php

namespace App\Services;

use App\Repositories\IRepository;
use App\ValueObjects\QueryOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Service implements IService
{
    protected IRepository $repository;

    public function __construct(IRepository $repository)
    {
        $this->repository = $repository;
    }

    public function query(array $with = []): Builder
    {
        return $this->repository->query($with);
    }

    public function getAll(?QueryOption $options = null): Collection
    {
        return $this->repository->getAll($options);
    }

    public function getById(int $id, array $eagers = []): ?Model
    {
        return $this->repository->getById($id, $eagers);
    }

    public function getBy(array $criteria, ?QueryOption $options = null): Collection
    {
        return $this->repository->getBy($criteria, $options);
    }

    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Model
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id, bool $force = false): bool
    {
        return $this->repository->delete($id, $force);
    }

    public function count(array $criteria = []): int
    {
        return $this->repository->count($criteria);
    }

    public function autoComplete(
        string $term,
        ?string $column = 'name',
        array $selectedColumns = ['*'],
        ?QueryOption $options = null
    ): Collection {
        $options ??= new QueryOption;

        return $this->repository->autoComplete($term, $column, $selectedColumns, $options);
    }

    public function paginateAll(?QueryOption $options = null): LengthAwarePaginator
    {
        return $this->repository->paginateAll($options);
    }

    public function paginateBy(array $criteria, ?QueryOption $options = null): LengthAwarePaginator
    {
        return $this->repository->paginateBy($criteria, $options);
    }
}
