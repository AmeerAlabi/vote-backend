<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * Six-digit one-time codes, generated with a CSPRNG and stored hashed.
 */
final class OneTimeCode
{
    public static function generate(): string
    {
        return (string) random_int(100000, 999999);
    }

    public static function hash(string $code): string
    {
        return Hash::make($code);
    }

    public static function matches(string $code, ?string $hash): bool
    {
        return $hash !== null && Hash::check($code, $hash);
    }
}
