<?php

namespace App\DTO\Dashboard;

class DashboardOverviewResponse
{
    /**
     * @param array<int, DashboardEnrolledCourseResponse> $enrolledCourses
     * @param array<int, DashboardRecentActivityResponse> $recentActivity
     * @param array<int, array<string, mixed>> $certificates
     * @param array<int, array<string, mixed>> $weeklyPlan
     */
    public function __construct(
        public DashboardStatsResponse $stats,
        public array $enrolledCourses,
        public array $recentActivity,
        public array $certificates,
        public array $weeklyPlan,
    ) {}

    public function toArray(): array
    {
        return [
            'stats' => $this->stats->toArray(),
            'enrolled_courses' => array_map(fn (DashboardEnrolledCourseResponse $item): array => $item->toArray(), $this->enrolledCourses),
            'recent_activity' => array_map(fn (DashboardRecentActivityResponse $item): array => $item->toArray(), $this->recentActivity),
            'certificates' => $this->certificates,
            'weekly_plan' => $this->weeklyPlan,
        ];
    }
}
