<?php

use App\Models\Blog;
use App\Models\BlogTag;
use App\Models\BlogUnlock;
use App\Models\Star;
use App\Models\StarTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withHeader('Authorization', createDeveloperAccessToken());
});

function createBlog(array $attributes = []): Blog
{
    $tag = BlogTag::query()->create([
        'name' => $attributes['tag_name'] ?? 'Giao tiếp',
        'slug' => $attributes['tag_slug'] ?? 'giao-tiep',
    ]);

    return Blog::query()->create([
        'title' => $attributes['title'] ?? 'Blog title',
        'description' => $attributes['description'] ?? 'Blog description',
        'thumbnail' => $attributes['thumbnail'] ?? 'https://example.com/thumb.jpg',
        'content' => $attributes['content'] ?? 'Nội dung bài viết mẫu để kiểm tra khả năng đọc.',
        'slug' => $attributes['slug'] ?? fake()->slug(),
        'status' => $attributes['status'] ?? true,
        'required_star' => $attributes['required_star'] ?? 0,
        'tag_id' => $attributes['tag_id'] ?? $tag->id,
    ]);
}

it('returns free and paid blogs in listing for guest', function (): void {
    $freeBlog = createBlog([
        'title' => 'Blog miễn phí',
        'slug' => 'blog-mien-phi',
        'required_star' => 0,
    ]);
    $paidBlog = createBlog([
        'title' => 'Blog cần sao',
        'slug' => 'blog-can-sao',
        'required_star' => 12,
        'tag_name' => 'Ngữ pháp',
        'tag_slug' => 'ngu-phap',
    ]);

    $response = $this->getJson('/api/v1/blogs');

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'title' => $freeBlog->title,
        'slug' => $freeBlog->slug,
        'is_free' => true,
        'can_view' => true,
        'is_unlocked' => true,
    ]);
    $response->assertJsonFragment([
        'title' => $paidBlog->title,
        'slug' => $paidBlog->slug,
        'is_free' => false,
        'can_view' => false,
        'is_unlocked' => false,
        'required_star' => 12,
    ]);
    $response->assertJsonPath('data.star_balance', null);
});

it('returns full content for free blog detail to guest', function (): void {
    $blog = createBlog([
        'slug' => 'free-blog',
        'content' => 'Nội dung miễn phí đầy đủ.',
        'required_star' => 0,
    ]);

    $response = $this->getJson("/api/v1/blogs/slug/{$blog->slug}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.blog.slug', $blog->slug);
    $response->assertJsonPath('data.blog.can_view', true);
    $response->assertJsonPath('data.blog.content', 'Nội dung miễn phí đầy đủ.');
});

it('returns locked payload for paid blog detail to guest', function (): void {
    $blog = createBlog([
        'slug' => 'paid-blog',
        'required_star' => 7,
        'content' => 'Nội dung không được lộ.',
    ]);

    $response = $this->getJson("/api/v1/blogs/slug/{$blog->slug}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.blog.slug', $blog->slug);
    $response->assertJsonPath('data.blog.can_view', false);
    $response->assertJsonPath('data.blog.is_unlocked', false);
    $response->assertJsonPath('data.blog.content', null);
});

it('returns full content for authenticated user who already unlocked paid blog', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('blogs')->plainTextToken;
    $blog = createBlog([
        'slug' => 'unlocked-blog',
        'required_star' => 9,
        'content' => 'Nội dung đã mở.',
    ]);

    BlogUnlock::query()->create([
        'blog_id' => $blog->id,
        'user_id' => $user->id,
    ]);

    $response = $this->getJson("/api/v1/blogs/slug/{$blog->slug}", [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.blog.can_view', true);
    $response->assertJsonPath('data.blog.is_unlocked', true);
    $response->assertJsonPath('data.blog.content', 'Nội dung đã mở.');
    $response->assertJsonPath('data.star_balance', 0);
});

it('unlocks blog and deducts stars exactly once', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('blogs')->plainTextToken;
    $blog = createBlog([
        'required_star' => 6,
        'content' => 'Nội dung cần 6 sao.',
    ]);

    Star::query()->create([
        'user_id' => $user->id,
        'amount' => 15,
    ]);

    $firstResponse = $this->postJson("/api/v1/blogs/{$blog->id}/unlock", [], [
        'User-Authorization' => $token,
    ]);

    $firstResponse->assertStatus(200);
    $firstResponse->assertJsonPath('data.blog.can_view', true);
    $firstResponse->assertJsonPath('data.blog.is_unlocked', true);
    $firstResponse->assertJsonPath('data.star_balance', 9);

    expect(Star::query()->where('user_id', $user->id)->value('amount'))->toBe(9);
    expect(BlogUnlock::query()->where('blog_id', $blog->id)->where('user_id', $user->id)->count())->toBe(1);
    expect(StarTransaction::query()->where('user_id', $user->id)->where('amount', -6)->count())->toBe(1);

    $secondResponse = $this->postJson("/api/v1/blogs/{$blog->id}/unlock", [], [
        'User-Authorization' => $token,
    ]);

    $secondResponse->assertStatus(200);
    $secondResponse->assertJsonPath('data.star_balance', 9);
    expect(Star::query()->where('user_id', $user->id)->value('amount'))->toBe(9);
    expect(BlogUnlock::query()->where('blog_id', $blog->id)->where('user_id', $user->id)->count())->toBe(1);
    expect(StarTransaction::query()->where('user_id', $user->id)->where('amount', -6)->count())->toBe(1);
});

it('rejects unlock when user does not have enough stars', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('blogs')->plainTextToken;
    $blog = createBlog([
        'required_star' => 10,
    ]);

    Star::query()->create([
        'user_id' => $user->id,
        'amount' => 4,
    ]);

    $response = $this->postJson("/api/v1/blogs/{$blog->id}/unlock", [], [
        'User-Authorization' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment([
        'message' => 'Không đủ sao để mở bài viết này.',
        'status' => false,
        'status_code' => 422,
    ]);
    $response->assertJsonPath('errors.required_star', 10);
    $response->assertJsonPath('errors.star_balance', 4);

    expect(Star::query()->where('user_id', $user->id)->value('amount'))->toBe(4);
    expect(BlogUnlock::query()->where('blog_id', $blog->id)->where('user_id', $user->id)->count())->toBe(0);
});
