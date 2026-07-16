<?php

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines fillable attributes', function (): void {
    $model = new Quiz;

    expect($model->getFillable())->toEqual([
        'lesson_id',
        'pass_percent',
        'name',
        'order',
    ]);
});

it('defines lesson relation', function (): void {
    $method = new ReflectionMethod(Quiz::class, 'lesson');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
});

it('defines questions relation', function (): void {
    $method = new ReflectionMethod(Quiz::class, 'questions');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
});

it('defines attempts relation', function (): void {
    $method = new ReflectionMethod(Quiz::class, 'attempts');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
});
