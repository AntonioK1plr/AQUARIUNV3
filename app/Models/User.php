<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_CLIENTE = 'cliente';
    public const ROLE_ADMINISTRADOR = 'administrador';
    public const ROLE_ALMACENISTA = 'almacenista';
    public const ROLE_CAJERO_POS = 'cajero_pos';
    public const ROLE_VETERINARIO = 'veterinario';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public static function staffRoles(): array
    {
        return [
            self::ROLE_ADMINISTRADOR,
            self::ROLE_ALMACENISTA,
            self::ROLE_CAJERO_POS,
            self::ROLE_VETERINARIO,
        ];
    }
}
