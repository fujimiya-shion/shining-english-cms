<?php

namespace App\Http\Controllers\Api\V1\Contact;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Contact\ContactStoreRequest;
use App\Services\Security\Recaptcha\IRecaptchaVerifier;
use App\Services\Security\Recaptcha\RecaptchaVerificationException;
use App\Services\Contact\IContactService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ContactController extends ApiController
{
    public function __construct(
        private readonly IRecaptchaVerifier $recaptchaVerifier,
        private readonly IContactService $contactService,
    ) {}

    public function store(ContactStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $this->recaptchaVerifier->verifyOrFail(
                token: $data['recaptcha_token'],
                expectedAction: (string) config('recaptcha.contact_action'),
                ipAddress: $data['ip_address'] ?? $request->ip(),
            );
        } catch (RecaptchaVerificationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable) {
            return $this->error('Unable to submit contact request.', 422);
        }

        $this->contactService->submitContact(
            name: $data['name'],
            email: $data['email'],
            message: $data['message'],
            ipAddress: $data['ip_address'] ?? $request->ip(),
            userAgent: $data['user_agent'] ?? $request->userAgent(),
        );

        return $this->created(data: null, message: 'Contact submitted successfully.');
    }
}
