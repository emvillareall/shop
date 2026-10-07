<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompraRecepcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modo' => ['nullable', Rule::in(['manual', 'todo'])],
            'idempotency_key' => ['required', 'uuid'],
            'observacion' => ['nullable', 'string', 'max:1000'],
            'items' => ['required_if:modo,manual', 'array', 'max:500'],
            'items.*.compra_item_id' => ['required', 'integer', 'min:1'],
            'items.*.cantidad_aceptada' => ['required', 'integer', 'min:0'],
            'items.*.cantidad_danada' => ['required', 'integer', 'min:0'],
            'items.*.cantidad_incorrecta' => ['required', 'integer', 'min:0'],
            'items.*.cantidad_faltante_declarada' => ['required', 'integer', 'min:0'],
            'items.*.cierra_item' => ['required', 'boolean'],
            'items.*.observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'idempotency_key.uuid' => 'La clave de envío no es válida. Recarga la pantalla e inténtalo nuevamente.',
            'items.required_if' => 'Debes indicar al menos una línea de recepción.',
            'items.*.cantidad_aceptada.integer' => 'Las cantidades aceptadas deben ser enteros.',
            'items.*.cantidad_aceptada.min' => 'Las cantidades aceptadas no pueden ser negativas.',
            'items.*.cantidad_danada.integer' => 'Las cantidades dañadas deben ser enteros.',
            'items.*.cantidad_danada.min' => 'Las cantidades dañadas no pueden ser negativas.',
            'items.*.cantidad_incorrecta.integer' => 'Las cantidades incorrectas deben ser enteros.',
            'items.*.cantidad_incorrecta.min' => 'Las cantidades incorrectas no pueden ser negativas.',
            'items.*.cantidad_faltante_declarada.integer' => 'Las cantidades faltantes deben ser enteros.',
            'items.*.cantidad_faltante_declarada.min' => 'Las cantidades faltantes no pueden ser negativas.',
            'items.*.cierra_item.boolean' => 'El cierre de línea no es válido.',
        ];
    }
}
