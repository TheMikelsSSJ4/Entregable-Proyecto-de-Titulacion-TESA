<?php

namespace App\Policies;

use App\Enums\EstadoCotizacion;
use App\Enums\RolNombre;
use App\Models\Cotizacion;
use App\Models\Usuario;

/**
 * Cotizaciones (proformas): las emiten Vendedores y Gerencia.
 * Contabilidad puede consultarlas pero no generarlas.
 */
class CotizacionPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        return $usuario->accedeAFinanzas()
            || $cotizacion->usuario_id === $usuario->id
            || $cotizacion->vendedor_id === $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolNombre::Gerente, RolNombre::Vendedor);
    }

    public function update(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        if ($cotizacion->estado === EstadoCotizacion::Facturada) {
            return false;
        }

        return $usuario->esGerente()
            || ($usuario->esVendedor() && in_array($usuario->id, [$cotizacion->usuario_id, $cotizacion->vendedor_id], true));
    }
}
