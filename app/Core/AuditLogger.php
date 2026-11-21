<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class AuditLogger
{
    /** @param array<string, mixed>|null $metadata */
    public static function record(?int $userId, string $action, string $entityType, ?int $entityId = null, ?array $metadata = null): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata, created_at)
             VALUES (:user_id, :action, :entity_type, :entity_id, :ip, :user_agent, :metadata, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'metadata' => $metadata === null ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    private function __construct()
    {
    }
}
