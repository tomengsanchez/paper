<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Database;

class GrievanceController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function dashboard(): void
    {
        $this->requireCapability('view_grievance');
        $db = Database::getInstance();

        $selectedProjectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
        if ($selectedProjectId < 0) {
            $selectedProjectId = 0;
        }

        // Total grievances (optionally filtered by project)
        if ($selectedProjectId > 0) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM grievances WHERE project_id = ?');
            $stmt->execute([$selectedProjectId]);
            $total = (int) $stmt->fetchColumn();
        } else {
            $total = (int) $db->query('SELECT COUNT(*) FROM grievances')->fetchColumn();
        }

        // Recent grievances list (optionally filtered by project)
        $recentSql = 'SELECT g.id, g.grievance_case_number, g.date_recorded, g.status, g.progress_level,
            COALESCE(p.full_name, g.respondent_full_name) as respondent_name
            FROM grievances g
            LEFT JOIN profiles p ON p.id = g.profile_id';
        $recentParams = [];
        if ($selectedProjectId > 0) {
            $recentSql .= ' WHERE g.project_id = ?';
            $recentParams[] = $selectedProjectId;
        }
        $recentSql .= ' ORDER BY g.id DESC LIMIT 10';
        $stmt = $db->prepare($recentSql);
        $stmt->execute($recentParams);
        $recent = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // Status breakdown (optionally filtered by project)
        $statusSql = 'SELECT status, COUNT(*) as cnt FROM grievances';
        $statusParams = [];
        if ($selectedProjectId > 0) {
            $statusSql .= ' WHERE project_id = ?';
            $statusParams[] = $selectedProjectId;
        }
        $statusSql .= ' GROUP BY status';
        $stmt = $db->prepare($statusSql);
        $stmt->execute($statusParams);
        $statusBreakdown = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // Monthly trend and this/last month counts (single aggregate query)
        $trendMonths = 12;
        $trendStart = date('Y-m-01 00:00:00', strtotime('-' . ($trendMonths - 1) . ' months'));
        $trendSql = '
            SELECT DATE_FORMAT(date_recorded, "%Y-%m") AS ym, COUNT(*) AS cnt
            FROM grievances
            WHERE date_recorded >= ?
        ';
        $trendParams = [$trendStart];
        if ($selectedProjectId > 0) {
            $trendSql .= ' AND project_id = ?';
            $trendParams[] = $selectedProjectId;
        }
        $trendSql .= ' GROUP BY ym ORDER BY ym';
        $stmt = $db->prepare($trendSql);
        $stmt->execute($trendParams);
        $trendByKey = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $key = (string) ($row->ym ?? '');
            if ($key === '') {
                continue;
            }
            $trendByKey[$key] = (int) ($row->cnt ?? 0);
        }

        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i months"));
            $monthlyTrend[] = [
                'month' => $key,
                'label' => date('M Y', strtotime("-$i months")),
                'count' => $trendByKey[$key] ?? 0,
            ];
        }

        $currentKey = date('Y-m');
        $lastKey = date('Y-m', strtotime('-1 month'));
        $thisMonth = $trendByKey[$currentKey] ?? 0;
        $lastMonth = $trendByKey[$lastKey] ?? 0;

        // By project breakdown (honors selected project filter, so it will
        // typically show a single row when a project is selected).
        $byProjectSql = '
            SELECT proj.name as project_name, COUNT(*) as cnt
            FROM grievances g
            LEFT JOIN projects proj ON proj.id = g.project_id
        ';
        $byProjectParams = [];
        if ($selectedProjectId > 0) {
            $byProjectSql .= ' WHERE g.project_id = ?';
            $byProjectParams[] = $selectedProjectId;
        }
        $byProjectSql .= '
            GROUP BY g.project_id, proj.name
            ORDER BY cnt DESC
            LIMIT 8
        ';
        $stmt = $db->prepare($byProjectSql);
        $stmt->execute($byProjectParams);
        $byProject = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // In-progress by stage (optionally filtered by project)
        $inProgressSql = "
            SELECT COALESCE(pl.name, CONCAT('Level ', g.progress_level)) as level_name, COUNT(*) as cnt
            FROM grievances g
            LEFT JOIN grievance_progress_levels pl ON pl.id = g.progress_level
            WHERE g.status = 'in_progress'
        ";
        $inProgressParams = [];
        if ($selectedProjectId > 0) {
            $inProgressSql .= " AND g.project_id = ?";
            $inProgressParams[] = $selectedProjectId;
        }
        $inProgressSql .= "
            GROUP BY g.progress_level, pl.name
            ORDER BY g.progress_level
        ";
        $stmt = $db->prepare($inProgressSql);
        $stmt->execute($inProgressParams);
        $inProgressLevels = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // Count grievances that have exceeded days_to_address based on when they
        // entered the current in-progress level (not the original date_recorded).
        $needsEscalationSql = "
            SELECT g.progress_level, COUNT(*) as cnt
            FROM grievances g
            JOIN grievance_progress_levels pl ON pl.id = g.progress_level
            JOIN (
                SELECT grievance_id, progress_level, MAX(created_at) AS level_started_at
                FROM grievance_status_log
                WHERE status = 'in_progress' AND progress_level IS NOT NULL
                GROUP BY grievance_id, progress_level
            ) l ON l.grievance_id = g.id AND l.progress_level = g.progress_level
            WHERE g.status = 'in_progress'
              AND pl.days_to_address IS NOT NULL
              AND pl.days_to_address > 0
              AND DATEDIFF(CURDATE(), DATE(l.level_started_at)) > pl.days_to_address
        ";
        $needsEscalationParams = [];
        if ($selectedProjectId > 0) {
            $needsEscalationSql .= " AND g.project_id = ?";
            $needsEscalationParams[] = $selectedProjectId;
        }
        $needsEscalationSql .= "
            GROUP BY g.progress_level
        ";
        $stmt = $db->prepare($needsEscalationSql);
        $stmt->execute($needsEscalationParams);
        $needsEscalationRaw = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $needsEscalationByLevel = [];
        foreach ($needsEscalationRaw as $row) {
            $levelId = (int) ($row->progress_level ?? 0);
            if ($levelId <= 0) {
                continue;
            }
            $needsEscalationByLevel[$levelId] = (int) ($row->cnt ?? 0);
        }

        // By category of grievance (JSON array column, optionally filtered by project)
        $byCategorySql = "
            SELECT c.id, c.name, COUNT(g.id) AS cnt
            FROM grievance_categories c
            LEFT JOIN grievances g
              ON JSON_CONTAINS(g.grievance_category_ids, CAST(c.id AS CHAR), '$')
        ";
        $byCategoryParams = [];
        if ($selectedProjectId > 0) {
            $byCategorySql .= " AND g.project_id = ?";
            $byCategoryParams[] = $selectedProjectId;
        }
        $byCategorySql .= "
            GROUP BY c.id, c.name
            ORDER BY cnt DESC, c.sort_order, c.name
        ";
        $stmt = $db->prepare($byCategorySql);
        $stmt->execute($byCategoryParams);
        $byCategory = $stmt->fetchAll(\PDO::FETCH_OBJ);

        // By type of grievance (JSON array column, optionally filtered by project)
        $byTypeSql = "
            SELECT t.id, t.name, COUNT(g.id) AS cnt
            FROM grievance_types t
            LEFT JOIN grievances g
              ON JSON_CONTAINS(g.grievance_type_ids, CAST(t.id AS CHAR), '$')
        ";
        $byTypeParams = [];
        if ($selectedProjectId > 0) {
            $byTypeSql .= " AND g.project_id = ?";
            $byTypeParams[] = $selectedProjectId;
        }
        $byTypeSql .= "
            GROUP BY t.id, t.name
            ORDER BY cnt DESC, t.sort_order, t.name
        ";
        $stmt = $db->prepare($byTypeSql);
        $stmt->execute($byTypeParams);
        $byType = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $this->json([
            'totalGrievances'        => $total,
            'recentGrievances'       => $recent,
            'statusBreakdown'        => $statusBreakdown,
            'thisMonth'              => $thisMonth,
            'lastMonth'              => $lastMonth,
            'byProject'              => $byProject,
            'monthlyTrend'           => $monthlyTrend,
            'byCategory'             => $byCategory,
            'byType'                 => $byType,
            'inProgressLevels'       => $inProgressLevels,
            'needsEscalationByLevel' => $needsEscalationByLevel,
        ]);
    }
}

