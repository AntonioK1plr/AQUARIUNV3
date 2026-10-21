<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulatedOrderPayment extends Model
{
    protected $fillable = ['order_id', 'reference', 'method', 'status', 'amount', 'amount_tendered', 'change_amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'amount_tendered' => 'decimal:2', 'change_amount' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
