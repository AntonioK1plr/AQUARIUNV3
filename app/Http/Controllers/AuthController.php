<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\PasswordReset;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function createLogin(Request $request): Response
    {
        $continueToCart = $request->query('continuar') === 'carrito';
        if ($continueToCart) {
            $request->session()->put('url.intended', route('cart.index'));
        }

        return Inertia::render('Auth/Login', ['continueToCart' => $continueToCart]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            $user = User::query()->where('email', $credentials['email'])->first();
            if ($user && ! $user->is_active) {
                return back()->withErrors(['email' => 'Tu cuenta está desactivada. Contacta a administración.'])->onlyInput('email');
            }

            return back()->withErrors(['email' => 'Las credenciales no coinciden con nuestros registros.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        $dashboard = $this->dashboardRoute($user);

        if (in_array($user->role, User::staffRoles(), true)) {
            return redirect($dashboard);
        }

        return redirect()->intended($dashboard);
    }

    public function createRegistration(Request $request): Response
    {
        $continueToCart = $request->query('continuar') === 'carrito';
        if ($continueToCart) {
            $request->session()->put('url.intended', route('cart.index'));
        }

        return Inertia::render('Auth/Register', ['continueToCart' => $continueToCart]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ], [
            'password.min' => 'La contraseña debe tener al menos 10 caracteres.',
            'password.letters' => 'La contraseña debe incluir letras.',
            'password.mixed' => 'La contraseña debe combinar mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_CLIENTE,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.show'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function account(Request $request): Response
    {
        return Inertia::render('Account/Show', [
            'user' => $request->user()->only('name', 'email', 'phone', 'address', 'role'),
        ]);
    }

    private function dashboardRoute(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMINISTRADOR => route('admin.dashboard'),
            User::ROLE_ALMACENISTA => route('warehouse.index'),
            User::ROLE_CAJERO_POS => route('pos.index'),
            User::ROLE_VETERINARIO => route('veterinary.appointments.index'),
            default => route('account.show'),
        };
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+() .-]{7,30}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
        $request->user()->update($data);

        return back()->with('success', 'Tus datos de cuenta se actualizaron.');
    }

    public function createPasswordRequest(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function requestPasswordReset(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = PasswordBroker::sendResetLink($request->only('email'));

        return back()->with($status === PasswordBroker::RESET_LINK_SENT ? 'success' : 'error', __($status));
    }

    public function createPasswordReset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $status = PasswordBroker::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === PasswordBroker::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
