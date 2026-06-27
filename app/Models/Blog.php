<?php

namespace App\Models;

use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
        'tag_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(BlogTag::class, 'tag_id');
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
}
