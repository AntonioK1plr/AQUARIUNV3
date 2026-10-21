<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateStaffAccount extends Command
{
    protected $signature = 'aquarium:staff:create {--role=administrador : administrador, almacenista, cajero_pos o veterinario}';

    protected $description = 'Create the first local staff account through a private console prompt.';

    public function handle(): int
    {
        $name = $this->ask('Nombre del empleado');
        $email = $this->ask('Correo del empleado');
        $phone = $this->ask('Teléfono (opcional)');
        $role = $this->option('role');

        if (! in_array($role, User::staffRoles(), true)) {
            $this->error('Rol inválido. Usa administrador, almacenista, cajero_pos o veterinario.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Ya existe una cuenta con ese correo.');

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mínimo 10 caracteres, mayúsculas, minúsculas y número)');
        $confirmation = $this->secret('Confirma la contraseña');
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'role' => $role,
            'password' => $password,
        ]);

        $this->info("Cuenta {$role} creada para {$email}.");

        return self::SUCCESS;
    }
}
