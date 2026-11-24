<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\RestablecerContrasenaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RecuperacionContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_enlaza_a_la_recuperacion(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'))
            ->assertSee('x-data="{ show: false }"', false);
    }

    public function test_envia_enlace_al_correo_registrado(): void
    {
        Notification::fake();
        $usuario = $this->contador();

        $this->post(route('password.email'), ['email' => strtoupper($usuario->email)])
            ->assertSessionHas('status');

        Notification::assertSentTo($usuario, RestablecerContrasenaNotification::class);
    }

    public function test_responde_igual_si_el_correo_no_existe(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nadie@privileg.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_restablece_la_contrasena_con_un_token_valido(): void
    {
        $usuario = $this->vendedor();
        $token = Password::createToken($usuario);

        $this->get(route('password.reset', ['token' => $token, 'email' => $usuario->email]))->assertOk();

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'NuevaClave2026',
            'password_confirmation' => 'NuevaClave2026',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NuevaClave2026', $usuario->fresh()->password));
    }

    public function test_rechaza_token_invalido(): void
    {
        $usuario = $this->vendedor();
        $hashAnterior = $usuario->password;

        $this->post(route('password.store'), [
            'token' => 'token-falso',
            'email' => $usuario->email,
            'password' => 'NuevaClave2026',
            'password_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasErrors('email');

        $this->assertSame($hashAnterior, $usuario->fresh()->password);
    }

    public function test_usuario_autenticado_no_ve_la_recuperacion(): void
    {
        $this->actingAs(Usuario::factory()->create())
            ->get(route('password.request'))
            ->assertRedirect(route('dashboard'));
    }
}
