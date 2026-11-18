<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_cut_id',
        'amount',
        'method',
        'reason',
        'customer_name',
        'customer_id',
        'customer_credit_id',
        'idempotency_key',
        'happened_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'happened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyCut(): BelongsTo
    {
        return $this->belongsTo(DailyCut::class, 'daily_cut_id');
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(CustomerCredit::class, 'customer_credit_id');
    }

    /**
     * Human-readable label for the payment category.
     */
    public function categoryLabel(): string
    {
        return CashMovement::CATEGORIES['customer_payment']['label'];
    }
}
