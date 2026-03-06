<?php
namespace App\Models;

use Core\Database;
use App\UserProjects;

class SocioEconomicForm
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function listPaginated(string $search, int $page, int $perPage): array
    {
        $db = self::db();
        $params = [];

        $allowed = UserProjects::allowedProjectIds();
        $projectFilter = '';
        if ($allowed !== null) {
            if (empty($allowed)) {
                $projectFilter = ' AND 1=0';
            } else {
                $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                $projectFilter = " AND f.project_id IN ($placeholders)";
                foreach ($allowed as $pid) {
                    $params[] = $pid;
                }
            }
        }

        $searchCond = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $searchCond = ' AND (f.title LIKE ? OR p.name LIKE ?)';
            $params[] = $term;
            $params[] = $term;
        }

        $page = max(1, $page);
        $limit = max(10, min(100, $perPage));
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                f.id,
                f.project_id,
                f.title,
                f.description,
                f.created_at,
                f.updated_at,
                p.name AS project_name
            FROM socio_economic_forms f
            LEFT JOIN projects p ON p.id = f.project_id
            WHERE 1=1 {$projectFilter} {$searchCond}
            ORDER BY f.created_at DESC, f.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "
            SELECT COUNT(*)
            FROM socio_economic_forms f
            LEFT JOIN projects p ON p.id = f.project_id
            WHERE 1=1 {$projectFilter} {$searchCond}
        ";
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

    public static function find(int $id): ?object
    {
        $db = self::db();

        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null) {
            if (empty($allowed)) {
                return null;
            }
        }

        $params = [$id];
        $projectFilter = '';
        if ($allowed !== null) {
            if (!in_array($id, $allowed, true)) {
                // Form is linked to a project that current user should not see
                // We enforce via join filter instead of checking project_id here
            }
        }

        $sql = "
            SELECT
                f.id,
                f.project_id,
                f.title,
                f.description,
                f.created_at,
                f.updated_at,
                p.name AS project_name
            FROM socio_economic_forms f
            LEFT JOIN projects p ON p.id = f.project_id
            WHERE f.id = ?
            LIMIT 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row) {
            return null;
        }

        if ($allowed !== null && $row->project_id !== null && !in_array((int)$row->project_id, $allowed, true)) {
            return null;
        }

        return $row;
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $stmt = $db->prepare('
            INSERT INTO socio_economic_forms (project_id, title, description)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([
            $data['project_id'] !== '' ? (int)$data['project_id'] : null,
            trim($data['title'] ?? ''),
            trim($data['description'] ?? ''),
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = self::db();
        $stmt = $db->prepare('
            UPDATE socio_economic_forms
            SET project_id = ?, title = ?, description = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $data['project_id'] !== '' ? (int)$data['project_id'] : null,
            trim($data['title'] ?? ''),
            trim($data['description'] ?? ''),
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = self::db();
        $stmt = $db->prepare('DELETE FROM socio_economic_forms WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}

