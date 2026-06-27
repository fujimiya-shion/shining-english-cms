<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

class HomeStatisticResponse extends AHomePayloadResponse
{
    /**
     * @param  list<HomeStatisticItem>  $items
     */
    public function __construct(
        public array $items,
    ) {}

    #[\Override]
    public function type(): string
    {
        return 'statistics';
    }

    #[\Override]
    public function data(): array
    {
        return [
            'items' => array_map(
                fn (HomeStatisticItem $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}

class HomeStatisticItem
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
        ];
    }
}
