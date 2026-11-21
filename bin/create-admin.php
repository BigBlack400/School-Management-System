<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\PasswordHasher;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only run from the command line.\n");
    exit(1);
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$firstName = trim((string) ($argv[1] ?? ''));
$lastName = trim((string) ($argv[2] ?? ''));
$email = strtolower(trim((string) ($argv[3] ?? '')));
$password = (string) getenv('SMS_BOOTSTRAP_PASSWORD');

if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 14) {
    fwrite(STDERR, "Usage: set SMS_BOOTSTRAP_PASSWORD to a unique password (14+ chars), then run php bin/create-admin.php \"First\" \"Last\" admin@example.org\n");
    exit(1);
}

$db = Database::connection();
$db->beginTransaction();
try {
    $exists = $db->query(
        "SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id
         WHERE r.name = 'super_administrator' LIMIT 1"
    )->fetchColumn();
    if ($exists) {
        throw new RuntimeException('A super administrator already exists. Use the user administration workflow instead.');
    }

    $insert = $db->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, status, created_at)
         VALUES (:first_name, :last_name, :email, :password_hash, "active", CURRENT_TIMESTAMP)'
    );
    $insert->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password_hash' => PasswordHasher::hash($password),
    ]);
    $userId = (int) $db->lastInsertId();

    $assign = $db->prepare(
        "INSERT INTO user_roles (user_id, role_id)
         SELECT :user_id, id FROM roles WHERE name = 'super_administrator'"
    );
    $assign->execute(['user_id' => $userId]);
    $db->commit();
    fwrite(STDOUT, "Super administrator created. Remove SMS_BOOTSTRAP_PASSWORD from the environment now.\n");
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Bootstrap failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
