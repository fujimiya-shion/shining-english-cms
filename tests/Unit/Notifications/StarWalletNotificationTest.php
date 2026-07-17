<?php

use App\Enums\NotificationType;
use App\Notifications\StarWalletNotification;
use Tests\TestCase;

uses(TestCase::class);

it('uses database channel', function (): void {
    $notification = new StarWalletNotification(
        amount: 5,
        balanceAfter: 20,
        transactionType: 'increase',
        description: 'Check-in hàng ngày',
    );

    expect($notification->via(new stdClass))->toBe(['database']);
});

it('sends star wallet data for positive amount', function (): void {
    $notification = new StarWalletNotification(
        amount: 10,
        balanceAfter: 50,
        transactionType: 'daily_checkin',
        description: 'Nhận 10 sao từ check-in',
    );

    $data = $notification->toDatabase(new stdClass);

    expect($data['type'])->toBe(NotificationType::StarWallet->value);
    expect($data['amount'])->toBe(10);
    expect($data['balance_after'])->toBe(50);
    expect($data['transaction_type'])->toBe('daily_checkin');
    expect($data['description'])->toBe('Nhận 10 sao từ check-in');
    expect($data['title'])->toBe('Nhận sao');
});

it('sends star wallet data for negative amount', function (): void {
    $notification = new StarWalletNotification(
        amount: -5,
        balanceAfter: 15,
        transactionType: 'star_payment',
        description: 'Thanh toán khóa học bằng sao',
    );

    $data = $notification->toDatabase(new stdClass);

    expect($data['type'])->toBe(NotificationType::StarWallet->value);
    expect($data['amount'])->toBe(-5);
    expect($data['balance_after'])->toBe(15);
    expect($data['transaction_type'])->toBe('star_payment');
    expect($data['title'])->toBe('Sao đã được sử dụng');
});

it('is queued', function (): void {
    $reflection = new ReflectionClass(StarWalletNotification::class);
    expect($reflection->implementsInterface(Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue();
});
