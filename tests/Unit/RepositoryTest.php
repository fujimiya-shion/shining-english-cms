<?php

use App\Repositories\Repository;
use App\ValueObjects\QueryOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
    ]);

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::dropAllTables();
    Schema::create('test_models', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('status')->nullable();
        $table->integer('age')->nullable();
        $table->integer('price')->nullable();
        $table->timestamps();
    });

    Schema::create('test_model_children', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('test_model_id');
        $table->string('name')->nullable();
    });

    Schema::create('test_models_without_timestamps', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
    });

    app()->instance(TestRepository::class, new TestRepository(new TestModel));
});

afterEach(function (): void {
    Schema::dropAllTables();
});

test('getAll returns all records', function (): void {
    TestModel::query()->create([
        'name' => 'John',
        'status' => 'active',
        'age' => 25,
        'price' => 15,
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);
    TestModel::query()->create([
        'name' => 'Jane',
        'status' => 'inactive',
        'age' => 30,
        'price' => 25,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->getAll();

    expect($result)->toHaveCount(2);
    expect($result->pluck('name')->values()->all())
        ->toEqual(['Jane', 'John']);
});

test('query builds builder with eager loads and default ordering', function (): void {
    $repository = app()->make(TestRepository::class);
    $query = $repository->query(['children']);

    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
    expect($query->getEagerLoads())->toHaveKey('children');
    expect($query->getQuery()->orders)->toHaveCount(1);
    expect($query->getQuery()->orders[0]['column'])->toBe('test_models.created_at');
    expect($query->getQuery()->orders[0]['direction'])->toBe('desc');
});

test('getAll applies eager loading options', function (): void {
    $parent = TestModel::query()->create(['name' => 'Parent', 'status' => 'active']);
    TestModelChild::query()->create(['test_model_id' => $parent->id, 'name' => 'Child']);

    $options = (new QueryOption)->setWith(['children']);
    $repository = app()->make(TestRepository::class);
    $result = $repository->getAll($options);

    expect($result)->toHaveCount(1);
    expect($result->first()->relationLoaded('children'))->toBeTrue();
    expect($result->first()->children)->toHaveCount(1);
});

test('paginateAll returns a paginator', function (): void {
    TestModel::query()->create([
        'name' => 'John',
        'status' => 'active',
        'age' => 25,
        'price' => 15,
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);
    TestModel::query()->create([
        'name' => 'Jane',
        'status' => 'inactive',
        'age' => 30,
        'price' => 25,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $options = (new QueryOption)->setPage(1)->setPerPage(1);
    $repository = app()->make(TestRepository::class);

    $result = $repository->paginateAll($options);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->items())->toHaveCount(1);
    expect($result->items()[0]->name)->toBe('Jane');
});

test('getBy returns records matching criteria', function (): void {
    TestModel::query()->create(['name' => 'John', 'status' => 'active', 'age' => 25, 'price' => 15]);
    TestModel::query()->create(['name' => 'Jane', 'status' => 'inactive', 'age' => 30, 'price' => 25]);
    TestModel::query()->create(['name' => 'Joana', 'status' => 'active', 'age' => 19, 'price' => 12]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->getBy([
        'status' => 'active',
        'age' => ['>', 20],
    ]);

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBe('John');
});

test('paginateBy returns a paginator', function (): void {
    TestModel::query()->create(['name' => 'John', 'status' => 'active', 'age' => 25, 'price' => 15]);
    TestModel::query()->create(['name' => 'Jane', 'status' => 'inactive', 'age' => 30, 'price' => 25]);

    $options = (new QueryOption)->setPage(1)->setPerPage(1);
    $repository = app()->make(TestRepository::class);

    $result = $repository->paginateBy(['status' => 'inactive'], $options);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->items())->toHaveCount(1);
    expect($result->items()[0]->name)->toBe('Jane');
});

test('getBy supports like and between operators', function (): void {
    TestModel::query()->create(['name' => 'John', 'status' => 'active', 'age' => 25, 'price' => 15]);
    TestModel::query()->create(['name' => 'Jane', 'status' => 'inactive', 'age' => 30, 'price' => 25]);
    TestModel::query()->create(['name' => 'Joana', 'status' => 'active', 'age' => 19, 'price' => 12]);

    $repository = app()->make(TestRepository::class);
    $likeResult = $repository->getBy([
        'name' => ['like', 'Jo%'],
    ]);

    expect($likeResult->pluck('name')->sort()->values()->all())
        ->toEqual(['Joana', 'John']);

    $betweenResult = $repository->getBy([
        'price' => ['between', [10, 20]],
    ]);

    expect($betweenResult->pluck('name')->sort()->values()->all())
        ->toEqual(['Joana', 'John']);
});

test('getBy supports null criteria', function (): void {
    TestModel::query()->create(['name' => null, 'status' => null, 'age' => null, 'price' => 5]);
    TestModel::query()->create(['name' => 'Jane', 'status' => 'inactive', 'age' => 30, 'price' => 25]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->getBy([
        'status' => null,
    ]);

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBeNull();
});

test('getBy supports in and not in operators', function (): void {
    TestModel::query()->create(['name' => 'Alpha', 'status' => 'active', 'age' => 20, 'price' => 10]);
    TestModel::query()->create(['name' => 'Beta', 'status' => 'inactive', 'age' => 21, 'price' => 20]);
    TestModel::query()->create(['name' => 'Gamma', 'status' => 'inactive', 'age' => 22, 'price' => 30]);

    $repository = app()->make(TestRepository::class);

    $inResult = $repository->getBy([
        'name' => ['in', ['Alpha', 'Beta']],
    ]);

    expect($inResult)->toHaveCount(2);

    $notInResult = $repository->getBy([
        'name' => ['not in', ['Alpha', 'Beta']],
    ]);

    expect($notInResult)->toHaveCount(1);
    expect($notInResult->first()->name)->toBe('Gamma');
});

test('getBy supports nin alias', function (): void {
    TestModel::query()->create(['name' => 'Alpha', 'status' => 'active', 'age' => 20, 'price' => 10]);
    TestModel::query()->create(['name' => 'Beta', 'status' => 'inactive', 'age' => 21, 'price' => 20]);
    TestModel::query()->create(['name' => 'Gamma', 'status' => 'inactive', 'age' => 22, 'price' => 30]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->getBy([
        'name' => ['nin', ['Alpha']],
    ]);

    expect($result->pluck('name')->sort()->values()->all())
        ->toEqual(['Beta', 'Gamma']);
});

test('getBy supports not between and not like operators', function (): void {
    TestModel::query()->create(['name' => 'Alpha', 'status' => 'active', 'age' => 20, 'price' => 10]);
    TestModel::query()->create(['name' => 'Beta', 'status' => 'inactive', 'age' => 21, 'price' => 20]);
    TestModel::query()->create(['name' => 'Gamma', 'status' => 'inactive', 'age' => 22, 'price' => 30]);

    $repository = app()->make(TestRepository::class);

    $notBetween = $repository->getBy([
        'price' => ['not between', [15, 25]],
    ]);

    expect($notBetween->pluck('name')->sort()->values()->all())
        ->toEqual(['Alpha', 'Gamma']);

    $notLike = $repository->getBy([
        'name' => ['not like', 'Al%'],
    ]);

    expect($notLike->pluck('name')->sort()->values()->all())
        ->toEqual(['Beta', 'Gamma']);
});

test('getBy supports null and not null operators', function (): void {
    TestModel::query()->create(['name' => 'Alpha', 'status' => null, 'age' => 20, 'price' => 10]);
    TestModel::query()->create(['name' => 'Beta', 'status' => 'active', 'age' => 21, 'price' => 20]);

    $repository = app()->make(TestRepository::class);

    $nullResult = $repository->getBy([
        'status' => ['null'],
    ]);

    expect($nullResult)->toHaveCount(1);
    expect($nullResult->first()->name)->toBe('Alpha');

    $notNullResult = $repository->getBy([
        'status' => ['not null'],
    ]);

    expect($notNullResult)->toHaveCount(1);
    expect($notNullResult->first()->name)->toBe('Beta');
});

test('getBy falls back to equals on unknown operator', function (): void {
    TestModel::query()->create(['name' => 'Alpha', 'status' => 'active', 'age' => 20, 'price' => 10]);
    TestModel::query()->create(['name' => 'Beta', 'status' => 'inactive', 'age' => 21, 'price' => 20]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->getBy([
        'status' => ['weird', 'active'],
    ]);

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBe('Alpha');
});

test('getById returns a model when found', function (): void {
    $record = TestModel::query()->create(['name' => 'John', 'status' => 'active', 'age' => 25, 'price' => 15]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->getById($record->id);

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($record->id);
});

test('getById eager loads relations', function (): void {
    $parent = TestModel::query()->create(['name' => 'Parent', 'status' => 'active']);
    TestModelChild::query()->create(['test_model_id' => $parent->id, 'name' => 'Child']);

    $repository = app()->make(TestRepository::class);
    $result = $repository->getById($parent->id, ['children']);

    expect($result)->not->toBeNull();
    expect($result->relationLoaded('children'))->toBeTrue();
    expect($result->children)->toHaveCount(1);
});

test('getById returns null when missing', function (): void {
    $repository = app()->make(TestRepository::class);
    $found = $repository->getById(999);

    expect($found)->toBeNull();
});

test('create persists a new record', function (): void {
    $repository = app()->make(TestRepository::class);
    $created = $repository->create(['name' => 'New', 'status' => 'active', 'age' => 22, 'price' => 9]);

    expect($created)->toBeInstanceOf(Model::class);
    expect($created->id)->not->toBeNull();
});

test('create rethrows exceptions from model', function (): void {
    $repository = new TestRepository(new FailingCreateModel);

    expect(fn () => $repository->create(['name' => 'Bad']))
        ->toThrow(RuntimeException::class);
});

test('update persists changes', function (): void {
    $record = TestModel::query()->create(['name' => 'Old', 'status' => 'active', 'age' => 22, 'price' => 9]);

    $repository = app()->make(TestRepository::class);
    $updated = $repository->update($record->id, ['name' => 'Updated']);

    expect($updated->name)->toBe('Updated');
    expect(TestModel::query()->find($record->id)->name)->toBe('Updated');
});

test('update rethrows when model not found', function (): void {
    $repository = app()->make(TestRepository::class);

    expect(fn () => $repository->update(9999, ['name' => 'Updated']))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('count returns matching total', function (): void {
    TestModel::query()->create(['name' => 'John', 'status' => 'active', 'age' => 25, 'price' => 15]);
    TestModel::query()->create(['name' => 'Jane', 'status' => 'inactive', 'age' => 30, 'price' => 25]);

    $repository = app()->make(TestRepository::class);
    $total = $repository->count(['status' => 'active']);

    expect($total)->toBe(1);
});

test('autoComplete returns prefix matches', function (): void {
    TestModel::query()->create(['name' => 'Alpha', 'status' => 'active', 'age' => 25, 'price' => 15]);
    TestModel::query()->create(['name' => 'Alpine', 'status' => 'active', 'age' => 19, 'price' => 12]);
    TestModel::query()->create(['name' => 'Beta', 'status' => 'inactive', 'age' => 30, 'price' => 25]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->autoComplete('Al');

    expect($result->pluck('name')->sort()->values()->all())
        ->toEqual(['Alpha', 'Alpine']);
});

test('autoComplete returns empty collection for empty term', function (): void {
    TestModel::query()->create(['name' => 'Alpha', 'status' => 'active', 'age' => 25, 'price' => 15]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->autoComplete('');

    expect($result)->toHaveCount(0);
});

test('autoComplete works when upper bound is null', function (): void {
    $term = chr(255);
    TestModel::query()->create(['name' => $term.'a', 'status' => 'active', 'age' => 25, 'price' => 15]);
    TestModel::query()->create(['name' => chr(254).'z', 'status' => 'inactive', 'age' => 30, 'price' => 25]);

    $repository = app()->make(TestRepository::class);
    $result = $repository->autoComplete($term);

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBe($term.'a');
});

test('nextPrefix returns null for empty string', function (): void {
    $repository = app()->make(TestRepository::class);

    $method = new ReflectionMethod(Repository::class, 'nextPrefix');
    $method->setAccessible(true);

    $result = $method->invoke($repository, '   ');

    expect($result)->toBeNull();
});

test('nextPrefix returns null when no higher prefix exists', function (): void {
    $repository = app()->make(TestRepository::class);

    $method = new ReflectionMethod(Repository::class, 'nextPrefix');
    $method->setAccessible(true);

    $result = $method->invoke($repository, chr(255));

    expect($result)->toBeNull();
});

test('nextPrefix increments the last byte', function (): void {
    $repository = app()->make(TestRepository::class);

    $method = new ReflectionMethod(Repository::class, 'nextPrefix');
    $method->setAccessible(true);

    $result = $method->invoke($repository, 'ab');

    expect($result)->toBe('ac');
});

test('applyPrefixMatch returns query unchanged for empty term', function (): void {
    $repository = app()->make(TestRepository::class);
    $query = TestModel::query();
    $originalSql = $query->toSql();

    $method = new ReflectionMethod(Repository::class, 'applyPrefixMatch');
    $method->setAccessible(true);

    /** @var \Illuminate\Database\Eloquent\Builder $result */
    $result = $method->invoke($repository, $query, 'name', '   ');

    expect($result->toSql())->toBe($originalSql);
});

test('applyDefaultOrderIfMissing keeps existing order clauses', function (): void {
    $repository = app()->make(TestRepository::class);
    $query = TestModel::query()->orderBy('name');

    $method = new ReflectionMethod(Repository::class, 'applyDefaultOrderIfMissing');
    $method->setAccessible(true);

    /** @var \Illuminate\Database\Eloquent\Builder $result */
    $result = $method->invoke($repository, $query, new QueryOption);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0]['column'])->toBe('name');
});

test('applyDefaultOrderIfMissing skips ordering when model table has no created_at column', function (): void {
    $repository = new TestRepositoryWithoutTimestamps(new TestModelWithoutTimestamps);
    $query = TestModelWithoutTimestamps::query();

    $method = new ReflectionMethod(Repository::class, 'applyDefaultOrderIfMissing');
    $method->setAccessible(true);

    /** @var \Illuminate\Database\Eloquent\Builder $result */
    $result = $method->invoke($repository, $query, new QueryOption);

    expect($result->getQuery()->orders ?? [])->toBe([]);
});

test('delete returns true for existing record', function () {
    TestModel::query()->create(['id' => 3, 'name' => 'John']);
    $repository = app()->make(TestRepository::class);
    $result = $repository->delete(3);
    expect($result)->toBe(true);
});

test('delete returns false for non existing record', function () {
    TestModel::query()->create(['id' => 3, 'name' => 'John']);
    $repository = app()->make(TestRepository::class);
    $result = $repository->delete(4);
    expect($result)->toBe(false);
});

test('delete rethrows exceptions from query delete', function (): void {
    $repository = new TestRepository(new FailingDeleteModel);

    expect(fn () => $repository->delete(3))
        ->toThrow(RuntimeException::class);
});

class TestModel extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TestModelChild::class);
    }
}

class TestModelChild extends Model
{
    protected $table = 'test_model_children';

    protected $guarded = [];

    public $timestamps = false;
}

class TestModelWithoutTimestamps extends Model
{
    protected $table = 'test_models_without_timestamps';

    protected $guarded = [];

    public $timestamps = false;
}

class FailingCreateModel extends TestModel
{
    public function create(array $attributes = []): Model
    {
        throw new RuntimeException('Forced failure');
    }
}

class FailingDeleteModel extends TestModel
{
    public function newQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->once()->with('id', 3)->andReturnSelf();
        $query->shouldReceive('delete')->once()->andThrow(new RuntimeException('Forced delete failure'));

        return $query;
    }
}

class TestRepository extends Repository
{
    public function __construct(TestModel $model)
    {
        parent::__construct($model);
    }
}

class TestRepositoryWithoutTimestamps extends Repository
{
    public function __construct(TestModelWithoutTimestamps $model)
    {
        parent::__construct($model);
    }
}
