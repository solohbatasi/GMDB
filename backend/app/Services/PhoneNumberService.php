<?php

namespace App\Services;

use InvalidArgumentException;

class PhoneNumberService
{
    public function normalizeKenyanMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            throw new InvalidArgumentException('Please provide a valid phone number.');
        }

        if (str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        } elseif (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            $digits = '254'.$digits;
        } elseif (str_starts_with($digits, '2540')) {
            $digits = '254'.substr($digits, 4);
        }

        if (! preg_match('/\A254(7|1)\d{8}\z/', $digits)) {
            throw new InvalidArgumentException('Please provide a valid Kenyan mobile number.');
        }

        return $digits;
    }
}
