<?php

use App\Http\Controllers\Api\V1\Lesson\LessonController;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\Lesson\ILessonService;
use App\Services\LessonAccess\ILessonAccessService;
use App\Services\LessonComment\ILessonCommentService;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

function makeLessonRequestWithId(int $id): Request
{
    $request = Request::create('/', 'GET');

    $request->setRouteResolver(function () use ($id) {
        return new class($id)
        {
            public function __construct(private int $id) {}

            public function parameter(string $key, $default = null)
            {
                return $key === 'id' ? $this->id : $default;
            }
        };
    });

    return $request;
}

function makeUserLessonRequest(int $lessonId, ?int $userId = 7): Request
{
    $request = makeLessonRequestWithId($lessonId);
    $request->setUserResolver(fn () => $userId ? tap(new \App\Models\User, fn ($user) => $user->id = $userId) : null);

    return $request;
}

it('can be instantiated', function (): void {
    app()->instance(ILessonAccessService::class, \Mockery::mock(ILessonAccessService::class));
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));
    $controller = app()->make(LessonController::class);

    expect($controller)->toBeInstanceOf(LessonController::class);
});

it('returns success response from index', function (): void {
    $items = new Collection;
    $paginator = new LengthAwarePaginator($items, 0, 15, 1);

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('paginateAll')->once()->andReturn($paginator);
    app()->instance(ILessonService::class, $service);
    app()->instance(ILessonAccessService::class, \Mockery::mock(ILessonAccessService::class));
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->index(new Request);

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
        'meta' => [
            'page' => 1,
            'per_page' => 15,
            'total' => 0,
            'page_count' => 0,
        ],
    ]);
});

it('returns notfound when lesson quiz has no lesson record', function (): void {
    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn(null);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->quiz(makeLessonRequestWithId(10));

    assertJsonResponsePayload($response, 404, [
        'message' => 'Not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns notfound when lesson quiz has no quiz record', function (): void {
    $relation = \Mockery::mock(HasOne::class);
    $relation->shouldReceive('with')
        ->once()
        ->with(['questions.answers'])
        ->andReturnSelf();
    $relation->shouldReceive('first')
        ->once()
        ->andReturn(null);

    $lesson = \Mockery::mock(Lesson::class)->makePartial();
    $lesson->shouldReceive('quiz')
        ->once()
        ->andReturn($relation);

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canAccessLessonProtectedContent')->once()->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->quiz(makeUserLessonRequest(10));

    assertJsonResponsePayload($response, 404, [
        'message' => 'Not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns quiz data for lesson quiz endpoint', function (): void {
    $quiz = new Quiz;
    $quiz->setAttribute('id', 1);

    $relation = \Mockery::mock(HasOne::class);
    $relation->shouldReceive('with')
        ->once()
        ->with(['questions.answers'])
        ->andReturnSelf();
    $relation->shouldReceive('first')
        ->once()
        ->andReturn($quiz);

    $lesson = \Mockery::mock(Lesson::class)->makePartial();
    $lesson->shouldReceive('quiz')
        ->once()
        ->andReturn($relation);

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canAccessLessonProtectedContent')->once()->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->quiz(makeUserLessonRequest(10));

    assertJsonResponsePayload($response, 200, [
        'message' => 'Get Quiz Successfully',
        'status' => true,
        'status_code' => 200,
        'data' => ['id' => 1],
    ]);
});

it('downloads lesson document by index', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('lesson-documents/grammar-guide.pdf', 'pdf-content');

    $lesson = new Lesson([
        'documents' => ['lesson-documents/grammar-guide.pdf'],
        'document_names' => ['grammar-guide.pdf'],
    ]);
    $lesson->id = 10;

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canAccessLessonProtectedContent')->once()->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->downloadDocument(makeUserLessonRequest(10), 10, 0);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('content-disposition'))->toContain('grammar-guide.pdf');
});

it('returns not found when lesson document index is invalid', function (): void {
    $lesson = new Lesson([
        'documents' => ['lesson-documents/grammar-guide.pdf'],
        'document_names' => ['grammar-guide.pdf'],
    ]);
    $lesson->id = 10;

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canAccessLessonProtectedContent')->once()->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->downloadDocument(makeUserLessonRequest(10), 10, 1);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns not found when downloading document for missing lesson', function (): void {
    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn(null);
    app()->instance(ILessonService::class, $service);
    app()->instance(ILessonAccessService::class, \Mockery::mock(ILessonAccessService::class));
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->downloadDocument(makeUserLessonRequest(10), 10, 0);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('falls back to basename when document name at index is empty', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('lesson-documents/grammar-guide.pdf', 'pdf-content');

    $lesson = new Lesson([
        'documents' => ['lesson-documents/grammar-guide.pdf'],
        'document_names' => [],
    ]);
    $lesson->id = 10;

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canAccessLessonProtectedContent')->once()->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->downloadDocument(makeUserLessonRequest(10), 10, 0);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('content-disposition'))->toContain('grammar-guide.pdf');
});

it('returns not found when streaming video for missing lesson', function (): void {
    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn(null);
    app()->instance(ILessonService::class, $service);
    app()->instance(ILessonAccessService::class, \Mockery::mock(ILessonAccessService::class));
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->video(makeUserLessonRequest(10), 10);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns not found when lesson video path is invalid', function (): void {
    $lesson = new Lesson([
        'video_url' => 'lessons/missing.mp4',
    ]);
    $lesson->id = 10;

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canWatchLessonVideo')->once()->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->video(makeUserLessonRequest(10), 10);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('streams lesson video inline', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('lessons/sample.mp4', 'video-content');

    $lesson = new Lesson([
        'video_url' => 'lessons/sample.mp4',
    ]);
    $lesson->id = 10;

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);
    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canWatchLessonVideo')->once()->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);
    $response = $controller->video(makeUserLessonRequest(10), 10);

    expect($response->getStatusCode())->toBe(200);
    expect((string) $response->headers->get('accept-ranges'))->toBe('bytes');
    expect((string) $response->headers->get('content-disposition'))->toContain('inline');
    expect((string) $response->headers->get('content-disposition'))->toContain('sample.mp4');
});

it('returns unauthorized for protected lesson endpoints', function (): void {
    $lesson = new Lesson(['video_url' => 'lessons/sample.mp4']);
    $lesson->id = 10;

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->times(3)->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);

    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canAccessLessonProtectedContent')->twice()->andReturnFalse();
    $accessService->shouldReceive('canWatchLessonVideo')->once()->andReturnFalse();
    app()->instance(ILessonAccessService::class, $accessService);
    app()->instance(ILessonCommentService::class, \Mockery::mock(ILessonCommentService::class));

    $controller = app()->make(LessonController::class);

    assertJsonResponsePayload($controller->downloadDocument(makeUserLessonRequest(10), 10, 0), 401, [
        'status' => false,
        'status_code' => 401,
    ]);
    assertJsonResponsePayload($controller->video(makeUserLessonRequest(10), 10), 401, [
        'status' => false,
        'status_code' => 401,
    ]);
    assertJsonResponsePayload($controller->quiz(makeUserLessonRequest(10)), 401, [
        'status' => false,
        'status_code' => 401,
    ]);
});

it('stores lesson comments for accessible lessons and handles guard branches', function (): void {
    $lesson = new Lesson;
    $lesson->id = 10;
    $user = tap(new \App\Models\User, fn ($user) => $user->id = 7);

    $service = \Mockery::mock(ILessonService::class);
    $service->shouldReceive('getById')->once()->with(99)->andReturnNull();
    $service->shouldReceive('getById')->twice()->with(10)->andReturn($lesson);
    app()->instance(ILessonService::class, $service);

    $accessService = \Mockery::mock(ILessonAccessService::class);
    $accessService->shouldReceive('canWatchLessonVideo')->once()->with(7, $lesson)->andReturnFalse();
    $accessService->shouldReceive('canWatchLessonVideo')->once()->with(7, $lesson)->andReturnTrue();
    app()->instance(ILessonAccessService::class, $accessService);

    $commentService = \Mockery::mock(ILessonCommentService::class);
    $commentService->shouldReceive('createForUser')
        ->once()
        ->with(10, 7, 'Question content')
        ->andReturn(new \App\Models\LessonComment([
            'lesson_id' => 10,
            'user_id' => 7,
            'content' => 'Question content',
        ]));
    app()->instance(ILessonCommentService::class, $commentService);

    $controller = app()->make(LessonController::class);

    $missing = \App\Http\Requests\Api\V1\Lesson\LessonCommentStoreRequest::create('/lessons/99/comments', 'POST', ['content' => 'Question content']);
    $missing->setContainer(app())->setRedirector(app('redirect'));
    $missing->setUserResolver(fn () => $user);
    $missing->validateResolved();
    assertJsonResponsePayload($controller->storeComment($missing, 99), 404, ['status' => false, 'status_code' => 404]);

    $unauth = \App\Http\Requests\Api\V1\Lesson\LessonCommentStoreRequest::create('/lessons/10/comments', 'POST', ['content' => 'Question content']);
    $unauth->setContainer(app())->setRedirector(app('redirect'));
    $unauth->setUserResolver(fn () => $user);
    $unauth->validateResolved();
    assertJsonResponsePayload($controller->storeComment($unauth, 10), 401, ['status' => false, 'status_code' => 401]);

    $request = \App\Http\Requests\Api\V1\Lesson\LessonCommentStoreRequest::create('/lessons/10/comments', 'POST', ['content' => 'Question content']);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn () => $user);
    $request->validateResolved();
    assertJsonResponsePayload($controller->storeComment($request, 10), 201, [
        'status' => true,
        'status_code' => 201,
        'message' => 'Comment submitted',
    ]);
});
