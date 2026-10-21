<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'client_id', 'cashier_id', 'customer_name', 'customer_email',
        'channel', 'status', 'subtotal', 'tax_rate', 'tax', 'total',
        'fulfillment_type', 'fulfillment_status', 'pickup_code',
        'shipping_address',
        'shipping_tracking_reference',
        'care_responsibility_accepted_at',
        'compatibility_acknowledged_at',
        'compatibility_warnings',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'tax_rate' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2',
            'shipping_address' => 'array', 'compatibility_warnings' => 'array',
            'care_responsibility_accepted_at' => 'datetime', 'compatibility_acknowledged_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(SimulatedOrderPayment::class);
    }

    public function fulfillmentEvents(): HasMany
    {
        return $this->hasMany(OrderFulfillmentEvent::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
