<?php
declare(strict_types=1);

namespace App\Core;

final class Environment
{
    public static function get(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return is_string($value) ? $value : $default;
    }

    private function __construct()
    {
    }
}
