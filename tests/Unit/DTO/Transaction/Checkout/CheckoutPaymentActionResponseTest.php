<?php

use App\DTO\Transaction\Checkout\CheckoutPaymentActionResponse;
use Tests\TestCase;

uses(TestCase::class);

it('constructs with all fields', function (): void {
    $dto = new CheckoutPaymentActionResponse(
        type: 'payos',
        url: 'https://pay.example.com/checkout',
        metadata: ['order_id' => 123],
    );

    expect($dto->type)->toBe('payos');
    expect($dto->url)->toBe('https://pay.example.com/checkout');
    expect($dto->metadata)->toBe(['order_id' => 123]);
});

it('serializes to array', function (): void {
    $dto = new CheckoutPaymentActionResponse(type: 'cod', url: 'https://example.com', metadata: null);

    expect($dto->toArray())->toMatchArray([
        'type' => 'cod',
        'url' => 'https://example.com',
    ]);
});

it('excludes null metadata from array', function (): void {
    $dto = new CheckoutPaymentActionResponse(type: 'cod', url: 'https://example.com');

    $array = $dto->toArray();
    expect($array)->not->toHaveKey('metadata');
});

it('keeps metadata when not null', function (): void {
    $dto = new CheckoutPaymentActionResponse(type: 'payos', url: 'https://pay.example.com', metadata: ['key' => 'value']);

    expect($dto->toArray())->toHaveKey('metadata');
    expect($dto->toArray()['metadata'])->toBe(['key' => 'value']);
});
