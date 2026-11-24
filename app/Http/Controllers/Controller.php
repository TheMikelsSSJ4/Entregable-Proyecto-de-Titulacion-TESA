<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Filas por página de los listados (?por_pagina=20). Sólo se aceptan las
     * opciones de config('contable.filas_por_pagina'); la elección se guarda en
     * la sesión para que se mantenga al cambiar de página o de módulo.
     */
    protected function filasPorPagina(Request $request): int
    {
        $opciones = config('contable.filas_por_pagina');
        $solicitadas = $request->integer('por_pagina');

        if (in_array($solicitadas, $opciones, true)) {
            $request->session()->put('filas_por_pagina', $solicitadas);

            return $solicitadas;
        }

        $guardadas = (int) $request->session()->get('filas_por_pagina');

        return in_array($guardadas, $opciones, true)
            ? $guardadas
            : (int) config('contable.filas_por_pagina_defecto');
    }
}
