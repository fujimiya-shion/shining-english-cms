<?php

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\Course;

it('includes view on website when course has slug', function (): void {
    $page = new EditCourse;
    $ref = new ReflectionProperty($page, 'record');
    $ref->setValue($page, new Course(['slug' => 'my-course']));

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->toContain('viewOnWebsite');
});

it('excludes view on website when course has no slug', function (): void {
    $page = new EditCourse;
    $ref = new ReflectionProperty($page, 'record');
    $ref->setValue($page, new Course(['slug' => null]));

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->not->toContain('viewOnWebsite');
});
