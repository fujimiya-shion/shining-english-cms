<?php

use App\Enums\PaymentMethod;
use App\Models\LessonNote;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Repositories\LessonNote\ILessonNoteRepository;
use App\Repositories\LessonNote\LessonNoteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

it('implements shared lesson note repository contract', function (): void {
    $model = new LessonNote;
    $repository = new LessonNoteRepository($model);

    assertRepositoryContract($repository, ILessonNoteRepository::class, $model);
});

it('lists lesson notes by user id with lesson relationships', function (): void {
    $user = User::factory()->create();
    $note = LessonNote::factory()->for($user)->create();
    LessonNote::factory()->create();

    $repository = new LessonNoteRepository(new LessonNote);
    $results = $repository->listByUserId($user->id);

    expect($results)->toHaveCount(1);
    expect($results->first()?->is($note))->toBeTrue();
    expect($results->first()?->relationLoaded('lesson'))->toBeTrue();
});

it('lists lesson notes by lesson id for a user', function (): void {
    $user = User::factory()->create();
    $note = LessonNote::factory()->for($user)->create();
    LessonNote::factory()->for($user)->create();

    $repository = new LessonNoteRepository(new LessonNote);
    $results = $repository->listByLessonId($user->id, $note->lesson_id);

    expect($results)->toHaveCount(1);
    expect($results->first()?->lesson_id)->toBe($note->lesson_id);
});

it('finds an owned lesson note by id and returns null when it is missing', function (): void {
    $user = User::factory()->create();
    $note = LessonNote::factory()->for($user)->create();

    $repository = new LessonNoteRepository(new LessonNote);

    $found = $repository->findOwnedById($user->id, $note->id);
    $missing = $repository->findOwnedById($user->id, 999999);

    expect($found?->is($note))->toBeTrue();
    expect($found?->relationLoaded('lesson'))->toBeTrue();
    expect($missing)->toBeNull();
});
