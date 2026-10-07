<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class AmountInCents
{
    public static function parse(string|int $value): int
    {
        $normalized = trim((string) $value);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            throw new InvalidArgumentException('Monto con separadores ambiguos.');
        }

        $normalized = str_replace(',', '.', $normalized);

        if (!preg_match('/\A\d+(?:\.\d{1,2})?\z/D', $normalized)) {
            throw new InvalidArgumentException('Monto invalido.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');

        // Prevent integer overflow before applying the cents multiplier.
        if (strlen($whole) > 12) {
            throw new InvalidArgumentException('Monto demasiado grande.');
        }

        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($cents < 0) {
            throw new InvalidArgumentException('Monto invalido.');
        }

        return $cents;
    }

    public static function parsePercentHundredths(string|int $value): int
    {
        $normalized = trim((string) $value);

        if (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!preg_match('/\A\d{1,3}(?:\.\d{1,2})?\z/D', $normalized)) {
            throw new InvalidArgumentException('Porcentaje invalido.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $hundredths = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($hundredths < 0 || $hundredths > 10000) {
            throw new InvalidArgumentException('Porcentaje fuera de rango.');
        }

        return $hundredths;
    }

    public static function percentage(int $cents, int $percentHundredths): int
    {
        if ($cents < 0 || $percentHundredths < 0) {
            throw new InvalidArgumentException('Valores monetarios invalidos.');
        }

        // percentHundredths / 10000 is the percentage multiplier.
        return intdiv(($cents * $percentHundredths) + 5000, 10000);
    }

    public static function format(int $cents): string
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Monto invalido.');
        }

        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
