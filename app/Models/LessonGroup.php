<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (LessonGroup $group): void {
            if (! $group->wasChanged(['name', 'sort_order'])) {
                return;
            }

            Lesson::query()
                ->where('lesson_group_id', $group->id)
                ->update([
                    'group_name' => $group->name,
                    'group_order' => (int) $group->sort_order,
                ]);
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
