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

    /**
     * Paginated list with search and sort at DB level. Returns ['items' => [...], 'total' => N, 'page' => N, 'per_page' => N, 'total_pages' => N].
     */
    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage): array
    {
        $db = self::db();
        $aidName = self::getAttributeId('name');
        $aidDesc = self::getAttributeId('description');
        $aidCoord = self::getAttributeId('coordinator_id');
        if (!$aidName) return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0];

        $sortCol = match ($sortBy) {
            'name' => 'pr.name',
            'description' => 'pr.description',
            'coordinator_name' => 'coordinator_name',
            default => 'pr.id',
        };
        $dir = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $params = [];
        $searchCond = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $conds = [];
            if (in_array('name', $searchColumns)) { $conds[] = 'pr.name LIKE ?'; $params[] = $term; }
            if (in_array('description', $searchColumns)) { $conds[] = 'pr.description LIKE ?'; $params[] = $term; }
            if (in_array('coordinator_name', $searchColumns)) { $conds[] = 'COALESCE(u.username, \'\') LIKE ?'; $params[] = $term; }
            if (!empty($conds)) {
                $searchCond = ' AND (' . implode(' OR ', $conds) . ')';
            }
        }

        $offset = ($page - 1) * $perPage;
        $limit = max(1, min(100, $perPage));

        $sql = "SELECT pr.id, pr.name, pr.description, pr.coordinator_id,
            COALESCE(u.username, '') as coordinator_name
FROM (
  SELECT e.id,
    MAX(CASE WHEN v.attribute_id = $aidName THEN v.value END) as name,
    MAX(CASE WHEN v.attribute_id = $aidDesc THEN v.value END) as description,
    MAX(CASE WHEN v.attribute_id = $aidCoord THEN v.value END) as coordinator_id
  FROM eav_entities e
  LEFT JOIN eav_values v ON v.entity_id = e.id AND v.attribute_id IN ($aidName,$aidDesc,$aidCoord)
  WHERE e.entity_type = 'project'
  GROUP BY e.id
) pr
LEFT JOIN users u ON u.id = pr.coordinator_id
WHERE 1=1 $searchCond
ORDER BY $sortCol $dir
LIMIT $limit OFFSET $offset";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM (
  SELECT e.id,
    MAX(CASE WHEN v.attribute_id = $aidName THEN v.value END) as name,
    MAX(CASE WHEN v.attribute_id = $aidDesc THEN v.value END) as description,
    MAX(CASE WHEN v.attribute_id = $aidCoord THEN v.value END) as coordinator_id
  FROM eav_entities e
  LEFT JOIN eav_values v ON v.entity_id = e.id AND v.attribute_id IN ($aidName,$aidDesc,$aidCoord)
  WHERE e.entity_type = 'project'
  GROUP BY e.id
) pr
LEFT JOIN users u ON u.id = pr.coordinator_id
WHERE 1=1 $searchCond";

        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();
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
