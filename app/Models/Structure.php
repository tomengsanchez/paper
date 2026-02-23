<?php
namespace App\Models;

use Core\Database;
use App\Models\Profile;

class Structure
{
    protected static string $table = 'structures';

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    /**
     * Generate next STRID atomically to avoid duplicates under concurrent create.
     */
    public static function generateSTRID(): string
    {
        $db = self::db();
        $lockName = 'strid_generate';
        $db->query("SELECT GET_LOCK('$lockName', 10)")->fetchAll();
        try {
            $yearMonth = date('Ym');
            $prefix = "STRID-{$yearMonth}";
            $stmt = $db->prepare('SELECT strid FROM structures WHERE strid LIKE ? ORDER BY strid DESC LIMIT 1');
            $stmt->execute([$prefix . '%']);
            $last = $stmt->fetchColumn();
            if (!$last) return $prefix . '000000001';
            $num = (int) substr($last, strlen($prefix));
            return $prefix . str_pad($num + 1, 9, '0', STR_PAD_LEFT);
        } finally {
            $db->query("SELECT RELEASE_LOCK('$lockName')")->fetchAll();
        }
    }

    /** Structures for a profile, with optional limit (default 100). Use limit for profiles with many structures. */
    public static function byOwner(int $profileId, int $limit = 100, ?int $afterId = null): array
    {
        $db = self::db();
        $params = [$profileId];
        $cursorCond = '';
        if ($afterId !== null) {
            $cursorCond = ' AND s.id < ?';
            $params[] = $afterId;
        }
        $limit = max(1, min(500, $limit));
        $stmt = $db->prepare("
            SELECT s.id, s.strid, s.owner_id, s.structure_tag, s.description, s.tagging_images, s.structure_images, s.other_details
            FROM structures s
            WHERE s.owner_id = ? $cursorCond
            ORDER BY s.id DESC
            LIMIT $limit
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage, ?int $afterId = null, ?int $beforeId = null): array
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
        $useFulltext = $search !== '' && strlen($search) >= 3;
        if ($search !== '') {
            if ($useFulltext) {
                $ownerSearch = in_array('owner_name', $searchColumns);
                $searchCond = ' AND (MATCH(s.strid, s.structure_tag, s.description) AGAINST(? IN NATURAL LANGUAGE MODE)';
                $params[] = $search;
                if ($ownerSearch) {
                    $searchCond .= ' OR COALESCE(p.full_name, p.papsid, \'\') LIKE ?';
                    $params[] = '%' . $search . '%';
                }
                $searchCond .= ')';
            } else {
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
        }

        $limit = max(1, min(100, $perPage));
        $cursorCond = '';
        if ($afterId !== null) {
            $cursorCond = $dir === 'DESC' ? ' AND s.id < ?' : ' AND s.id > ?';
            $params[] = $afterId;
        } elseif ($beforeId !== null) {
            $cursorCond = $dir === 'DESC' ? ' AND s.id > ?' : ' AND s.id < ?';
            $params[] = $beforeId;
        }

        $offset = ($afterId === null && $beforeId === null) ? ($page - 1) * $limit : 0;
        $limitClause = $cursorCond !== '' ? "LIMIT $limit" : "LIMIT $limit OFFSET $offset";

        $sql = "SELECT s.id, s.strid, s.owner_id, s.structure_tag, s.description, s.tagging_images, s.structure_images, s.other_details,
            COALESCE(p.full_name, p.papsid, '') as owner_name
            FROM structures s
            LEFT JOIN profiles p ON p.id = s.owner_id
            WHERE 1=1 $searchCond $cursorCond
            ORDER BY $sortCol $dir
            $limitClause";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countParams = $cursorCond !== '' ? array_slice($params, 0, -1) : $params;
        $countSql = "SELECT COUNT(*) FROM structures s
            LEFT JOIN profiles p ON p.id = s.owner_id
            WHERE 1=1 $searchCond";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($countParams);
        $total = (int) $stmtCount->fetchColumn();
        $totalPages = (int) ceil($total / $limit);

        $firstId = !empty($items) ? (int) $items[0]->id : null;
        $lastId = !empty($items) ? (int) $items[count($items) - 1]->id : null;

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
            'total_pages' => $totalPages,
            'first_id' => $firstId,
            'last_id' => $lastId,
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
        $strid = self::generateSTRID();
        $ownerId = ($oid = (int) ($data['owner_id'] ?? 0)) ? $oid : null;
        $stmt = self::db()->prepare('INSERT INTO structures (strid, owner_id, structure_tag, description, tagging_images, structure_images, other_details) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $strid,
            $ownerId,
            trim($data['structure_tag'] ?? ''),
            trim($data['description'] ?? ''),
            $data['tagging_images'] ?? '[]',
            $data['structure_images'] ?? '[]',
            trim($data['other_details'] ?? ''),
        ]);
        $id = (int) self::db()->lastInsertId();
        if ($ownerId) {
            Profile::incrementStructureCount($ownerId);
        }
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $current = self::find($id);
        if (!$current) return false;
        $newOwnerId = ($oid = (int) ($data['owner_id'] ?? 0)) ? $oid : null;
        $oldOwnerId = (int) ($current->owner_id ?? 0) ?: null;

        $stmt = self::db()->prepare('UPDATE structures SET strid = ?, owner_id = ?, structure_tag = ?, description = ?, tagging_images = ?, structure_images = ?, other_details = ? WHERE id = ?');
        $stmt->execute([
            $data['strid'] ?? '',
            $newOwnerId,
            trim($data['structure_tag'] ?? ''),
            trim($data['description'] ?? ''),
            $data['tagging_images'] ?? '[]',
            $data['structure_images'] ?? '[]',
            trim($data['other_details'] ?? ''),
            $id,
        ]);

        if ($oldOwnerId !== $newOwnerId) {
            if ($oldOwnerId) Profile::decrementStructureCount($oldOwnerId);
            if ($newOwnerId) Profile::incrementStructureCount($newOwnerId);
        }
        return true;
    }

    /** Bulk: create structure with pre-generated STRID (skips lock, for seeders/imports) */
    public static function createWithStrid(string $strid, array $data): int
    {
        $ownerId = ($oid = (int) ($data['owner_id'] ?? 0)) ? $oid : null;
        $stmt = self::db()->prepare('INSERT INTO structures (strid, owner_id, structure_tag, description, tagging_images, structure_images, other_details) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $strid,
            $ownerId,
            trim($data['structure_tag'] ?? ''),
            trim($data['description'] ?? ''),
            $data['tagging_images'] ?? '[]',
            $data['structure_images'] ?? '[]',
            trim($data['other_details'] ?? ''),
        ]);
        $id = (int) self::db()->lastInsertId();
        if ($ownerId) {
            Profile::incrementStructureCount($ownerId);
        }
        return $id;
    }

    /** Bulk: generate N STRIDs in one lock (for seeders) */
    public static function generateSTRIDBatch(int $count): array
    {
        if ($count <= 0) return [];
        $db = self::db();
        $lockName = 'strid_generate';
        $db->query("SELECT GET_LOCK('$lockName', 30)")->fetchAll();
        try {
            $yearMonth = date('Ym');
            $prefix = "STRID-{$yearMonth}";
            $stmt = $db->prepare('SELECT strid FROM structures WHERE strid LIKE ? ORDER BY strid DESC LIMIT 1');
            $stmt->execute([$prefix . '%']);
            $last = $stmt->fetchColumn();
            $startNum = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
            $result = [];
            for ($i = 0; $i < $count; $i++) {
                $result[] = $prefix . str_pad((string) ($startNum + $i), 9, '0', STR_PAD_LEFT);
            }
            return $result;
        } finally {
            $db->query("SELECT RELEASE_LOCK('$lockName')")->fetchAll();
        }
    }

    public static function delete(int $id): bool
    {
        $current = self::find($id);
        if (!$current) return false;
        $ownerId = (int) ($current->owner_id ?? 0) ?: null;
        $stmt = self::db()->prepare('DELETE FROM structures WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0 && $ownerId) {
            Profile::decrementStructureCount($ownerId);
            return true;
        }
        return $stmt->rowCount() > 0;
    }
}
