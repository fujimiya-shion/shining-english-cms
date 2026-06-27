<?php

declare(strict_types=1);

namespace App\Integrations\Payments\DTO;

use App\DTO\Transaction\Checkout\CheckoutPaymentActionResponse;

class PaymentInitializationResult
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public ?string $actionType = null,
        public ?string $actionUrl = null,
        public ?array $metadata = null,
    ) {}

    public static function none(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function redirect(string $url, ?array $metadata = null): self
    {
        return new self(
            actionType: 'redirect',
            actionUrl: $url,
            metadata: $metadata,
        );
    }

    public function toCheckoutAction(): ?CheckoutPaymentActionResponse
    {
        if ($this->actionType === null || $this->actionUrl === null) {
            return null;
        }

        return new CheckoutPaymentActionResponse(
            type: $this->actionType,
            url: $this->actionUrl,
            metadata: $this->metadata,
        );
    }
}
