<?php

use App\Repositories\Repository;
use App\ValueObjects\QueryOption;
use Illuminate\Database\Eloquent\Model;

class EmptyOrderByModel extends Model
{
    protected $table = 'empty_order_test';
}

class EmptyOrderByRepo extends Repository
{
    public function __construct()
    {
        $this->model = new EmptyOrderByModel;
    }

    protected function getDefaultOrderBy(): string
    {
        return '';
    }
}

it('skips ordering when default order by is empty', function (): void {
    $method = new ReflectionMethod(Repository::class, 'applyDefaultOrderIfMissing');
    $result = $method->invoke(new EmptyOrderByRepo, EmptyOrderByModel::query(), new QueryOption);

    expect($result->getQuery()->orders ?? [])->toBe([]);
});
