<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local-only demo logins for acceptance and role-flow checks.
 * Run explicitly with `php artisan db:seed --class=LocalDemoAccountsSeeder`.
 */
class LocalDemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Las cuentas demo solo se pueden crear en entornos local o testing.');
        }

        $password = config('aquarium.demo_password');

        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Configura AQUARIUM_DEMO_PASSWORD con al menos 12 caracteres en el archivo .env local.');
        }

        $accounts = [
            ['name' => 'Cliente de Pruebas AQUARIUM', 'email' => 'cliente.demo@aquarium.local', 'role' => User::ROLE_CLIENTE],
            ['name' => 'Administración de Pruebas AQUARIUM', 'email' => 'admin.demo@aquarium.local', 'role' => User::ROLE_ADMINISTRADOR],
            ['name' => 'Almacén de Pruebas AQUARIUM', 'email' => 'almacen.demo@aquarium.local', 'role' => User::ROLE_ALMACENISTA],
            ['name' => 'Caja POS de Pruebas AQUARIUM', 'email' => 'caja.demo@aquarium.local', 'role' => User::ROLE_CAJERO_POS],
            ['name' => 'Veterinaria de Pruebas AQUARIUM', 'email' => 'veterinario.demo@aquarium.local', 'role' => User::ROLE_VETERINARIO],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
