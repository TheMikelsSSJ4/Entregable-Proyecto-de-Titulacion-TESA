<?php

namespace App\Enums;

enum EstadoUsuario: string
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Inactivo => 'Inactivo',
        };
    }

    public function tono(): string
    {
        return match ($this) {
            self::Activo => 'menta',
            self::Inactivo => 'niebla',
        };
    }
}
