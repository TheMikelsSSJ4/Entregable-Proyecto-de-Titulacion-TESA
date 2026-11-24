<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida cédula (10 dígitos, módulo 10) o RUC (13 dígitos) ecuatorianos.
 *
 * - Cédula / RUC de persona natural: tercer dígito 0-5 y dígito verificador módulo 10.
 * - RUC de sociedad pública (6) o privada (9): se valida estructura, provincia y
 *   establecimiento. El SRI dejó de garantizar el dígito verificador módulo 11 en
 *   los RUC emitidos recientemente, por lo que no se exige para evitar rechazar RUC reales.
 */
class IdentificacionEcuatoriana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valor = (string) $value;

        if (! preg_match('/^\d{10}(\d{3})?$/', $valor)) {
            $fail('La :attribute debe tener 10 dígitos (cédula) o 13 dígitos (RUC), sólo números.');

            return;
        }

        $provincia = (int) substr($valor, 0, 2);

        if (! (($provincia >= 1 && $provincia <= 24) || $provincia === 30)) {
            $fail('Los dos primeros dígitos de la :attribute no corresponden a una provincia válida.');

            return;
        }

        $tercerDigito = (int) $valor[2];

        if (strlen($valor) === 10) {
            if ($tercerDigito >= 6 || ! self::cedulaValida($valor)) {
                $fail('La cédula ingresada no es válida (dígito verificador incorrecto).');
            }

            return;
        }

        if (substr($valor, 10, 3) === '000') {
            $fail('El RUC debe terminar en un código de establecimiento válido (por ejemplo 001).');

            return;
        }

        if ($tercerDigito < 6) {
            if (! self::cedulaValida(substr($valor, 0, 10))) {
                $fail('El RUC de persona natural no corresponde a una cédula válida.');
            }

            return;
        }

        if (! in_array($tercerDigito, [6, 9], true)) {
            $fail('El tercer dígito del RUC no es válido.');
        }
    }

    /**
     * Algoritmo módulo 10 del Registro Civil del Ecuador.
     */
    public static function cedulaValida(string $cedula): bool
    {
        if (! preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        foreach ($coeficientes as $posicion => $coeficiente) {
            $producto = (int) $cedula[$posicion] * $coeficiente;
            $suma += $producto >= 10 ? $producto - 9 : $producto;
        }

        $verificador = (10 - ($suma % 10)) % 10;

        return $verificador === (int) $cedula[9];
    }
}
