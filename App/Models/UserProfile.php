<?php
namespace App\Models;

use Core\Database;

class UserProfile
{
    protected static string $table = 'user_profiles';

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('
            SELECT up.id, up.name, up.user_id, up.role_id, u.username, r.name as role_name
            FROM user_profiles up
            LEFT JOIN users u ON u.id = up.user_id
            LEFT JOIN roles r ON r.id = up.role_id
            ORDER BY up.id DESC
        ');
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = self::db()->prepare('
            SELECT up.id, up.name, up.user_id, up.role_id, u.username, r.name as role_name
            FROM user_profiles up
            LEFT JOIN users u ON u.id = up.user_id
            LEFT JOIN roles r ON r.id = up.role_id
            WHERE up.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function userIdLinked(int $userId, ?int $excludeId = null): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM user_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return false;
        if ($excludeId && (int) $row['id'] === $excludeId) return false;
        return true;
    }

    public static function create(array $data): int
    {
        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId && self::userIdLinked($userId)) {
            throw new \RuntimeException('User is already linked to another profile.');
        }
        $stmt = self::db()->prepare('INSERT INTO user_profiles (name, user_id, role_id) VALUES (?, ?, ?)');
        $stmt->execute([
            trim($data['name'] ?? ''),
            $userId ?: null,
            ($rid = (int) ($data['role_id'] ?? 0)) ? $rid : null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId && self::userIdLinked($userId, $id)) {
            throw new \RuntimeException('User is already linked to another profile.');
        }
        $stmt = self::db()->prepare('UPDATE user_profiles SET name = ?, user_id = ?, role_id = ? WHERE id = ?');
        $stmt->execute([
            trim($data['name'] ?? ''),
            $userId ?: null,
            ($rid = (int) ($data['role_id'] ?? 0)) ? $rid : null,
            $id,
        ]);
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM user_profiles WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function getUsersForDropdown(?int $excludeProfileId = null): array
    {
        $db = self::db();
        $all = $db->query('SELECT id, username FROM users ORDER BY username')->fetchAll(\PDO::FETCH_OBJ);
        $linked = $db->query('SELECT user_id FROM user_profiles WHERE user_id IS NOT NULL')->fetchAll(\PDO::FETCH_COLUMN);
        $currentUserId = null;
        if ($excludeProfileId) {
            $row = $db->prepare('SELECT user_id FROM user_profiles WHERE id = ?');
            $row->execute([$excludeProfileId]);
            $currentUserId = $row->fetchColumn();
        }
        $linkedOther = array_flip(array_map('intval', $linked));
        return array_values(array_filter($all, function ($u) use ($linkedOther, $currentUserId) {
            $id = (int) $u->id;
            if ($currentUserId && $id === (int) $currentUserId) return true;
            return !isset($linkedOther[$id]);
        }));
    }
}
