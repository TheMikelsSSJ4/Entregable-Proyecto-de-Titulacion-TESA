<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad HTTP para la aplicación servida en la LAN de la empresa.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ninguna página puede mostrarse en un marco, salvo las rutas que lo
        // permitan expresamente (vista previa de reportes: PermiteVistaEnMarco).
        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // En local, Vite (npm run dev), Herd y Boost inyectan scripts propios; la CSP
        // se aplica al desplegar en el servidor de la empresa (APP_ENV=production).
        // 'unsafe-eval' es requerido por Alpine.js para evaluar sus expresiones;
        // los scripts siguen limitados al propio servidor ('self').
        if (! app()->environment('local', 'testing') && ! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'"
            );
        }

        return $response;
    }
}
