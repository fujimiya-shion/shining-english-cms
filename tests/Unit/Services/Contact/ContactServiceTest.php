<?php

namespace Tests\Unit\Services\Contact;

use App\Jobs\SendContactReplyMailJob;
use App\Jobs\SendContactSubmittedMailJob;
use App\Models\Contact;
use App\Repositories\Contact\ContactRepository;
use App\Repositories\Contact\IContactRepository;
use App\Services\Contact\ContactService;
use App\Services\Contact\IContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements shared service contract', function (): void {
    $model = new Contact;
    $repository = new ContactRepository($model);
    $service = new ContactService($repository);

    assertServiceContract($service, IContactService::class, $repository);
});

it('submits contact and dispatches company notification job', function (): void {
    Bus::fake();
    $contact = new Contact(['id' => 10]);
    $contact->id = 10;

    $repository = Mockery::mock(IContactRepository::class);
    $repository->shouldReceive('create')
        ->once()
        ->with([
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'message' => 'Xin tu van.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
        ])
        ->andReturn($contact);

    $service = new ContactService($repository);
    $result = $service->submitContact(
        name: 'Nguyen Van A',
        email: 'a@example.com',
        message: 'Xin tu van.',
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    expect($result)->toBe($contact);
    Bus::assertDispatched(SendContactSubmittedMailJob::class);
});

it('replies to contact and dispatches reply mail job', function (): void {
    Bus::fake();
    $contact = new Contact(['id' => 11]);
    $contact->id = 11;

    $repository = Mockery::mock(IContactRepository::class);
    $repository->shouldReceive('update')
        ->once()
        ->with(11, Mockery::on(function (array $data): bool {
            return $data['reply_subject'] === 'Re: Tu van'
                && $data['reply_message'] === 'Cam on ban da lien he.'
                && isset($data['replied_at']);
        }))
        ->andReturn($contact);

    $service = new ContactService($repository);
    $result = $service->replyToContact(
        contactId: 11,
        subject: 'Re: Tu van',
        message: 'Cam on ban da lien he.',
    );

    expect($result)->toBe($contact);
    Bus::assertDispatched(SendContactReplyMailJob::class);
});

