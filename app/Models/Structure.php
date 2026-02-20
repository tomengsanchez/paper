<?php
namespace App\Models;

use Core\Model;
use Core\Database;

class Structure extends Model
{
    protected static string $entityType = 'structure';
    protected static array $attributes = [
        'strid' => 'string',
        'owner_id' => 'int',
        'structure_tag' => 'string',
        'description' => 'text',
        'tagging_images' => 'text',
        'structure_images' => 'text',
        'other_details' => 'text',
    ];

    public static function generateSTRID(): string
    {
        $yearMonth = date('Ym');
        $prefix = "STRID-{$yearMonth}";
        $db = self::db();
        $attrId = self::getAttributeId('strid');
        if (!$attrId) return $prefix . '000000001';

        $stmt = $db->prepare('SELECT value FROM eav_values v 
            JOIN eav_entities e ON v.entity_id = e.id 
            WHERE v.attribute_id = ? AND e.entity_type = "structure" AND value LIKE ?
            ORDER BY value DESC LIMIT 1');
        $stmt->execute([$attrId, $prefix . '%']);
        $last = $stmt->fetchColumn();
        if (!$last) return $prefix . '000000001';

        $num = (int) substr($last, strlen($prefix));
        return $prefix . str_pad($num + 1, 9, '0', STR_PAD_LEFT);
    }

    public static function all(): array
    {
        $db = self::db();
        $attrId = self::getAttributeId('strid');
        if (!$attrId) return [];

        $stmt = $db->query('SELECT id FROM eav_entities WHERE entity_type = "structure" ORDER BY id DESC');
        $entities = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $result = [];

        foreach ($entities as $eid) {
            $ownerId = self::getValue($eid, 'owner_id');
            $ownerName = self::getProfileDisplayName($db, $ownerId);
            $result[] = (object) [
                'id' => $eid,
                'strid' => self::getValue($eid, 'strid'),
                'owner_id' => $ownerId,
                'owner_name' => $ownerName,
                'structure_tag' => self::getValue($eid, 'structure_tag'),
                'description' => self::getValue($eid, 'description'),
                'tagging_images' => self::getValue($eid, 'tagging_images'),
                'structure_images' => self::getValue($eid, 'structure_images'),
                'other_details' => self::getValue($eid, 'other_details'),
            ];
        }
        return $result;
    }

    public static function find(int $id): ?object
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM eav_entities WHERE id = ? AND entity_type = "structure"');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) return null;

        $ownerId = self::getValue($id, 'owner_id');
        $ownerName = self::getProfileDisplayName($db, $ownerId);

        return (object) [
            'id' => $id,
            'strid' => self::getValue($id, 'strid'),
            'owner_id' => $ownerId,
            'owner_name' => $ownerName,
            'structure_tag' => self::getValue($id, 'structure_tag'),
            'description' => self::getValue($id, 'description'),
            'tagging_images' => self::getValue($id, 'tagging_images'),
            'structure_images' => self::getValue($id, 'structure_images'),
            'other_details' => self::getValue($id, 'other_details'),
        ];
    }

    private static function getProfileDisplayName(\PDO $db, $profileId): ?string
    {
        if (!$profileId) return null;
        $stmt = $db->prepare('SELECT a.name, v.value FROM eav_values v 
            JOIN eav_attributes a ON v.attribute_id = a.id 
            WHERE v.entity_id = ? AND a.entity_type = "profile" AND a.name IN ("full_name","papsid")');
        $stmt->execute([$profileId]);
        $vals = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $vals[$row['name']] = trim($row['value'] ?? '');
        }
        return $vals['full_name'] ?: $vals['papsid'] ?: null;
    }

    public static function parseImages(string $json): array
    {
        if (empty(trim($json))) return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $strid = $data['strid'] ?? self::generateSTRID();
        $db->prepare('INSERT INTO eav_entities (entity_type) VALUES ("structure")')->execute();
        $id = (int) $db->lastInsertId();
        self::setValue($id, 'strid', $strid);
        self::setValue($id, 'owner_id', $data['owner_id'] ?? '');
        self::setValue($id, 'structure_tag', $data['structure_tag'] ?? '');
        self::setValue($id, 'description', $data['description'] ?? '');
        self::setValue($id, 'tagging_images', $data['tagging_images'] ?? '[]');
        self::setValue($id, 'structure_images', $data['structure_images'] ?? '[]');
        self::setValue($id, 'other_details', $data['other_details'] ?? '');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        if (isset($data['strid'])) self::setValue($id, 'strid', $data['strid']);
        self::setValue($id, 'owner_id', $data['owner_id'] ?? '');
        self::setValue($id, 'structure_tag', $data['structure_tag'] ?? '');
        self::setValue($id, 'description', $data['description'] ?? '');
        self::setValue($id, 'tagging_images', $data['tagging_images'] ?? '[]');
        self::setValue($id, 'structure_images', $data['structure_images'] ?? '[]');
        self::setValue($id, 'other_details', $data['other_details'] ?? '');
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM eav_entities WHERE id = ? AND entity_type = "structure"');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
