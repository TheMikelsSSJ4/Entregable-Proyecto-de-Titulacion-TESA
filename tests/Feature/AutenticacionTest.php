<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_usuario_activo_inicia_sesion(): void
    {
        $usuario = $this->contador();

        $this->post(route('login.store'), [
            'email' => strtoupper($usuario->email),
            'password' => UsuarioFactory::PASSWORD,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $usuario = Usuario::factory()->inactivo()->create();

        $this->post(route('login.store'), [
            'email' => $usuario->email,
            'password' => UsuarioFactory::PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_contrasena_incorrecta_no_autentica(): void
    {
        $usuario = $this->contador();

        $this->post(route('login.store'), [
            'email' => $usuario->email,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $usuario = $this->contador();

        foreach (range(1, 5) as $intento) {
            $this->post(route('login.store'), ['email' => $usuario->email, 'password' => 'incorrecta']);
        }

        $this->post(route('login.store'), [
            'email' => $usuario->email,
            'password' => UsuarioFactory::PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_desactivado_durante_la_sesion_es_expulsado(): void
    {
        $usuario = $this->vendedor();
        $this->actingAs($usuario);

        $usuario->update(['estado' => EstadoUsuario::Inactivo]);

        $this->get(route('clientes.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_cierre_de_sesion(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
