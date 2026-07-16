<?php

class EmptyOrderByRepository extends \App\Repositories\Repository
{
    public function __construct(\Illuminate\Database\Eloquent\Model $model)
    {
        $this->model = $model;
    }

    protected function getDefaultOrderBy(): string
    {
        return '';
    }
}

class EmptyOrderModel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'test_empty_order';

    public $timestamps = false;
}

use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    \Illuminate\Support\Facades\Schema::create('test_empty_order', function ($t): void {
        $t->id();
        $t->string('name');
    });
});

afterEach(function (): void {
    \Illuminate\Support\Facades\Schema::dropIfExists('test_empty_order');
});

it('skips ordering when default order by is empty', function (): void {
    $model = new EmptyOrderModel;
    $repo = new EmptyOrderByRepository($model);
    $query = EmptyOrderModel::query();

    $method = new ReflectionMethod(\App\Repositories\Repository::class, 'applyDefaultOrderIfMissing');
    $result = $method->invoke($repo, $query, new \App\ValueObjects\QueryOption);

    expect($result->getQuery()->orders ?? [])->toBe([]);
});
