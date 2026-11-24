<?php

namespace App\Enums;

/**
 * Estado persistido de la factura. La condición "vencida" no se guarda:
 * se deriva comparando fecha_vencimiento con la fecha actual (ver Factura::diasMora()).
 */
enum EstadoFactura: string
{
    case Pendiente = 'pendiente';
    case Parcial = 'parcial';
    case Pagada = 'pagada';
    case Anulada = 'anulada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Parcial => 'Parcial',
            self::Pagada => 'Pagada',
            self::Anulada => 'Anulada',
        };
    }

    public function tono(): string
    {
        return match ($this) {
            self::Pendiente => 'oro',
            self::Parcial => 'cian',
            self::Pagada => 'menta',
            self::Anulada => 'niebla',
        };
    }

    /**
     * Estados que todavía tienen saldo por cobrar.
     *
     * @return list<string>
     */
    public static function abiertos(): array
    {
        return [self::Pendiente->value, self::Parcial->value];
    }
}
