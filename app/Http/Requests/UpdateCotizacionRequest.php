<?php

namespace App\Http\Requests;

use App\Enums\EstadoCotizacion;
use App\Models\Cotizacion;
use Illuminate\Validation\Rule;

class UpdateCotizacionRequest extends StoreCotizacionRequest
{
    public function authorize(): bool
    {
        $cotizacion = $this->route('cotizacion');

        return $cotizacion instanceof Cotizacion && ($this->user()?->can('update', $cotizacion) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'estado' => ['required', Rule::enum(EstadoCotizacion::class)->only(EstadoCotizacion::editables())],
        ];
    }
}
