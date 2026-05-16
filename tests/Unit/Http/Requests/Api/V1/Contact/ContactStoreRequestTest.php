<?php

use App\Http\Requests\Api\V1\Contact\ContactStoreRequest;

it('defines expected contact store request rules and messages', function (): void {
    $request = new ContactStoreRequest;

    expect($request->authorize())->toBeTrue();
    expect($request->rules())->toBe([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email'],
        'message' => ['required', 'string', 'max:5000'],
        'recaptcha_token' => ['required', 'string'],
        'ip_address' => ['nullable', 'ip'],
        'user_agent' => ['nullable', 'string'],
    ]);
    expect($request->messages())->toBe([
        'name.required' => 'Name is required.',
        'email.required' => 'Email is required.',
        'email.email' => 'Email must be valid.',
        'message.required' => 'Message is required.',
        'recaptcha_token.required' => 'reCAPTCHA token is required.',
        'ip_address.ip' => 'IP address must be valid.',
    ]);
});
