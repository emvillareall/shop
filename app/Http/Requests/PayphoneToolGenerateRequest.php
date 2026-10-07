<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayphoneToolGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'string',
                'regex:/\A\d+(?:[.,]\d{1,2})?\z/D',
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'modalidad' => [
                'required',
                'string',
                Rule::in(['habitual', 'sin_intereses_3', 'sin_intereses_6']),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('modalidad') || trim((string) $this->input('modalidad')) === '') {
            $this->merge(['modalidad' => 'habitual']);
        }
    }
}
