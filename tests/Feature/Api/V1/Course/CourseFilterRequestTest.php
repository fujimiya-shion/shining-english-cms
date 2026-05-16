<?php

use App\Services\Course\ICourseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
});

it('accepts valid filter params and returns success response', function (): void {
    $items = new Collection;
    $paginator = new LengthAwarePaginator($items, 0, 15, 1);

    $service = \Mockery::mock(ICourseService::class);
    $service->shouldReceive('filter')
        ->once()
        ->andReturn($paginator);
    app()->instance(ICourseService::class, $service);

    $response = $this->getJson('/api/v1/courses/filter?category_id=2&price_min=100&price_max=300&rating_min=3.5&rating_max=4.5&learned_min=10&learned_max=20&q=basic&page=1&perPage=15');

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'message' => 'OK',
        'status' => true,
        'status_code' => 200,
    ]);
});

it('returns validation errors in json format', function (): void {
    $response = $this->getJson('/api/v1/courses/filter?level_id=invalid');

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Level id must be an integer.',
    ]);
    $response->assertJsonPath('errors.level_id.0', 'Level id must be an integer.');
});
