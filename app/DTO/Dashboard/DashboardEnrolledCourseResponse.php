<?php

namespace App\DTO\Dashboard;

class DashboardEnrolledCourseResponse
{
    public function __construct(
        public array $course,
        public int $progress,
        public string $instructor,
        public ?string $nextLesson,
        public string $lastAccessed,
    ) {}

    public function toArray(): array
    {
        return [
            'course' => $this->course,
            'progress' => $this->progress,
            'instructor' => $this->instructor,
            'next_lesson' => $this->nextLesson,
            'last_accessed' => $this->lastAccessed,
        ];
    }
}
