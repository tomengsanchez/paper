<?php
namespace App\Models;

use Core\Database;

class Project
{
    protected static string $table = 'projects';

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage): array
    {
        $db = self::db();
        $sortCol = match ($sortBy) {
            'name' => 'pr.name',
            'description' => 'pr.description',
            'coordinator_name' => 'u.username',
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

        $sql = "SELECT pr.id, pr.name, pr.description, pr.coordinator_id, COALESCE(u.username, '') as coordinator_name
            FROM projects pr
            LEFT JOIN users u ON u.id = pr.coordinator_id
            WHERE 1=1 $searchCond
            ORDER BY $sortCol $dir
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM projects pr
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
        $stmt = self::db()->query('
            SELECT pr.id, pr.name, pr.description, pr.coordinator_id, u.username as coordinator_name
            FROM projects pr
            LEFT JOIN users u ON u.id = pr.coordinator_id
            ORDER BY pr.id DESC
        ');
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = self::db()->prepare('
            SELECT pr.id, pr.name, pr.description, pr.coordinator_id, u.username as coordinator_name
            FROM projects pr
            LEFT JOIN users u ON u.id = pr.coordinator_id
            WHERE pr.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO projects (name, description, coordinator_id) VALUES (?, ?, ?)');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['description'] ?? ''),
            ($id = (int) ($data['coordinator_id'] ?? 0)) ? $id : null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        $stmt = self::db()->prepare('UPDATE projects SET name = ?, description = ?, coordinator_id = ? WHERE id = ?');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['description'] ?? ''),
            ($cid = (int) ($data['coordinator_id'] ?? 0)) ? $cid : null,
            $id,
        ]);
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
