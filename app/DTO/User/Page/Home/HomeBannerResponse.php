<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

class HomeBannerResponse extends AHomePayloadResponse
{
    /**
     * @param  list<HomeBannerActionButton>  $bannerActionButtons
     * @param  list<HomeBannerHighlight>  $bannerHighlights
     */
    public function __construct(
        public string $bannerLogo,
        public string $bannerEyebrow,
        public string $bannerTitle,
        public string $bannerDescription,
        public array $bannerActionButtons,
        public array $bannerHighlights,
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'banner';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'banner_logo' => $this->bannerLogo,
            'banner_eyebrow' => $this->bannerEyebrow,
            'banner_title' => $this->bannerTitle,
            'banner_description' => $this->bannerDescription,
            'banner_action_buttons' => array_map(
                fn (HomeBannerActionButton $button): array => $button->toArray(),
                $this->bannerActionButtons,
            ),
            'banner_highlights' => array_map(
                fn (HomeBannerHighlight $highlight): array => $highlight->toArray(),
                $this->bannerHighlights,
            ),
        ];
    }

    public function toJson(
        int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }
}

class HomeBannerActionButton
{
    public function __construct(
        public string $title,
        public string $action,
        public HomeBannerActionButtonTypes $type,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'action' => $this->action,
            'type' => $this->type->name,
        ];
    }

    public function toJson(
        int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }
}

enum HomeBannerActionButtonTypes: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
}

class HomeBannerHighlight
{
    public function __construct(
        public string $text,
        public ?string $iconPath,
        public ?string $iconType,
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'icon_path' => $this->iconPath,
            'icon_type' => $this->iconType,
        ];
    }

    public function toJson(
        int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }
}
