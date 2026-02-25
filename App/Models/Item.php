<?php
namespace App\Models;

use Core\Database;

class Item
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM items ORDER BY id DESC');
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];
    }

    public static function find(int $id): ?object
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO items (name, description) VALUES (?, ?)');
        $stmt->execute([$data['name'] ?? '', $data['description'] ?? '']);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE items SET name = ?, description = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$data['name'] ?? '', $data['description'] ?? '', $id]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM items WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
