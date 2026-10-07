<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorReclamoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('administracion-completa'));
    }

    public function rules(): array
    {
        return [
            'referencia_proveedor' => ['nullable', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:2000'],
            'lineas' => ['required', 'array', 'min:1', 'max:100'],
            'lineas.*.compra_recepcion_item_id' => ['required', 'integer', 'min:1'],
            'lineas.*.tipo_incidencia' => ['required', Rule::in(['DANADA', 'INCORRECTA', 'FALTANTE'])],
            'lineas.*.cantidad_reclamada' => ['required', 'integer', 'min:1'],
            'lineas.*.observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
