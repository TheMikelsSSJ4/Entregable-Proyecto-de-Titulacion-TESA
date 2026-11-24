<?php

namespace App\Policies;

use App\Enums\EstadoFactura;
use App\Models\Factura;
use App\Models\Usuario;

/**
 * Transacciones (ventas al contado y a crédito).
 * - Gerente y Contador: ven y anulan cualquier factura.
 * - Vendedor: registra facturas y consulta las que vendió o registró.
 */
class FacturaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Factura $factura): bool
    {
        return $usuario->accedeAFinanzas()
            || $factura->usuario_id === $usuario->id
            || $factura->vendedor_id === $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function anular(Usuario $usuario, Factura $factura): bool
    {
        return $usuario->accedeAFinanzas() && $factura->estado !== EstadoFactura::Anulada;
    }
}
