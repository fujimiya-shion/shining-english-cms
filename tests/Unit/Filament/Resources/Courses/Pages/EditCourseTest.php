<?php

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\Course;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;

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

    $record = new Course;
    $record->thumbnail = null;

    $reflection = new ReflectionProperty($page, 'record');
    $reflection->setAccessible(true);
    $reflection->setValue($page, $record);

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
