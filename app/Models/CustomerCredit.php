<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerCredit extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLED = 'settled';

    public const METHODS = ['cash', 'card', 'transfer'];

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'description',
        'total_cents',
        'balance_cents',
        'status',
        'idempotency_key',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'balance_cents' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class)->latest('happened_at')->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paidCents(): int
    {
        return max(0, $this->total_cents - $this->balance_cents);
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }
}
