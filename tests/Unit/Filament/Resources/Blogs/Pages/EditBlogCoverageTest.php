<?php

use App\Filament\Resources\Blogs\Pages\EditBlog;
use App\Models\Blog;

it('includes view on website when blog has slug', function (): void {
    $page = new EditBlog;
    $ref = new ReflectionProperty($page, 'record');
    $ref->setValue($page, new Blog(['slug' => 'hello-world']));

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->toContain('viewOnWebsite');
});

it('excludes view on website when blog has no slug', function (): void {
    $page = new EditBlog;
    $ref = new ReflectionProperty($page, 'record');
    $ref->setValue($page, new Blog(['slug' => null]));

    $actions = invokeProtectedMethod($page, 'getHeaderActions');
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->not->toContain('viewOnWebsite');
});
