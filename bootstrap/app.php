<?php

use App\Exceptions\OperacionNoPermitidaException;
use App\Http\Middleware\EnsureUsuarioActivo;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'usuario.activo' => EnsureUsuarioActivo::class,
        ]);

        // Preferencia de tema (claro/oscuro): la escribe el navegador, no se cifra.
        $middleware->encryptCookies(except: ['privileg_tema']);

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Reglas de negocio contables: se informan al usuario sin perder lo digitado.
        $exceptions->render(function (OperacionNoPermitidaException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        // Violación de clave foránea (ON DELETE RESTRICT) en PostgreSQL: SQLSTATE 23503.
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($e->getCode() !== '23503' || $request->expectsJson()) {
                return null;
            }

            return back()->with('error', 'No se puede eliminar el registro porque tiene documentos asociados (integridad referencial).');
        });
    })->create();
