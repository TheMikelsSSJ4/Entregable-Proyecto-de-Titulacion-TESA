<?php

namespace App\Enums;

enum MetodoPago: string
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Deposito = 'deposito';
    case Cheque = 'cheque';
    case Tarjeta = 'tarjeta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia bancaria',
            self::Deposito => 'Depósito',
            self::Cheque => 'Cheque',
            self::Tarjeta => 'Tarjeta',
        };
    }
}
