<?php

use App\Enums\NotificationType;
use Tests\TestCase;

uses(TestCase::class);

it('has 4 cases', function (): void {
    $cases = NotificationType::cases();

    expect($cases)->toHaveCount(4);
});

it('PaymentSuccess has correct value', function (): void {
    expect(NotificationType::PaymentSuccess->value)->toBe('payment_success');
});

it('StarWallet has correct value', function (): void {
    expect(NotificationType::StarWallet->value)->toBe('star_wallet');
});

it('Enrollment has correct value', function (): void {
    expect(NotificationType::Enrollment->value)->toBe('enrollment');
});

it('LessonCompleted has correct value', function (): void {
    expect(NotificationType::LessonCompleted->value)->toBe('lesson_completed');
});
