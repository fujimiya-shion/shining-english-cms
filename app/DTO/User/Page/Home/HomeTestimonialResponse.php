<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

use App\Models\CourseReview;

class HomeTestimonialResponse extends AHomePayloadResponse
{
    /**
     * @param list<CourseReview> $reviews
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $reviews,
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'testimonials';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'items' => $this->reviews,
        ];
    }
}