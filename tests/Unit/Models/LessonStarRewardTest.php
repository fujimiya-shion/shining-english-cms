<?php

use App\Models\LessonStarReward;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('defines fillable casts and relations', function (): void {
    $model = new LessonStarReward;

    expect($model->getFillable())->toBe([
        'user_id',
        'course_id',
        'lesson_id',
        'source',
        'amount',
        'awarded_at',
    ]);
    expect($model->getCasts())->toMatchArray([
        'user_id' => 'integer',
        'course_id' => 'integer',
        'lesson_id' => 'integer',
        'amount' => 'integer',
        'awarded_at' => 'datetime',
    ]);
    expect($model->user())->toBeInstanceOf(BelongsTo::class);
    expect($model->course())->toBeInstanceOf(BelongsTo::class);
    expect($model->lesson())->toBeInstanceOf(BelongsTo::class);
});
