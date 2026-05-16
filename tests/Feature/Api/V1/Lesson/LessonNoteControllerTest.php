<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
});

function createLessonNoteFixture(): Lesson
{
    $course = Course::factory()->create();

    return Lesson::query()->create([
        'name' => 'Lesson 1 - Note Target',
        'slug' => 'lesson-1-note-target',
        'course_id' => $course->id,
        'video_url' => 'https://example.com/video.mp4',
    ]);
}

it('lesson not found', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('lesson-note')->plainTextToken;
    $notfoundLessonId = 999;

    $response = $this->postJson("/api/v1/lessons/{$notfoundLessonId}/notes", [
        'content' => 'Comment in notfound lesson',
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(404);
    $response->assertJsonFragment([
        'message' => 'Lesson not found',
        'status' => false,
        'status_code' => 404,
    ]);

    expect(LessonNote::query()->count())->toBe(0);
});

it('creates a lesson note for the current user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('lesson-note')->plainTextToken;
    $lesson = createLessonNoteFixture();

    $response = $this->postJson("/api/v1/lessons/{$lesson->id}/notes", [
        'content' => 'Need to review this pronunciation section.',
    ], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment([
        'message' => 'Note created',
    ]);
    $response->assertJsonPath('data.lesson.id', $lesson->id);
    $response->assertJsonPath('data.lesson.name', $lesson->name);

    expect(LessonNote::query()->where('user_id', $user->id)->where('lesson_id', $lesson->id)->count())->toBe(1);
});

it('lists current user notes for a specific lesson', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('lesson-note')->plainTextToken;
    $other = User::factory()->create();
    $lesson = createLessonNoteFixture();

    $note = LessonNote::factory()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $user->id,
        'content' => 'My own lesson note',
    ]);

    LessonNote::factory()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $other->id,
        'content' => 'Another user note',
    ]);

    $response = $this->getJson("/api/v1/lessons/{$lesson->id}/notes", [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.id', $note->id);
    $response->assertJsonPath('data.0.lesson.name', $lesson->name);
    expect($response->json('data'))->toHaveCount(1);
});

it('lists all current user lesson notes with lesson context', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('lesson-note')->plainTextToken;
    $lesson = createLessonNoteFixture();

    $note = LessonNote::factory()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $user->id,
    ]);

    $response = $this->getJson('/api/v1/notes', [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.0.id', $note->id);
    $response->assertJsonPath('data.0.lesson.id', $lesson->id);
    $response->assertJsonPath('data.0.lesson.name', $lesson->name);
    $response->assertJsonPath('data.0.lesson.course.id', $lesson->course_id);
});

it('deletes only the current user note', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('lesson-note')->plainTextToken;
    $lesson = createLessonNoteFixture();

    $note = LessonNote::factory()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $user->id,
    ]);

    $response = $this->deleteJson("/api/v1/notes/{$note->id}", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'message' => 'Note deleted',
    ]);

    expect($note->fresh()->trashed())->toBeTrue();
});

it('returns not found when deleting another user note', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('lesson-note')->plainTextToken;
    $lesson = createLessonNoteFixture();
    $other = User::factory()->create();

    $note = LessonNote::factory()->create([
        'lesson_id' => $lesson->id,
        'user_id' => $other->id,
    ]);

    $response = $this->deleteJson("/api/v1/notes/{$note->id}", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(404);
    $response->assertJsonFragment([
        'message' => 'Note not found',
        'status' => false,
        'status_code' => 404,
    ]);
});
