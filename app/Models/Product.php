<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'scientific_name',
        'description',
        'category',
        'water_type',
        'brand',
        'warehouse_location',
        'origin',
        'temperament',
        'min_ph',
        'max_ph',
        'min_temp_c',
        'max_temp_c',
        'adult_size_cm',
        'minimum_tank_liters',
        'minimum_group_size',
        'care_level',
        'care_notes',
        'price',
        'cost_price',
        'stock',
        'image_path',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'min_ph' => 'decimal:1',
            'max_ph' => 'decimal:1',
            'min_temp_c' => 'decimal:1',
            'max_temp_c' => 'decimal:1',
            'adult_size_cm' => 'decimal:1',
            'minimum_tank_liters' => 'integer',
            'minimum_group_size' => 'integer',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('stock', '>', 0);
    }

    public function isLive(): bool
    {
        return in_array($this->category, ['pez', 'invertebrado', 'coral'], true);
    }

    public function cartReservations(): HasMany
    {
        return $this->hasMany(CartReservation::class);
    }
}
