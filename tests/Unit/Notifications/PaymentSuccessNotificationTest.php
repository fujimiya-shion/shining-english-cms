<?php

use App\Enums\NotificationType;
use App\Notifications\PaymentSuccessNotification;
use Tests\TestCase;

uses(TestCase::class);

it('uses database channel', function (): void {
    $notification = new PaymentSuccessNotification(
        orderId: 1,
        orderCode: 'ORD-001',
        totalAmount: 200000,
        courseNames: 'Khóa học A, Khóa học B',
    );

    expect($notification->via(new stdClass))->toBe(['database']);
});

it('sends payment success data', function (): void {
    $notification = new PaymentSuccessNotification(
        orderId: 42,
        orderCode: 'ORD-042',
        totalAmount: 150000,
        courseNames: 'IELTS Cơ bản',
    );

    $data = $notification->toDatabase(new stdClass);

    expect($data['type'])->toBe(NotificationType::PaymentSuccess->value);
    expect($data['order_id'])->toBe(42);
    expect($data['order_code'])->toBe('ORD-042');
    expect($data['total_amount'])->toBe(150000);
    expect($data['course_names'])->toBe('IELTS Cơ bản');
    expect($data['title'])->toBe('Thanh toán thành công');
    expect($data['body'])->toContain('42');
    expect($data['body'])->toContain('IELTS Cơ bản');
});

it('is queued', function (): void {
    $reflection = new ReflectionClass(PaymentSuccessNotification::class);
    expect($reflection->implementsInterface(Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue();
    expect(in_array('Illuminate\Bus\Queueable', array_keys($reflection->getTraits())))->toBeTrue();
});
