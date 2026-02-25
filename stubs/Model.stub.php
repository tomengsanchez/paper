<?php
namespace App\Models;

use Core\Database;

/**
 * Copy to App/Models/ResourceName.php
 * Replace ResourceName and table name "resource_names" with your resource.
 */
class ResourceName
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM resource_names ORDER BY id DESC');
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];
    }

    public static function find(int $id): ?object
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM resource_names WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO resource_names (name, created_at) VALUES (?, NOW())');
        $stmt->execute([$data['name'] ?? '']);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE resource_names SET name = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$data['name'] ?? '', $id]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM resource_names WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
