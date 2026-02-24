<?php
namespace App\Models;

use Core\Database;

class GrievanceStatusLog
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function parseAttachments(?string $json): array
    {
        if (empty(trim($json ?? ''))) return [];
        $d = json_decode($json, true);
        return is_array($d) ? $d : [];
    }

    public static function byGrievance(int $grievanceId): array
    {
        $stmt = self::db()->prepare('
            SELECT l.*, u.username as created_by_name
            FROM grievance_status_log l
            LEFT JOIN users u ON u.id = l.created_by
            WHERE l.grievance_id = ?
            ORDER BY l.created_at DESC
        ');
        $stmt->execute([$grievanceId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function create(int $grievanceId, string $status, ?int $progressLevel, string $note, array $attachments, ?int $createdBy = null): int
    {
        $db = self::db();
        $stmt = $db->prepare('INSERT INTO grievance_status_log (grievance_id, status, progress_level, note, attachments, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$grievanceId, $status, $progressLevel, $note, json_encode($attachments), $createdBy]);
        return (int) $db->lastInsertId();
    }
}
