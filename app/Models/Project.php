<?php
namespace App\Models;

use Core\Model;
use Core\Database;

class Project extends Model
{
    protected static string $entityType = 'project';
    protected static array $attributes = [
        'name' => 'string',
        'description' => 'text',
        'coordinator_id' => 'int',
    ];

    public static function all(): array
    {
        $db = self::db();
        $nameId = self::getAttributeId('name');
        $descId = self::getAttributeId('description');
        $coordId = self::getAttributeId('coordinator_id');
        if (!$nameId) return [];

        $stmt = $db->query('SELECT id FROM eav_entities WHERE entity_type = "project" ORDER BY id DESC');
        $entities = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $result = [];

        foreach ($entities as $eid) {
            $name = self::getValue($eid, 'name');
            $desc = self::getValue($eid, 'description');
            $coordIdVal = self::getValue($eid, 'coordinator_id');
            $coordName = null;
            if ($coordIdVal) {
                $u = $db->prepare('SELECT username FROM users WHERE id = ?');
                $u->execute([$coordIdVal]);
                $coordName = $u->fetchColumn();
            }
            $result[] = (object) [
                'id' => $eid,
                'name' => $name,
                'description' => $desc,
                'coordinator_id' => $coordIdVal,
                'coordinator_name' => $coordName,
            ];
        }
        return $result;
    }

    public static function find(int $id): ?object
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM eav_entities WHERE id = ? AND entity_type = "project"');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) return null;

        $coordIdVal = self::getValue($id, 'coordinator_id');
        $coordName = null;
        if ($coordIdVal) {
            $u = $db->prepare('SELECT username FROM users WHERE id = ?');
            $u->execute([$coordIdVal]);
            $coordName = $u->fetchColumn();
        }

        return (object) [
            'id' => $id,
            'name' => self::getValue($id, 'name'),
            'description' => self::getValue($id, 'description'),
            'coordinator_id' => $coordIdVal,
            'coordinator_name' => $coordName,
        ];
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $db->prepare('INSERT INTO eav_entities (entity_type) VALUES ("project")')->execute();
        $id = (int) $db->lastInsertId();
        self::setValue($id, 'name', $data['name'] ?? '');
        self::setValue($id, 'description', $data['description'] ?? '');
        self::setValue($id, 'coordinator_id', $data['coordinator_id'] ?? '');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        self::setValue($id, 'name', $data['name'] ?? '');
        self::setValue($id, 'description', $data['description'] ?? '');
        self::setValue($id, 'coordinator_id', $data['coordinator_id'] ?? '');
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM eav_entities WHERE id = ? AND entity_type = "project"');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
