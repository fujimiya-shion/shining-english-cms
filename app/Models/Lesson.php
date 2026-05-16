<?php

namespace App\Models;

use App\Traits\Slugable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, Slugable, SoftDeletes;

    protected $attributes = [
        'star_reward_video' => 0,
        'star_reward_quiz' => 0,
        'has_quiz' => false,
        'group_order' => 0,
        'lesson_order' => 0,
    ];

    protected $fillable = [
        'name',
        'slug',
        'course_id',
        'lesson_group_id',
        'group_name',
        'group_order',
        'lesson_order',
        'video_url',
        'documents',
        'document_names',
        'description',
        'duration_minutes',
        'star_reward_video',
        'star_reward_quiz',
        'has_quiz',
        'is_preview_free',
    ];

    protected $casts = [
        'has_quiz' => 'boolean',
        'lesson_group_id' => 'integer',
        'group_order' => 'integer',
        'lesson_order' => 'integer',
        'duration_minutes' => 'integer',
        'is_preview_free' => 'boolean',
        'documents' => 'array',
        'document_names' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Lesson $lesson): void {
            if (! $lesson->lesson_group_id) {
                if ((int) ($lesson->lesson_order ?? 0) <= 0) {
                    $lesson->lesson_order = self::computeNextLessonOrder($lesson);
                }

                return;
            }

            $group = $lesson->relationLoaded('lessonGroup')
                ? $lesson->lessonGroup
                : LessonGroup::query()->find($lesson->lesson_group_id);

            if (! $group) {
                return;
            }

            $lesson->group_name = $group->name;
            $lesson->group_order = (int) $group->sort_order;

            if ((int) ($lesson->lesson_order ?? 0) <= 0) {
                $lesson->lesson_order = self::computeNextLessonOrder($lesson);
            }
        });
    }

    private static function computeNextLessonOrder(Lesson $lesson): int
    {
        if ((int) ($lesson->course_id ?? 0) <= 0) {
            return 1;
        }

        $query = self::query()->where('course_id', (int) $lesson->course_id);

        if ((int) ($lesson->lesson_group_id ?? 0) > 0) {
            $query->where('lesson_group_id', (int) $lesson->lesson_group_id);
        } elseif (! empty($lesson->group_name)) {
            $query->where('group_name', $lesson->group_name);
        }

        if ($lesson->exists) {
            $query->where('id', '!=', $lesson->id);
        }

        return ((int) $query->max('lesson_order')) + 1;
    }

    public function setDocumentNamesAttribute(?array $value): void
    {
        if ($value === null) {
            $this->attributes['document_names'] = null;

            return;
        }

        $normalized = [];

        if (array_is_list($value)) {
            $documentPaths = collect($this->documents ?? [])->values();

            foreach ($value as $index => $name) {
                if (! is_string($name)) {
                    continue;
                }

                $displayName = trim($name);
                $path = $documentPaths->get($index);
                $fallbackName = is_string($path) ? basename($path) : $displayName;

                if ($displayName === '') {
                    if ($fallbackName === '') {
                        continue;
                    }

                    $normalized[] = $fallbackName;

                    continue;
                }

                $extension = pathinfo($fallbackName, PATHINFO_EXTENSION);

                if (
                    $extension !== ''
                    && strtolower(pathinfo($displayName, PATHINFO_EXTENSION)) !== strtolower($extension)
                ) {
                    $displayName .= ".{$extension}";
                }

                $normalized[] = $displayName;
            }

            $this->attributes['document_names'] = json_encode(array_values($normalized));

            return;
        }

        foreach ($value as $path => $name) {
            if (! is_string($path)) {
                continue;
            }

            $fallbackName = basename($path);
            $displayName = is_string($name) ? trim($name) : '';

            if ($displayName === '') {
                $normalized[$path] = $fallbackName;

                continue;
            }

            $extension = pathinfo($fallbackName, PATHINFO_EXTENSION);

            if (
                $extension !== ''
                && strtolower(pathinfo($displayName, PATHINFO_EXTENSION)) !== strtolower($extension)
            ) {
                $displayName .= ".{$extension}";
            }

            $normalized[$path] = $displayName;
        }

        $this->attributes['document_names'] = json_encode($normalized);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonGroup(): BelongsTo
    {
        return $this->belongsTo(LessonGroup::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }
}
