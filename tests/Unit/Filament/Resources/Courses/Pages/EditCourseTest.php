<?php

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\Course;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Validation\ValidationException;

function setCoursePageRecord(EditCourse $page, ?string $thumbnail): void
{
    $record = new Course;
    $record->setRawAttributes(['thumbnail' => $thumbnail], true);

    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setValue($page, $record);
}

test('edit course page defines header actions', function (): void {
    $page = new EditCourse;

    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect(actionClassList($actions))->toEqual([
        DeleteAction::class,
        ForceDeleteAction::class,
        RestoreAction::class,
    ]);
});

test('edit course page defines a saved notification title', function (): void {
    $page = new EditCourse;

    expect(invokeProtectedMethod($page, 'getSavedNotificationTitle'))->toBe('Course updated successfully.');
});

test('edit course page allows saving without a thumbnail when the record has none', function (): void {
    $page = new EditCourse;
    setCoursePageRecord($page, null);

    $data = invokeProtectedMethod($page, 'mutateFormDataBeforeSave', [[
        'thumbnail_source' => 'upload',
        'thumbnail_file' => '',
        'thumbnail_url' => '',
        'name' => 'Updated name',
    ]]);

    expect($data['thumbnail'])->toBeNull();
    expect($data)->toHaveKey('name');
    expect($data)->not->toHaveKey('thumbnail_source');
    expect($data)->not->toHaveKey('thumbnail_file');
    expect($data)->not->toHaveKey('thumbnail_url');
});

test('edit course normalizes thumbnail before fill', function (string $thumbnail, string $source, string $url, string $file): void {
    config(['app.url' => 'https://app.test']);
    $page = new EditCourse;

    $data = invokeProtectedMethod($page, 'mutateFormDataBeforeFill', [[
        'thumbnail' => $thumbnail,
    ]]);

    expect($data['thumbnail_source'])->toBe($source);
    expect($data['thumbnail_url'])->toBe($url);
    expect($data['thumbnail_file'])->toBe($file);
})->with([
    'external url' => ['https://cdn.test/thumb.jpg', 'url', 'https://cdn.test/thumb.jpg', ''],
    'app storage url' => ['https://app.test/storage/courses/thumb.jpg', 'upload', '', 'courses/thumb.jpg'],
    'path storage url' => ['https://cdn.test/storage/courses/thumb.jpg', 'url', 'https://cdn.test/storage/courses/thumb.jpg', 'courses/thumb.jpg'],
    'storage path' => ['/storage/courses/thumb.jpg', 'upload', '', 'courses/thumb.jpg'],
    'public path' => ['public/courses/thumb.jpg', 'upload', '', 'courses/thumb.jpg'],
    'relative path' => ['courses/thumb.jpg', 'upload', '', 'courses/thumb.jpg'],
    'empty thumbnail' => ['', 'upload', '', ''],
]);

test('edit course saves external thumbnail url', function (): void {
    $page = new EditCourse;
    setCoursePageRecord($page, null);

    $data = invokeProtectedMethod($page, 'mutateFormDataBeforeSave', [[
        'thumbnail_source' => 'url',
        'thumbnail_file' => '',
        'thumbnail_url' => 'https://example.com/thumb.jpg',
    ]]);

    expect($data['thumbnail'])->toBe('https://example.com/thumb.jpg');
});

test('edit course saves uploaded thumbnail or keeps current uploaded thumbnail', function (?string $current, string $uploaded, string $expected): void {
    config(['app.url' => 'https://app.test']);
    $page = new EditCourse;
    setCoursePageRecord($page, $current);

    $data = invokeProtectedMethod($page, 'mutateFormDataBeforeSave', [[
        'thumbnail_source' => 'upload',
        'thumbnail_file' => $uploaded,
        'thumbnail_url' => '',
    ]]);

    expect($data['thumbnail'])->toBe($expected);
})->with([
    'new upload' => [null, 'courses/new.jpg', 'courses/new.jpg'],
    'current upload' => ['public/courses/current.jpg', '', 'courses/current.jpg'],
]);

test('edit course validates thumbnail before save', function (?string $current, array $payload, string $field): void {
    $page = new EditCourse;
    setCoursePageRecord($page, $current);

    try {
        invokeProtectedMethod($page, 'mutateFormDataBeforeSave', [$payload]);
        $this->fail('Expected validation exception.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }
})->with([
    'missing url' => [null, [
        'thumbnail_source' => 'url',
        'thumbnail_url' => '',
    ], 'thumbnail_url'],
    'invalid url' => [null, [
        'thumbnail_source' => 'url',
        'thumbnail_url' => 'not-a-url',
    ], 'thumbnail_url'],
    'missing upload while current is external url' => ['https://cdn.test/current.jpg', [
        'thumbnail_source' => 'upload',
        'thumbnail_file' => '',
    ], 'thumbnail_file'],
]);
