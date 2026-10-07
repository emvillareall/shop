<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'acceso_administrativo_completo' => 'boolean',
        'puede_ver_productos' => 'boolean',
        'puede_crear_productos' => 'boolean',
        'puede_editar_productos' => 'boolean',
    ];

    public function estaActivo(): bool
    {
        return (string) $this->estado_user === '1';
    }

    public function tieneAccesoAdministrativoCompleto(): bool
    {
        return $this->estaActivo() && $this->acceso_administrativo_completo;
    }
}
