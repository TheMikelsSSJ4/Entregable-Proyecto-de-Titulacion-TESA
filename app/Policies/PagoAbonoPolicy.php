<?php

namespace App\Policies;

use App\Models\PagoAbono;
use App\Models\Usuario;

/**
 * Pagos y abonos (RF-04): los registra Contabilidad o Gerencia;
 * sólo Gerencia puede revertir un abono registrado por error.
 */
class PagoAbonoPolicy
{
    public function create(Usuario $usuario): bool
    {
        return $usuario->accedeAFinanzas();
    }

    public function delete(Usuario $usuario, PagoAbono $pagoAbono): bool
    {
        return $usuario->esGerente();
    }
}
