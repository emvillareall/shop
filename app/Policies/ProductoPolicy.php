<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    public function before(User $user): ?bool
    {
        if (! $user->estaActivo()) {
            return false;
        }

        return $user->tieneAccesoAdministrativoCompleto() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->puede_ver_productos;
    }

    public function view(User $user, Producto $producto): bool
    {
        return $user->puede_ver_productos;
    }

    public function create(User $user): bool
    {
        return $user->puede_crear_productos;
    }

    public function update(User $user, Producto $producto): bool
    {
        return $user->puede_editar_productos;
    }

    public function delete(User $user, Producto $producto): bool
    {
        return false;
    }
}
