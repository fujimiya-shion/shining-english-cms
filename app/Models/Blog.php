<?php

namespace App\Models;

use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class Blog extends Model
{
    use HasFactory, Slugable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'short_description',
        'thumbnail',
        'content',
        'slug',
        'status',
        'required_star',
        'tag_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'required_star' => 'integer',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(BlogTag::class, 'tag_id');
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(BlogUnlock::class);
    }

    public function isFree(): bool
    {
        if ((int) $this->required_star <= 0) {
            return true;
        }

        return false;
    }

    public function isUnlockedBy(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return BlogUnlock::query()
            ->where('blog_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canViewBy(?User $user): bool
    {
        if ($this->isFree()) {
            return true;
        }

        return $this->isUnlockedBy($user);
    }

    protected function userCanView(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->canViewBy($this->resolveCurrentUser()),
        );
    }

    public function getReadTimeMinutesAttribute(): int
    {
        $plainContent = trim(strip_tags((string) $this->content));
        if ($plainContent === '') {
            return 1;
        }

        $wordCount = str_word_count($plainContent);

        return max(1, (int) ceil($wordCount / 200));
    }

    public function getThumbnailAttribute(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $thumbnail = trim($value);

        if (Str::startsWith($thumbnail, ['http://', 'https://'])) {
            return $thumbnail;
        }

        if (Str::startsWith($thumbnail, '/storage/')) {
            return rtrim((string) config('app.url'), '/').$thumbnail;
        }

        if (Str::startsWith($thumbnail, 'public/')) {
            return rtrim((string) config('app.url'), '/').'/storage/'.ltrim(Str::after($thumbnail, 'public/'), '/');
        }

        return rtrim((string) config('app.url'), '/').'/storage/'.ltrim($thumbnail, '/');
    }

    protected function resolveCurrentUser(): ?User
    {
        $user = request()->user();
        if ($user instanceof User) {
            return $user;
        }

        $token = request()->header('User-Authorization');
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        return $accessToken?->tokenable instanceof User ? $accessToken->tokenable : null;
    }
}
