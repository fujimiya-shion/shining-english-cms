<?php

use App\Filament\Resources\Blogs\Pages\EditBlog;
use App\Models\Blog;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Validation\ValidationException;

function setBlogPageRecord(EditBlog $page, string $thumbnail): void
{
    $record = new Blog;
    $record->setRawAttributes(['thumbnail' => $thumbnail], true);

    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setValue($page, $record);
}

test('edit blog page defines header actions', function (): void {
    $page = new EditBlog;

    expect(actionClassList(invokeProtectedMethod($page, 'getHeaderActions')))->toEqual([
        DeleteAction::class,
        ForceDeleteAction::class,
        RestoreAction::class,
    ]);
});

test('edit blog normalizes thumbnail before fill', function (string $thumbnail, string $source, string $url, string $file): void {
    config(['app.url' => 'https://app.test']);
    $page = new EditBlog;

    $data = invokeProtectedMethod($page, 'mutateFormDataBeforeFill', [[
        'thumbnail' => $thumbnail,
    ]]);

    expect($data['thumbnail_source'])->toBe($source);
    expect($data['thumbnail_url'])->toBe($url);
    expect($data['thumbnail_file'])->toBe($file);
})->with([
    'external url' => ['https://cdn.test/thumb.jpg', 'url', 'https://cdn.test/thumb.jpg', ''],
    'app storage url' => ['https://app.test/storage/blogs/thumb.jpg', 'upload', '', 'blogs/thumb.jpg'],
    'path storage url' => ['https://cdn.test/storage/blogs/thumb.jpg', 'url', 'https://cdn.test/storage/blogs/thumb.jpg', 'blogs/thumb.jpg'],
    'storage path' => ['/storage/blogs/thumb.jpg', 'upload', '', 'blogs/thumb.jpg'],
    'public path' => ['public/blogs/thumb.jpg', 'upload', '', 'blogs/thumb.jpg'],
    'relative path' => ['blogs/thumb.jpg', 'upload', '', 'blogs/thumb.jpg'],
    'empty thumbnail' => ['', 'upload', '', ''],
]);

test('edit blog saves external thumbnail url', function (): void {
    $page = new EditBlog;
    setBlogPageRecord($page, '');

    $data = invokeProtectedMethod($page, 'mutateFormDataBeforeSave', [[
        'thumbnail_source' => 'url',
        'thumbnail_file' => '',
        'thumbnail_url' => 'https://example.com/thumb.jpg',
    ]]);

    expect($data['thumbnail'])->toBe('https://example.com/thumb.jpg');
});

test('edit blog saves uploaded thumbnail or keeps current uploaded thumbnail', function (string $current, string $uploaded, string $expected): void {
    config(['app.url' => 'https://app.test']);
    $page = new EditBlog;
    setBlogPageRecord($page, $current);

    $data = invokeProtectedMethod($page, 'mutateFormDataBeforeSave', [[
        'thumbnail_source' => 'upload',
        'thumbnail_file' => $uploaded,
        'thumbnail_url' => '',
    ]]);

    expect($data['thumbnail'])->toBe($expected);
})->with([
    'new upload' => ['', 'blogs/new.jpg', 'blogs/new.jpg'],
    'current upload' => ['public/blogs/current.jpg', '', 'blogs/current.jpg'],
]);

test('edit blog validates thumbnail before save', function (string $current, array $payload, string $field): void {
    $page = new EditBlog;
    setBlogPageRecord($page, $current);

    try {
        invokeProtectedMethod($page, 'mutateFormDataBeforeSave', [$payload]);
        $this->fail('Expected validation exception.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }
})->with([
    'missing url' => ['', [
        'thumbnail_source' => 'url',
        'thumbnail_url' => '',
    ], 'thumbnail_url'],
    'invalid url' => ['', [
        'thumbnail_source' => 'url',
        'thumbnail_url' => 'not-a-url',
    ], 'thumbnail_url'],
    'missing upload' => ['', [
        'thumbnail_source' => 'upload',
        'thumbnail_file' => '',
    ], 'thumbnail_file'],
]);
