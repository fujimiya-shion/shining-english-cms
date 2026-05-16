<?php

namespace App\Http\Controllers\Api\V1\Blog;

use App\Http\Controllers\Api\ApiController;
use App\Models\Blog;
use App\Models\BlogTag;
use App\Models\Star;
use App\Models\User;
use App\Services\Star\IStarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class BlogController extends ApiController
{
    public function __construct(
        protected IStarService $starService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
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
            'items' => $blogs->map(fn (Blog $blog): array => $this->formatBlog($blog, $user, false))->values()->all(),
            'topics' => $topics,
            'star_balance' => $this->resolveStarBalance($user),
        ]);
    }

    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $user = $this->resolveCurrentUser($request);
        $blog = Blog::query()
            ->with('tag:id,name,slug')
            ->where('status', true)
            ->where('slug', $slug)
            ->first();

        if (! $blog instanceof Blog) {
            return $this->notfound();
        }

        return $this->success(data: [
            'blog' => $this->formatBlog($blog, $user, true),
            'star_balance' => $this->resolveStarBalance($user),
        ]);
    }

    public function unlock(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->unauthorized('Unauthenticated');
        }

        $blog = Blog::query()
            ->with('tag:id,name,slug')
            ->where('status', true)
            ->find($id);

        if (! $blog instanceof Blog) {
            return $this->notfound();
        }

        if ($blog->isFree()) {
            return $this->success(data: [
                'blog' => $this->formatBlog($blog, $user, true),
                'star_balance' => $this->resolveStarBalance($user),
            ]);
        }

        $result = DB::transaction(function () use ($blog, $user): array {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($blog->isUnlockedBy($user)) {
                return [
                    'success' => true,
                    'deducted' => false,
                    'star_balance' => $this->resolveStarBalance($user),
                ];
            }

            $spent = $this->starService->spendStarByUserId(
                amount: (int) $blog->required_star,
                userId: (int) $user->id,
                message: sprintf('Unlock blog #%d: %s', $blog->id, $blog->title),
            );

            if (! $spent) {
                return [
                    'success' => false,
                    'deducted' => false,
                    'star_balance' => $this->resolveStarBalance($user),
                ];
            }

            $user->blogUnlocks()->create([
                'blog_id' => $blog->id,
            ]);

            return [
                'success' => true,
                'deducted' => true,
                'star_balance' => $this->resolveStarBalance($user),
            ];
        });

        if ($result['success'] !== true) {
            return $this->error(
                message: 'Không đủ sao để mở bài viết này.',
                statusCode: 422,
                errors: [
                    'required_star' => (int) $blog->required_star,
                    'star_balance' => $result['star_balance'],
                ],
            );
        }

        return $this->success(
            message: $result['deducted'] ? 'Mở bài viết thành công.' : 'Bài viết đã được mở trước đó.',
            data: [
                'blog' => $this->formatBlog($blog->fresh(['tag:id,name,slug']), $user, true),
                'star_balance' => $result['star_balance'],
            ],
        );
    }

    protected function resolveCurrentUser(Request $request): ?User
    {
        $user = $request->user();
        if ($user instanceof User) {
            return $user;
        }

        $token = $request->header('User-Authorization');
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        return $accessToken?->tokenable instanceof User ? $accessToken->tokenable : null;
    }

    protected function resolveStarBalance(?User $user): ?int
    {
        if (! $user instanceof User) {
            return null;
        }

        $record = Star::query()->where('user_id', $user->id)->first();

        return $record ? (int) $record->amount : 0;
    }

    protected function formatBlog(Blog $blog, ?User $user, bool $includeContent): array
    {
        $canView = $blog->canViewBy($user);
        $isFree = $blog->isFree();
        $isUnlocked = $isFree || $blog->isUnlockedBy($user);
        $date = $blog->created_at ?? $blog->updated_at;

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'description' => $blog->description,
            'short_description' => $blog->short_description,
            'thumbnail' => $blog->thumbnail,
            'required_star' => (int) $blog->required_star,
            'is_free' => $isFree,
            'can_view' => $canView,
            'is_unlocked' => $isUnlocked,
            'read_time_minutes' => $blog->read_time_minutes,
            'published_at' => $date?->toISOString(),
            'tag' => $blog->tag ? [
                'id' => $blog->tag->id,
                'name' => $blog->tag->name,
                'slug' => $blog->tag->slug,
            ] : null,
            'content' => $includeContent && $canView ? $blog->content : null,
        ];
    }
}
