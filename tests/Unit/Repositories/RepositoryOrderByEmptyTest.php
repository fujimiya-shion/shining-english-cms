<?php

class EmptyOrderByRepoCustomModel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'empty_order_test_custom';
}

class EmptyOrderByRepo extends \App\Repositories\Repository
{
    public function __construct()
    {
        $this->model = new EmptyOrderByRepoCustomModel;
    }

    protected function getDefaultOrderBy(): string
    {
        return '';
    }
}

it('skips ordering when default order by is empty', function (): void {
    $query = EmptyOrderByRepoCustomModel::query();

    $method = new ReflectionMethod(\App\Repositories\Repository::class, 'applyDefaultOrderIfMissing');
    $result = $method->invoke(new EmptyOrderByRepo, $query, new \App\ValueObjects\QueryOption);

    expect($result->getQuery()->orders ?? [])->toBe([]);
});
