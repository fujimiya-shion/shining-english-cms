<?php

declare(strict_types=1);

return [
    'goals' => [
        'daily_study_minutes' => (int) env('DASHBOARD_DAILY_STUDY_GOAL_MINUTES', 5),
        'daily_lessons' => (int) env('DASHBOARD_DAILY_LESSON_GOAL', 2),
        'focus_courses' => (int) env('DASHBOARD_FOCUS_COURSES_GOAL', 2),
        'streak_days' => (int) env('DASHBOARD_STREAK_DAYS_GOAL', 7),
    ],
];
