<?php
namespace App\Models;

use Core\Database;

class Profile
{
    protected static string $table = 'profiles';

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    /**
     * Generate next PAPSID atomically to avoid duplicates under concurrent create.
     */
    public static function generatePAPSID(): string
    {
        $db = self::db();
        $lockName = 'papsid_generate';
        $db->query("SELECT GET_LOCK('$lockName', 10)")->fetchAll();
        try {
            $yearMonth = date('Ym');
            $prefix = "PAPS-{$yearMonth}";
            $stmt = $db->prepare('SELECT papsid FROM profiles WHERE papsid LIKE ? ORDER BY papsid DESC LIMIT 1');
            $stmt->execute([$prefix . '%']);
            $last = $stmt->fetchColumn();
            if (!$last) return $prefix . '0000000001';
            $num = (int) substr($last, strlen($prefix));
            return $prefix . str_pad($num + 1, 10, '0', STR_PAD_LEFT);
        } finally {
            $db->query("SELECT RELEASE_LOCK('$lockName')")->fetchAll();
        }
    }

    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage): array
    {
        $db = self::db();
        $sortCol = match ($sortBy) {
            'papsid' => 'p.papsid',
            'control_number' => 'p.control_number',
            'full_name' => 'p.full_name',
            'age' => 'p.age',
            'contact_number' => 'p.contact_number',
            'project_name' => 'proj.name',
            'other_details' => 'struct_cnt.cnt',
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
            if (in_array('project_name', $searchColumns)) { $conds[] = 'COALESCE(proj.name, \'\') LIKE ?'; $params[] = $term; }
            if (in_array('other_details', $searchColumns)) { $conds[] = 'CAST(COALESCE(struct_cnt.cnt, 0) AS CHAR) LIKE ?'; $params[] = $term; }
            if (!empty($conds)) {
                $searchCond = ' AND (' . implode(' OR ', $conds) . ')';
            }
        }

        $offset = ($page - 1) * $perPage;
        $limit = max(1, min(100, $perPage));

        $sql = "SELECT p.id, p.papsid, p.control_number, p.full_name, p.age, p.contact_number, p.project_id,
            COALESCE(proj.name, '') as project_name,
            COALESCE(struct_cnt.cnt, 0) as structure_count
            FROM profiles p
            LEFT JOIN projects proj ON proj.id = p.project_id
            LEFT JOIN (SELECT owner_id, COUNT(*) as cnt FROM structures GROUP BY owner_id) struct_cnt ON struct_cnt.owner_id = p.id
            WHERE 1=1 $searchCond
            ORDER BY $sortCol $dir
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM profiles p
            LEFT JOIN projects proj ON proj.id = p.project_id
            LEFT JOIN (SELECT owner_id, COUNT(*) as cnt FROM structures GROUP BY owner_id) struct_cnt ON struct_cnt.owner_id = p.id
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
        $stmt = self::db()->query('
            SELECT p.id, p.papsid, p.control_number, p.full_name, p.age, p.contact_number, p.project_id, proj.name as project_name
            FROM profiles p
            LEFT JOIN projects proj ON proj.id = p.project_id
            ORDER BY p.id DESC
        ');
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = self::db()->prepare('
            SELECT p.id, p.papsid, p.control_number, p.full_name, p.age, p.contact_number, p.project_id, proj.name as project_name
            FROM profiles p
            LEFT JOIN projects proj ON proj.id = p.project_id
            WHERE p.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $papsid = self::generatePAPSID();
        $age = ($data['age'] ?? '') !== '' ? (int) $data['age'] : null;
        $stmt = self::db()->prepare('INSERT INTO profiles (papsid, control_number, full_name, age, contact_number, project_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $papsid,
            trim($data['control_number'] ?? ''),
            trim($data['full_name'] ?? ''),
            $age,
            trim($data['contact_number'] ?? ''),
            ($pid = (int) ($data['project_id'] ?? 0)) ? $pid : null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $current = self::find($id);
        if (!$current) return false;
        $age = isset($data['age']) && $data['age'] !== '' ? (int) $data['age'] : null;
        $papsid = isset($data['papsid']) ? $data['papsid'] : $current->papsid;
        $stmt = self::db()->prepare('UPDATE profiles SET papsid = ?, control_number = ?, full_name = ?, age = ?, contact_number = ?, project_id = ? WHERE id = ?');
        $stmt->execute([
            $papsid,
            trim($data['control_number'] ?? ''),
            trim($data['full_name'] ?? ''),
            $age,
            trim($data['contact_number'] ?? ''),
            ($pid = (int) ($data['project_id'] ?? 0)) ? $pid : null,
            $id,
        ]);
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM profiles WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
