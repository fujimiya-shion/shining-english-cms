<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

class HomeProcessResponse extends AHomePayloadResponse
{
    /**
     * @param  list<HomeProcessStep>  $steps
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $steps,
        public array $tags = [],
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'process';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'steps' => array_map(
                fn (HomeProcessStep $step): array => $step->toArray(),
                $this->steps,
            ),
            'tags' => $this->tags,
        ];
    }
}

class HomeProcessStep
{
    public function __construct(
        public string $label,
        public string $title,
        public string $description,
        public ?string $iconPath = null,
        public ?string $iconType = null,
    ) {}

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'title' => $this->title,
            'description' => $this->description,
            'icon_path' => $this->iconPath,
            'icon_type' => $this->iconType,
        ];
    }
}
