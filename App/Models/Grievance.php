<?php
namespace App\Models;

use Core\Database;
use App\UserProjects;

class Grievance
{
    protected static string $table = 'grievances';

    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function parseJson(?string $json): array
    {
        if (empty(trim($json ?? ''))) return [];
        $d = json_decode($json, true);
        return is_array($d) ? array_map('intval', $d) : [];
    }

    public static function find(int $id): ?object
    {
        $db = self::db();
        $stmt = $db->prepare('
            SELECT g.*, p.full_name as profile_name, p.papsid, proj.name as project_name
            FROM grievances g
            LEFT JOIN profiles p ON p.id = g.profile_id
            LEFT JOIN projects proj ON proj.id = g.project_id
            WHERE g.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row) {
            return null;
        }
        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null) {
            $projectId = (int) ($row->project_id ?? 0);
            if ($projectId > 0 && !in_array($projectId, $allowed, true)) {
                return null;
            }
        }
        return $row;
    }

    public static function listPaginated(string $search, array $searchColumns, string $sortBy, string $sortOrder, int $page, int $perPage, ?int $afterId = null, ?int $beforeId = null, array $filters = []): array
    {
        $db = self::db();
        $sortCol = match ($sortBy) {
            'date_recorded' => 'g.date_recorded',
            'grievance_case_number' => 'g.grievance_case_number',
            'status' => 'g.status',
            'respondent_name' => 'COALESCE(p.full_name, g.respondent_full_name)',
            'profile_name' => 'p.full_name',
            default => 'g.id',
        };
        $dir = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $params = [];
        $whereCond = '';

        // Text search on selected columns (cast param so collation matches table columns)
        if ($search !== '') {
            $term = '%' . $search . '%';
            $coll = ' COLLATE utf8mb4_unicode_ci';
            $likeParam = 'CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci';
            $conds = [];
            if (in_array('grievance_case_number', $searchColumns, true)) { $conds[] = 'g.grievance_case_number' . $coll . ' LIKE ' . $likeParam; $params[] = $term; }
            if (in_array('status', $searchColumns, true)) { $conds[] = 'g.status' . $coll . ' LIKE ' . $likeParam; $params[] = $term; }
            if (in_array('respondent_name', $searchColumns, true)) { $conds[] = '(COALESCE(p.full_name, g.respondent_full_name)' . $coll . ' LIKE ' . $likeParam . ')'; $params[] = $term; }
            if (in_array('profile_name', $searchColumns, true)) { $conds[] = 'p.full_name' . $coll . ' LIKE ' . $likeParam; $params[] = $term; }
            if (!empty($conds)) {
                $whereCond .= ' AND (' . implode(' OR ', $conds) . ')';
            }
        }

        // Structured filters
        $statusFilter = $filters['status'] ?? null;
        if ($statusFilter && in_array($statusFilter, ['open', 'in_progress', 'closed'], true)) {
            $whereCond .= ' AND g.status = ?';
            $params[] = $statusFilter;
        }

        $projectIdFilter = isset($filters['project_id']) ? (int) $filters['project_id'] : 0;
        if ($projectIdFilter > 0) {
            $whereCond .= ' AND g.project_id = ?';
            $params[] = $projectIdFilter;
        }

        $progressLevelFilter = isset($filters['progress_level']) ? (int) $filters['progress_level'] : 0;
        if ($progressLevelFilter > 0) {
            $whereCond .= ' AND g.progress_level = ?';
            $params[] = $progressLevelFilter;
        }

        $needsEscalationFilter = $filters['needs_escalation'] ?? null;
        if ($needsEscalationFilter === '1') {
            // Only grievances that currently need escalation/closure based on days_to_address
            $whereCond .= "
                AND g.status = 'in_progress'
                AND EXISTS (
                    SELECT 1
                    FROM grievance_progress_levels pl
                    JOIN (
                        SELECT grievance_id, progress_level, MAX(created_at) AS level_started_at
                        FROM grievance_status_log
                        WHERE status = 'in_progress' AND progress_level IS NOT NULL
                        GROUP BY grievance_id, progress_level
                    ) l ON l.grievance_id = g.id AND l.progress_level = g.progress_level
                    WHERE pl.id = g.progress_level
                      AND pl.days_to_address IS NOT NULL
                      AND pl.days_to_address > 0
                      AND DATEDIFF(CURDATE(), DATE(l.level_started_at)) > pl.days_to_address
                )
            ";
        }

        $limit = max(1, min(100, $perPage));
        $cursorCond = '';
        if ($afterId !== null) {
            $cursorCond = $dir === 'DESC' ? ' AND g.id < ?' : ' AND g.id > ?';
            $params[] = $afterId;
        } elseif ($beforeId !== null) {
            $cursorCond = $dir === 'DESC' ? ' AND g.id > ?' : ' AND g.id < ?';
            $params[] = $beforeId;
        }

        $offset = ($afterId === null && $beforeId === null) ? ($page - 1) * $limit : 0;
        $limitClause = $cursorCond !== '' ? "LIMIT $limit" : "LIMIT $limit OFFSET $offset";

        // Restrict to grievances in the user's allowed projects (non-admin)
        $allowed = UserProjects::allowedProjectIds();
        $projectFilter = '';
        if ($allowed !== null) {
            if (empty($allowed)) {
                $projectFilter = ' AND 1=0';
            } else {
                $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                $projectFilter = " AND g.project_id IN ($placeholders)";
                foreach ($allowed as $pid) {
                    $params[] = $pid;
                }
            }
        }

        $sql = "SELECT g.id, g.date_recorded, g.grievance_case_number, g.project_id, g.status, g.progress_level, g.profile_id, g.respondent_full_name,
            COALESCE(p.full_name, g.respondent_full_name) as respondent_name,
            p.full_name as profile_name,
            proj.name as project_name
            FROM grievances g
            LEFT JOIN profiles p ON p.id = g.profile_id
            LEFT JOIN projects proj ON proj.id = g.project_id
            WHERE 1=1 $whereCond $projectFilter $cursorCond
            ORDER BY $sortCol $dir
            $limitClause";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countParams = $cursorCond !== '' ? array_slice($params, 0, -1) : $params;
        $countSql = "SELECT COUNT(*) FROM grievances g LEFT JOIN profiles p ON p.id = g.profile_id LEFT JOIN projects proj ON proj.id = g.project_id WHERE 1=1 $whereCond $projectFilter";
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

    public static function create(array $data): int
    {
        $db = self::db();
        $stmt = $db->prepare('INSERT INTO grievances (
            date_recorded, grievance_case_number, project_id, is_paps, profile_id, respondent_full_name,
            gender, gender_specify, valid_id_philippines, id_number, vulnerability_ids, respondent_type_ids, respondent_type_other_specify,
            home_business_address, mobile_number, email, contact_others_specify,
            grm_channel_ids, preferred_language_ids, preferred_language_other_specify, grievance_type_ids, grievance_category_ids,
            location_same_as_address, location_specify,
            incident_one_time, incident_date, incident_multiple, incident_dates, incident_ongoing,
            description_complaint, desired_resolution, status, progress_level
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            self::parseDatetime($data['date_recorded'] ?? null),
            trim($data['grievance_case_number'] ?? ''),
            (int) ($data['project_id'] ?? 0) ?: null,
            !empty($data['is_paps']) ? 1 : 0,
            (int) ($data['profile_id'] ?? 0) ?: null,
            trim($data['respondent_full_name'] ?? ''),
            trim($data['gender'] ?? ''),
            trim($data['gender_specify'] ?? ''),
            trim($data['valid_id_philippines'] ?? ''),
            trim($data['id_number'] ?? ''),
            json_encode(self::ensureArray($data['vulnerability_ids'] ?? [])),
            json_encode(self::ensureArray($data['respondent_type_ids'] ?? [])),
            trim($data['respondent_type_other_specify'] ?? ''),
            trim($data['home_business_address'] ?? ''),
            trim($data['mobile_number'] ?? ''),
            trim($data['email'] ?? ''),
            trim($data['contact_others_specify'] ?? ''),
            json_encode(self::ensureArray($data['grm_channel_ids'] ?? [])),
            json_encode(self::ensureArray($data['preferred_language_ids'] ?? [])),
            trim($data['preferred_language_other_specify'] ?? ''),
            json_encode(self::ensureArray($data['grievance_type_ids'] ?? [])),
            json_encode(self::ensureArray($data['grievance_category_ids'] ?? [])),
            !empty($data['location_same_as_address']) ? 1 : 0,
            trim($data['location_specify'] ?? ''),
            !empty($data['incident_one_time']) ? 1 : 0,
            self::parseDate($data['incident_date'] ?? null),
            !empty($data['incident_multiple']) ? 1 : 0,
            trim($data['incident_dates'] ?? ''),
            !empty($data['incident_ongoing']) ? 1 : 0,
            trim($data['description_complaint'] ?? ''),
            trim($data['desired_resolution'] ?? ''),
            $data['status'] ?? 'open',
            isset($data['progress_level']) && $data['progress_level'] !== '' ? (int) $data['progress_level'] : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = self::db()->prepare('UPDATE grievances SET
            date_recorded = ?, grievance_case_number = ?, project_id = ?, is_paps = ?, profile_id = ?, respondent_full_name = ?,
            gender = ?, gender_specify = ?, valid_id_philippines = ?, id_number = ?, vulnerability_ids = ?, respondent_type_ids = ?, respondent_type_other_specify = ?,
            home_business_address = ?, mobile_number = ?, email = ?, contact_others_specify = ?,
            grm_channel_ids = ?, preferred_language_ids = ?, preferred_language_other_specify = ?, grievance_type_ids = ?, grievance_category_ids = ?,
            location_same_as_address = ?, location_specify = ?,
            incident_one_time = ?, incident_date = ?, incident_multiple = ?, incident_dates = ?, incident_ongoing = ?,
            description_complaint = ?, desired_resolution = ?, status = ?, progress_level = ?
            WHERE id = ?');
        $stmt->execute([
            self::parseDatetime($data['date_recorded'] ?? null),
            trim($data['grievance_case_number'] ?? ''),
            (int) ($data['project_id'] ?? 0) ?: null,
            !empty($data['is_paps']) ? 1 : 0,
            (int) ($data['profile_id'] ?? 0) ?: null,
            trim($data['respondent_full_name'] ?? ''),
            trim($data['gender'] ?? ''),
            trim($data['gender_specify'] ?? ''),
            trim($data['valid_id_philippines'] ?? ''),
            trim($data['id_number'] ?? ''),
            json_encode(self::ensureArray($data['vulnerability_ids'] ?? [])),
            json_encode(self::ensureArray($data['respondent_type_ids'] ?? [])),
            trim($data['respondent_type_other_specify'] ?? ''),
            trim($data['home_business_address'] ?? ''),
            trim($data['mobile_number'] ?? ''),
            trim($data['email'] ?? ''),
            trim($data['contact_others_specify'] ?? ''),
            json_encode(self::ensureArray($data['grm_channel_ids'] ?? [])),
            json_encode(self::ensureArray($data['preferred_language_ids'] ?? [])),
            trim($data['preferred_language_other_specify'] ?? ''),
            json_encode(self::ensureArray($data['grievance_type_ids'] ?? [])),
            json_encode(self::ensureArray($data['grievance_category_ids'] ?? [])),
            !empty($data['location_same_as_address']) ? 1 : 0,
            trim($data['location_specify'] ?? ''),
            !empty($data['incident_one_time']) ? 1 : 0,
            self::parseDate($data['incident_date'] ?? null),
            !empty($data['incident_multiple']) ? 1 : 0,
            trim($data['incident_dates'] ?? ''),
            !empty($data['incident_ongoing']) ? 1 : 0,
            trim($data['description_complaint'] ?? ''),
            trim($data['desired_resolution'] ?? ''),
            $data['status'] ?? 'open',
            isset($data['progress_level']) && $data['progress_level'] !== '' ? (int) $data['progress_level'] : null,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function updateStatus(int $id, string $status, ?int $progressLevel): bool
    {
        $stmt = self::db()->prepare('UPDATE grievances SET status = ?, progress_level = ? WHERE id = ?');
        $stmt->execute([$status, $progressLevel, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM grievances WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function generateCaseNumber(): string
    {
        $yearMonth = date('Ym');
        $prefix = "GRV-{$yearMonth}-";
        $stmt = self::db()->prepare('SELECT grievance_case_number FROM grievances WHERE grievance_case_number LIKE ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        if (!$last) return $prefix . '0001';
        $num = (int) substr($last, strlen($prefix));
        return $prefix . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }

    private static function parseDatetime($v): ?string
    {
        if ($v === null || $v === '') return null;
        $t = strtotime($v);
        return $t ? date('Y-m-d H:i:s', $t) : null;
    }

    private static function parseDate($v): ?string
    {
        if ($v === null || $v === '') return null;
        $t = strtotime($v);
        return $t ? date('Y-m-d', $t) : null;
    }

    private static function ensureArray($v): array
    {
        if (is_array($v)) return array_map('intval', array_filter($v));
        if (is_string($v)) {
            $d = json_decode($v, true);
            return is_array($d) ? array_map('intval', array_filter($d)) : [];
        }
        return [];
    }
}
