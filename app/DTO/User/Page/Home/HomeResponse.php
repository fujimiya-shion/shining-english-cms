<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

class HomeResponse
{
    /**
     * Summary of __construct
     *
     * @param  list<AHomePayloadResponse>  $payloads
     */
    public function __construct(
        public array $payloads
    ) {}

    public function toArray(): array
    {
        return [
            'payloads' => array_map(fn (AHomePayloadResponse $payload): array => $payload->toArray(), $this->payloads),
        ];
    }
}
