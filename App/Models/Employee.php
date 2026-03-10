<?php
namespace App\Models;

use Core\Database;

class Employee
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM employees ORDER BY name');
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM employees WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO employees (name, email, department, position, is_system_user, user_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['email'] ?? '') ?: null,
            trim($data['department'] ?? '') ?: null,
            trim($data['position'] ?? '') ?: null,
            !empty($data['is_system_user']) ? 1 : 0,
            isset($data['user_id']) ? (int) $data['user_id'] : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE employees SET name = ?, email = ?, department = ?, position = ?, is_system_user = ?, user_id = ? WHERE id = ?');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['email'] ?? '') ?: null,
            trim($data['department'] ?? '') ?: null,
            trim($data['position'] ?? '') ?: null,
            !empty($data['is_system_user']) ? 1 : 0,
            isset($data['user_id']) ? (int) $data['user_id'] : null,
            $id,
        ]);
    }

    public static function findUserById(int $userId): ?object
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function delete(int $id): void
    {
        $db = Database::getInstance();
        $db->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
    }
}
