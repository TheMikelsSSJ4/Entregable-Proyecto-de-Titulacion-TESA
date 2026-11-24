<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EnviarEnlaceRecuperacionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * "Olvidé mi contraseña": envía el enlace de restablecimiento por correo.
 */
class RecuperarContrasenaController extends Controller
{
    public function create(): View
    {
        return view('auth.olvide-contrasena');
    }

    /**
     * La respuesta es siempre la misma, exista o no el correo, para no revelar
     * qué cuentas están registradas (enumeración de usuarios).
     */
    public function store(EnviarEnlaceRecuperacionRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña en los próximos minutos.');
    }
}
