<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Environment;
use App\Core\PasswordHasher;
use App\Core\View;

$root = dirname(__DIR__);
$vendorAutoload = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    require $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'helpers.php';
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Csrf.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'View.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'PasswordHasher.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Environment.php';

if (is_file($vendorAutoload)) {
    if (!class_exists('Dompdf\\Dompdf') || !class_exists('Symfony\\Component\\Mailer\\Mailer')) {
        throw new RuntimeException('Composer runtime autoload smoke test failed.');
    }
}

session_start();

$password = 'smoke-test-password-with-enough-length';
$hash = PasswordHasher::hash($password);
if (!PasswordHasher::verify($password, $hash) || PasswordHasher::verify('not-the-password', $hash)) {
    throw new RuntimeException('Password hashing smoke test failed.');
}

$csrf = Csrf::token();
if (strlen($csrf) !== 64 || !hash_equals($csrf, Csrf::token())) {
    throw new RuntimeException('CSRF token smoke test failed.');
}

putenv('SMS_SMOKE_ENV_VALUE=available');
if (Environment::get('SMS_SMOKE_ENV_VALUE') !== 'available') {
    throw new RuntimeException('Environment lookup smoke test failed.');
}
putenv('SMS_SMOKE_ENV_VALUE');

ob_start();
View::render('auth/login', ['csrf' => $csrf, 'error' => '<script>alert(1)</script>']);
$html = (string) ob_get_clean();
if (str_contains($html, '<script>alert(1)</script>') || !str_contains($html, '&lt;script&gt;')) {
    throw new RuntimeException('View output escaping smoke test failed.');
}

$sourceDirectories = new RecursiveDirectoryIterator(dirname(__DIR__), FilesystemIterator::SKIP_DOTS);
$withoutVendor = new RecursiveCallbackFilterIterator(
    $sourceDirectories,
    static fn (SplFileInfo $file): bool => !$file->isDir() || $file->getFilename() !== 'vendor'
);
foreach (new RecursiveIteratorIterator($withoutVendor) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
        if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;
        }
        $literal = substr($token[1], 1, -1);
        preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', $literal, $matches);
        foreach (array_count_values($matches[0]) as $placeholder => $count) {
            if ($count > 1) {
                throw new RuntimeException(sprintf(
                    'Repeated named PDO placeholder %s in %s.',
                    $placeholder,
                    $file->getPathname()
                ));
            }
        }
    }
}

fwrite(STDOUT, "Smoke checks passed: password hashing, CSRF token generation, view escaping, and PDO placeholder uniqueness.\n");
