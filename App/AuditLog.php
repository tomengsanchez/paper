<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Generic audit log for entity history (creation, edits, status changes, etc.).
 *
 * Usage:
 *  - AuditLog::record('profile', $id, 'created');
 *  - AuditLog::record('profile', $id, 'updated', ['full_name' => ['from' => 'Old', 'to' => 'New']]);
 */
class AuditLog
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function record(string $entityType, int $entityId, string $action, array $changes = []): void
    {
        $db = self::db();
        $createdBy = Auth::id();
        $json = $changes ? json_encode($changes) : null;
        $stmt = $db->prepare('INSERT INTO audit_log (entity_type, entity_id, action, changes, created_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$entityType, $entityId, $action, $json, $createdBy]);
    }

    /**
     * History for a given entity, newest first.
     *
     * @return array<int,object> entries with properties: id, entity_type, entity_id, action, changes (array), created_at, created_by, created_by_name
     */
    public static function for(string $entityType, int $entityId, int $limit = 50): array
    {
        $db = self::db();
        $limit = max(1, min(200, (int) $limit));
        $sql = "
            SELECT a.*, u.username AS created_by_name
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.entity_type = ? AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT {$limit}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$entityType, $entityId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($rows as $row) {
            $row->changes = $row->changes ? (json_decode($row->changes, true) ?: []) : [];
        }
        return $rows;
    }
}

