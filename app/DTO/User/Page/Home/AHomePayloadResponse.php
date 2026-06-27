<?php

declare(strict_types=1);

namespace App\DTO\User\Page\Home;

abstract class AHomePayloadResponse
{
    abstract public function type(): string;

    abstract public function data(): array;

    final public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'data' => $this->data(),
        ];
    }
}
