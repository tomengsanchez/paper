<?php
namespace App\Models;

use Core\Database;

class GrievanceProgressLevel
{
    protected static string $table = 'grievance_progress_levels';

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT * FROM grievance_progress_levels ORDER BY sort_order, name');
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = self::db()->prepare('SELECT * FROM grievance_progress_levels WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $stmt = $db->prepare('INSERT INTO grievance_progress_levels (name, description, sort_order) VALUES (?, ?, ?)');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['description'] ?? ''),
            (int) ($data['sort_order'] ?? 0),
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = self::db()->prepare('UPDATE grievance_progress_levels SET name = ?, description = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['description'] ?? ''),
            (int) ($data['sort_order'] ?? 0),
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM grievance_progress_levels WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
