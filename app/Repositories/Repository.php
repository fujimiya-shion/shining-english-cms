<?php

namespace App\Repositories;

use App\ValueObjects\QueryOption;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Throwable;

abstract class Repository implements IRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    protected function applyQueryOption(Builder $query, ?QueryOption $options = null): Builder
    {
        $options ??= new QueryOption;

        if (! empty($options->with)) {
            $query->with($options->with);
        }

        return $query;
    }

    protected function applyDefaultOrderIfMissing(Builder $query, ?QueryOption $options = null): Builder
    {
        $options ??= new QueryOption;

        if (! empty($query->getQuery()->orders)) {
            return $query;
        }

        $orderBy = $options->getOrderBy();
        if ($orderBy === '' || ! Schema::hasColumn($this->model->getTable(), $orderBy)) {
            return $query;
        }

        return $query->orderBy($this->model->qualifyColumn($orderBy), $options->getOrderDirection());
    }

    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $column => $value) {
            if (! is_array($value)) {
                $value === null ? $query->whereNull($column) : $query->where($column, '=', $value);

                continue;
            }

            $op = strtolower((string) ($value[0] ?? '='));
            $val = $value[1] ?? null;

            switch ($op) {
                case 'in':
                    $query->whereIn($column, is_array($val) ? $val : [$val]);
                    break;
                case 'not in':
                case 'nin':
                    $query->whereNotIn($column, is_array($val) ? $val : [$val]);
                    break;
                case 'between':
                    $query->whereBetween($column, is_array($val) ? $val : []);
                    break;
                case 'not between':
                    $query->whereNotBetween($column, is_array($val) ? $val : []);
                    break;
                case 'null':
                    $query->whereNull($column);
                    break;
                case 'not null':
                    $query->whereNotNull($column);
                    break;

                case 'like':
                case 'not like':
                case '=':
                case '!=':
                case '<>':
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $query->where($column, $op, $val);
                    break;

                default:
                    $query->where($column, '=', $val);
                    break;
            }
        }

        return $query;
    }

    /* =========================
     |  Query APIs
     ========================= */

    public function query(array $with = []): Builder
    {
        $query = $this->model->newQuery();

        if ($with !== []) {
            $query->with($with);
        }

        return $this->applyDefaultOrderIfMissing($query);
    }

    public function getAll(?QueryOption $options = null): Collection
    {
        $query = $this->applyQueryOption($this->model->newQuery(), $options);
        $query = $this->applyDefaultOrderIfMissing($query, $options);

        return $query->get();
    }

    public function paginateAll(?QueryOption $options = null): LengthAwarePaginator
    {
        $options ??= new QueryOption;
        $query = $this->applyQueryOption($this->model->newQuery(), $options);
        $query = $this->applyDefaultOrderIfMissing($query, $options);

        return $query->paginate(perPage: $options->perPage, page: $options->page);
    }

    public function getById(int $id, array $eagers = []): ?Model
    {
        $query = $this->model->newQuery();

        if (! empty($eagers)) {
            $query->with($eagers);
        }

        return $query->find($id);
    }

    public function getBy(array $criteria, ?QueryOption $options = null): Collection
    {
        $query = $this->model->newQuery();
        $query = $this->applyCriteria($query, $criteria);
        $query = $this->applyQueryOption($query, $options);
        $query = $this->applyDefaultOrderIfMissing($query, $options);

        return $query->get();
    }

    public function paginateBy(array $criteria, ?QueryOption $options = null): LengthAwarePaginator
    {
        $options ??= new QueryOption;
        $query = $this->model->newQuery();
        $query = $this->applyCriteria($query, $criteria);
        $query = $this->applyQueryOption($query, $options);
        $query = $this->applyDefaultOrderIfMissing($query, $options);

        return $query->paginate(perPage: $options->perPage, page: $options->page);
    }

    /* =========================
     |  Write APIs
     ========================= */

    public function create(array $data): Model
    {
        try {
            DB::beginTransaction();
            $created = $this->model->create($data);
            DB::commit();

            return $created;
        } catch (Throwable $e) {
            DB::rollBack();
            logger()->error('An error occured when create new '.class_basename($this->model).' record');
            logger()->error($e->getTraceAsString());
            throw $e;
        }
    }

    public function update(int $id, array $data): Model
    {
        try {
            DB::beginTransaction();

            /** @var Model $record */
            $record = $this->model->newQuery()->findOrFail($id);

            $record->fill($data);
            $record->save();

            DB::commit();

            return $record->refresh();
        } catch (Throwable $e) {
            DB::rollBack();
            logger()->error('An error occured when update '.class_basename($this->model)." record #{$id}");
            logger()->error($e->getTraceAsString());
            throw $e;
        }
    }

    public function count(array $criteria = []): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyCriteria($query, $criteria);

        return $query->count();
    }

    public function delete(int $id, bool $force = false): bool
    {
        $query = $this->model->newQuery()->where('id', $id);
        try {
            $rowAffected = $force ? $query->forceDelete() : $query->delete();

            return is_int($rowAffected) && $rowAffected > 0;
        } catch (Throwable $e) {
            logger()->error('An error occured when delete model: '.class_basename($this->model));
            logger()->error($e->getTraceAsString());
            throw $e;
        }
    }

    /* =========================
     |  AutoComplete (NO LIKE)
     ========================= */

    protected function nextPrefix(string $prefix): ?string
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return null;
        }

        $chars = str_split($prefix);
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            $o = ord($chars[$i]);
            if ($o < 255) {
                $chars[$i] = chr($o + 1);

                return implode('', array_slice($chars, 0, $i + 1));
            }
        }

        return null;
    }

    protected function applyPrefixMatch(Builder $query, string $column, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $lower = $term;
        $upper = $this->nextPrefix($term);

        $query->where($column, '>=', $lower);

        if ($upper !== null) {
            $query->where($column, '<', $upper);
        }

        return $query;
    }

    public function autoComplete(
        string $term,
        ?string $column = 'name',
        array $selectedColumns = ['*'],
        ?QueryOption $options = null
    ): Collection {
        $options ??= new QueryOption;

        $term = trim($term);
        if ($term === '') {
            return new Collection;
        }

        $query = $this->model->newQuery();
        $query = $this->applyQueryOption($query, $options);

        $query->select($selectedColumns);
        $this->applyPrefixMatch($query, $column, $term);

        return $query
            ->orderBy($column)
            ->limit($options->perPage)
            ->get();
    }
}
