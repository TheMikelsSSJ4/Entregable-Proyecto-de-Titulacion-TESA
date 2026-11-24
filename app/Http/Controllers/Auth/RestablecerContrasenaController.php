<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RestablecerContrasenaRequest;
use App\Models\Usuario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class RestablecerContrasenaController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.restablecer-contrasena', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(RestablecerContrasenaRequest $request): RedirectResponse
    {
        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Usuario $usuario, #[\SensitiveParameter] string $password): void {
                $usuario->forceFill(['password' => $password])->save();

                event(new PasswordReset($usuario));
            },
        );

        if ($estado !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'El enlace no es válido o ya caducó. Solicite uno nuevo.']);
        }

        return redirect()->route('login')
            ->with('status', 'Su contraseña fue restablecida. Ya puede iniciar sesión.');
    }
}
