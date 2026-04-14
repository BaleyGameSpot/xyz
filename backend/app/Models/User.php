<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'role',
        'subscription_type',
        'subscription_expiry',
        'status',
        'fcm_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'subscription_expiry' => 'datetime',
        'password'           => 'hashed',
    ];

    // JWT Interface methods
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role'  => $this->role,
            'email' => $this->email,
        ];
    }

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('payment_status', 'confirmed')
            ->where('end_date', '>=', now()->toDateString())
            ->latest('end_date');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSubscribed($query)
    {
        return $query->where('subscription_type', '!=', 'none')
            ->where('subscription_expiry', '>=', now());
    }

    // Helpers
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_type !== 'none'
            && $this->subscription_expiry !== null
            && $this->subscription_expiry->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function getIsBlockedAttribute(): bool
    {
        return $this->status === 'blocked';
    }

    public function getAllowedPairsLimit(): ?int
    {
        $limits = [
            'basic'   => 2,
            'best'    => 5,
            'premium' => null,
        ];
        return $limits[$this->subscription_type] ?? 0;
    }

    public function getDailySignalsLimit(): int
    {
        $limits = [
            'basic'   => 2,
            'best'    => 4,
            'premium' => 10,
            'none'    => 0,
        ];
        return $limits[$this->subscription_type] ?? 0;
    }

    public function getAllowedTimeframes(): array
    {
        $timeframes = [
            'basic'   => ['15m', '30m'],
            'best'    => ['1m', '3m', '5m', '15m', '1h', '4h'],
            'premium' => ['1m', '3m', '5m', '15m', '30m', '1h', '4h', '1D'],
            'none'    => [],
        ];
        return $timeframes[$this->subscription_type] ?? [];
    }
}