<?php
declare(strict_types=1);

namespace Payroll;

use RuntimeException;

final class Money
{
    public static function cents(string $amount): int
    {
        if (!preg_match('/^(-?)(\d{1,12})(?:\.(\d{1,2}))?$/D', trim($amount), $parts)) {
            throw new RuntimeException('Invalid monetary value in payroll data.');
        }
        $cents = ((int) $parts[2] * 100) + (int) str_pad($parts[3] ?? '', 2, '0');
        return $parts[1] === '-' ? -$cents : $cents;
    }

    public static function number(int $cents): float
    {
        return $cents / 100;
    }
}
