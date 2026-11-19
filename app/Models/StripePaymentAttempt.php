<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripePaymentAttempt extends Model
{
    protected $fillable = [
        'order_id', 'appointment_id', 'client_request_key', 'payment_intent_id', 'amount', 'currency',
        'status', 'session_hash', 'failure_code', 'paid_at', 'expires_at', 'refund_reason', 'refunded_at', 'stripe_mode',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'expires_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
