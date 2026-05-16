<?php
declare(strict_types=1);

namespace App\Services\Dashboard;

use App\DTO\Dashboard\DashboardEnrolledCourseResponse;
use App\DTO\Dashboard\DashboardOverviewResponse;
use App\DTO\Dashboard\DashboardRecentActivityResponse;
use App\DTO\Dashboard\DashboardStatsResponse;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\UserQuizAttempt;
use App\Repositories\Dashboard\IDashboardRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService implements IDashboardService
{
    public function __construct(
        protected IDashboardRepository $dashboardRepository,
    ) {}

    public function overview(int $userId): DashboardOverviewResponse
    {
        $enrollments = $this->dashboardRepository->getEnrollmentsByUserId($userId);

        $courseIds = $enrollments->pluck('course_id')->filter()->values();

        $progressRows = $this->dashboardRepository->getLessonProgressByUserAndCourseIds($userId, $courseIds);

        $completedRows = $progressRows->whereNotNull('completed_at');
        $currentRows = $progressRows->where('is_current', true);

        $attempts = $this->dashboardRepository->getRecentQuizAttemptsByUserId($userId, 10);

        $hoursThisWeek = (float) $completedRows
            ->filter(function (LessonProgress $row): bool {
                if (! $row->completed_at) {
                    return false;
                }

                return Carbon::parse($row->completed_at)->isCurrentWeek();
            })
            ->sum(fn (LessonProgress $row): int => (int) ($row->lesson?->duration_minutes ?? 0)) / 60;

        $courses = $this->buildCourses($enrollments, $completedRows, $currentRows);
        $recentActivity = $this->buildRecentActivity($completedRows, $attempts, $enrollments);
        $streakDays = $this->estimateStreakDays($completedRows);
        $weeklyPlan = $this->buildWeeklyPlan($completedRows, $currentRows, $streakDays);

        return new DashboardOverviewResponse(
            stats: new DashboardStatsResponse(
                enrolledCourses: (int) $enrollments->count(),
                hoursThisWeek: $hoursThisWeek,
                certificates: 0,
                streakDays: $streakDays,
            ),
            enrolledCourses: $courses,
            recentActivity: $recentActivity,
            certificates: [],
            weeklyPlan: $weeklyPlan,
        );
    }

    /**
     * @param Collection<int, Enrollment> $enrollments
     * @param Collection<int, LessonProgress> $completedRows
     * @param Collection<int, LessonProgress> $currentRows
     * @return array<int, DashboardEnrolledCourseResponse>
     */
    protected function buildCourses(Collection $enrollments, Collection $completedRows, Collection $currentRows): array
    {
        return $enrollments->map(function (Enrollment $enrollment) use ($completedRows, $currentRows): DashboardEnrolledCourseResponse {
            $courseId = (int) $enrollment->course_id;
            $courseCompleted = $completedRows->where('course_id', $courseId);
            $totalCompleted = $courseCompleted->count();
            $estimatedTotalLessons = max($totalCompleted + 3, 3);
            $progressPercent = min((int) round(($totalCompleted / $estimatedTotalLessons) * 100), 100);

            $currentLesson = $currentRows->firstWhere('course_id', $courseId)?->lesson;
            $lastAccessedAt = $courseCompleted
                ->sortByDesc('completed_at')
                ->first()?->completed_at ?? $enrollment->enrolled_at;
            $coursePayload = $enrollment->course?->toArray() ?? [];
            $coursePayload['name'] = (string) ($coursePayload['name'] ?? 'Khóa học');
            $coursePayload['slug'] = (string) ($coursePayload['slug'] ?? '');
            $coursePayload['thumbnail'] = (string) ($coursePayload['thumbnail'] ?? '');
            $coursePayload['price'] = (int) ($coursePayload['price'] ?? 0);
            $coursePayload['learned'] = (int) ($coursePayload['learned'] ?? 0);
            $coursePayload['lessons_count'] = (int) ($coursePayload['lessons_count'] ?? 0);
            $coursePayload['comments_count'] = (int) ($coursePayload['comments_count'] ?? 0);
            $coursePayload['total_duration_minutes'] = (int) ($coursePayload['total_duration_minutes'] ?? 0);

            if (! isset($coursePayload['category']) || ! is_array($coursePayload['category'])) {
                $coursePayload['category'] = [
                    'id' => $enrollment->course?->category?->id,
                    'name' => (string) ($enrollment->course?->category?->name ?? 'Tiếng Anh'),
                    'slug' => (string) ($enrollment->course?->category?->slug ?? ''),
                ];
            }

            return new DashboardEnrolledCourseResponse(
                course: $coursePayload,
                progress: $progressPercent,
                instructor: 'Shining English',
                nextLesson: $currentLesson?->name,
                lastAccessed: $lastAccessedAt ? Carbon::parse($lastAccessedAt)->diffForHumans() : 'Vừa xong',
            );
        })->values()->all();
    }

    /**
     * @param Collection<int, LessonProgress> $completedRows
     * @param Collection<int, UserQuizAttempt> $attempts
     * @param Collection<int, Enrollment> $enrollments
     * @return array<int, DashboardRecentActivityResponse>
     */
    protected function buildRecentActivity(Collection $completedRows, Collection $attempts, Collection $enrollments): array
    {
        $completedActivities = $completedRows
            ->sortByDesc('completed_at')
            ->take(4)
            ->map(function (LessonProgress $row): array {
                return [
                    'type' => 'completed',
                    'title' => 'Hoàn thành bài: ' . (string) ($row->lesson?->name ?? 'Bài học'),
                    'course' => (string) ($row->lesson?->course?->name ?? 'Khóa học'),
                    'time' => $row->completed_at ? Carbon::parse($row->completed_at)->diffForHumans() : 'Vừa xong',
                    'score' => null,
                    'at' => $row->completed_at ? Carbon::parse($row->completed_at)->timestamp : 0,
                ];
            });

        $attemptActivities = $attempts
            ->take(4)
            ->map(function (UserQuizAttempt $attempt): array {
                return [
                    'type' => 'passed',
                    'title' => 'Đạt quiz: ' . (string) ($attempt->quiz?->lesson?->name ?? 'Quiz'),
                    'course' => (string) ($attempt->quiz?->lesson?->course?->name ?? 'Khóa học'),
                    'time' => $attempt->submitted_at ? Carbon::parse($attempt->submitted_at)->diffForHumans() : 'Vừa xong',
                    'score' => (int) round((float) $attempt->score_percent),
                    'at' => $attempt->submitted_at ? Carbon::parse($attempt->submitted_at)->timestamp : 0,
                ];
            });

        $enrolledActivities = $enrollments
            ->take(4)
            ->map(function (Enrollment $enrollment): array {
                return [
                    'type' => 'enrolled',
                    'title' => 'Đăng ký khóa mới',
                    'course' => (string) ($enrollment->course?->name ?? 'Khóa học'),
                    'time' => $enrollment->enrolled_at ? Carbon::parse($enrollment->enrolled_at)->diffForHumans() : 'Vừa xong',
                    'score' => null,
                    'at' => $enrollment->enrolled_at ? Carbon::parse($enrollment->enrolled_at)->timestamp : 0,
                ];
            });

        return $completedActivities
            ->concat($attemptActivities)
            ->concat($enrolledActivities)
            ->sortByDesc('at')
            ->take(8)
            ->values()
            ->map(function (array $item, int $index): DashboardRecentActivityResponse {
                return new DashboardRecentActivityResponse(
                    id: $index + 1,
                    type: (string) $item['type'],
                    title: (string) $item['title'],
                    course: (string) $item['course'],
                    time: (string) $item['time'],
                    score: isset($item['score']) ? (is_null($item['score']) ? null : (int) $item['score']) : null,
                );
            })
            ->all();
    }

    /**
     * @param Collection<int, LessonProgress> $completedRows
     */
    protected function estimateStreakDays(Collection $completedRows): int
    {
        $dates = $completedRows
            ->filter(fn (LessonProgress $row): bool => (bool) $row->completed_at)
            ->map(fn (LessonProgress $row): string => Carbon::parse($row->completed_at)->toDateString())
            ->unique()
            ->sortDesc()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = Carbon::today();

        while (true) {
            if ($dates->contains($cursor->toDateString())) {
                $streak++;
                $cursor = $cursor->subDay();
                continue;
            }

            if ($streak === 0 && $dates->contains(Carbon::yesterday()->toDateString())) {
                $streak++;
                $cursor = Carbon::yesterday()->subDay();
                continue;
            }

            break;
        }

        return $streak;
    }

    /**
     * @param Collection<int, LessonProgress> $completedRows
     * @param Collection<int, LessonProgress> $currentRows
     * @return array<int, array{label: string, status: string, tone: string}>
     */
    protected function buildWeeklyPlan(
        Collection $completedRows,
        Collection $currentRows,
        int $streakDays,
    ): array {
        $todayCompletedRows = $completedRows->filter(function (LessonProgress $row): bool {
            if (! $row->completed_at) {
                return false;
            }

            return Carbon::parse($row->completed_at)->isToday();
        });

        $completedLessonsToday = (int) $todayCompletedRows->count();
        $dailyLessonGoal = $this->goalInt('daily_lessons', 2);
        $lessonTone = $completedLessonsToday >= $dailyLessonGoal
            ? 'done'
            : ($completedLessonsToday > 0 ? 'doing' : 'todo');

        $minutesToday = (int) $todayCompletedRows
            ->sum(fn (LessonProgress $row): int => (int) ($row->lesson?->duration_minutes ?? 0));
        $dailyMinutesGoal = $this->goalInt('daily_study_minutes', 5);
        $hoursTone = $minutesToday >= $dailyMinutesGoal
            ? 'done'
            : ($minutesToday > 0 ? 'doing' : 'todo');

        $activeCourseIdsToday = $todayCompletedRows
            ->pluck('course_id')
            ->filter()
            ->unique();
        $currentCourseIds = $currentRows
            ->pluck('course_id')
            ->filter()
            ->unique();
        $focusCourseCount = (int) $activeCourseIdsToday
            ->merge($currentCourseIds)
            ->unique()
            ->count();
        $focusGoal = $this->goalInt('focus_courses', 2);
        $focusTone = $focusCourseCount >= $focusGoal
            ? 'done'
            : ($focusCourseCount > 0 ? 'doing' : 'todo');

        $streakGoal = $this->goalInt('streak_days', 7);
        $streakTone = $streakDays >= $streakGoal
            ? 'done'
            : ($streakDays > 0 ? 'doing' : 'todo');

        return [
            [
                'label' => 'Hoàn thành bài học hôm nay',
                'status' => "{$completedLessonsToday}/{$dailyLessonGoal} bài",
                'tone' => $lessonTone,
            ],
            [
                'label' => 'Giờ học hôm nay',
                'status' => "{$minutesToday}m/{$dailyMinutesGoal}m",
                'tone' => $hoursTone,
            ],
            [
                'label' => 'Giữ nhịp học hôm nay',
                'status' => "{$focusCourseCount}/{$focusGoal} khóa đang học",
                'tone' => $focusTone,
            ],
            [
                'label' => 'Duy trì chuỗi học',
                'status' => "{$streakDays}/{$streakGoal} ngày",
                'tone' => $streakTone,
            ],
        ];
    }

    private function goalInt(string $key, int $fallback): int
    {
        $value = (int) config("dashboard.goals.{$key}", $fallback);

        return $value > 0 ? $value : $fallback;
    }
}
