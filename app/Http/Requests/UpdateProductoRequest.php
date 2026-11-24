<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateProductoRequest extends StoreProductoRequest
{
    public function authorize(): bool
    {
        $producto = $this->route('producto');

        return $producto instanceof Producto && ($this->user()?->can('update', $producto) ?? false);
    }

    protected function reglaUnica(): Unique
    {
        /** @var Producto $producto */
        $producto = $this->route('producto');

        return Rule::unique('productos', 'codigo')->ignore($producto->id);
    }
}
