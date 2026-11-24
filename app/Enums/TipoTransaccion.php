<?php

namespace App\Enums;

enum TipoTransaccion: string
{
    case Contado = 'contado';
    case Credito = 'credito';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Contado => 'Contado',
            self::Credito => 'Crédito',
        };
    }
}
