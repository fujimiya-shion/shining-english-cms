<?php

use App\Http\Controllers\Api\V1\Course\CourseController;
use App\Http\Requests\Api\V1\Course\CourseCurrentLessonRequest;
use App\Http\Requests\Api\V1\Course\CourseFilterRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\Cart\ICartService;
use App\Services\Course\ICourseService;
use App\Services\Enrollment\IEnrollmentService;
use App\ValueObjects\CourseFilter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('can be instantiated', function (): void {
    $controller = app()->make(CourseController::class);

    expect($controller)->toBeInstanceOf(CourseController::class);
});

it('returns success response from index', function (): void {
    $items = new Collection;
    $paginator = new LengthAwarePaginator($items, 0, 15, 1);

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('paginateAll')->once()->andReturn($paginator);
    app()->instance(ICourseService::class, $service);

    $controller = app()->make(CourseController::class);
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

it('inherits success and error json helpers', function (): void {
    $controller = app()->make(CourseController::class);

    $success = $controller->success('OK', ['id' => 1], 200);
    $error = $controller->error('Bad Request', 400, ['field' => ['invalid']]);

    assertJsonResponsePayload($success, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
    ]);

    assertJsonResponsePayload($error, 400, [
        'message' => 'Bad Request',
        'status' => false,
        'status_code' => 400,
    ]);
});

it('filters courses with supported criteria', function (): void {
    $items = new Collection;
    $paginator = new LengthAwarePaginator($items, 0, 15, 1);

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('filter')
        ->once()
        ->with(
            \Mockery::on(function (CourseFilter $filters): bool {
                return $filters->categoryId === 2
                    && $filters->levelId === null
                    && $filters->priceMin === 100
                    && $filters->priceMax === 300
                    && $filters->ratingMin === 3.5
                    && $filters->ratingMax === 4.5
                    && $filters->learnedMin === 10
                    && $filters->learnedMax === 20
                    && $filters->keyword === 'basic';
            })
        )
        ->andReturn($paginator);
    app()->instance(ICourseService::class, $service);

    $controller = app()->make(CourseController::class);
    $request = CourseFilterRequest::create('/api/v1/courses/filter', 'GET', [
        'category_id' => 2,
        'price_min' => 100,
        'price_max' => 300,
        'rating_min' => 3.5,
        'rating_max' => 4.5,
        'learned_min' => 10,
        'learned_max' => 20,
        'q' => 'basic',
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    $response = $controller->filter($request);

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

it('returns filter props from service', function (): void {
    $payload = [
        'categories' => [
            [
                'id' => 1,
                'name' => 'Grammar Basics',
                'slug' => 'grammar-basics',
                'course_count' => 10,
            ],
        ],
        'price' => ['min' => 100, 'max' => 500],
        'rating' => ['min' => 1.0, 'max' => 5.0],
        'learned' => ['min' => 0, 'max' => 100],
        'levels' => [
            ['value' => 1, 'label' => 'Beginner', 'count' => 6],
            ['value' => 2, 'label' => 'Intermediate', 'count' => 4],
        ],
    ];

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getFilterProps')->once()->andReturn($payload);
    app()->instance(ICourseService::class, $service);

    $controller = app()->make(CourseController::class);
    $response = $controller->getFilterProps();

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
        'data' => $payload,
    ]);
});

it('shows course by slug', function (): void {
    $course = new Course;
    $course->id = 9;
    $course->name = 'Grammar Basics';
    $course->slug = 'grammar-basics';

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getBySlug')
        ->once()
        ->with('grammar-basics')
        ->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/slug/grammar-basics', 'GET');

    $response = $controller->showBySlug('grammar-basics');

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
    ]);
});

it('returns not found when slug does not exist', function (): void {
    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getBySlug')
        ->once()
        ->with('missing-course')
        ->andReturn(null);
    app()->instance(ICourseService::class, $service);

    $controller = app()->make(CourseController::class);
    $response = $controller->showBySlug('missing-course');

    assertJsonResponsePayload($response, 404, [
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns course access state for authenticated user', function (): void {
    $course = new Course;
    $course->id = 12;
    $course->name = 'Grammar Basics';
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')
        ->once()
        ->with(12)
        ->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $cartService = \Mockery::mock(ICartService::class);
    $cartService->shouldReceive('hasCourse')
        ->once()
        ->with(7, 12)
        ->andReturnTrue();
    app()->instance(ICartService::class, $cartService);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')
        ->once()
        ->with(7, 12)
        ->andReturnFalse();
    $enrollmentService->shouldReceive('hasPendingEnrollment')
        ->once()
        ->with(7, 12)
        ->andReturnTrue();
    app()->instance(IEnrollmentService::class, $enrollmentService);

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/12/access', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = $controller->access($request, 12);

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
        'data' => [
            'course_id' => 12,
            'enrolled' => false,
            'pending_access' => true,
            'in_cart' => true,
            'is_free_course' => true,
            'can_enroll_free' => true,
        ],
    ]);
});

it('returns not found when checking access for missing course', function (): void {
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')
        ->once()
        ->with(12)
        ->andReturnNull();
    app()->instance(ICourseService::class, $service);

    $cartService = \Mockery::mock(ICartService::class);
    app()->instance(ICartService::class, $cartService);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    app()->instance(IEnrollmentService::class, $enrollmentService);

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/12/access', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = $controller->access($request, 12);

    assertJsonResponsePayload($response, 404, [
        'status' => false,
        'status_code' => 404,
    ]);
});

it('returns learning progress for enrolled user', function (): void {
    $course = new Course;
    $course->id = 12;
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')
        ->once()
        ->with(12)
        ->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')
        ->once()
        ->with(7, 12)
        ->andReturnTrue();
    $enrollmentService->shouldReceive('getLearningProgress')
        ->once()
        ->with(7, 12)
        ->andReturn([
            'course_id' => 12,
            'current_lesson_id' => 100,
            'completed_lesson_ids' => [99],
            'total_lessons' => 2,
            'progress_percentage' => 50.0,
            'has_reviewed' => false,
        ]);
    app()->instance(IEnrollmentService::class, $enrollmentService);

    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/12/learning-progress', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = $controller->learningProgress($request, 12);

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
        'data' => [
            'course_id' => 12,
            'current_lesson_id' => 100,
            'completed_lesson_ids' => [99],
            'total_lessons' => 2,
            'progress_percentage' => 50.0,
            'has_reviewed' => false,
        ],
    ]);
});

it('returns unauthorized for learning progress when user is not enrolled', function (): void {
    $course = new Course;
    $course->id = 12;
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')->once()->with(12)->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 12)->andReturnFalse();
    app()->instance(IEnrollmentService::class, $enrollmentService);

    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/12/learning-progress', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = $controller->learningProgress($request, 12);

    assertJsonResponsePayload($response, 401, [
        'message' => 'Course access denied',
        'status' => false,
        'status_code' => 401,
    ]);
});

it('completes lesson and returns next lesson metadata', function (): void {
    $course = new Course;
    $course->id = 12;
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')->once()->with(12)->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 12)->andReturnTrue();
    $enrollmentService->shouldReceive('completeLesson')
        ->once()
        ->with(7, 12, 100)
        ->andReturn([
            'course_id' => 12,
            'current_lesson_id' => 101,
            'completed_lesson_ids' => [100],
            'total_lessons' => 2,
            'progress_percentage' => 50.0,
            'next_lesson' => [
                'id' => 101,
                'has_quiz' => true,
            ],
        ]);
    app()->instance(IEnrollmentService::class, $enrollmentService);

    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/12/lessons/100/complete', 'POST');
    $request->setUserResolver(fn () => $user);

    $response = $controller->completeLesson($request, 12, 100);

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
        'data' => [
            'course_id' => 12,
            'current_lesson_id' => 101,
            'completed_lesson_ids' => [100],
            'total_lessons' => 2,
            'progress_percentage' => 50.0,
            'next_lesson' => [
                'id' => 101,
                'has_quiz' => true,
            ],
        ],
    ]);
});

it('updates current lesson for enrolled user', function (): void {
    $course = new Course;
    $course->id = 12;
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')->once()->with(12)->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 12)->andReturnTrue();
    $enrollmentService->shouldReceive('setCurrentLesson')
        ->once()
        ->with(7, 12, 101)
        ->andReturn([
            'course_id' => 12,
            'current_lesson_id' => 101,
            'completed_lesson_ids' => [100],
            'total_lessons' => 2,
            'progress_percentage' => 50.0,
        ]);
    app()->instance(IEnrollmentService::class, $enrollmentService);

    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));

    $controller = app()->make(CourseController::class);
    $request = CourseCurrentLessonRequest::create('/api/v1/courses/12/current-lesson', 'POST', [
        'lesson_id' => 101,
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->setUserResolver(fn () => $user);
    $request->validateResolved();

    $response = $controller->setCurrentLesson($request, 12);

    assertJsonResponsePayload($response, 200, [
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
        'data' => [
            'course_id' => 12,
            'current_lesson_id' => 101,
            'completed_lesson_ids' => [100],
            'total_lessons' => 2,
            'progress_percentage' => 50.0,
        ],
    ]);
});

it('returns not found when learning progress course does not exist', function (): void {
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')->once()->with(12)->andReturnNull();
    app()->instance(ICourseService::class, $service);
    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));
    app()->instance(IEnrollmentService::class, \Mockery::mock(IEnrollmentService::class));

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/12/learning-progress', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = $controller->learningProgress($request, 12);
    assertJsonResponsePayload($response, 404, ['status' => false, 'status_code' => 404]);
});

it('returns not found when learning progress is missing', function (): void {
    $course = new Course;
    $course->id = 12;
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')->once()->with(12)->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 12)->andReturnTrue();
    $enrollmentService->shouldReceive('getLearningProgress')->once()->with(7, 12)->andReturnNull();
    app()->instance(IEnrollmentService::class, $enrollmentService);
    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));

    $controller = app()->make(CourseController::class);
    $request = Request::create('/api/v1/courses/12/learning-progress', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = $controller->learningProgress($request, 12);
    assertJsonResponsePayload($response, 404, ['status' => false, 'status_code' => 404]);
});

it('returns not found and unauthorized branches in complete lesson', function (): void {
    $course = new Course;
    $course->id = 12;
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')->once()->with(99)->andReturnNull();
    $service->shouldReceive('getById')->once()->with(12)->andReturn($course);
    $service->shouldReceive('getById')->once()->with(13)->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 12)->andReturnFalse();
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 13)->andReturnTrue();
    $enrollmentService->shouldReceive('completeLesson')->once()->with(7, 13, 101)->andReturnNull();
    app()->instance(IEnrollmentService::class, $enrollmentService);
    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));

    $controller = app()->make(CourseController::class);

    $notFoundResponse = $controller->completeLesson(tap(Request::create('/api/v1/courses/99/lessons/100/complete', 'POST'), fn (Request $r) => $r->setUserResolver(fn () => $user)), 99, 100);
    assertJsonResponsePayload($notFoundResponse, 404, ['status' => false, 'status_code' => 404]);

    $unauthorizedResponse = $controller->completeLesson(tap(Request::create('/api/v1/courses/12/lessons/100/complete', 'POST'), fn (Request $r) => $r->setUserResolver(fn () => $user)), 12, 100);
    assertJsonResponsePayload($unauthorizedResponse, 401, ['status' => false, 'status_code' => 401]);

    $progressMissingResponse = $controller->completeLesson(tap(Request::create('/api/v1/courses/13/lessons/101/complete', 'POST'), fn (Request $r) => $r->setUserResolver(fn () => $user)), 13, 101);
    assertJsonResponsePayload($progressMissingResponse, 404, ['status' => false, 'status_code' => 404]);
});

it('returns not found and unauthorized branches in set current lesson', function (): void {
    $course = new Course;
    $course->id = 12;
    $user = new User;
    $user->id = 7;

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('getById')->once()->with(99)->andReturnNull();
    $service->shouldReceive('getById')->once()->with(12)->andReturn($course);
    $service->shouldReceive('getById')->once()->with(13)->andReturn($course);
    app()->instance(ICourseService::class, $service);

    $enrollmentService = \Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 12)->andReturnFalse();
    $enrollmentService->shouldReceive('isEnrolled')->once()->with(7, 13)->andReturnTrue();
    $enrollmentService->shouldReceive('setCurrentLesson')->once()->with(7, 13, 101)->andReturnNull();
    app()->instance(IEnrollmentService::class, $enrollmentService);
    app()->instance(ICartService::class, \Mockery::mock(ICartService::class));

    $controller = app()->make(CourseController::class);

    $notFoundReq = CourseCurrentLessonRequest::create('/api/v1/courses/99/current-lesson', 'POST', ['lesson_id' => 101]);
    $notFoundReq->setContainer(app())->setRedirector(app('redirect'));
    $notFoundReq->setUserResolver(fn () => $user);
    $notFoundReq->validateResolved();
    assertJsonResponsePayload($controller->setCurrentLesson($notFoundReq, 99), 404, ['status' => false, 'status_code' => 404]);

    $unauthReq = CourseCurrentLessonRequest::create('/api/v1/courses/12/current-lesson', 'POST', ['lesson_id' => 101]);
    $unauthReq->setContainer(app())->setRedirector(app('redirect'));
    $unauthReq->setUserResolver(fn () => $user);
    $unauthReq->validateResolved();
    assertJsonResponsePayload($controller->setCurrentLesson($unauthReq, 12), 401, ['status' => false, 'status_code' => 401]);

    $missingReq = CourseCurrentLessonRequest::create('/api/v1/courses/13/current-lesson', 'POST', ['lesson_id' => 101]);
    $missingReq->setContainer(app())->setRedirector(app('redirect'));
    $missingReq->setUserResolver(fn () => $user);
    $missingReq->validateResolved();
    assertJsonResponsePayload($controller->setCurrentLesson($missingReq, 13), 404, ['status' => false, 'status_code' => 404]);
});
