<?php
namespace App\Models;

use Core\Database;
use App\UserProjects;

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
            if (!empty($conds)) {
                $searchCond = ' AND (' . implode(' OR ', $conds) . ')';
            }
        }

        // Restrict to projects linked to current user (except Administrator)
        $allowed = UserProjects::allowedProjectIds();
        $projectFilter = '';
        if ($allowed !== null) {
            if (empty($allowed)) {
                $projectFilter = ' AND 1=0';
            } else {
                $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                $projectFilter = " AND pr.id IN ($placeholders)";
                foreach ($allowed as $pid) {
                    $params[] = $pid;
                }
            }
        }

        $offset = ($page - 1) * $perPage;
        $limit = max(1, min(100, $perPage));

        $sql = "SELECT pr.id, pr.name, pr.description
            FROM projects pr
            WHERE 1=1 $searchCond $projectFilter
            ORDER BY $sortCol $dir
            LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM projects pr
            WHERE 1=1 $searchCond $projectFilter";
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
        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null) {
            if (empty($allowed)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($allowed), '?'));
            $stmt = $db->prepare("
                SELECT pr.id, pr.name, pr.description
                FROM projects pr
                WHERE pr.id IN ($placeholders)
                ORDER BY pr.id DESC
            ");
            $stmt->execute($allowed);
        } else {
            $stmt = $db->query('
                SELECT pr.id, pr.name, pr.description
                FROM projects pr
                ORDER BY pr.id DESC
            ');
        }
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $db = self::db();
        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null) {
            if (empty($allowed) || !in_array($id, $allowed, true)) {
                return null;
            }
        }
        $stmt = $db->prepare('
            SELECT pr.id, pr.name, pr.description
            FROM projects pr
            WHERE pr.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO projects (name, description) VALUES (?, ?)');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['description'] ?? ''),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if (!self::find($id)) return false;
        $stmt = self::db()->prepare('UPDATE projects SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([
            trim($data['name'] ?? ''),
            trim($data['description'] ?? ''),
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
