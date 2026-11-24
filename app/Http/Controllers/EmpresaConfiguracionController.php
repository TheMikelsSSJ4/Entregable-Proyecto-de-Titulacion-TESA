<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmpresaConfiguracionRequest;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Módulo de Configuración de Empresa (exclusivo del Administrador General).
 */
class EmpresaConfiguracionController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('configurar-empresa');

        return view('empresa.edit', ['empresa' => EmpresaConfiguracion::actual()]);
    }

    public function update(UpdateEmpresaConfiguracionRequest $request): RedirectResponse
    {
        $empresa = EmpresaConfiguracion::query()->find(EmpresaConfiguracion::ID_UNICO)
            ?? (new EmpresaConfiguracion)->forceFill(['id' => EmpresaConfiguracion::ID_UNICO]);

        $empresa->fill($request->validated())->save();

        return redirect()->route('empresa.edit')
            ->with('success', 'Datos de la empresa actualizados. Se aplicarán en todos los PDFs y Excels nuevos.');
    }
}
