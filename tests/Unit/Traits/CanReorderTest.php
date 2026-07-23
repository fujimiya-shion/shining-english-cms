<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

class CanReorderTestModel extends Model
{
    use \App\Traits\CanReorder;

    protected $table = 'can_reorder_test';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    Schema::create('can_reorder_test', function ($table): void {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('order')->default(0);
    });
});

afterEach(function (): void {
    Schema::dropIfExists('can_reorder_test');
});

it('sets order to id on creation', function (): void {
    $first = CanReorderTestModel::query()->create(['name' => 'First']);
    $second = CanReorderTestModel::query()->create(['name' => 'Second']);

    expect($first->order)->toBe($first->id);
    expect($second->order)->toBe($second->id);
});

it('sets order to id when no records exist', function (): void {
    $model = CanReorderTestModel::query()->create(['name' => 'First']);

    expect($model->order)->toBe($model->id);
});

it('does not override existing non-zero order', function (): void {
    $model = CanReorderTestModel::query()->create(['name' => 'Existing', 'order' => 10]);

    expect($model->order)->toBe(10);
});
