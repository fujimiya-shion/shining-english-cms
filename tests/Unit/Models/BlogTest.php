<?php

use App\Models\Blog;
use App\Models\BlogTag;
use App\Models\BlogUnlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defines fillable attributes', function (): void {
    $model = new Blog;

    expect($model->getFillable())->toEqual([
        'title',
        'description',
        'short_description',
        'thumbnail',
        'content',
        'slug',
        'status',
        'required_star',
        'tag_id',
    ]);
});

it('casts attributes correctly', function (): void {
    $model = new Blog;

    expect($model->getCasts())->toMatchArray([
        'status' => 'boolean',
        'required_star' => 'integer',
    ]);
});

it('defines tag relation', function (): void {
    $method = new ReflectionMethod(Blog::class, 'tag');

    expect($method->getReturnType()?->getName())->toBe(BelongsTo::class);
    expect((new Blog)->tag())->toBeInstanceOf(BelongsTo::class);
});

it('defines unlocks relation', function (): void {
    $method = new ReflectionMethod(Blog::class, 'unlocks');

    expect($method->getReturnType()?->getName())->toBe(HasMany::class);
    expect((new Blog)->unlocks())->toBeInstanceOf(HasMany::class);
});

it('uses soft deletes', function (): void {
    $model = new Blog;

    expect(method_exists($model, 'trashed'))->toBeTrue();
});

it('allows viewing when required star is zero', function (): void {
    $tag = BlogTag::query()->create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    $blog = Blog::query()->create([
        'title' => 'Free Blog',
        'description' => 'Desc',
        'status' => true,
        'required_star' => 0,
        'tag_id' => $tag->id,
    ]);

    expect($blog->user_can_view)->toBeTrue();
});

it('requires unlock when required star is positive', function (): void {
    $tag = BlogTag::query()->create([
        'name' => 'Premium',
        'slug' => 'premium',
    ]);

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $blog = Blog::query()->create([
        'title' => 'Premium Blog',
        'description' => 'Desc',
        'status' => true,
        'required_star' => 5,
        'tag_id' => $tag->id,
    ]);

    expect($blog->user_can_view)->toBeFalse();

    request()->headers->set('User-Authorization', $token);
    expect($blog->user_can_view)->toBeFalse();

    BlogUnlock::query()->create([
        'blog_id' => $blog->id,
        'user_id' => $user->id,
    ]);

    expect($blog->user_can_view)->toBeTrue();
});

it('denies viewing when token is missing or invalid', function (): void {
    $tag = BlogTag::query()->create([
        'name' => 'Locked',
        'slug' => 'locked',
    ]);

    $blog = Blog::query()->create([
        'title' => 'Locked Blog',
        'description' => 'Desc',
        'status' => true,
        'required_star' => 3,
        'tag_id' => $tag->id,
    ]);

    request()->headers->remove('User-Authorization');
    expect($blog->user_can_view)->toBeFalse();

    request()->headers->set('User-Authorization', 'invalid-token');
    expect($blog->user_can_view)->toBeFalse();
});
