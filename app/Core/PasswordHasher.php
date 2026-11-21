<?php
declare(strict_types=1);

namespace App\Core;

final class PasswordHasher
{
    public static function hash(string $password): string
    {
        $algorithm = in_array('argon2id', password_algos(), true)
            ? PASSWORD_ARGON2ID
            : PASSWORD_DEFAULT;
        return password_hash($password, $algorithm);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function __construct()
    {
    }
}
