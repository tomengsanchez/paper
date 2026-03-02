<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use Core\Database;
use App\UserProjects;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Main dashboard data: Profile, Structure, Grievance, Users.
     * Scoped to projects linked to the current user.
     */
    public function index(): void
    {
        $db = Database::getInstance();
        $allowedProjects = UserProjects::allowedProjectIds();

        $result = [
            'profile' => $this->profileStats($db, $allowedProjects),
            'structure' => $this->structureStats($db, $allowedProjects),
            'grievance' => $this->grievanceStats($db, $allowedProjects),
            'users' => $this->usersByRole($db, $allowedProjects),
        ];

        $this->json($result);
    }

    private function profileStats(\PDO $db, ?array $allowedProjects): array
    {
        $profileWhere = $this->projectCondition('p.project_id', $allowedProjects);
        $params = $allowedProjects ?? [];

        $profileSub = "SELECT p.id FROM profiles p WHERE 1=1 {$profileWhere}";

        $stmt = $db->prepare("SELECT action, COUNT(*) as cnt FROM audit_log WHERE entity_type = 'profile' AND entity_id IN ($profileSub) GROUP BY action");
        $stmt->execute($params);
        $created = 0;
        $updated = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
            if (($r->action ?? '') === 'created') $created = (int) $r->cnt;
            if (($r->action ?? '') === 'updated') $updated = (int) $r->cnt;
        }

        $structWhere = $this->projectCondition('pr.project_id', $allowedProjects);
        $stmt = $db->prepare("SELECT COUNT(*) FROM audit_log a JOIN structures s ON a.entity_id = s.id AND a.entity_type = 'structure' AND a.action = 'created' JOIN profiles pr ON s.owner_id = pr.id WHERE 1=1 {$structWhere}");
        $stmt->execute($params);
        $addedStructures = (int) $stmt->fetchColumn();

        return [
            'created' => $created,
            'updated' => $updated,
            'added_structures' => $addedStructures,
        ];
    }

    private function structureStats(\PDO $db, ?array $allowedProjects): array
    {
        $structSub = "SELECT s.id FROM structures s JOIN profiles pr ON s.owner_id = pr.id WHERE 1=1 " . $this->projectCondition('pr.project_id', $allowedProjects);
        $params = $allowedProjects ?? [];

        $stmt = $db->prepare("SELECT action, COUNT(*) as cnt FROM audit_log WHERE entity_type = 'structure' AND entity_id IN ($structSub) GROUP BY action");
        $stmt->execute($params);
        $created = 0;
        $updated = 0;
        $added_images = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
            $a = $r->action ?? '';
            if ($a === 'created') $created = (int) $r->cnt;
            if ($a === 'updated') $updated = (int) $r->cnt;
            if ($a === 'attachments_uploaded') $added_images = (int) $r->cnt;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'added_images' => $added_images,
        ];
    }

    private function grievanceStats(\PDO $db, ?array $allowedProjects): array
    {
        $where = $this->projectCondition('g.project_id', $allowedProjects);
        $params = $allowedProjects ?? [];

        $grievanceSub = "SELECT g.id FROM grievances g WHERE 1=1 {$where}";

        $stmt = $db->prepare("SELECT action, COUNT(*) as cnt FROM audit_log WHERE entity_type = 'grievance' AND entity_id IN ($grievanceSub) GROUP BY action");
        $stmt->execute($params);
        $created = 0;
        $updated = 0;
        $status_changed = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
            $a = $r->action ?? '';
            if ($a === 'created') $created = (int) $r->cnt;
            if ($a === 'updated') $updated = (int) $r->cnt;
            if ($a === 'status_changed') $status_changed = (int) $r->cnt;
        }

        $stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM grievances g WHERE 1=1 {$where} GROUP BY status");
        $stmt->execute($params);
        $byStatus = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $r) {
            $byStatus[] = ['status' => $r->status ?? '', 'count' => (int) $r->cnt];
        }

        $escSql = "
            SELECT COUNT(*) FROM grievances g
            JOIN grievance_progress_levels pl ON pl.id = g.progress_level
            JOIN (
                SELECT grievance_id, progress_level, MAX(created_at) AS level_started_at
                FROM grievance_status_log
                WHERE status = 'in_progress' AND progress_level IS NOT NULL
                GROUP BY grievance_id, progress_level
            ) l ON l.grievance_id = g.id AND l.progress_level = g.progress_level
            WHERE g.status = 'in_progress'
              AND pl.days_to_address IS NOT NULL AND pl.days_to_address > 0
              AND DATEDIFF(CURDATE(), DATE(l.level_started_at)) > pl.days_to_address
              {$where}
        ";
        $stmt = $db->prepare($escSql);
        $stmt->execute($params);
        $escalations = (int) $stmt->fetchColumn();

        return [
            'created' => $created,
            'updated' => $updated,
            'status_changed' => $status_changed,
            'by_status' => $byStatus,
            'escalations' => $escalations,
        ];
    }

    private function usersByRole(\PDO $db, ?array $allowedProjects): array
    {
        $userFilter = '';
        $params = [];
        if ($allowedProjects !== null) {
            if (empty($allowedProjects)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($allowedProjects), '?'));
            $userFilter = " AND u.id IN (SELECT user_id FROM user_projects WHERE project_id IN ($ph))";
            $params = $allowedProjects;
        }

        $stmt = $db->prepare("
            SELECT r.name as role_name, COUNT(u.id) as cnt
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE 1=1 {$userFilter}
            GROUP BY r.id, r.name
            ORDER BY r.name
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($r) => ['role' => $r->role_name ?? '', 'count' => (int) ($r->cnt ?? 0)], $rows);
    }

    private function projectCondition(string $col, ?array $allowedProjects): string
    {
        if ($allowedProjects === null) {
            return '';
        }
        if (empty($allowedProjects)) {
            return ' AND 1=0';
        }
        $ph = implode(',', array_fill(0, count($allowedProjects), '?'));
        return " AND {$col} IN ($ph)";
    }
}
