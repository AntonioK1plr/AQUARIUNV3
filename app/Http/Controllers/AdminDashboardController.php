<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        $roleCounts = User::query()
            ->whereIn('role', User::staffRoles())
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return Inertia::render('Admin/Dashboard', [
            'summary' => [
                'employees' => (int) $roleCounts->sum(),
                'products' => Product::query()->where('is_active', true)->count(),
                'lowStock' => Product::query()->where('is_active', true)->whereBetween('stock', [1, 5])->count(),
                'outOfStock' => Product::query()->where('is_active', true)->where('stock', 0)->count(),
            ],
            'roleCounts' => $roleCounts,
        ]);
    }
}
