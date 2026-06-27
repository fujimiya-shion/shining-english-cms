<?php

namespace App\Http\Controllers\Api\V1\Blog;

use App\Http\Controllers\Api\ApiController;
use App\Models\Blog;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $blogs = Blog::query()
            ->with('tag:id,name,slug')
            ->where('status', true)
            ->latest()
            ->get();

        $topics = BlogTag::query()
            ->whereIn('id', $blogs->pluck('tag_id')->filter()->unique()->values())
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (BlogTag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])
            ->values()
            ->all();

        return $this->success(data: [
            'items' => $blogs->map(fn (Blog $blog): array => $this->formatBlog($blog, false))->values()->all(),
            'topics' => $topics,
        ]);
    }

    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $blog = Blog::query()
            ->with('tag:id,name,slug')
            ->where('status', true)
            ->where('slug', $slug)
            ->first();

        if (! $blog instanceof Blog) {
            return $this->notfound();
        }

        return $this->success(data: [
            'blog' => $this->formatBlog($blog, true),
        ]);
    }

    protected function formatBlog(Blog $blog, bool $includeContent): array
    {
        $date = $blog->created_at ?? $blog->updated_at;

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'description' => $blog->description,
            'short_description' => $blog->short_description,
            'thumbnail' => $blog->thumbnail,
            'read_time_minutes' => $blog->read_time_minutes,
            'published_at' => $date?->toISOString(),
            'tag' => $blog->tag ? [
                'id' => $blog->tag->id,
                'name' => $blog->tag->name,
                'slug' => $blog->tag->slug,
            ] : null,
            'content' => $includeContent ? $blog->content : null,
        ];
    }
}
