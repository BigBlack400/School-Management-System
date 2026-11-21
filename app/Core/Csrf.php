<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function validateRequest(): void
    {
        $provided = $_POST['_csrf'] ?? '';
        $stored = $_SESSION['_csrf'] ?? '';
        if (!is_string($provided) || !is_string($stored) || $stored === '' || !hash_equals($stored, $provided)) {
            http_response_code(419);
            exit('Your session expired. Reload the page and try again.');
        }
    }
}
