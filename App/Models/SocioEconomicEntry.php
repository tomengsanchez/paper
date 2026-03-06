<?php
namespace App\Models;

use Core\Database;
use Core\Auth;

class SocioEconomicEntry
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function create(int $formId, ?int $projectId, ?int $profileId, array $data): int
    {
        $db = self::db();
        $stmt = $db->prepare('
            INSERT INTO socio_economic_entries (form_id, project_id, profile_id, data_json, created_by)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $formId,
            $projectId ?: null,
            $profileId ?: null,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            Auth::id() ?: null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function listByForm(int $formId, int $page, int $perPage): array
    {
        $db = self::db();
        $page = max(1, $page);
        $limit = max(10, min(100, $perPage));
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                e.id,
                e.form_id,
                e.project_id,
                e.profile_id,
                e.created_by,
                e.created_at,
                p.name AS project_name,
                pr.full_name AS profile_name,
                u.username AS created_by_name
            FROM socio_economic_entries e
            LEFT JOIN projects p ON p.id = e.project_id
            LEFT JOIN profiles pr ON pr.id = e.profile_id
            LEFT JOIN users u ON u.id = e.created_by
            WHERE e.form_id = ?
            ORDER BY e.created_at DESC, e.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$formId]);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM socio_economic_entries WHERE form_id = ?");
        $stmtCount->execute([$formId]);
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
}

