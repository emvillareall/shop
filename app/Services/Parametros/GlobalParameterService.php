<?php

namespace App\Services\Parametros;

use App\Models\Parametro;

class GlobalParameterService
{
    public function getNumber(string $name, float $default = 0): float
    {
        $value = Parametro::query()
            ->whereRaw('LOWER(nombre_parametro) = ?', [mb_strtolower($name)])
            ->value('valor_parametro');

        if ($value === null || $value === '') {
            return $default;
        }

        return (float) $value;
    }

    public function ensureDefaults(): void
    {
        $defaults = [
            'iva_general' => 0.0,
            'recargo_payphone' => 0.07,
            'recargo_paypal' => 0.0,
        ];

        foreach ($defaults as $name => $value) {
            Parametro::query()->firstOrCreate(
                ['nombre_parametro' => $name],
                [
                    'valor_parametro' => $value,
                    'estado_parametro' => '1',
                ]
            );
        }
    }
}

