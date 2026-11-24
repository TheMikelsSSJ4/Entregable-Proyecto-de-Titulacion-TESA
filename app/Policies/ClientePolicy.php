<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\Usuario;

/**
 * Directorio de clientes: todos los roles consultan y registran clientes
 * (el vendedor los necesita para cotizar). Sólo Gerencia puede eliminarlos,
 * y la base de datos lo impide si tienen documentos (ON DELETE RESTRICT).
 */
class ClientePolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Cliente $cliente): bool
    {
        return true;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Cliente $cliente): bool
    {
        return true;
    }

    public function delete(Usuario $usuario, Cliente $cliente): bool
    {
        return $usuario->esGerente();
    }
}
