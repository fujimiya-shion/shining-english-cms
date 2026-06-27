<?php

use App\ValueObjects\CheckoutCustomerData;
use Tests\TestCase;

uses(TestCase::class);

it('creates instance with all null values by default', function (): void {
    $data = new CheckoutCustomerData;

    expect($data->fullName)->toBeNull();
    expect($data->email)->toBeNull();
    expect($data->phone)->toBeNull();
});

it('creates from array with buyer_ prefixed fields', function (): void {
    $data = CheckoutCustomerData::fromArray([
        'buyer_name' => 'John Doe',
        'buyer_email' => 'john@example.com',
        'buyer_phone' => '0123456789',
    ]);

    expect($data->fullName)->toBe('John Doe');
    expect($data->email)->toBe('john@example.com');
    expect($data->phone)->toBe('0123456789');
});

it('creates from array with unprefixed fields as fallback', function (): void {
    $data = CheckoutCustomerData::fromArray([
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '0987654321',
    ]);

    expect($data->fullName)->toBe('Jane Doe');
    expect($data->email)->toBe('jane@example.com');
    expect($data->phone)->toBe('0987654321');
});

it('prefers buyer_ prefixed fields over unprefixed fields', function (): void {
    $data = CheckoutCustomerData::fromArray([
        'buyer_name' => 'Buyer Name',
        'full_name' => 'Full Name',
        'buyer_email' => 'buyer@example.com',
        'email' => 'fallback@example.com',
    ]);

    expect($data->fullName)->toBe('Buyer Name');
    expect($data->email)->toBe('buyer@example.com');
});

it('normalizes empty strings to null', function (): void {
    $data = CheckoutCustomerData::fromArray([
        'buyer_name' => '  ',
        'buyer_email' => '',
        'phone' => '  123  ',
    ]);

    expect($data->fullName)->toBeNull();
    expect($data->email)->toBeNull();
    expect($data->phone)->toBe('123');
});

it('normalizes non-string values to null', function (): void {
    $data = CheckoutCustomerData::fromArray([
        'buyer_name' => 123,
        'buyer_email' => null,
        'phone' => ['not a string'],
    ]);

    expect($data->fullName)->toBeNull();
    expect($data->email)->toBeNull();
    expect($data->phone)->toBeNull();
});

it('trims whitespace from values', function (): void {
    $data = CheckoutCustomerData::fromArray([
        'buyer_name' => '  Alice  ',
        'buyer_email' => '  alice@test.com  ',
    ]);

    expect($data->fullName)->toBe('Alice');
    expect($data->email)->toBe('alice@test.com');
});
