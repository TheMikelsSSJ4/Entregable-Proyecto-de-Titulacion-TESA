<?php

namespace App\Providers;

use App\Models\EmpresaConfiguracion;
use App\Models\Usuario;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as VistaBlade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('es');

        // Detecta consultas N+1 y atributos descartados durante el desarrollo.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Route::resourceVerbs([
            'create' => 'crear',
            'edit' => 'editar',
        ]);

        Paginator::defaultView('pagination.neo');

        $this->definirPermisos();

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('recuperacion', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        // Tema de color elegido por el usuario (cookie sin cifrar, la escribe el navegador).
        View::composer(['components.layouts.app', 'components.layouts.guest'], function (VistaBlade $vista): void {
            $vista->with('temaActual', request()->cookie('privileg_tema') === 'claro' ? 'claro' : 'oscuro');
        });

        // Nombre de la empresa en el pie del layout principal.
        View::composer('components.layouts.app', function (VistaBlade $vista): void {
            $vista->with('empresaActual', EmpresaConfiguracion::actual());
        });
    }

    private function definirPermisos(): void
    {
        // Super Admin: el Administrador General pasa todas las políticas y gates.
        // Las reglas contables (saldos, anulaciones) siguen protegidas en los servicios.
        Gate::before(fn (Usuario $usuario): ?bool => $usuario->esAdministrador() ? true : null);

        // Cartera y Reportes: Gerencia y Contabilidad.
        Gate::define('ver-cartera', fn (Usuario $usuario): bool => $usuario->accedeAFinanzas());
        Gate::define('ver-reportes', fn (Usuario $usuario): bool => $usuario->accedeAFinanzas());

        // Configuración de empresa: exclusivo del Administrador General (vía Gate::before).
        Gate::define('configurar-empresa', fn (Usuario $usuario): bool => false);
    }
}
