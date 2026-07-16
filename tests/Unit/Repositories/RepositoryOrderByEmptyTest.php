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

it('skips ordering when default order by is empty', function (): void {
    $repo = new EmptyOrderByRepository(new \App\Models\Admin);
    $query = \App\Models\Admin::query();

    $method = new ReflectionMethod(\App\Repositories\Repository::class, 'applyDefaultOrderIfMissing');
    $result = $method->invoke($repo, $query, new \App\ValueObjects\QueryOption);

    expect($result->getQuery()->orders ?? [])->toBe([]);
});
