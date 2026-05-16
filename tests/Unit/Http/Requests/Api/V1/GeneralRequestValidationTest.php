<?php

use App\Http\Requests\Api\V1\Cart\CartStoreRequest;
use App\Http\Requests\Api\V1\Course\CourseCurrentLessonRequest;
use App\Http\Requests\Api\V1\Developer\DeveloperLoginRequest;
use App\Http\Requests\Api\V1\QuizAttempt\QuizAttemptStoreRequest;
use App\Http\Requests\Api\V1\Transaction\OrderStoreRequest;

it('defines expected cart store request rules', function (): void {
    $request = new CartStoreRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'course_id' => ['required', 'integer'],
        'quantity' => ['nullable', 'integer', 'min:1'],
    ]);
});

it('defines expected developer login request rules', function (): void {
    $request = new DeveloperLoginRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'email' => 'required|email',
        'password' => 'required',
    ]);
});

it('defines expected quiz attempt store request rules and messages', function (): void {
    $request = new QuizAttemptStoreRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'score_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        'passed' => ['required', 'boolean'],
        'submitted_at' => ['nullable', 'date'],
    ]);
    expect($request->messages())->toBe([
        'score_percent.required' => 'Score is required.',
        'score_percent.numeric' => 'Score must be a number.',
        'score_percent.min' => 'Score must be at least 0.',
        'score_percent.max' => 'Score must be at most 100.',
        'passed.required' => 'Passed is required.',
        'passed.boolean' => 'Passed must be true or false.',
        'submitted_at.date' => 'Submitted at must be a valid datetime.',
    ]);
});

it('defines expected order store request rules and messages', function (): void {
    $request = new OrderStoreRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'type' => ['required', 'string', 'in:cart,buy_now'],
        'payment_method' => ['nullable', 'string', 'in:cod,payos'],
        'course_id' => ['required_if:type,buy_now', 'integer'],
        'quantity' => ['nullable', 'integer', 'min:1'],
    ]);
    expect($request->messages())->toBe([
        'type.required' => 'Order type is required.',
        'type.in' => 'Order type must be cart or buy_now.',
        'payment_method.in' => 'Payment method must be cod or payos.',
        'course_id.required_if' => 'Course id is required for buy now.',
        'course_id.integer' => 'Course id must be an integer.',
        'quantity.integer' => 'Quantity must be an integer.',
        'quantity.min' => 'Quantity must be at least 1.',
    ]);
});

it('defines expected course current lesson request rules and messages', function (): void {
    $request = new CourseCurrentLessonRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'lesson_id' => ['required', 'integer', 'min:1'],
    ]);
    expect($request->messages())->toBe([
        'lesson_id.required' => 'Lesson is required.',
        'lesson_id.integer' => 'Lesson must be an integer.',
        'lesson_id.min' => 'Lesson must be at least 1.',
    ]);
});
