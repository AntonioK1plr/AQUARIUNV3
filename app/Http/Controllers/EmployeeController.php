<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(User::staffRoles())],
        ]);

        $employees = User::query()
            ->whereIn('role', User::staffRoles())
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $query->where(function ($search) use ($term) {
                    $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                    $search->where('name', $operator, "%{$term}%")
                        ->orWhere('email', $operator, "%{$term}%")
                        ->orWhere('phone', $operator, "%{$term}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $employees,
            'filters' => $filters,
            'roleCounts' => User::query()->whereIn('role', User::staffRoles())
                ->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'roleOptions' => [
                ['value' => User::ROLE_ADMINISTRADOR, 'label' => 'Administrador'],
                ['value' => User::ROLE_ALMACENISTA, 'label' => 'Almacenista'],
                ['value' => User::ROLE_CAJERO_POS, 'label' => 'Cajero POS'],
                ['value' => User::ROLE_VETERINARIO, 'label' => 'Veterinario'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(User::staffRoles())],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        User::create($data);

        return back()->with('success', 'La cuenta del empleado quedó registrada.');
    }
}
