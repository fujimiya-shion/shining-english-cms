<?php

namespace App\DTO\Dashboard;

class DashboardCourseResponse
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $slug,
        public string $thumbnail,
        public int $price,
        public int $learned,
        public int $lessonsCount,
        public int $commentsCount,
        public int $totalDurationMinutes,
        public array $category,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail' => $this->thumbnail,
            'price' => $this->price,
            'learned' => $this->learned,
            'lessons_count' => $this->lessonsCount,
            'comments_count' => $this->commentsCount,
            'total_duration_minutes' => $this->totalDurationMinutes,
            'category' => $this->category,
        ];
    }
}
