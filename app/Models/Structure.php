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

    public static function byOwner(int $profileId): array
    {
        $db = self::db();
        $ownerAttrId = self::getAttributeId('owner_id');
        if (!$ownerAttrId) return [];

        $stmt = $db->prepare('SELECT v.entity_id FROM eav_values v
            JOIN eav_entities e ON v.entity_id = e.id
            WHERE v.attribute_id = ? AND v.value = ? AND e.entity_type = "structure"
            ORDER BY e.id DESC');
        $stmt->execute([$ownerAttrId, (string) $profileId]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $result = [];
        foreach ($ids as $eid) {
            $s = self::find((int) $eid);
            if ($s) $result[] = $s;
        }
        return $result;
    }

    /**
     * Paginated list with search and sort at DB level. Returns ['items' => [...], 'total' => N, 'page' => N, 'per_page' => N, 'total_pages' => N].
     * Uses denormalized structure_list table for fast path when no search (40K+ records).
     */
    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $limit = max(1, min(100, $perPage));

        if ($search === '' && self::structureListTableExists()) {
            return self::listPaginatedFromCache($sortBy, $sortOrder, $page, $perPage, $limit);
        }

        return self::listPaginatedFromEav($search, $searchColumns, $sortBy, $sortOrder, $page, $perPage, $limit);
    }

    private static function structureListTableExists(): bool
    {
        try {
            $db = self::db();
            $stmt = $db->query("SHOW TABLES LIKE 'structure_list'");
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function listPaginatedFromCache(string $sortBy, string $sortOrder, int $page, int $perPage, int $limit): array
    {
        $db = self::db();
        $sortCol = match ($sortBy) {
            'strid' => 'strid',
            'owner_name' => 'owner_name',
            'structure_tag' => 'structure_tag',
            'description' => 'description',
            default => 'id',
        };
        $dir = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
        $offset = ($page - 1) * $limit;

        $total = (int) $db->query("SELECT COUNT(*) FROM structure_list")->fetchColumn();
        $totalPages = (int) ceil($total / $limit);

        $sql = "SELECT id, strid, owner_id, owner_name, structure_tag, description FROM structure_list ORDER BY `$sortCol` $dir LIMIT $limit OFFSET $offset";
        $stmt = $db->query($sql);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
            'total_pages' => $totalPages,
        ];
    }

    private static function listPaginatedFromEav(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage, int $limit): array
    {
        $db = self::db();
        $aidStrid = self::getAttributeId('strid');
        $aidOwner = self::getAttributeId('owner_id');
        $aidTag = self::getAttributeId('structure_tag');
        $aidDesc = self::getAttributeId('description');
        if (!$aidStrid) return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0];

        $aidFullName = $db->query("SELECT id FROM eav_attributes WHERE entity_type='profile' AND name='full_name'")->fetchColumn();
        $aidPapsid = $db->query("SELECT id FROM eav_attributes WHERE entity_type='profile' AND name='papsid'")->fetchColumn();

        $sortCol = match ($sortBy) {
            'strid' => 's.strid',
            'owner_name' => 'owner_name',
            'structure_tag' => 's.structure_tag',
            'description' => 's.description',
            default => 's.id',
        };
        $dir = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $params = [];
        $searchCond = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $conds = [];
            if (in_array('strid', $searchColumns)) { $conds[] = 's.strid LIKE ?'; $params[] = $term; }
            if (in_array('owner_name', $searchColumns)) { $conds[] = 'COALESCE(prof.fn_val, prof.pa_val, \'\') LIKE ?'; $params[] = $term; }
            if (in_array('structure_tag', $searchColumns)) { $conds[] = 's.structure_tag LIKE ?'; $params[] = $term; }
            if (in_array('description', $searchColumns)) { $conds[] = 's.description LIKE ?'; $params[] = $term; }
            if (!empty($conds)) {
                $searchCond = ' AND (' . implode(' OR ', $conds) . ')';
            }
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT s.id, s.strid, s.owner_id, s.structure_tag, s.description,
            COALESCE(prof.fn_val, prof.pa_val, '') as owner_name
FROM (
  SELECT e.id,
    MAX(CASE WHEN v.attribute_id = $aidStrid THEN v.value END) as strid,
    MAX(CASE WHEN v.attribute_id = $aidOwner THEN v.value END) as owner_id,
    MAX(CASE WHEN v.attribute_id = $aidTag THEN v.value END) as structure_tag,
    MAX(CASE WHEN v.attribute_id = $aidDesc THEN v.value END) as description
  FROM eav_entities e
  LEFT JOIN eav_values v ON v.entity_id = e.id AND v.attribute_id IN ($aidStrid,$aidOwner,$aidTag,$aidDesc)
  WHERE e.entity_type = 'structure'
  GROUP BY e.id
) s
LEFT JOIN (
  SELECT p.id as pid,
    fn.value as fn_val,
    pa.value as pa_val
  FROM eav_entities p
  LEFT JOIN eav_values fn ON fn.entity_id = p.id AND fn.attribute_id = " . (int)$aidFullName . "
  LEFT JOIN eav_values pa ON pa.entity_id = p.id AND pa.attribute_id = " . (int)$aidPapsid . "
  WHERE p.entity_type = 'profile'
) prof ON prof.pid = s.owner_id
WHERE 1=1 $searchCond
ORDER BY $sortCol $dir
LIMIT $limit OFFSET $offset";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        if ($search === '') {
            $stmtTotal = $db->query("SELECT COUNT(*) FROM eav_entities WHERE entity_type = 'structure'");
            $total = (int) $stmtTotal->fetchColumn();
        } else {
            $countSql = "SELECT COUNT(*) FROM (
  SELECT e.id,
    MAX(CASE WHEN v.attribute_id = $aidStrid THEN v.value END) as strid,
    MAX(CASE WHEN v.attribute_id = $aidOwner THEN v.value END) as owner_id,
    MAX(CASE WHEN v.attribute_id = $aidTag THEN v.value END) as structure_tag,
    MAX(CASE WHEN v.attribute_id = $aidDesc THEN v.value END) as description
  FROM eav_entities e
  LEFT JOIN eav_values v ON v.entity_id = e.id AND v.attribute_id IN ($aidStrid,$aidOwner,$aidTag,$aidDesc)
  WHERE e.entity_type = 'structure'
  GROUP BY e.id
) s
LEFT JOIN (
  SELECT p.id as pid, fn.value as fn_val, pa.value as pa_val
  FROM eav_entities p
  LEFT JOIN eav_values fn ON fn.entity_id = p.id AND fn.attribute_id = " . (int)$aidFullName . "
  LEFT JOIN eav_values pa ON pa.entity_id = p.id AND pa.attribute_id = " . (int)$aidPapsid . "
  WHERE p.entity_type = 'profile'
) prof ON prof.pid = s.owner_id
WHERE 1=1 $searchCond";
            $stmtCount = $db->prepare($countSql);
            $stmtCount->execute($params);
            $total = (int) $stmtCount->fetchColumn();
        }
        $totalPages = (int) ceil($total / $limit);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
            'total_pages' => $totalPages,
        ];
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
        self::syncStructureList($id);
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
        self::syncStructureList($id);
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM eav_entities WHERE id = ? AND entity_type = "structure"');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            self::deleteFromStructureList($id);
            return true;
        }
        return false;
    }

    /** Sync one structure to structure_list (for fast list page) */
    public static function syncStructureList(int $id): void
    {
        if (!self::structureListTableExists()) return;
        $s = self::find($id);
        if (!$s) return;
        $db = self::db();
        $stmt = $db->prepare('INSERT INTO structure_list (id, strid, owner_id, owner_name, structure_tag, description) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE strid=VALUES(strid), owner_id=VALUES(owner_id), owner_name=VALUES(owner_name), structure_tag=VALUES(structure_tag), description=VALUES(description)');
        $stmt->execute([
            $id,
            $s->strid ?? '',
            $s->owner_id ?: null,
            $s->owner_name ?? null,
            $s->structure_tag ?? null,
            $s->description ?? null,
        ]);
    }

    private static function deleteFromStructureList(int $id): void
    {
        if (!self::structureListTableExists()) return;
        $db = self::db();
        $db->prepare('DELETE FROM structure_list WHERE id = ?')->execute([$id]);
    }

    /** Rebuild structure_list from EAV (run after migration or if cache is stale) */
    public static function rebuildStructureList(): int
    {
        if (!self::structureListTableExists()) return 0;
        $db = self::db();
        $aidStrid = self::getAttributeId('strid');
        $aidOwner = self::getAttributeId('owner_id');
        $aidTag = self::getAttributeId('structure_tag');
        $aidDesc = self::getAttributeId('description');
        if (!$aidStrid) return 0;
        $aidFullName = $db->query("SELECT id FROM eav_attributes WHERE entity_type='profile' AND name='full_name'")->fetchColumn();
        $aidPapsid = $db->query("SELECT id FROM eav_attributes WHERE entity_type='profile' AND name='papsid'")->fetchColumn();

        $db->exec('TRUNCATE TABLE structure_list');
        $sql = "INSERT INTO structure_list (id, strid, owner_id, owner_name, structure_tag, description)
SELECT s.id, s.strid, s.owner_id, COALESCE(prof.fn_val, prof.pa_val), s.structure_tag, s.description
FROM (
  SELECT e.id,
    MAX(CASE WHEN v.attribute_id = $aidStrid THEN v.value END) as strid,
    MAX(CASE WHEN v.attribute_id = $aidOwner THEN v.value END) as owner_id,
    MAX(CASE WHEN v.attribute_id = $aidTag THEN v.value END) as structure_tag,
    MAX(CASE WHEN v.attribute_id = $aidDesc THEN v.value END) as description
  FROM eav_entities e
  LEFT JOIN eav_values v ON v.entity_id = e.id AND v.attribute_id IN ($aidStrid,$aidOwner,$aidTag,$aidDesc)
  WHERE e.entity_type = 'structure'
  GROUP BY e.id
) s
LEFT JOIN (
  SELECT p.id as pid, fn.value as fn_val, pa.value as pa_val
  FROM eav_entities p
  LEFT JOIN eav_values fn ON fn.entity_id = p.id AND fn.attribute_id = " . (int)$aidFullName . "
  LEFT JOIN eav_values pa ON pa.entity_id = p.id AND pa.attribute_id = " . (int)$aidPapsid . "
  WHERE p.entity_type = 'profile'
) prof ON prof.pid = s.owner_id";
        $db->exec($sql);
        return (int) $db->query('SELECT COUNT(*) FROM structure_list')->fetchColumn();
    }
}
