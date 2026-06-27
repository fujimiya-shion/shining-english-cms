<?php

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use Filament\Actions\CreateAction;

test('list lessons page defines header actions', function (): void {
    $page = new ListLessons;

    $actions = invokeProtectedMethod($page, 'getHeaderActions');

    expect(actionClassList($actions))->toEqual([
        CreateAction::class,
    ]);
});

test('list lessons page binds lesson resource', function (): void {
    $page = new ListLessons;

    $resource = getProtectedPropertyValue($page, 'resource');

    expect($resource)->toBe(LessonResource::class);
});
