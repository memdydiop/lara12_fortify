<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'expires_at',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function isRegistered(): bool
    {
        return ! is_null($this->registered_at);
    }
}