<?php

namespace Tests\Unit;

use App\Rules\IdentificacionEcuatoriana;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IdentificacionEcuatorianaTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function identificacionesValidas(): array
    {
        return [
            'cédula Tungurahua' => ['1836809234'],
            'RUC persona natural' => ['1835607746001'],
            'RUC sociedad privada' => ['1893947597001'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function identificacionesInvalidas(): array
    {
        return [
            'dígito verificador incorrecto' => ['1836809235'],
            'longitud de 11 dígitos' => ['18368092341'],
            'contiene letras' => ['18368O9234'],
            'provincia inexistente' => ['2936809234'],
            'RUC con establecimiento 000' => ['1835607746000'],
            'RUC natural con cédula inválida' => ['1835607747001'],
            'tercer dígito 7 en RUC' => ['1873947597001'],
        ];
    }

    #[DataProvider('identificacionesValidas')]
    public function test_acepta_identificaciones_validas(string $valor): void
    {
        $this->assertNull($this->validar($valor));
    }

    #[DataProvider('identificacionesInvalidas')]
    public function test_rechaza_identificaciones_invalidas(string $valor): void
    {
        $this->assertNotNull($this->validar($valor));
    }

    private function validar(string $valor): ?string
    {
        $error = null;

        (new IdentificacionEcuatoriana)->validate('ruc_cedula', $valor, function (string $mensaje) use (&$error): void {
            $error = $mensaje;
        });

        return $error;
    }
}
