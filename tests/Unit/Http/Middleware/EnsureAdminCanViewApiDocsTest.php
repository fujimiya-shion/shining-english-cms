<?php

use App\Http\Middleware\EnsureAdminCanViewApiDocs;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

it('allows api docs in local or testing environments', function (): void {
    app()->detectEnvironment(fn () => 'local');

    $request = Request::create('/docs/api', 'GET');
    $response = new Response('ok');

    $result = (new EnsureAdminCanViewApiDocs)->handle(
        $request,
        fn (Request $request): Response => $response,
    );

    expect($result)->toBe($response);
});
