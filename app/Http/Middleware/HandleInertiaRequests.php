<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name', 'email', 'role')
                : null,
            'flash.success' => fn () => $request->session()->get('success'),
            'flash.warning' => fn () => $request->session()->get('warning'),
            'flash.error' => fn () => $request->session()->get('error'),
            'flash.receipt' => fn () => $request->session()->get('receipt'),
            'cart.itemsCount' => fn () => collect($request->session()->get('cart', []))->sum(),
        ];
    }

    public function version($request): ?string
    {
        return parent::version($request);
    }
}
