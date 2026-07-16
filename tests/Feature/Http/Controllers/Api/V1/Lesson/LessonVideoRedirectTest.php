<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->course = Course::factory()->create(['slug' => 'test-course']);
    $this->lesson = Lesson::factory()->create([
        'course_id' => $this->course->id,
        'video_url' => 'https://cdn.example.com/videos/lesson.mp4',
        'is_preview_free' => false,
    ]);
    $this->user = User::factory()->create();
    $token = $this->user->createToken('test')->plainTextToken;

    Enrollment::query()->create([
        'user_id' => $this->user->id,
        'course_id' => $this->course->id,
    ]);

    $this->withToken($token);
});

it('redirects to cdn url when video_url starts with http', function (): void {
    $response = $this->getJson("/api/v1/lessons/{$this->lesson->id}/video");

    $response->assertStatus(302);
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toBe('https://cdn.example.com/videos/lesson.mp4');
});
