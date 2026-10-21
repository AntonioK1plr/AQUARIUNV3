<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('aquarium:orders:expire-pickups --hours=48')
    ->everyMinute()
    ->description('Cancela pedidos Click & Collect vencidos y libera inventario con reembolso simulado.');

Schedule::command('aquarium:carts:expire-reservations')
    ->everyMinute()
    ->description('Libera apartados de carrito que superaron 30 minutos.');
