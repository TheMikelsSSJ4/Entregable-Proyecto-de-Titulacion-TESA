<?php

namespace Tests;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function gerente(): Usuario
    {
        return Usuario::factory()->gerente()->create();
    }

    protected function contador(): Usuario
    {
        return Usuario::factory()->contador()->create();
    }

    protected function vendedor(): Usuario
    {
        return Usuario::factory()->vendedor()->create();
    }
}
