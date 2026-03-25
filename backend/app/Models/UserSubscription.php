<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'admin_note',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'confirmed_at' => 'datetime',
        'amount'       => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('payment_status', 'confirmed')
            ->where('end_date', '>=', now()->toDateString());
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->payment_status === 'confirmed'
            && $this->end_date->isFuture();
    }

    public function daysRemaining(): int
    {
        if (! $this->isActive()) {
            return 0;
        }
        return now()->diffInDays($this->end_date);
    }
}
