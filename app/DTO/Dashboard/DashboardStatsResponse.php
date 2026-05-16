<?php

namespace App\DTO\Dashboard;

class DashboardStatsResponse
{
    public function __construct(
        public int $enrolledCourses,
        public float $hoursThisWeek,
        public int $certificates,
        public int $streakDays,
    ) {}

    public function toArray(): array
    {
        return [
            'enrolled_courses' => $this->enrolledCourses,
            'hours_this_week' => $this->hoursThisWeek,
            'certificates' => $this->certificates,
            'streak_days' => $this->streakDays,
        ];
    }
}
