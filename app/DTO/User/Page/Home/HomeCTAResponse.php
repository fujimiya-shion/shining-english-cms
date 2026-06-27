<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

class HomeCTAResponse extends AHomePayloadResponse
{
    /**
     * @param  list<HomeCtaActionButton>  $actionButtons
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $actionButtons,
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'cta';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'action_buttons' => array_map(
                fn (HomeCTAActionButton $button): array => $button->toArray(),
                $this->actionButtons,
            ),
        ];
    }
}

class HomeCTAActionButton
{
    public function __construct(
        public string $title,
        public string $action,
        public HomeCTAActionButtonType $type,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'action' => $this->action,
            'type' => $this->type->name,
        ];
    }
}

enum HomeCTAActionButtonType: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
}
