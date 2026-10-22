<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Appointment;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\PosClosure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(User::staffRoles())],
            'status' => ['nullable', Rule::in(['activa', 'desactivada'])],
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
            ->when(isset($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'activa'))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $employees->getCollection()->transform(function (User $employee) {
            $employee->setAttribute('can_delete', ! $this->hasOperationalHistory($employee));

            return $employee;
        });

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

    public function updateStatus(Request $request, User $employee): RedirectResponse
    {
        abort_unless(in_array($employee->role, User::staffRoles(), true), 404);

        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $active = (bool) $data['is_active'];

        if (! $active && $employee->is($request->user())) {
            return back()->with('error', 'No puedes desactivar la cuenta con la que estás administrando.');
        }

        if (! $active && $this->isLastActiveAdministrator($employee)) {
            return back()->with('error', 'Debe permanecer al menos una cuenta administradora activa.');
        }

        DB::transaction(function () use ($employee, $active) {
            $lockedEmployee = User::query()->lockForUpdate()->findOrFail($employee->id);
            $lockedEmployee->update(['is_active' => $active]);

            if (! $active && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $lockedEmployee->id)->delete();
            }
        });

        return back()->with('success', $active
            ? 'Cuenta de empleado reactivada.'
            : 'Cuenta de empleado desactivada; ya no podrá iniciar sesión ni continuar usando sesiones activas.');
    }

    public function destroy(Request $request, User $employee): RedirectResponse
    {
        abort_unless(in_array($employee->role, User::staffRoles(), true), 404);

        if ($employee->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta de administración.');
        }

        if ($this->isLastActiveAdministrator($employee)) {
            return back()->with('error', 'Debe permanecer al menos una cuenta administradora activa.');
        }

        if ($this->hasOperationalHistory($employee)) {
            return back()->with('error', 'Esta cuenta conserva movimientos, citas, ventas o cierres. Desactívala para restringir el acceso sin perder la auditoría.');
        }

        DB::transaction(function () use ($employee) {
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $employee->id)->delete();
            }

            DB::table('password_reset_tokens')->where('email', $employee->email)->delete();
            $employee->delete();
        });

        return back()->with('success', 'La cuenta sin actividad operativa fue eliminada.');
    }

    private function isLastActiveAdministrator(User $employee): bool
    {
        if ($employee->role !== User::ROLE_ADMINISTRADOR || ! $employee->is_active) {
            return false;
        }

        $activeAdministrators = User::query()
            ->where('role', User::ROLE_ADMINISTRADOR)
            ->where('is_active', true)
            ->count();

        return $activeAdministrators <= 1;
    }

    private function hasOperationalHistory(User $employee): bool
    {
        $id = $employee->id;

        return Appointment::query()->where('client_id', $id)->orWhere('veterinarian_id', $id)->exists()
            || Order::query()->where('client_id', $id)->orWhere('cashier_id', $id)->exists()
            || InventoryLot::query()->where('recorded_by', $id)->exists()
            || InventoryMovement::query()->where('recorded_by', $id)->exists()
            || OrderFulfillmentEvent::query()->where('performed_by', $id)->exists()
            || PosClosure::query()->where('cashier_id', $id)->exists();
    }
}
