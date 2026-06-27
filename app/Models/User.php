<?php

namespace App\Models;

use App\Enums\AuthenticatedBy;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, MustVerifyEmail, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'email',
        'phone',
        'birthday',
        'avatar',
        'city_id',
        'password',
        'authenticated_by',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'city_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthday' => 'date',
            'password' => 'hashed',
            'authenticated_by' => AuthenticatedBy::class,
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(UserQuizAttempt::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courseReviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function lessonComments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }

    public function lessonNotes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }

    public function setPasswordAttribute(?string $password): void
    {
        if ($password === null || $password === '') {
            $this->attributes['password'] = null;

            return;
        }

        $this->attributes['password'] = Hash::needsRehash($password)
            ? Hash::make($password)
            : $password;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: static function (?string $value): ?string {
                if (! filled($value)) {
                    return $value;
                }

                if (Str::startsWith($value, ['http://', 'https://'])) {
                    return $value;
                }

                return Storage::disk('public')->url(ltrim($value, '/'));
            }
        );
    }

    protected function cityName(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->city?->name,
        );
    }
}
