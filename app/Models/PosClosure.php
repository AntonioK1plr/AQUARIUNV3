<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosClosure extends Model
{
    protected $fillable = [
        'cashier_id', 'business_date', 'opening_float', 'expected_cash', 'counted_cash',
        'difference', 'sales_count', 'cash_sales', 'card_sales', 'transfer_sales', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date', 'opening_float' => 'decimal:2', 'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2', 'difference' => 'decimal:2', 'cash_sales' => 'decimal:2',
            'card_sales' => 'decimal:2', 'transfer_sales' => 'decimal:2', 'closed_at' => 'datetime',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
