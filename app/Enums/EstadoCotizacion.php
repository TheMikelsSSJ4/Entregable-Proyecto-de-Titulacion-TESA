<?php

namespace App\Enums;

enum EstadoCotizacion: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Facturada = 'facturada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
            self::Facturada => 'Facturada',
        };
    }

    public function tono(): string
    {
        return match ($this) {
            self::Pendiente => 'oro',
            self::Aprobada => 'cian',
            self::Rechazada => 'cobre',
            self::Facturada => 'menta',
        };
    }

    /**
     * Estados que un usuario puede asignar manualmente (Facturada sólo la asigna el sistema).
     *
     * @return list<self>
     */
    public static function editables(): array
    {
        return [self::Pendiente, self::Aprobada, self::Rechazada];
    }

    public function puedeFacturarse(): bool
    {
        return in_array($this, [self::Pendiente, self::Aprobada], true);
    }
}
