<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

class HomeHeroResponse extends AHomePayloadResponse
{
    /**
     * Summary of __construct
     *
     * @param  mixed  $title
     * @param  mixed  $htmlTitle
     * @param  list<HomeHeroActionButton>  $actions
     * @param  list<HomeHeroCTA>  $ctas
     * @param  list<HomeHeroImageTag>  $imageTags
     */
    public function __construct(
        public ?string $title,
        public ?string $htmlTitle,
        public string $description,
        public array $actions,
        public array $ctas,
        public string $image,
        public array $imageTags,
        public HomeHeroImageCTA $imageCTA,
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'hero';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'title' => $this->title,
            'html_title' => $this->htmlTitle,
            'description' => $this->description,
            'actions' => array_map(
                fn (HomeHeroActionButton $action): array => $action->toArray(),
                $this->actions,
            ),
            'ctas' => array_map(
                fn (HomeHeroCTA $cta): array => $cta->toArray(),
                $this->ctas,
            ),
            'image' => $this->image,
            'image_tags' => array_map(
                fn (HomeHeroImageTag $tag): array => $tag->toArray(),
                $this->imageTags,
            ),
            'image_cta' => $this->imageCTA->toArray(),
        ];
    }
}

class HomeHeroActionButton
{
    public function __construct(
        public string $title,
        public string $action,
        public string $type,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'action' => $this->action,
            'type' => $this->type,
        ];
    }
}

class HomeHeroCTA
{
    public function __construct(
        public string $title,
        public string $description,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}

class HomeHeroImageTag
{
    public function __construct(
        public string $text,
        public string $hexBgColor,
        public string $hexTextColor,
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'hex_bg_color' => $this->hexBgColor,
            'hex_text_color' => $this->hexTextColor,
        ];
    }
}

class HomeHeroImageCTA
{
    public function __construct(
        public string $icon,
        public string $title,
        public string $description,
    ) {}

    public function toArray(): array
    {
        return [
            'icon' => $this->icon,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
