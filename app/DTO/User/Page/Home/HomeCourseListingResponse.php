<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

use App\Models\Course;

class HomeCourseListingResponse extends AHomePayloadResponse
{
    /**
     * Summary of __construct
     *
     * @param  list<Course>  $courses
     * @param  list<string>  $hexBgColors
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $courses,
        public array $hexBgColors = [],
        public HomeCourseListingRenderBackgroundTypes $renderBackgroundType = HomeCourseListingRenderBackgroundTypes::BACKEND_RESPONSE
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'courses';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'courses' => $this->courses,
            'hex_bg_colors' => $this->hexBgColors,
            'render_background_type' => $this->renderBackgroundType->name,
        ];
    }
}

enum HomeCourseListingRenderBackgroundTypes: string
{
    case BACKEND_RESPONSE = 'backend_response';
    case FRONTEND = 'frontend';
}
