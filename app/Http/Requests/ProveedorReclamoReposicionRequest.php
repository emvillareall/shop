<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorReclamoReposicionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('administracion-completa'));
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'cantidad_aceptada' => ['required', 'integer', 'min:0'],
            'cantidad_danada' => ['required', 'integer', 'min:0'],
            'cantidad_incorrecta' => ['required', 'integer', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
