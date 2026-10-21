<?php

namespace App\Console\Commands;

use App\Models\CartReservation;
use Illuminate\Console\Command;

class ExpireCartReservations extends Command
{
    protected $signature = 'aquarium:carts:expire-reservations {--dry-run : Solo contar reservas que vencieron}';

    protected $description = 'Libera unidades de carrito cuando se cumple su apartado temporal de 30 minutos.';

    public function handle(): int
    {
        $expired = CartReservation::query()->where('expires_at', '<=', now());
        $count = $expired->count();

        if (! $this->option('dry-run')) {
            $expired->delete();
        }

        $this->info($this->option('dry-run')
            ? "Simulación: {$count} reservas de carrito vencieron."
            : "Reservas de carrito vencidas y liberadas: {$count}.");

        return self::SUCCESS;
    }
}
