<?php
namespace App\Models;

use Core\Model;
use Core\Database;

class Profile extends Model
{
    protected static string $entityType = 'profile';
    protected static array $attributes = [
        'papsid' => 'string',
        'control_number' => 'string',
        'full_name' => 'string',
        'age' => 'int',
        'contact_number' => 'string',
        'project_id' => 'int',
    ];

    public static function generatePAPSID(): string
    {
        $yearMonth = date('Ym');
        $prefix = "PAPS-{$yearMonth}";
        $db = self::db();
        $attrId = self::getAttributeId('papsid');
        if (!$attrId) return $prefix . '0000000001';

        $stmt = $db->prepare('SELECT value FROM eav_values v 
            JOIN eav_entities e ON v.entity_id = e.id 
            WHERE v.attribute_id = ? AND e.entity_type = "profile" AND value LIKE ?
            ORDER BY value DESC LIMIT 1');
        $stmt->execute([$attrId, $prefix . '%']);
        $last = $stmt->fetchColumn();
        if (!$last) return $prefix . '0000000001';

        $num = (int) substr($last, strlen($prefix));
        return $prefix . str_pad($num + 1, 10, '0', STR_PAD_LEFT);
    }

    public static function all(): array
    {
        $db = self::db();
        $papsId = self::getAttributeId('papsid');
        if (!$papsId) return [];

        $stmt = $db->query('SELECT id FROM eav_entities WHERE entity_type = "profile" ORDER BY id DESC');
        $entities = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $result = [];

        foreach ($entities as $eid) {
            $projectId = self::getValue($eid, 'project_id');
            $projectName = null;
            if ($projectId) {
                $ev = $db->prepare('SELECT v.value FROM eav_values v 
                    JOIN eav_attributes a ON v.attribute_id = a.id 
                    WHERE v.entity_id = ? AND a.entity_type = "project" AND a.name = "name"');
                $ev->execute([$projectId]);
                $projectName = $ev->fetchColumn();
            }

            $result[] = (object) [
                'id' => $eid,
                'papsid' => self::getValue($eid, 'papsid'),
                'control_number' => self::getValue($eid, 'control_number'),
                'full_name' => self::getValue($eid, 'full_name'),
                'age' => self::getValue($eid, 'age'),
                'contact_number' => self::getValue($eid, 'contact_number'),
                'project_id' => $projectId,
                'project_name' => $projectName,
            ];
        }
        return $result;
    }

    public static function find(int $id): ?object
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM eav_entities WHERE id = ? AND entity_type = "profile"');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) return null;

        $projectId = self::getValue($id, 'project_id');
        $projectName = null;
        if ($projectId) {
            $ev = $db->prepare('SELECT v.value FROM eav_values v 
                JOIN eav_attributes a ON v.attribute_id = a.id 
                WHERE v.entity_id = ? AND a.entity_type = "project" AND a.name = "name"');
            $ev->execute([$projectId]);
            $projectName = $ev->fetchColumn();
        }

        return (object) [
            'id' => $id,
            'papsid' => self::getValue($id, 'papsid'),
            'control_number' => self::getValue($id, 'control_number'),
            'full_name' => self::getValue($id, 'full_name'),
            'age' => self::getValue($id, 'age'),
            'contact_number' => self::getValue($id, 'contact_number'),
            'project_id' => $projectId,
            'project_name' => $projectName,
        ];
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $papsid = $data['papsid'] ?? self::generatePAPSID();
        $db->prepare('INSERT INTO eav_entities (entity_type) VALUES ("profile")')->execute();
        $id = (int) $db->lastInsertId();
        self::setValue($id, 'papsid', $papsid);
        self::setValue($id, 'control_number', $data['control_number'] ?? '');
        self::setValue($id, 'full_name', $data['full_name'] ?? '');
        self::setValue($id, 'age', $data['age'] ?? '');
        self::setValue($id, 'contact_number', $data['contact_number'] ?? '');
        self::setValue($id, 'project_id', $data['project_id'] ?? '');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        if (isset($data['papsid'])) self::setValue($id, 'papsid', $data['papsid']);
        self::setValue($id, 'control_number', $data['control_number'] ?? '');
        self::setValue($id, 'full_name', $data['full_name'] ?? '');
        self::setValue($id, 'age', $data['age'] ?? '');
        self::setValue($id, 'contact_number', $data['contact_number'] ?? '');
        self::setValue($id, 'project_id', $data['project_id'] ?? '');
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM eav_entities WHERE id = ? AND entity_type = "profile"');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
