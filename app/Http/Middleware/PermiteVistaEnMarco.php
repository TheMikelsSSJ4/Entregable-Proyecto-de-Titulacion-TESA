<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite mostrar la respuesta dentro de un <iframe> del propio sistema (vista
 * previa de reportes). El resto de páginas sigue con X-Frame-Options: DENY.
 *
 * La vista previa es HTML estático: su CSP no admite scripts, formularios ni
 * recursos externos; sólo los estilos de la plantilla del PDF.
 */
class PermiteVistaEnMarco
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'"
        );

        return $response;
    }
}
