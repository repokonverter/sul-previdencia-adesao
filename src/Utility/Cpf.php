<?php

declare(strict_types=1);

namespace App\Utility;

/**
 * CPF gravado só com dígitos e conferido pelos dígitos verificadores.
 */
final class Cpf
{
    /**
     * @return string|null os dígitos, ou null se não havia valor
     */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string)$value);

        return $digits === '' ? null : $digits;
    }

    public static function isValid(mixed $value): bool
    {
        $cpf = self::normalize($value);

        // Onze dígitos repetidos passam no cálculo, mas não são CPF.
        if ($cpf === null || strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        foreach ([9, 10] as $length) {
            $sum = 0;

            for ($i = 0; $i < $length; $i++) {
                $sum += (int)$cpf[$i] * ($length + 1 - $i);
            }

            $digit = ($sum * 10) % 11 % 10;

            if ($digit !== (int)$cpf[$length]) {
                return false;
            }
        }

        return true;
    }

    public static function format(?string $cpf): ?string
    {
        if ($cpf === null || strlen($cpf) !== 11) {
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
}
