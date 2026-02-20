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

    /**
     * Paginated list with search and sort at DB level. Returns ['items' => [...], 'total' => N, 'page' => N, 'per_page' => N, 'total_pages' => N].
     */
    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage): array
    {
        $db = self::db();
        $aidPapsid = self::getAttributeId('papsid');
        $aidControl = self::getAttributeId('control_number');
        $aidFullName = self::getAttributeId('full_name');
        $aidAge = self::getAttributeId('age');
        $aidContact = self::getAttributeId('contact_number');
        $aidProjectId = self::getAttributeId('project_id');
        if (!$aidPapsid) return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0];

        $projNameAttrId = $db->query("SELECT id FROM eav_attributes WHERE entity_type='project' AND name='name'")->fetchColumn();
        $structOwnerAttrId = $db->query("SELECT id FROM eav_attributes WHERE entity_type='structure' AND name='owner_id'")->fetchColumn();
        $structOwnerAttrId = $structOwnerAttrId ? (int) $structOwnerAttrId : 0;

        $sortCol = match ($sortBy) {
            'papsid' => 'p.papsid',
            'control_number' => 'p.control_number',
            'full_name' => 'p.full_name',
            'age' => 'CAST(p.age AS UNSIGNED)',
            'contact_number' => 'p.contact_number',
            'project_name' => 'project_name',
            'other_details' => 'structure_count',
            default => 'p.id',
        };
        $dir = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $params = [];
        $searchCond = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $conds = [];
            if (in_array('papsid', $searchColumns)) { $conds[] = 'p.papsid LIKE ?'; $params[] = $term; }
            if (in_array('control_number', $searchColumns)) { $conds[] = 'p.control_number LIKE ?'; $params[] = $term; }
            if (in_array('full_name', $searchColumns)) { $conds[] = 'p.full_name LIKE ?'; $params[] = $term; }
            if (in_array('age', $searchColumns)) { $conds[] = 'CAST(p.age AS CHAR) LIKE ?'; $params[] = $term; }
            if (in_array('contact_number', $searchColumns)) { $conds[] = 'p.contact_number LIKE ?'; $params[] = $term; }
            if (in_array('project_name', $searchColumns)) { $conds[] = 'COALESCE(proj.vname, \'\') LIKE ?'; $params[] = $term; }
            if (in_array('other_details', $searchColumns)) { $conds[] = 'CAST(COALESCE(struct_cnt.cnt, 0) AS CHAR) LIKE ?'; $params[] = $term; }
            if (!empty($conds)) {
                $searchCond = ' AND (' . implode(' OR ', $conds) . ')';
            }
        }

        $offset = ($page - 1) * $perPage;
        $limit = max(1, min(100, $perPage));

        $sql = "SELECT p.id, p.papsid, p.control_number, p.full_name, p.age, p.contact_number, p.project_id,
            COALESCE(proj.vname, '') as project_name,
            COALESCE(struct_cnt.cnt, 0) as structure_count
FROM (
  SELECT e.id,
    MAX(CASE WHEN v.attribute_id = $aidPapsid THEN v.value END) as papsid,
    MAX(CASE WHEN v.attribute_id = $aidControl THEN v.value END) as control_number,
    MAX(CASE WHEN v.attribute_id = $aidFullName THEN v.value END) as full_name,
    MAX(CASE WHEN v.attribute_id = $aidAge THEN v.value END) as age,
    MAX(CASE WHEN v.attribute_id = $aidContact THEN v.value END) as contact_number,
    MAX(CASE WHEN v.attribute_id = $aidProjectId THEN v.value END) as project_id
  FROM eav_entities e
  LEFT JOIN eav_values v ON v.entity_id = e.id AND v.attribute_id IN ($aidPapsid,$aidControl,$aidFullName,$aidAge,$aidContact,$aidProjectId)
  WHERE e.entity_type = 'profile'
  GROUP BY e.id
) p
LEFT JOIN (
  SELECT pe.id as pid, pv.value as vname
  FROM eav_entities pe
  LEFT JOIN eav_values pv ON pv.entity_id = pe.id AND pv.attribute_id = " . (int)$projNameAttrId . "
  WHERE pe.entity_type = 'project'
) proj ON proj.pid = p.project_id
LEFT JOIN (
  SELECT CAST(ov.value AS UNSIGNED) as profile_id, COUNT(*) as cnt
  FROM eav_values ov
  JOIN eav_entities se ON ov.entity_id = se.id
  WHERE ov.attribute_id = $structOwnerAttrId AND se.entity_type = 'structure'
  GROUP BY ov.value
) struct_cnt ON struct_cnt.profile_id = p.id
WHERE 1=1 $searchCond
ORDER BY $sortCol $dir
LIMIT $limit OFFSET $offset";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM (
  SELECT e.id,
    MAX(CASE WHEN v.attribute_id = $aidPapsid THEN v.value END) as papsid,
    MAX(CASE WHEN v.attribute_id = $aidControl THEN v.value END) as control_number,
    MAX(CASE WHEN v.attribute_id = $aidFullName THEN v.value END) as full_name,
    MAX(CASE WHEN v.attribute_id = $aidAge THEN v.value END) as age,
    MAX(CASE WHEN v.attribute_id = $aidContact THEN v.value END) as contact_number,
    MAX(CASE WHEN v.attribute_id = $aidProjectId THEN v.value END) as project_id
  FROM eav_entities e
  LEFT JOIN eav_values v ON v.entity_id = e.id AND v.attribute_id IN ($aidPapsid,$aidControl,$aidFullName,$aidAge,$aidContact,$aidProjectId)
  WHERE e.entity_type = 'profile'
  GROUP BY e.id
) p
LEFT JOIN (
  SELECT pe.id as pid, pv.value as vname
  FROM eav_entities pe
  LEFT JOIN eav_values pv ON pv.entity_id = pe.id AND pv.attribute_id = " . (int)$projNameAttrId . "
  WHERE pe.entity_type = 'project'
) proj ON proj.pid = p.project_id
LEFT JOIN (
  SELECT CAST(ov.value AS UNSIGNED) as profile_id, COUNT(*) as cnt
  FROM eav_values ov
  JOIN eav_entities se ON ov.entity_id = se.id
  WHERE ov.attribute_id = $structOwnerAttrId AND se.entity_type = 'structure'
  GROUP BY ov.value
) struct_cnt ON struct_cnt.profile_id = p.id
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
