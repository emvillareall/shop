<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorReclamoLineaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('administracion-completa'));
    }

    public function rules(): array
    {
        return [
            'compra_recepcion_item_id' => ['required', 'integer', 'min:1'],
            'tipo_incidencia' => ['required', Rule::in(['DANADA', 'INCORRECTA', 'FALTANTE'])],
            'cantidad_reclamada' => ['required', 'integer', 'min:1'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
