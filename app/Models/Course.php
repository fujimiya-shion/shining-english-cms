<?php

namespace App\Models;

use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, Slugable, SoftDeletes;

    protected $appends = [
        'comments_count',
    ];

    protected $fillable = [
        'name',
        'slug',
        'price',
        'status',
        'thumbnail',
        'category_id',
        'level_id',
        'description',
        'rating',
        'learned',
        'allow_star_payment',
        'star_price',
    ];

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('status', 1);
    }

    public function scopeWithCardCounts(Builder $query): Builder
    {
        return $query->withCount([
            'lessons',
            'reviews as comments_count',
        ]);
    }

    public function scopeWithCardMetrics(Builder $query): Builder
    {
        return $query
            ->withCardCounts()
            ->withSum('lessons as total_duration_minutes', 'duration_minutes');
    }

    public function getThumbnailAttribute($value): ?string
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

    public function getLessonsCountAttribute($value): int
    {
        if ($value !== null) {
            return (int) $value;
        }

        if (array_key_exists('lessons_count', $this->attributes)) {
            return (int) $this->attributes['lessons_count'];
        }

        if ($this->relationLoaded('lessons')) {
            return (int) $this->lessons->count();
        }

        return 0;
    }

    public function getCommentsCountAttribute(): int
    {
        if (array_key_exists('comments_count', $this->attributes)) {
            return (int) $this->attributes['comments_count'];
        }

        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        if ($this->relationLoaded('reviews')) {
            return (int) $this->reviews->count();
        }

        return 0;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function lessonGroups(): HasMany
    {
        return $this->hasMany(LessonGroup::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }
}
