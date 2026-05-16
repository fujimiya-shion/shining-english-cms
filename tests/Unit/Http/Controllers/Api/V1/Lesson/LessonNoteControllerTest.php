<?php

use App\Http\Controllers\Api\V1\Lesson\LessonNoteController;
use App\Models\Lesson;
use App\Models\LessonNote;
use App\Models\User;
use App\Services\Lesson\ILessonService;
use App\Services\LessonNote\ILessonNoteService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('lists lesson notes for the authenticated user', function (): void {
    $user = new User;
    $user->id = 8;
    $notes = new EloquentCollection([new LessonNote(['content' => 'note'])]);

    $lessonNoteService = \Mockery::mock(ILessonNoteService::class);
    $lessonNoteService->shouldReceive('listByUserId')->once()->with(8)->andReturn($notes);
    app()->instance(ILessonNoteService::class, $lessonNoteService);
    app()->instance(ILessonService::class, \Mockery::mock(ILessonService::class));

    $controller = app()->make(LessonNoteController::class);
    $request = Request::create('/api/v1/lesson-notes', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->index($request);

    assertJsonResponsePayload($response, 200, [
        'message' => 'Get lesson notes successfully',
        'status' => true,
        'status_code' => 200,
    ]);
});

it('returns not found when listing notes by a missing lesson', function (): void {
    $user = new User;
    $user->id = 8;

    $lessonService = \Mockery::mock(ILessonService::class);
    $lessonService->shouldReceive('getById')->once()->with(99)->andReturnNull();
    app()->instance(ILessonService::class, $lessonService);
    app()->instance(ILessonNoteService::class, \Mockery::mock(ILessonNoteService::class));

    $controller = app()->make(LessonNoteController::class);
    $request = Request::create('/api/v1/lessons/99/notes', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->indexByLesson($request, 99);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Lesson not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('lists lesson notes for a specific existing lesson', function (): void {
    $user = new User;
    $user->id = 8;
    $lesson = new Lesson;
    $lesson->id = 12;
    $notes = new EloquentCollection([new LessonNote(['content' => 'note'])]);

    $lessonService = \Mockery::mock(ILessonService::class);
    $lessonService->shouldReceive('getById')->once()->with(12)->andReturn($lesson);
    app()->instance(ILessonService::class, $lessonService);

    $lessonNoteService = \Mockery::mock(ILessonNoteService::class);
    $lessonNoteService->shouldReceive('listByLessonId')->once()->with(8, 12)->andReturn($notes);
    app()->instance(ILessonNoteService::class, $lessonNoteService);

    $controller = app()->make(LessonNoteController::class);
    $request = Request::create('/api/v1/lessons/12/notes', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->indexByLesson($request, 12);

    assertJsonResponsePayload($response, 200, [
        'message' => 'Get lesson notes successfully',
        'status' => true,
        'status_code' => 200,
    ]);
});

it('stores a note for an existing lesson', function (): void {
    $user = new User;
    $user->id = 8;
    $lesson = new Lesson;
    $lesson->id = 12;
    $note = new LessonNote(['content' => 'Remember this']);

    $lessonService = \Mockery::mock(ILessonService::class);
    $lessonService->shouldReceive('getById')->once()->with(12)->andReturn($lesson);
    app()->instance(ILessonService::class, $lessonService);

    $lessonNoteService = \Mockery::mock(ILessonNoteService::class);
    $lessonNoteService->shouldReceive('createForUser')->once()->with(8, 12, 'Remember this')->andReturn($note);
    app()->instance(ILessonNoteService::class, $lessonNoteService);

    $controller = app()->make(LessonNoteController::class);
    $request = Request::create('/api/v1/lessons/12/notes', 'POST', [
        'content' => 'Remember this',
    ]);
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->store($request, 12);

    assertJsonResponsePayload($response, 201, [
        'message' => 'Note created',
        'status' => true,
        'status_code' => 201,
    ]);
});

it('returns not found when storing a note for a missing lesson', function (): void {
    $user = new User;
    $user->id = 8;

    $lessonService = \Mockery::mock(ILessonService::class);
    $lessonService->shouldReceive('getById')->once()->with(12)->andReturnNull();
    app()->instance(ILessonService::class, $lessonService);
    app()->instance(ILessonNoteService::class, \Mockery::mock(ILessonNoteService::class));

    $controller = app()->make(LessonNoteController::class);
    $request = Request::create('/api/v1/lessons/12/notes', 'POST', [
        'content' => 'Remember this',
    ]);
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->store($request, 12);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Lesson not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns not found when deleting a missing note', function (): void {
    $user = new User;
    $user->id = 8;

    $lessonNoteService = \Mockery::mock(ILessonNoteService::class);
    $lessonNoteService->shouldReceive('deleteByUserId')->once()->with(8, 44)->andReturnFalse();
    app()->instance(ILessonNoteService::class, $lessonNoteService);
    app()->instance(ILessonService::class, \Mockery::mock(ILessonService::class));

    $controller = app()->make(LessonNoteController::class);
    $request = Request::create('/api/v1/lesson-notes/44', 'DELETE');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->delete($request, 44);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Note not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('deletes a lesson note owned by the authenticated user', function (): void {
    $user = new User;
    $user->id = 8;

    $lessonNoteService = \Mockery::mock(ILessonNoteService::class);
    $lessonNoteService->shouldReceive('deleteByUserId')->once()->with(8, 44)->andReturnTrue();
    app()->instance(ILessonNoteService::class, $lessonNoteService);
    app()->instance(ILessonService::class, \Mockery::mock(ILessonService::class));

    $controller = app()->make(LessonNoteController::class);
    $request = Request::create('/api/v1/lesson-notes/44', 'DELETE');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->delete($request, 44);

    assertJsonResponsePayload($response, 200, [
        'message' => 'Note deleted',
        'status' => true,
        'status_code' => 200,
    ]);
});
