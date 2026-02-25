<?php
namespace App\Models;

use Core\Database;

class GrievanceAttachment
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function byGrievance(int $grievanceId): array
    {
        $stmt = self::db()->prepare('
            SELECT * FROM grievance_attachments
            WHERE grievance_id = ?
            ORDER BY sort_order ASC, id ASC
        ');
        $stmt->execute([$grievanceId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = self::db()->prepare('SELECT * FROM grievance_attachments WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(int $grievanceId, string $title, string $description, string $filePath, int $sortOrder = 0): int
    {
        $db = self::db();
        $stmt = $db->prepare('INSERT INTO grievance_attachments (grievance_id, title, description, file_path, sort_order) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$grievanceId, $title, $description, $filePath, $sortOrder]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, string $title, string $description, ?string $filePath = null): void
    {
        if ($filePath !== null) {
            $stmt = self::db()->prepare('UPDATE grievance_attachments SET title = ?, description = ?, file_path = ? WHERE id = ?');
            $stmt->execute([$title, $description, $filePath, $id]);
        } else {
            $stmt = self::db()->prepare('UPDATE grievance_attachments SET title = ?, description = ? WHERE id = ?');
            $stmt->execute([$title, $description, $id]);
        }
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM grievance_attachments WHERE id = ?')->execute([$id]);
    }
}
