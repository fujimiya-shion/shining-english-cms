<?php

namespace App\Services\Dashboard;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\UserQuizAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService implements IDashboardService
{
    public function overview(int $userId): array
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $userId)
            ->with(['course:id,name,thumbnail,category_id', 'course.category:id,name'])
            ->latest('enrolled_at')
            ->get();

        $courseIds = $enrollments->pluck('course_id')->filter()->values();

        $progressRows = LessonProgress::query()
            ->where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->with(['lesson:id,name,course_id,duration_minutes'])
            ->get();

        $completedRows = $progressRows->whereNotNull('completed_at');
        $currentRows = $progressRows->where('is_current', true);

        $attempts = UserQuizAttempt::query()
            ->where('user_id', $userId)
            ->with(['quiz:id,lesson_id', 'quiz.lesson:id,name,course_id', 'quiz.lesson.course:id,name'])
            ->latest('submitted_at')
            ->limit(10)
            ->get();

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

        return [
            'stats' => [
                'enrolled_courses' => (int) $enrollments->count(),
                'hours_this_week' => round($hoursThisWeek, 1),
                'certificates' => 0,
                'streak_days' => $this->estimateStreakDays($completedRows),
            ],
            'enrolled_courses' => $courses,
            'recent_activity' => $recentActivity,
            'certificates' => [],
            'weekly_plan' => [],
        ];
    }

    /**
     * @param Collection<int, Enrollment> $enrollments
     * @param Collection<int, LessonProgress> $completedRows
     * @param Collection<int, LessonProgress> $currentRows
     * @return array<int, array<string, mixed>>
     */
    protected function buildCourses(Collection $enrollments, Collection $completedRows, Collection $currentRows): array
    {
        return $enrollments->map(function (Enrollment $enrollment) use ($completedRows, $currentRows): array {
            $courseId = (int) $enrollment->course_id;
            $courseCompleted = $completedRows->where('course_id', $courseId);
            $totalCompleted = $courseCompleted->count();
            $estimatedTotalLessons = max($totalCompleted + 3, 3);
            $progressPercent = min((int) round(($totalCompleted / $estimatedTotalLessons) * 100), 100);

            $currentLesson = $currentRows->firstWhere('course_id', $courseId)?->lesson;
            $lastAccessedAt = $courseCompleted
                ->sortByDesc('completed_at')
                ->first()?->completed_at ?? $enrollment->enrolled_at;

            return [
                'id' => $courseId,
                'title' => (string) ($enrollment->course?->name ?? 'Khóa học'),
                'category' => (string) ($enrollment->course?->category?->name ?? 'Tiếng Anh'),
                'progress' => $progressPercent,
                'image' => (string) ($enrollment->course?->thumbnail ?? ''),
                'instructor' => 'Shining English',
                'next_lesson' => $currentLesson?->name,
                'last_accessed' => $lastAccessedAt ? Carbon::parse($lastAccessedAt)->diffForHumans() : 'Vừa xong',
            ];
        })->values()->all();
    }

    /**
     * @param Collection<int, LessonProgress> $completedRows
     * @param Collection<int, UserQuizAttempt> $attempts
     * @param Collection<int, Enrollment> $enrollments
     * @return array<int, array<string, mixed>>
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
            ->map(function (array $item, int $index): array {
                unset($item['at']);
                $item['id'] = $index + 1;

                return $item;
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
}

