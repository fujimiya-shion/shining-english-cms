<?php

use App\DTO\Dashboard\DashboardOverviewResponse;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\UserQuizAttempt;
use App\Repositories\Dashboard\IDashboardRepository;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\IDashboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('implements service contract', function (): void {
    $repository = Mockery::mock(IDashboardRepository::class);
    $service = new DashboardService($repository);

    expect($service)->toBeInstanceOf(IDashboardService::class);
});

it('returns overview for user with no activity', function (): void {
    $enrollments = new Collection;
    $courseIds = $enrollments->pluck('course_id')->filter()->values();
    $empty = new Collection;

    $repository = Mockery::mock(IDashboardRepository::class);
    $repository->shouldReceive('getEnrollmentsByUserId')
        ->once()
        ->with(1)
        ->andReturn($enrollments);
    $repository->shouldReceive('getLessonProgressByUserAndCourseIds')
        ->once()
        ->with(1, Mockery::on(function (Collection $ids) use ($courseIds): bool {
            return $ids->values()->all() === $courseIds->values()->all();
        }))
        ->andReturn($empty);
    $repository->shouldReceive('getRecentQuizAttemptsByUserId')
        ->once()
        ->with(1, 10)
        ->andReturn($empty);

    $service = new DashboardService($repository);
    $result = $service->overview(1);

    expect($result)->toBeInstanceOf(DashboardOverviewResponse::class);
    expect($result->stats->enrolledCourses)->toBe(0);
    expect($result->stats->hoursThisWeek)->toBe(0.0);
    expect($result->stats->streakDays)->toBe(0);
    expect($result->enrolledCourses)->toBe([]);
    expect($result->recentActivity)->toBe([]);
    expect($result->weeklyPlan)->toHaveCount(4);
});

it('returns overview for user with active learning data', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-27 10:00:00'));
    config([
        'dashboard.goals.daily_lessons' => 1,
        'dashboard.goals.daily_study_minutes' => 15,
        'dashboard.goals.focus_courses' => 1,
        'dashboard.goals.streak_days' => 1,
    ]);

    $category = new Category([
        'id' => 7,
        'name' => 'IELTS',
        'slug' => 'ielts',
    ]);

    $course = new Course;
    $course->setRawAttributes([
        'id' => 10,
        'name' => 'IELTS Foundation',
        'slug' => 'ielts-foundation',
        'thumbnail' => 'courses/thumb.jpg',
        'price' => 1500000,
        'learned' => 12,
        'lessons_count' => 8,
        'comments_count' => 2,
        'total_duration_minutes' => 120,
        'category_id' => 7,
    ], true);
    $course->setRelation('category', $category);

    $enrollment = new Enrollment([
        'user_id' => 1,
        'course_id' => 10,
        'enrolled_at' => Carbon::parse('2026-06-25 08:00:00'),
    ]);
    $enrollment->setRelation('course', $course);

    $lessonCourse = new Course;
    $lessonCourse->setRawAttributes(['id' => 10, 'name' => 'IELTS Foundation'], true);

    $completedLesson = new Lesson([
        'id' => 100,
        'name' => 'Warm up',
        'course_id' => 10,
        'duration_minutes' => 30,
    ]);
    $completedLesson->setRelation('course', $lessonCourse);

    $currentLesson = new Lesson([
        'id' => 101,
        'name' => 'Next lesson',
        'course_id' => 10,
        'duration_minutes' => 20,
    ]);
    $currentLesson->setRelation('course', $lessonCourse);

    $completedProgress = new LessonProgress([
        'user_id' => 1,
        'course_id' => 10,
        'lesson_id' => 100,
        'is_current' => false,
        'completed_at' => Carbon::parse('2026-06-27 09:00:00'),
    ]);
    $completedProgress->setRelation('lesson', $completedLesson);

    $currentProgress = new LessonProgress([
        'user_id' => 1,
        'course_id' => 10,
        'lesson_id' => 101,
        'is_current' => true,
        'completed_at' => null,
    ]);
    $currentProgress->setRelation('lesson', $currentLesson);

    $quiz = new Quiz(['id' => 33, 'lesson_id' => 100]);
    $quiz->setRelation('lesson', $completedLesson);

    $attempt = new UserQuizAttempt([
        'user_id' => 1,
        'quiz_id' => 33,
        'score_percent' => 88.4,
        'submitted_at' => Carbon::parse('2026-06-27 09:30:00'),
    ]);
    $attempt->setRelation('quiz', $quiz);

    $repository = Mockery::mock(IDashboardRepository::class);
    $repository->shouldReceive('getEnrollmentsByUserId')
        ->once()
        ->with(1)
        ->andReturn(new Collection([$enrollment]));
    $repository->shouldReceive('getLessonProgressByUserAndCourseIds')
        ->once()
        ->with(1, Mockery::on(fn (Collection $ids): bool => $ids->all() === [10]))
        ->andReturn(new Collection([$completedProgress, $currentProgress]));
    $repository->shouldReceive('getRecentQuizAttemptsByUserId')
        ->once()
        ->with(1, 10)
        ->andReturn(new Collection([$attempt]));

    $result = (new DashboardService($repository))->overview(1);
    $array = $result->toArray();

    expect($result)->toBeInstanceOf(DashboardOverviewResponse::class);
    expect($result->stats->enrolledCourses)->toBe(1);
    expect($result->stats->hoursThisWeek)->toBe(0.5);
    expect($result->stats->streakDays)->toBe(1);
    expect($array['enrolled_courses'][0]['progress'])->toBe(25);
    expect($array['enrolled_courses'][0]['next_lesson'])->toBe('Next lesson');
    expect($array['enrolled_courses'][0]['course']['category']['name'])->toBe('IELTS');
    expect($array['recent_activity'])->toHaveCount(3);
    expect($array['recent_activity'][0]['type'])->toBe('passed');
    expect($array['recent_activity'][0]['score'])->toBe(88);
    expect(array_column($array['weekly_plan'], 'tone'))->toBe(['done', 'done', 'done', 'done']);

    Carbon::setTestNow();
});

it('estimates streak from yesterday when today has no completion', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-27 10:00:00'));

    $progress = new LessonProgress([
        'completed_at' => Carbon::parse('2026-06-26 09:00:00'),
    ]);

    $service = new DashboardService(Mockery::mock(IDashboardRepository::class));

    expect(invokeProtectedMethod($service, 'estimateStreakDays', [new Collection([$progress])]))->toBe(1);

    Carbon::setTestNow();
});

it('builds todo weekly plan when goals are invalid and no work is done', function (): void {
    config([
        'dashboard.goals.daily_lessons' => 0,
        'dashboard.goals.daily_study_minutes' => -1,
        'dashboard.goals.focus_courses' => 0,
        'dashboard.goals.streak_days' => -1,
    ]);

    $service = new DashboardService(Mockery::mock(IDashboardRepository::class));

    $weeklyPlan = invokeProtectedMethod($service, 'buildWeeklyPlan', [
        new Collection,
        new Collection,
        0,
    ]);

    expect(array_column($weeklyPlan, 'status'))->toBe([
        '0/2 bài',
        '0m/5m',
        '0/2 khóa đang học',
        '0/7 ngày',
    ]);
    expect(array_column($weeklyPlan, 'tone'))->toBe(['todo', 'todo', 'todo', 'todo']);
});

it('builds course with fallback category when course has no category', function (): void {
    $course = new Course;
    $course->setRawAttributes([
        'id' => 20,
        'name' => 'No Category Course',
        'slug' => 'no-cat',
        'thumbnail' => '',
        'price' => 0,
        'learned' => 0,
    ], true);

    $enrollment = new Enrollment([
        'user_id' => 1,
        'course_id' => 20,
        'enrolled_at' => Carbon::parse('2026-06-26 08:00:00'),
    ]);
    $enrollment->setRelation('course', $course);

    $repository = Mockery::mock(IDashboardRepository::class);
    $repository->shouldReceive('getEnrollmentsByUserId')
        ->once()
        ->with(1)
        ->andReturn(new Collection([$enrollment]));
    $repository->shouldReceive('getLessonProgressByUserAndCourseIds')
        ->once()
        ->with(1, Mockery::on(fn (Collection $ids): bool => $ids->all() === [20]))
        ->andReturn(new Collection);
    $repository->shouldReceive('getRecentQuizAttemptsByUserId')
        ->once()
        ->with(1, 10)
        ->andReturn(new Collection);

    $service = new DashboardService($repository);
    $array = $service->overview(1)->toArray();

    expect($array['enrolled_courses'][0]['course']['category']['name'])->toBe('Tiếng Anh');
});
