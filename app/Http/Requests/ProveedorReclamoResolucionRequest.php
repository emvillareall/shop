<?php

namespace App\Http\Requests;

use App\Models\ProveedorReclamoResolucion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorReclamoResolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('administracion-completa'));
    }

    public function rules(): array
    {
        return [
            'tipo_resolucion' => ['required', Rule::in(ProveedorReclamoResolucion::TIPOS_RESOLUCION)],
            'cantidad' => ['required', 'integer', 'min:1'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:2000'],
            'importe' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'moneda' => ['nullable', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
        ];
    }
}
