<?php

use App\Models\Developer;
use Illuminate\Support\Facades\Hash;

test('developer exposes expected fillable attributes', function (): void {
    $developer = new Developer;

    expect($developer->getFillable())->toEqual([
        'id',
        'email',
        'password',
    ]);
});

test('developer hides sensitive attributes', function (): void {
    $developer = new Developer;

    expect($developer->getHidden())->toEqual([
        'password',
        'remember_token',
    ]);
});

test('developer casts password as hashed', function (): void {
    $developer = new Developer;

    expect($developer->getCasts())->toHaveKey('password', 'hashed');
});

test('developer hashes password when setting it', function (): void {
    $developer = new Developer;
    $developer->password = 'secret123';

    expect(Hash::check('secret123', $developer->password))->toBeTrue();
    expect($developer->password)->not->toBe('secret123');
});

test('developer does not rehash an already hashed password', function (): void {
    $developer = new Developer;
    $hashedPassword = Hash::make('secret123');

    $developer->password = $hashedPassword;

    expect($developer->password)->toBe($hashedPassword);
});
