<?php

namespace App\Policies;

use App\Models\Usuario;

/**
 * Módulo de Seguridad y Usuarios: Gerencia y Administrador General (Gate::before).
 * Un Gerente no puede modificar cuentas de Administrador General.
 */
class UsuarioPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->esGerente();
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->esGerente();
    }

    public function update(Usuario $usuario, Usuario $objetivo): bool
    {
        return $usuario->esGerente() && ! $objetivo->esAdministrador();
    }
}
