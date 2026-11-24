<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateClienteRequest extends StoreClienteRequest
{
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente instanceof Cliente && ($this->user()?->can('update', $cliente) ?? false);
    }

    protected function reglaUnica(): Unique
    {
        /** @var Cliente $cliente */
        $cliente = $this->route('cliente');

        return Rule::unique('clientes', 'ruc_cedula')->ignore($cliente->id);
    }
}
