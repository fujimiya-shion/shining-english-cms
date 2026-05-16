<?php

use App\Http\Controllers\Api\V1\QuizAttempt\QuizAttemptController;
use App\Http\Requests\Api\V1\QuizAttempt\QuizAttemptStoreRequest;
use App\Models\User;
use App\Models\UserQuizAttempt;
use App\Services\UserQuizAttempt\IUserQuizAttemptService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('paginates quiz attempts for the authenticated user', function (): void {
    $user = new User;
    $user->id = 5;
    $items = new Collection([['score_percent' => 88]]);
    $paginator = new LengthAwarePaginator($items, 1, 15, 2);

    $service = \Mockery::mock(IUserQuizAttemptService::class);
    $service->shouldReceive('paginateBy')
        ->once()
        ->with(['user_id' => 5, 'quiz_id' => 9], \Mockery::type(App\ValueObjects\QueryOption::class))
        ->andReturn($paginator);
    app()->instance(IUserQuizAttemptService::class, $service);

    $controller = app()->make(QuizAttemptController::class);
    $request = Request::create('/api/v1/quizzes/9/attempts?page=2', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->index($request, 9);

    assertJsonResponsePayload($response, 200, [
        'status' => true,
        'status_code' => 200,
        'meta' => [
            'page' => 2,
            'per_page' => 15,
            'total' => 1,
            'page_count' => 1,
        ],
    ]);
});

it('records a quiz attempt', function (): void {
    $user = new User;
    $user->id = 5;
    $attempt = new UserQuizAttempt([
        'score_percent' => 95,
        'passed' => true,
    ]);
    $attempt->id = 101;

    $service = \Mockery::mock(IUserQuizAttemptService::class);
    $service->shouldReceive('recordAttempt')
        ->once()
        ->with(5, 9, 95.0, true, \Mockery::on(fn (?Carbon $submittedAt): bool => $submittedAt?->toIso8601String() === '2026-04-15T10:00:00+00:00'))
        ->andReturn($attempt);
    app()->instance(IUserQuizAttemptService::class, $service);

    $controller = app()->make(QuizAttemptController::class);
    $request = QuizAttemptStoreRequest::create('/api/v1/quizzes/9/attempts', 'POST', [
        'score_percent' => 95,
        'passed' => true,
        'submitted_at' => '2026-04-15T10:00:00+00:00',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->store($request, 9);

    assertJsonResponsePayload($response, 201, [
        'message' => 'Attempt recorded',
        'status' => true,
        'status_code' => 201,
        'data' => [
            'id' => 101,
            'score_percent' => 95,
            'passed' => true,
        ],
    ]);
});

it('records a quiz attempt without submitted at timestamp', function (): void {
    $user = new User;
    $user->id = 5;
    $attempt = new UserQuizAttempt([
        'score_percent' => 60,
        'passed' => false,
    ]);
    $attempt->id = 102;

    $service = \Mockery::mock(IUserQuizAttemptService::class);
    $service->shouldReceive('recordAttempt')
        ->once()
        ->with(5, 9, 60.0, false, null)
        ->andReturn($attempt);
    app()->instance(IUserQuizAttemptService::class, $service);

    $controller = app()->make(QuizAttemptController::class);
    $request = QuizAttemptStoreRequest::create('/api/v1/quizzes/9/attempts', 'POST', [
        'score_percent' => 60,
        'passed' => false,
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    $response = $controller->store($request, 9);

    assertJsonResponsePayload($response, 201, [
        'message' => 'Attempt recorded',
        'status' => true,
        'status_code' => 201,
        'data' => [
            'id' => 102,
            'score_percent' => 60,
            'passed' => false,
        ],
    ]);
});

it('returns not found when no latest attempt exists', function (): void {
    $user = new User;
    $user->id = 5;

    $service = \Mockery::mock(IUserQuizAttemptService::class);
    $service->shouldReceive('latestAttempt')->once()->with(5, 9)->andReturnNull();
    app()->instance(IUserQuizAttemptService::class, $service);

    $controller = app()->make(QuizAttemptController::class);
    $request = Request::create('/api/v1/quizzes/9/attempts/latest', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->latest($request, 9);

    assertJsonResponsePayload($response, 404, [
        'message' => 'Attempt not found',
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns the latest quiz attempt for the authenticated user', function (): void {
    $user = new User;
    $user->id = 5;
    $attempt = new UserQuizAttempt([
        'score_percent' => 88,
        'passed' => true,
    ]);
    $attempt->id = 11;

    $service = \Mockery::mock(IUserQuizAttemptService::class);
    $service->shouldReceive('latestAttempt')->once()->with(5, 9)->andReturn($attempt);
    app()->instance(IUserQuizAttemptService::class, $service);

    $controller = app()->make(QuizAttemptController::class);
    $request = Request::create('/api/v1/quizzes/9/attempts/latest', 'GET');
    $request->setUserResolver(fn (): User => $user);

    $response = $controller->latest($request, 9);

    assertJsonResponsePayload($response, 200, [
        'status' => true,
        'status_code' => 200,
        'data' => [
            'id' => 11,
            'score_percent' => 88,
            'passed' => true,
        ],
    ]);
});
