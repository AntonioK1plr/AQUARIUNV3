<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartReservation extends Model
{
    protected $fillable = ['session_hash', 'product_id', 'quantity', 'expires_at'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'expires_at' => 'datetime'];
    }
}
