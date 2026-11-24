<?php

namespace App\Enums;

/**
 * Perfiles del sistema. Administrador General tiene acceso absoluto (Gate::before);
 * Gerente, Contador y Vendedor son los de la Tabla 1 del documento.
 */
enum RolNombre: string
{
    case Administrador = 'Administrador General';
    case Gerente = 'Gerente';
    case Contador = 'Contador';
    case Vendedor = 'Vendedor';

    public function descripcion(): string
    {
        return match ($this) {
            self::Administrador => 'Acceso total: configuración, usuarios y todos los módulos.',
            self::Gerente => 'Supervisa cartera y transacciones, administra usuarios y catálogo.',
            self::Contador => 'Registra abonos, revisa alertas y genera reportes.',
            self::Vendedor => 'Emite cotizaciones y registra facturas iniciales.',
        };
    }
}
