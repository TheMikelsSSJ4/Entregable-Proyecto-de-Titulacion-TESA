<?php

namespace App\Http\Controllers;

use App\Enums\RolNombre;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Módulo de Seguridad y Usuarios (Gerencia y Administrador General).
 * Los usuarios no se eliminan: se desactivan para conservar la trazabilidad
 * de facturas y abonos registrados (ON DELETE RESTRICT).
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Usuario::class);

        return view('usuarios.index', [
            'usuarios' => Usuario::query()
                ->orderBy('estado')
                ->orderBy('nombre_completo')
                ->paginate($this->filasPorPagina($request)),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Usuario::class);

        return view('usuarios.create', [
            'usuario' => new Usuario,
            'roles' => $this->rolesAsignables(),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $usuario = Usuario::create($request->validated());

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario {$usuario->nombre_completo} creado correctamente.");
    }

    public function edit(Usuario $usuario): View
    {
        Gate::authorize('update', $usuario);

        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => $this->rolesAsignables(),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->safe()->except('password');

        if ($request->filled('password')) {
            $datos['password'] = $request->validated('password');
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario {$usuario->nombre_completo} actualizado.");
    }

    /**
     * Roles que el usuario actual puede asignar: sólo el Administrador General
     * puede otorgar el rol de Administrador General.
     *
     * @return Collection<int, Rol>
     */
    private function rolesAsignables(): Collection
    {
        return Rol::query()
            ->when(! auth()->user()->esAdministrador(), fn ($q) => $q->where('nombre', '!=', RolNombre::Administrador->value))
            ->orderBy('id')
            ->get();
    }
}
