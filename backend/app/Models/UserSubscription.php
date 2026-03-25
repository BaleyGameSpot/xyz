<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'payment_method',
        'payment_status',
        'transaction_id',
        'amount',
        'currency',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'amount'     => 'float',
    ];

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // --- Scopes ---

    public function scopeConfirmed($query)
    {
        return $query->where('payment_status', 'confirmed');
    }

    public function scopeActive($query)
    {
        return $query->confirmed()->where('end_date', '>=', now()->toDateString());
    }

    // --- Helpers ---

    public function isActive(): bool
    {
        return $this->payment_status === 'confirmed'
            && $this->end_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }
}
