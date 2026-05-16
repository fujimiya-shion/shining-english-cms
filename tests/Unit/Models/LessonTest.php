<?php

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

test('lesson model defaults star rewards to zero', function (): void {
    $lesson = new Lesson;

    expect($lesson->star_reward_video)->toBe(0);
    expect($lesson->star_reward_quiz)->toBe(0);
    expect($lesson->has_quiz)->toBeFalse();
});

it('defines fillable attributes', function (): void {
    $model = new Lesson;

    expect($model->getFillable())->toEqual([
        'name',
        'slug',
        'course_id',
        'lesson_group_id',
        'group_name',
        'group_order',
        'lesson_order',
        'video_url',
        'documents',
        'document_names',
        'description',
        'duration_minutes',
        'star_reward_video',
        'star_reward_quiz',
        'has_quiz',
        'is_preview_free',
    ]);
});

it('defines course relation', function (): void {
    $method = new ReflectionMethod(Lesson::class, 'course');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Lesson)->course())->toBeInstanceOf(BelongsTo::class);
});

it('defines quiz relation', function (): void {
    $method = new ReflectionMethod(Lesson::class, 'quiz');

    expect($method->getReturnType()?->getName())->toBe(HasOne::class);
    expect((new Lesson)->quiz())->toBeInstanceOf(HasOne::class);
});

it('defines comments relation', function (): void {
    $method = new ReflectionMethod(Lesson::class, 'comments');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Lesson)->comments())->toBeInstanceOf(HasMany::class);
});

it('defines notes relation', function (): void {
    $method = new ReflectionMethod(Lesson::class, 'notes');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Lesson)->notes())->toBeInstanceOf(HasMany::class);
});

it('defines casts for lesson attributes', function (): void {
    $model = new Lesson;

    expect($model->getCasts())->toMatchArray([
        'has_quiz' => 'boolean',
        'duration_minutes' => 'integer',
        'documents' => 'array',
        'document_names' => 'array',
    ]);
});

it('appends the original extension when document display name is renamed without one', function (): void {
    $lesson = new Lesson;

    $lesson->document_names = [
        'lesson-documents/01KI/bien-lai-mau.docx' => 'lesson-1',
        'lesson-documents/01KI/grammar-guide.pdf' => 'grammar-guide.pdf',
        'lesson-documents/01KI/practice.txt' => '',
    ];

    expect($lesson->document_names)->toBe([
        'lesson-documents/01KI/bien-lai-mau.docx' => 'lesson-1.docx',
        'lesson-documents/01KI/grammar-guide.pdf' => 'grammar-guide.pdf',
        'lesson-documents/01KI/practice.txt' => 'practice.txt',
    ]);
});

it('keeps list-based document names and infers extension from document path', function (): void {
    $lesson = new Lesson;
    $lesson->documents = [
        'lesson-documents/01KI/bien-lai-mau.docx',
        'lesson-documents/01KI/grammar-guide.pdf',
        'lesson-documents/01KI/practice.txt',
    ];

    $lesson->document_names = [
        'lesson-1',
        'grammar-guide.pdf',
        '',
    ];

    expect($lesson->document_names)->toBe([
        'lesson-1.docx',
        'grammar-guide.pdf',
        'practice.txt',
    ]);
});

it('sets document names to null when assigned null', function (): void {
    $lesson = new Lesson;
    $lesson->document_names = null;

    expect($lesson->document_names)->toBeNull();
});

it('skips invalid list values and empty fallback names in document names list', function (): void {
    $lesson = new Lesson;
    $lesson->documents = ['lesson-documents/01KI/grammar-guide.pdf'];

    $lesson->document_names = [
        'grammar-guide',
        '',
        123,
        'custom-name',
    ];

    expect($lesson->document_names)->toBe([
        'grammar-guide.pdf',
        'custom-name',
    ]);
});

it('ignores non-string keys when document names use map format', function (): void {
    $lesson = new Lesson;
    $lesson->document_names = [
        123 => 'numeric-key-name',
        'lesson-documents/01KI/grammar-guide.pdf' => 'grammar-guide',
    ];

    expect($lesson->document_names)->toBe([
        'lesson-documents/01KI/grammar-guide.pdf' => 'grammar-guide.pdf',
    ]);
});

it('uses soft deletes', function (): void {
    $model = new Lesson;

    expect(method_exists($model, 'trashed'))->toBeTrue();
});
