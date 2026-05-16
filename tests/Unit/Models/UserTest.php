<?php

use App\Enums\AuthenticatedBy;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('defines fillable attributes', function (): void {
    $user = new User;

    expect($user->getFillable())->toEqual([
        'name',
        'nickname',
        'email',
        'phone',
        'birthday',
        'avatar',
        'city_id',
        'password',
        'authenticated_by',
        'email_verified_at',
    ]);
});

it('hides sensitive attributes', function (): void {
    $user = new User;

    expect($user->getHidden())->toEqual([
        'password',
        'remember_token',
    ]);
});

it('casts attributes correctly', function (): void {
    $user = new User;

    expect($user->getCasts())->toMatchArray([
        'email_verified_at' => 'datetime',
        'birthday' => 'date',
        'password' => 'hashed',
        'authenticated_by' => AuthenticatedBy::class,
    ]);
});

it('defines city relation', function (): void {
    $user = new User;

    expect($user->city())->toBeInstanceOf(BelongsTo::class);
});

it('defines quiz attempts relation', function (): void {
    $user = new User;

    expect($user->quizAttempts())->toBeInstanceOf(HasMany::class);
});

it('defines devices relation', function (): void {
    $user = new User;

    expect($user->devices())->toBeInstanceOf(HasMany::class);
});

it('defines enrollments relation', function (): void {
    $user = new User;

    expect($user->enrollments())->toBeInstanceOf(HasMany::class);
});

it('defines blog unlocks relation', function (): void {
    $user = new User;

    expect($user->blogUnlocks())->toBeInstanceOf(HasMany::class);
});

it('defines course reviews relation', function (): void {
    $user = new User;

    expect($user->courseReviews())->toBeInstanceOf(HasMany::class);
});

it('defines lesson comments relation', function (): void {
    $user = new User;

    expect($user->lessonComments())->toBeInstanceOf(HasMany::class);
});

it('defines lesson notes relation', function (): void {
    $user = new User;

    expect($user->lessonNotes())->toBeInstanceOf(HasMany::class);
});

it('hashes password when setting it', function (): void {
    $user = new User;
    $user->password = 'secret';

    expect($user->password)->not->toBe('secret');
    expect(Hash::check('secret', $user->password))->toBeTrue();
});

it('does not rehash an already hashed password', function (): void {
    $user = new User;
    $hashed = Hash::make('secret');

    $user->password = $hashed;

    expect($user->password)->toBe($hashed);
    expect(Hash::check('secret', $user->password))->toBeTrue();
});

it('sends custom password reset notification', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $user->sendPasswordResetNotification('reset-token');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('sends email verification notification', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class);
});
