<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class FishCareGuideController extends Controller
{
    public function __invoke(): Response
    {
        $species = Product::query()
            ->where('is_active', true)
            ->where('category', 'pez')
            ->whereIn('water_type', ['dulce', 'salada'])
            ->orderBy('water_type')
            ->orderBy('minimum_tank_liters')
            ->orderBy('name')
            ->get([
                'id', 'name', 'scientific_name', 'water_type', 'minimum_tank_liters',
                'minimum_group_size', 'care_level', 'care_notes', 'min_ph', 'max_ph',
                'min_temp_c', 'max_temp_c',
            ]);

        $volumes = $species->groupBy('water_type')->map(fn ($items) => [
            'count' => $items->count(),
            'minimum' => $items->min('minimum_tank_liters'),
            'maximum' => $items->max('minimum_tank_liters'),
        ]);

        return Inertia::render('Care/FishGuide', [
            'species' => $species,
            'volumes' => $volumes,
        ]);
    }
}
