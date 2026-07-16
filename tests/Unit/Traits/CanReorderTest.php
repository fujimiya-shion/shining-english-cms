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

it('sets order to max plus one on creation', function (): void {
    CanReorderTestModel::query()->create(['name' => 'First', 'order' => 5]);
    CanReorderTestModel::query()->create(['name' => 'Second']);

    $second = CanReorderTestModel::query()->where('name', 'Second')->first();

    expect($second->order)->toBe(6);
});

it('sets order to 1 when no records exist', function (): void {
    $model = CanReorderTestModel::query()->create(['name' => 'First']);

    expect($model->order)->toBe(1);
});

it('does not override existing non-zero order', function (): void {
    $model = CanReorderTestModel::query()->create(['name' => 'Existing', 'order' => 10]);

    expect($model->order)->toBe(10);
});
