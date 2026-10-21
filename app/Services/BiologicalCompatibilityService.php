<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class BiologicalCompatibilityService
{
    /** @return array<int, array{products: array<int, string>, reasons: array<int, string>}> */
    public function warnings(Collection $products): array
    {
        $fish = $products->filter(fn (Product $product) => $product->isLive())->values();
        $warnings = [];

        for ($leftIndex = 0; $leftIndex < $fish->count(); $leftIndex++) {
            for ($rightIndex = $leftIndex + 1; $rightIndex < $fish->count(); $rightIndex++) {
                $left = $fish[$leftIndex];
                $right = $fish[$rightIndex];
                $reasons = [];

                if ($left->water_type && $right->water_type && $left->water_type !== $right->water_type) {
                    $reasons[] = 'requieren tipos de agua distintos';
                }
                if ($this->rangesDoNotOverlap($left->min_ph, $left->max_ph, $right->min_ph, $right->max_ph)) {
                    $reasons[] = 'sus rangos de pH no coinciden';
                }
                if ($this->rangesDoNotOverlap($left->min_temp_c, $left->max_temp_c, $right->min_temp_c, $right->max_temp_c)) {
                    $reasons[] = 'sus rangos de temperatura no coinciden';
                }
                if ($this->temperamentsConflict($left->temperament, $right->temperament)) {
                    $reasons[] = 'hay un posible conflicto de temperamento';
                }

                if ($reasons) {
                    $warnings[] = [
                        'products' => [$left->name, $right->name],
                        'reasons' => $reasons,
                    ];
                }
            }
        }

        return $warnings;
    }

    private function rangesDoNotOverlap(mixed $leftMin, mixed $leftMax, mixed $rightMin, mixed $rightMax): bool
    {
        if ($leftMin === null || $leftMax === null || $rightMin === null || $rightMax === null) {
            return false;
        }

        return (float) $leftMax < (float) $rightMin || (float) $rightMax < (float) $leftMin;
    }

    private function temperamentsConflict(?string $left, ?string $right): bool
    {
        $aggressive = ['agresivo', 'semi-agresivo', 'aggressive', 'semi-aggressive'];
        $left = mb_strtolower(trim((string) $left));
        $right = mb_strtolower(trim((string) $right));

        return ($left && $right) && (($left !== $right) && (in_array($left, $aggressive, true) || in_array($right, $aggressive, true)));
    }
}
