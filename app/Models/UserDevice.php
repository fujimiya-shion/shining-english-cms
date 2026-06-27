<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'personal_access_token_id',
        'device_identifier',
        'device_name',
        'platform',
        'ip_address',
        'user_agent',
        'logged_in_at',
        'last_seen_at',
        'logged_out_at',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }
}
