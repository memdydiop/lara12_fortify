<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'roles',
        'registered_at',
        'expires_at',
        'invited_by',
    ];

    protected $casts = [
        'roles' => 'array',
        'registered_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            $invitation->token = $invitation->token ?? Str::random(64);
            $invitation->expires_at = $invitation->expires_at ?? now()->addDays(7);
        });
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRegistered(): bool
    {
        return !is_null($this->registered_at);
    }

    public function isPending(): bool
    {
        return !$this->isRegistered() && !$this->isExpired();
    }

    public function markAsRegistered(): void
    {
        $this->update(['registered_at' => now()]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopePending($query)
    {
        return $query->whereNull('registered_at')
            ->where('expires_at', '>', now());
    }

    public function scopeRegistered($query)
    {
        return $query->whereNotNull('registered_at');
    }
}