<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\Usuario;

/**
 * Catálogo de productos: todos lo consultan para cotizar y facturar;
 * sólo Gerencia (y el Administrador General) define precios.
 */
class ProductoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->esGerente();
    }

    public function update(Usuario $usuario, Producto $producto): bool
    {
        return $usuario->esGerente();
    }

    public function delete(Usuario $usuario, Producto $producto): bool
    {
        return $usuario->esGerente();
    }
}
