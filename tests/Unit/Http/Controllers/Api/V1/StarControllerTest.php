<?php

use App\Http\Controllers\Api\V1\StarController;
use App\Models\Course;
use App\Models\User;
use App\Services\Course\ICourseService;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\Order\IOrderService;
use App\Services\Star\IStarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('handles star balance check-in and course payment flows', function (): void {
    DB::statement('CREATE TABLE IF NOT EXISTS daily_check_ins (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, checked_in_at DATETIME NOT NULL, reward_amount INT NOT NULL DEFAULT 0, created_at DATETIME, updated_at DATETIME)');
    config(['const.star.daily_checkin' => 2]);

    $user = new User;
    $user->id = 5;
    $request = Request::create('/stars', 'GET');
    $request->setUserResolver(fn () => $user);

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('getBalance')->andReturn(10, 12, 7, 5);
    $starService->shouldReceive('addStarByUserId')->once()->andReturnTrue();
    $starService->shouldReceive('spendStarByUserId')->once()->andReturnFalse();

    $orderService = Mockery::mock(IOrderService::class);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->with(5, 10)->andReturnFalse();

    $course = new Course;
    $course->id = 10;
    $course->name = 'Paid course';
    $course->allow_star_payment = true;
    $course->star_price = 20;

    $courseService = Mockery::mock(ICourseService::class);
    $courseService->shouldReceive('getById')->with(10)->andReturn($course);

    $controller = new StarController($starService, $orderService, $enrollmentService, $courseService);

    expect($controller->balance($request)->getData(true)['data']['balance'])->toBe(10);
    expect($controller->checkIn($request)->getStatusCode())->toBe(200);
    expect($controller->checkIn($request)->getStatusCode())->toBe(422);
    expect($controller->payForCourse($request, 10)->getStatusCode())->toBe(422);
});

it('returns success for already enrolled star course payment', function (): void {
    $user = new User;
    $user->id = 5;
    $request = Request::create('/stars/pay', 'POST');
    $request->setUserResolver(fn () => $user);

    $course = new Course;
    $course->id = 10;
    $course->allow_star_payment = true;
    $course->star_price = 20;

    $courseService = Mockery::mock(ICourseService::class);
    $courseService->shouldReceive('getById')->with(10)->andReturn($course);
    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->with(5, 10)->andReturnTrue();

    $controller = new StarController(
        Mockery::mock(IStarService::class),
        Mockery::mock(IOrderService::class),
        $enrollmentService,
        $courseService,
    );

    expect($controller->payForCourse($request, 10)->getStatusCode())->toBe(200);
});

it('returns notfound when course does not exist for star payment', function (): void {
    $user = new User;
    $user->id = 5;
    $request = Request::create('/stars/pay', 'POST');
    $request->setUserResolver(fn () => $user);

    $courseService = Mockery::mock(ICourseService::class);
    $courseService->shouldReceive('getById')->with(999)->andReturnNull();

    $controller = new StarController(
        Mockery::mock(IStarService::class),
        Mockery::mock(IOrderService::class),
        Mockery::mock(IEnrollmentService::class),
        $courseService,
    );

    $response = $controller->payForCourse($request, 999);
    expect($response->getStatusCode())->toBe(404);
});

it('returns error when course does not support star payment', function (): void {
    $user = new User;
    $user->id = 5;
    $request = Request::create('/stars/pay', 'POST');
    $request->setUserResolver(fn () => $user);

    $course = new Course;
    $course->id = 10;
    $course->allow_star_payment = false;

    $courseService = Mockery::mock(ICourseService::class);
    $courseService->shouldReceive('getById')->with(10)->andReturn($course);

    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->never();

    $controller = new StarController(
        Mockery::mock(IStarService::class),
        Mockery::mock(IOrderService::class),
        $enrollmentService,
        $courseService,
    );

    $response = $controller->payForCourse($request, 10);
    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['message'])->toBe('Khóa học này không hỗ trợ thanh toán bằng sao.');
});

it('returns error when user has insufficient stars', function (): void {
    $user = new User;
    $user->id = 5;
    $request = Request::create('/stars/pay', 'POST');
    $request->setUserResolver(fn () => $user);

    $course = new Course;
    $course->id = 10;
    $course->name = 'Paid';
    $course->allow_star_payment = true;
    $course->star_price = 50;

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('spendStarByUserId')
        ->once()
        ->with(50, 5, Mockery::type('string'), \App\Enums\StarTransactionType::StarPayment)
        ->andReturnFalse();
    $starService->shouldReceive('getBalance')->once()->andReturn(10);

    $courseService = Mockery::mock(ICourseService::class);
    $courseService->shouldReceive('getById')->with(10)->andReturn($course);

    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->with(5, 10)->andReturnFalse();

    $controller = new StarController(
        $starService,
        Mockery::mock(IOrderService::class),
        $enrollmentService,
        $courseService,
    );

    $response = $controller->payForCourse($request, 10);
    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['message'])->toBe('Không đủ sao để mở khóa học này.');
    expect($response->getData(true)['errors']['required_star'])->toBe(50);
    expect($response->getData(true)['errors']['star_balance'])->toBe(10);
});

it('returns success when star payment is processed', function (): void {
    $user = new User;
    $user->id = 5;
    $request = Request::create('/stars/pay', 'POST');
    $request->setUserResolver(fn () => $user);

    $course = new Course;
    $course->id = 10;
    $course->name = 'Paid';
    $course->allow_star_payment = true;
    $course->star_price = 20;

    $starService = Mockery::mock(IStarService::class);
    $starService->shouldReceive('spendStarByUserId')
        ->once()
        ->andReturnTrue();
    $starService->shouldReceive('getBalance')->andReturn(5);

    $orderService = Mockery::mock(IOrderService::class);
    $orderService->shouldReceive('createWithStarPayment')
        ->once()
        ->with(5, 10);

    $courseService = Mockery::mock(ICourseService::class);
    $courseService->shouldReceive('getById')->with(10)->andReturn($course);

    $enrollmentService = Mockery::mock(IEnrollmentService::class);
    $enrollmentService->shouldReceive('isEnrolled')->with(5, 10)->andReturnFalse();

    $controller = new StarController(
        $starService,
        $orderService,
        $enrollmentService,
        $courseService,
    );

    $response = $controller->payForCourse($request, 10);
    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['star_balance'])->toBe(5);
});
