<?php

/*
|--------------------------------------------------------------------------
| Parámetros contables de Privileg Distribuciones
|--------------------------------------------------------------------------
|
| Valores de negocio usados por FacturacionService y CarteraService.
| Se leen desde el .env para poder ajustarlos sin tocar el código.
|
*/

return [

    /*
     | Valores iniciales de la empresa. Los datos vigentes se administran desde el
     | módulo "Configuración de empresa" (tabla empresa_configuracion).
     */
    'empresa_defecto' => [
        'razon_social' => 'Privileg Distribuciones Ltda. S.A.',
        'ruc' => '1891700000001',
        'direccion' => 'Ambato, Tungurahua - Ecuador',
        'telefono' => null,
        'correo' => null,
    ],

    /*
     | Serie SRI: establecimiento y punto de emisión (formato 001-001-000000001).
     */
    'establecimiento' => env('FACTURA_ESTABLECIMIENTO', '001'),
    'punto_emision' => env('FACTURA_PUNTO_EMISION', '001'),

    /*
     | Tarifas de IVA vigentes en Ecuador (porcentaje). La primera es la general.
     */
    'tarifas_iva' => [15, 5, 0],

    /*
     | Plazos de crédito ofrecidos en el formulario de facturación (días).
     */
    'plazos_credito' => [15, 30, 45, 60, 90],
    'plazo_credito_defecto' => 30,

    /*
     | Días antes del vencimiento en que una factura se considera "por vencer"
     | y genera alerta preventiva en el Dashboard y en Cartera.
     */
    'dias_alerta_previa' => (int) env('CARTERA_DIAS_ALERTA_PREVIA', 7),

    /*
     | Tramos de antigüedad de la cartera vencida (días de mora).
     */
    'tramos_mora' => [
        ['desde' => 1, 'hasta' => 30, 'etiqueta' => '1 – 30 días'],
        ['desde' => 31, 'hasta' => 60, 'etiqueta' => '31 – 60 días'],
        ['desde' => 61, 'hasta' => 90, 'etiqueta' => '61 – 90 días'],
        ['desde' => 91, 'hasta' => null, 'etiqueta' => 'Más de 90 días'],
    ],

    /*
     | Filas por página que se pueden elegir en los listados. La elección se
     | recuerda durante la sesión del usuario y aplica a todos los listados.
     */
    'filas_por_pagina' => [10, 20, 50, 100],
    'filas_por_pagina_defecto' => 10,

    /*
     | Días de validez impresos en el PDF de una cotización.
     */
    'validez_cotizacion_dias' => 15,

];
