<?php
namespace App\Models;

use Core\Model;
use Core\Database;

class UserProfile extends Model
{
    protected static string $entityType = 'user_profile';
    protected static array $attributes = [
        'name' => 'string',
        'role_id' => 'int',
        'user_id' => 'int',
    ];

    public static function all(): array
    {
        $db = self::db();
        $nameId = self::getAttributeId('name');
        if (!$nameId) return [];

        $stmt = $db->query('SELECT id FROM eav_entities WHERE entity_type = "user_profile" ORDER BY id DESC');
        $entities = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $result = [];

        foreach ($entities as $eid) {
            $roleId = self::getValue($eid, 'role_id');
            $userId = self::getValue($eid, 'user_id');
            $roleName = null;
            $username = null;
            if ($roleId) {
                $r = $db->prepare('SELECT name FROM roles WHERE id = ?');
                $r->execute([$roleId]);
                $roleName = $r->fetchColumn();
            }
            if ($userId) {
                $u = $db->prepare('SELECT username FROM users WHERE id = ?');
                $u->execute([$userId]);
                $username = $u->fetchColumn();
            }
            $result[] = (object) [
                'id' => $eid,
                'name' => self::getValue($eid, 'name'),
                'role_id' => $roleId,
                'role_name' => $roleName,
                'user_id' => $userId,
                'username' => $username,
            ];
        }
        return $result;
    }

    public static function find(int $id): ?object
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM eav_entities WHERE id = ? AND entity_type = "user_profile"');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) return null;

        $roleId = self::getValue($id, 'role_id');
        $userId = self::getValue($id, 'user_id');
        $roleName = null;
        $username = null;
        if ($roleId) {
            $r = $db->prepare('SELECT name FROM roles WHERE id = ?');
            $r->execute([$roleId]);
            $roleName = $r->fetchColumn();
        }
        if ($userId) {
            $u = $db->prepare('SELECT username FROM users WHERE id = ?');
            $u->execute([$userId]);
            $username = $u->fetchColumn();
        }

        return (object) [
            'id' => $id,
            'name' => self::getValue($id, 'name'),
            'role_id' => $roleId,
            'role_name' => $roleName,
            'user_id' => $userId,
            'username' => $username,
        ];
    }

    public static function userIdLinked(int $userId, ?int $excludeId = null): bool
    {
        $db = self::db();
        $attrId = self::getAttributeId('user_id');
        if (!$attrId) return false;
        $stmt = $db->prepare('SELECT v.entity_id FROM eav_values v 
            JOIN eav_entities e ON v.entity_id = e.id 
            WHERE v.attribute_id = ? AND v.value = ? AND e.entity_type = "user_profile"');
        $stmt->execute([$attrId, (string)$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return false;
        if ($excludeId && (int)$row['entity_id'] === $excludeId) return false;
        return true;
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId && self::userIdLinked($userId)) {
            throw new \RuntimeException('User is already linked to another profile.');
        }
        $db->prepare('INSERT INTO eav_entities (entity_type) VALUES ("user_profile")')->execute();
        $id = (int) $db->lastInsertId();
        self::setValue($id, 'name', $data['name'] ?? '');
        self::setValue($id, 'role_id', $data['role_id'] ?? '');
        self::setValue($id, 'user_id', $userId ?: '');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId && self::userIdLinked($userId, $id)) {
            throw new \RuntimeException('User is already linked to another profile.');
        }
        self::setValue($id, 'name', $data['name'] ?? '');
        self::setValue($id, 'role_id', $data['role_id'] ?? '');
        self::setValue($id, 'user_id', $userId ?: '');
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM eav_entities WHERE id = ? AND entity_type = "user_profile"');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Returns users for dropdown: excludes already-linked users, except $excludeProfileId's user when editing */
    public static function getUsersForDropdown(?int $excludeProfileId = null): array
    {
        $db = self::db();
        $all = $db->query('SELECT id, username FROM users ORDER BY username')->fetchAll(\PDO::FETCH_OBJ);
        $attrId = self::getAttributeId('user_id');
        if (!$attrId) return $all;

        $stmt = $db->query('SELECT v.entity_id, v.value FROM eav_values v 
            JOIN eav_entities e ON v.entity_id = e.id 
            WHERE v.attribute_id = ' . (int)$attrId . ' AND e.entity_type = "user_profile" AND v.value != ""');
        $linkedOther = [];
        $currentUserId = null;
        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $eid = (int)$r['entity_id'];
            $uid = (int)$r['value'];
            if ($excludeProfileId && $eid === $excludeProfileId) {
                $currentUserId = $uid;
            } else {
                $linkedOther[$uid] = true;
            }
        }
        return array_values(array_filter($all, function ($u) use ($linkedOther, $currentUserId) {
            $id = (int)$u->id;
            if ($currentUserId && $id === $currentUserId) return true;
            return !isset($linkedOther[$id]);
        }));
    }
}
