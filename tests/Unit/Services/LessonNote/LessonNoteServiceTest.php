<?php

use App\Models\LessonNote;
use App\Repositories\LessonNote\ILessonNoteRepository;
use App\Services\LessonNote\ILessonNoteService;
use App\Services\LessonNote\LessonNoteService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

it('implements shared lesson note service contract', function (): void {
    $repository = \Mockery::mock(ILessonNoteRepository::class);
    $service = new LessonNoteService($repository);

    assertServiceContract($service, ILessonNoteService::class, $repository);
});

it('lists lesson notes by user and lesson id via repository', function (): void {
    $repository = \Mockery::mock(ILessonNoteRepository::class);
    $service = new LessonNoteService($repository);
    $notes = new EloquentCollection([new LessonNote(['content' => 'note'])]);

    $repository->shouldReceive('listByUserId')->once()->with(7)->andReturn($notes);
    $repository->shouldReceive('listByLessonId')->once()->with(7, 9)->andReturn($notes);

    expect($service->listByUserId(7))->toBe($notes);
    expect($service->listByLessonId(7, 9))->toBe($notes);
});

it('creates a lesson note for a user with trimmed content', function (): void {
    $repository = \Mockery::mock(ILessonNoteRepository::class);
    $service = new LessonNoteService($repository);

    $note = \Mockery::mock(LessonNote::class)->makePartial();
    $note->shouldReceive('load')
        ->once()
        ->with(['lesson:id,name,course_id', 'lesson.course:id,name,slug'])
        ->andReturnSelf();

    $repository->shouldReceive('create')
        ->once()
        ->with([
            'lesson_id' => 9,
            'user_id' => 7,
            'content' => 'trim me',
        ])
        ->andReturn($note);

    expect($service->createForUser(7, 9, '  trim me  '))->toBe($note);
});

it('deletes an owned lesson note and returns false when it is missing', function (): void {
    $repository = \Mockery::mock(ILessonNoteRepository::class);
    $service = new LessonNoteService($repository);

    $note = new LessonNote;
    $note->id = 15;

    $repository->shouldReceive('findOwnedById')->once()->with(7, 15)->andReturn($note);
    $repository->shouldReceive('delete')->once()->with(15)->andReturnTrue();
    $repository->shouldReceive('findOwnedById')->once()->with(7, 16)->andReturnNull();

    expect($service->deleteByUserId(7, 15))->toBeTrue();
    expect($service->deleteByUserId(7, 16))->toBeFalse();
});
