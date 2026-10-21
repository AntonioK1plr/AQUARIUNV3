<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLot extends Model
{
    protected $fillable = [
        'product_id', 'lot_code', 'supplier', 'received_at',
        'quantity_received', 'quantity_remaining', 'unit_cost', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'quantity_received' => 'integer',
            'quantity_remaining' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
