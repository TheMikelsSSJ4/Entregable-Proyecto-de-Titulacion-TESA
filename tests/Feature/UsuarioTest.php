<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolNombre;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerente_crea_usuario_con_contrasena_cifrada(): void
    {
        $gerente = $this->gerente();
        $rolContador = Rol::firstOrCreate(['nombre' => RolNombre::Contador->value]);

        $this->actingAs($gerente)->post(route('usuarios.store'), [
            'nombre_completo' => 'Ana Torres',
            'email' => 'Ana.Contador@Privileg.com',
            'rol_id' => $rolContador->id,
            'estado' => 'activo',
            'password' => 'Segura2026x',
            'password_confirmation' => 'Segura2026x',
        ])->assertRedirect(route('usuarios.index'));

        $usuario = Usuario::where('email', 'ana.contador@privileg.com')->firstOrFail();
        $this->assertTrue($usuario->esContador());
        $this->assertNotSame('Segura2026x', $usuario->password);
        $this->assertTrue(Hash::check('Segura2026x', $usuario->password));
    }

    public function test_rechaza_contrasena_debil(): void
    {
        $this->actingAs($this->gerente())->post(route('usuarios.store'), [
            'nombre_completo' => 'Usuario Débil',
            'email' => 'debil@privileg.com',
            'rol_id' => Rol::firstOrCreate(['nombre' => RolNombre::Vendedor->value])->id,
            'estado' => 'activo',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('usuarios', ['email' => 'debil@privileg.com']);
    }

    public function test_gerente_no_puede_desactivarse_ni_cambiar_su_rol(): void
    {
        $gerente = $this->gerente();
        $rolVendedor = Rol::firstOrCreate(['nombre' => RolNombre::Vendedor->value]);

        $this->actingAs($gerente)->put(route('usuarios.update', $gerente), [
            'nombre_completo' => $gerente->nombre_completo,
            'email' => $gerente->email,
            'rol_id' => $rolVendedor->id,
            'estado' => 'inactivo',
        ])->assertSessionHasErrors(['rol_id', 'estado']);

        $this->assertSame(EstadoUsuario::Activo, $gerente->fresh()->estado);
    }

    public function test_actualizar_sin_contrasena_conserva_la_anterior(): void
    {
        $gerente = $this->gerente();
        $vendedor = $this->vendedor();
        $hashAnterior = $vendedor->password;

        $this->actingAs($gerente)->put(route('usuarios.update', $vendedor), [
            'nombre_completo' => 'Nombre Actualizado',
            'email' => $vendedor->email,
            'rol_id' => $vendedor->rol_id,
            'estado' => 'inactivo',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('usuarios.index'));

        $vendedor->refresh();
        $this->assertSame('Nombre Actualizado', $vendedor->nombre_completo);
        $this->assertSame(EstadoUsuario::Inactivo, $vendedor->estado);
        $this->assertSame($hashAnterior, $vendedor->password);
    }
}
