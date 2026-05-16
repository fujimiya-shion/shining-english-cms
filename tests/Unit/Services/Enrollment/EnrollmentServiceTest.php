<?php

namespace Tests\Unit\Services\Enrollment;

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Enrollment\EnrollmentRepository;
use App\Repositories\Enrollment\IEnrollmentRepository;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\IEnrollmentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements shared service contract', function (): void {
    $model = new Enrollment;
    $repository = new EnrollmentRepository($model);
    $service = new EnrollmentService($repository);

    assertServiceContract($service, IEnrollmentService::class, $repository);
});

it('enrolls a user when missing', function (): void {
    $enrollment = new Enrollment;

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourseWithTrashed')
        ->once()
        ->with(10, 20)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data): bool {
            return $data['user_id'] === 10
                && $data['course_id'] === 20
                && $data['order_id'] === 30
                && isset($data['enrolled_at']);
        }))
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    $result = $service->enroll(10, 20, 30);

    expect($result)->toBe($enrollment);
});

it('returns existing enrollment', function (): void {
    $enrollment = new Enrollment;

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourseWithTrashed')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);
    $repository->shouldReceive('create')->never();

    $service = new EnrollmentService($repository);

    $result = $service->enroll(10, 20);

    expect($result)->toBe($enrollment);
});

it('returns existing enrollment when create hits a duplicate', function (): void {
    $enrollment = new Enrollment;

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourseWithTrashed')
        ->once()
        ->with(10, 20)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->andThrow(new QueryException(
            '',
            '',
            [],
            new RuntimeException('duplicate'),
        ));
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    $result = $service->enroll(10, 20);

    expect($result)->toBe($enrollment);
});

it('throws when duplicate occurs without existing enrollment', function (): void {
    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourseWithTrashed')
        ->once()
        ->with(10, 20)
        ->andReturnNull();
    $repository->shouldReceive('create')
        ->once()
        ->andThrow(new QueryException(
            '',
            '',
            [],
            new RuntimeException('duplicate'),
        ));
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturnNull();

    $service = new EnrollmentService($repository);

    expect(fn () => $service->enroll(10, 20))->toThrow(QueryException::class);
});

it('restores a soft deleted enrollment', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    /** @var Enrollment $enrollment */
    $enrollment = Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'order_id' => null,
        'enrolled_at' => now()->subDays(2),
    ]);

    $enrollment->delete();

    $repository = new EnrollmentRepository(new Enrollment);
    $service = new EnrollmentService($repository);

    $result = $service->enroll($user->id, $course->id, 123);

    expect($result->trashed())->toBeFalse();
    expect($result->order_id)->toBe(123);
});

it('checks enrollment status', function (): void {
    $enrollment = new Enrollment;
    $enrollment->order_id = null;

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    expect($service->isEnrolled(10, 20))->toBeTrue();
});

it('returns false when enrollment does not exist', function (): void {
    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturnNull();

    $service = new EnrollmentService($repository);

    expect($service->isEnrolled(10, 20))->toBeFalse();
});

it('returns false when enrollment order is not paid', function (): void {
    $enrollment = new Enrollment;
    $enrollment->order_id = 30;
    $enrollment->setRelation('order', new Order([
        'status' => OrderStatus::Pending,
    ]));

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    expect($service->isEnrolled(10, 20))->toBeFalse();
});

it('returns true when enrollment order is paid', function (): void {
    $enrollment = new Enrollment;
    $enrollment->order_id = 30;
    $enrollment->setRelation('order', new Order([
        'status' => OrderStatus::Paid,
    ]));

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    expect($service->isEnrolled(10, 20))->toBeTrue();
});

it('returns true when enrollment order is pending approval', function (): void {
    $enrollment = new Enrollment;
    $enrollment->order_id = 30;
    $enrollment->setRelation('order', new Order([
        'status' => OrderStatus::Pending,
    ]));

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    expect($service->hasPendingEnrollment(10, 20))->toBeTrue();
});

it('returns false when enrollment does not exist while checking pending approval', function (): void {
    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturnNull();

    $service = new EnrollmentService($repository);

    expect($service->hasPendingEnrollment(10, 20))->toBeFalse();
});

it('returns false when enrollment has no order while checking pending approval', function (): void {
    $enrollment = new Enrollment;
    $enrollment->order_id = null;

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    expect($service->hasPendingEnrollment(10, 20))->toBeFalse();
});

it('returns false when enrollment order is already paid while checking pending approval', function (): void {
    $enrollment = new Enrollment;
    $enrollment->order_id = 30;
    $enrollment->setRelation('order', new Order([
        'status' => OrderStatus::Paid,
    ]));

    $repository = Mockery::mock(IEnrollmentRepository::class);
    $repository->shouldReceive('findByUserAndCourse')
        ->once()
        ->with(10, 20)
        ->andReturn($enrollment);

    $service = new EnrollmentService($repository);

    expect($service->hasPendingEnrollment(10, 20))->toBeFalse();
});

it('returns persisted learning progress payload for enrollment', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $lessonA = Lesson::query()->create([
        'name' => 'A',
        'slug' => 'a',
        'course_id' => $course->id,
        'group_name' => 'M1',
        'video_url' => 'lessons/a.mp4',
        'duration_minutes' => 5,
        'has_quiz' => false,
    ]);
    $lessonB = Lesson::query()->create([
        'name' => 'B',
        'slug' => 'b',
        'course_id' => $course->id,
        'group_name' => 'M1',
        'video_url' => 'lessons/b.mp4',
        'duration_minutes' => 6,
        'has_quiz' => true,
    ]);

    Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'enrolled_at' => now(),
    ]);
    LessonProgress::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'lesson_id' => $lessonA->id,
        'completed_at' => now()->subMinute(),
        'is_current' => false,
    ]);
    LessonProgress::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'lesson_id' => $lessonB->id,
        'completed_at' => null,
        'is_current' => true,
    ]);

    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));
    $result = $service->getLearningProgress($user->id, $course->id);

    expect($result)->not->toBeNull();
    expect($result['current_lesson_id'])->toBe($lessonB->id);
    expect($result['completed_lesson_ids'])->toBe([$lessonA->id]);
    expect($result['total_lessons'])->toBe(2);
    expect($result['progress_percentage'])->toBe(50.0);
});

it('completes a lesson, moves to next lesson and returns next quiz hint', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $lessonA = Lesson::query()->create([
        'name' => 'A',
        'slug' => 'a',
        'course_id' => $course->id,
        'group_name' => 'M1',
        'video_url' => 'lessons/a.mp4',
        'duration_minutes' => 5,
        'has_quiz' => false,
    ]);
    $lessonB = Lesson::query()->create([
        'name' => 'B',
        'slug' => 'b',
        'course_id' => $course->id,
        'group_name' => 'M1',
        'video_url' => 'lessons/b.mp4',
        'duration_minutes' => 6,
        'has_quiz' => true,
    ]);

    Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'enrolled_at' => now(),
    ]);
    LessonProgress::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'lesson_id' => $lessonA->id,
        'completed_at' => null,
        'is_current' => true,
    ]);

    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));
    $result = $service->completeLesson($user->id, $course->id, $lessonA->id);

    expect($result)->not->toBeNull();
    expect($result['current_lesson_id'])->toBe($lessonB->id);
    expect($result['completed_lesson_ids'])->toBe([$lessonA->id]);
    expect($result['next_lesson'])->toBe([
        'id' => $lessonB->id,
        'has_quiz' => true,
    ]);
});

it('returns null progress payload when enrollment is missing', function (): void {
    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));

    expect($service->getLearningProgress(999, 999))->toBeNull();
});

it('returns null when completing lesson without enrollment', function (): void {
    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));

    expect($service->completeLesson(999, 999, 1))->toBeNull();
});

it('returns null when completing lesson that is not in course', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $foreignLesson = Lesson::factory()->create([
        'course_id' => $otherCourse->id,
    ]);

    Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'enrolled_at' => now(),
    ]);

    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));

    expect($service->completeLesson($user->id, $course->id, $foreignLesson->id))->toBeNull();
});

it('returns null when setting current lesson without enrollment', function (): void {
    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));

    expect($service->setCurrentLesson(999, 999, 1))->toBeNull();
});

it('returns null when setting current lesson that is not in course', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $foreignLesson = Lesson::factory()->create([
        'course_id' => $otherCourse->id,
    ]);

    Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'enrolled_at' => now(),
    ]);

    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));

    expect($service->setCurrentLesson($user->id, $course->id, $foreignLesson->id))->toBeNull();
});

it('returns zero progress when enrolled course has no lessons', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'enrolled_at' => now(),
    ]);

    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));
    $progress = $service->getLearningProgress($user->id, $course->id);

    expect($progress)->toBe([
        'course_id' => $course->id,
        'current_lesson_id' => null,
        'completed_lesson_ids' => [],
        'total_lessons' => 0,
        'progress_percentage' => 0.0,
        'has_reviewed' => false,
    ]);
});

it('sets current lesson and clears previous current flags', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $lessonA = Lesson::factory()->create(['course_id' => $course->id]);
    $lessonB = Lesson::factory()->create(['course_id' => $course->id]);

    Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'enrolled_at' => now(),
    ]);

    LessonProgress::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'lesson_id' => $lessonA->id,
        'completed_at' => null,
        'is_current' => true,
    ]);

    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));
    $result = $service->setCurrentLesson($user->id, $course->id, $lessonB->id);

    expect($result)->not->toBeNull();
    expect((bool) LessonProgress::query()
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->where('lesson_id', $lessonA->id)
        ->value('is_current'))->toBeFalse();
    expect((bool) LessonProgress::query()
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->where('lesson_id', $lessonB->id)
        ->value('is_current'))->toBeTrue();
});

it('ignores progress rows for soft deleted lessons when building progress payload', function (): void {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $lessonActive = Lesson::factory()->create(['course_id' => $course->id]);
    $lessonDeleted = Lesson::factory()->create(['course_id' => $course->id]);
    $lessonDeleted->delete();

    Enrollment::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'enrolled_at' => now(),
    ]);

    LessonProgress::query()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'lesson_id' => $lessonDeleted->id,
        'completed_at' => now(),
        'is_current' => true,
    ]);

    $service = new EnrollmentService(new EnrollmentRepository(new Enrollment));
    $result = $service->getLearningProgress($user->id, $course->id);

    expect($result['completed_lesson_ids'])->toBe([]);
    expect($result['current_lesson_id'])->toBe($lessonActive->id);
});
