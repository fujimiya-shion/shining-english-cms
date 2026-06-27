<?php

use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use App\Repositories\LessonComment\ILessonCommentRepository;
use App\Services\LessonComment\ILessonCommentService;
use App\Services\LessonComment\LessonCommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('implements service contract', function (): void {
    $repository = Mockery::mock(ILessonCommentRepository::class);
    $service = new LessonCommentService($repository);

    assertServiceContract($service, ILessonCommentService::class, $repository);
});

it('creates comment for user with trimmed content', function (): void {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    $comment = new LessonComment;
    $comment->id = 1;
    $comment->setRelation('user', $user);

    $repository = Mockery::mock(ILessonCommentRepository::class);
    $repository->shouldReceive('create')
        ->once()
        ->with([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'Nice lesson',
        ])
        ->andReturn($comment);
    $repository->shouldReceive('setEagerLoads')->andReturnSelf();

    $service = new LessonCommentService($repository);
    $result = $service->createForUser($lesson->id, $user->id, '  Nice lesson  ');

    expect($result)->toBe($comment);
});
