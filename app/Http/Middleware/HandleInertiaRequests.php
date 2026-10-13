<?php

namespace App\Http\Middleware;

use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version($request): ?string
    {
        return parent::version($request);
    }
}
