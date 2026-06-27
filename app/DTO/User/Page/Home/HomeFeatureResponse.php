<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

class HomeFeatureResponse extends AHomePayloadResponse
{
    /**
     * Summary of __construct
     *
     * @param  list<HomeFeatureCard>  $items
     */
    public function __construct(
        public string $eyebrow,
        public string $title,
        public string $description,
        public array $items,
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'feature';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'eyebrow' => $this->eyebrow,
            'title' => $this->title,
            'description' => $this->description,
            'items' => array_map(
                fn (HomeFeatureCard $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}

class HomeFeatureCard
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $iconPath = null,
        public ?string $iconType = null,
        public ?string $badgeText = null,
        public ?string $tagText = null,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'icon_path' => $this->iconPath,
            'icon_type' => $this->iconType,
            'badge_text' => $this->badgeText,
            'tag_text' => $this->tagText,
        ];
    }
}
