<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Account/Orders', [
            'orders' => Order::query()->with(['items', 'payment:id,order_id,reference,method,status'])
                ->where('client_id', $request->user()->id)
                ->orderByDesc('created_at')->paginate(8)->withQueryString(),
            'notifications' => $request->user()->notifications()->latest()->limit(10)->get(),
        ]);
    }
}
