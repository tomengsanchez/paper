<?php
namespace App\Models;

use Core\Database;

class Structure
{
    protected static string $table = 'structures';

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function generateSTRID(): string
    {
        $yearMonth = date('Ym');
        $prefix = "STRID-{$yearMonth}";
        $stmt = self::db()->prepare('SELECT strid FROM structures WHERE strid LIKE ? ORDER BY strid DESC LIMIT 1');
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        if (!$last) return $prefix . '000000001';
        $num = (int) substr($last, strlen($prefix));
        return $prefix . str_pad($num + 1, 9, '0', STR_PAD_LEFT);
    }

    public static function byOwner(int $profileId): array
    {
        $stmt = self::db()->prepare('
            SELECT s.id, s.strid, s.owner_id, s.structure_tag, s.description, s.tagging_images, s.structure_images, s.other_details
            FROM structures s
            WHERE s.owner_id = ?
            ORDER BY s.id DESC
        ');
        $stmt->execute([$profileId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage): array
    {
        $db = self::db();
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
            if (in_array('owner_name', $searchColumns)) { $conds[] = 'COALESCE(p.full_name, p.papsid, \'\') LIKE ?'; $params[] = $term; }
            if (in_array('structure_tag', $searchColumns)) { $conds[] = 's.structure_tag LIKE ?'; $params[] = $term; }
            if (in_array('description', $searchColumns)) { $conds[] = 's.description LIKE ?'; $params[] = $term; }
            if (!empty($conds)) {
                $searchCond = ' AND (' . implode(' OR ', $conds) . ')';
            }
        }

        $offset = ($page - 1) * $perPage;
        $limit = max(1, min(100, $perPage));

        $sql = "SELECT s.id, s.strid, s.owner_id, s.structure_tag, s.description, s.tagging_images, s.structure_images, s.other_details,
            COALESCE(p.full_name, p.papsid, '') as owner_name
            FROM structures s
            LEFT JOIN profiles p ON p.id = s.owner_id
            WHERE 1=1 $searchCond
            ORDER BY $sortCol $dir
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM structures s
            LEFT JOIN profiles p ON p.id = s.owner_id
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
            SELECT s.id, s.strid, s.owner_id, s.structure_tag, s.description, s.tagging_images, s.structure_images, s.other_details,
                COALESCE(p.full_name, p.papsid) as owner_name
            FROM structures s
            LEFT JOIN profiles p ON p.id = s.owner_id
            ORDER BY s.id DESC
        ');
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = self::db()->prepare('
            SELECT s.id, s.strid, s.owner_id, s.structure_tag, s.description, s.tagging_images, s.structure_images, s.other_details,
                COALESCE(p.full_name, p.papsid) as owner_name
            FROM structures s
            LEFT JOIN profiles p ON p.id = s.owner_id
            WHERE s.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function parseImages(string $json): array
    {
        if (empty(trim($json ?? ''))) return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function create(array $data): int
    {
        $strid = $data['strid'] ?? self::generateSTRID();
        $stmt = self::db()->prepare('INSERT INTO structures (strid, owner_id, structure_tag, description, tagging_images, structure_images, other_details) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $strid,
            ($oid = (int) ($data['owner_id'] ?? 0)) ? $oid : null,
            trim($data['structure_tag'] ?? ''),
            trim($data['description'] ?? ''),
            $data['tagging_images'] ?? '[]',
            $data['structure_images'] ?? '[]',
            trim($data['other_details'] ?? ''),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        $stmt = self::db()->prepare('UPDATE structures SET strid = ?, owner_id = ?, structure_tag = ?, description = ?, tagging_images = ?, structure_images = ?, other_details = ? WHERE id = ?');
        $stmt->execute([
            $data['strid'] ?? '',
            ($oid = (int) ($data['owner_id'] ?? 0)) ? $oid : null,
            trim($data['structure_tag'] ?? ''),
            trim($data['description'] ?? ''),
            $data['tagging_images'] ?? '[]',
            $data['structure_images'] ?? '[]',
            trim($data['other_details'] ?? ''),
            $id,
        ]);
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM structures WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
