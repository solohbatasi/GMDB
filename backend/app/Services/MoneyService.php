<?php

namespace App\Services;

class MoneyService
{
    public function decimalToCents(string|int|float|null $amount): int
    {
        $amount = trim((string) ($amount ?? '0'));

        if ($amount === '') {
            return 0;
        }

        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '+-');
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');
        $fraction = substr(str_pad(preg_replace('/\D/', '', $fraction), 2, '0'), 0, 2);
        $cents = ((int) preg_replace('/\D/', '', $whole)) * 100 + (int) $fraction;

        return $negative ? -$cents : $cents;
    }

    public function centsToDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
