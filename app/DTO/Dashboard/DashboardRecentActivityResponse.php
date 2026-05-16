<?php

namespace App\DTO\Dashboard;

class DashboardRecentActivityResponse
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $course,
        public string $time,
        public ?int $score,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'course' => $this->course,
            'time' => $this->time,
            'score' => $this->score,
        ];
    }
}
