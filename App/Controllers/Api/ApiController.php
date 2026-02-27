<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use Core\Database;
use App\Models\Structure;
use App\UserProjects;
use App\NotificationService;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function projects(): void
    {
        if (!Auth::canAny(['view_projects', 'add_projects', 'edit_projects', 'view_profiles', 'add_profiles', 'edit_profiles', 'view_grievance', 'add_grievance', 'edit_grievance', 'view_users', 'add_users', 'edit_users'])) {
            $this->json([]);
            return;
        }
        $q = $_GET['q'] ?? '';
        $db = Database::getInstance();
        $term = '%' . $q . '%';

        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null && empty($allowed)) {
            $this->json([]);
            return;
        }

        $params = [$term];
        $where = 'name LIKE ?';
        if ($allowed !== null) {
            $placeholders = implode(',', array_fill(0, count($allowed), '?'));
            $where .= " AND id IN ($placeholders)";
            foreach ($allowed as $pid) {
                $params[] = $pid;
            }
        }

        $sql = "SELECT id, name FROM projects WHERE $where ORDER BY name LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->json($rows);
    }

    public function profiles(): void
    {
        if (!Auth::canAny(['view_structure', 'add_structure', 'edit_structure', 'view_profiles', 'view_grievance', 'add_grievance', 'edit_grievance'])) {
            $this->json([]);
            return;
        }
        $q = trim($_GET['q'] ?? '');
        $db = Database::getInstance();
        $search = '%' . $q . '%';

        $allowed = UserProjects::allowedProjectIds();
        if ($allowed !== null && empty($allowed)) {
            $this->json([]);
            return;
        }

        $params = [$q, $search, $search];
        $projectFilter = '';
        if ($allowed !== null) {
            $placeholders = implode(',', array_fill(0, count($allowed), '?'));
            $projectFilter = " AND p.project_id IN ($placeholders)";
            foreach ($allowed as $pid) {
                $params[] = $pid;
            }
        }

        $sql = '
            SELECT p.id, COALESCE(NULLIF(p.full_name,""), p.papsid, "") as name,
                   p.project_id, proj.name as project_name
            FROM profiles p
            LEFT JOIN projects proj ON proj.id = p.project_id
            WHERE (? = "" OR p.full_name LIKE ? OR p.papsid LIKE ?)' . $projectFilter . '
            ORDER BY COALESCE(NULLIF(p.full_name,""), p.papsid)
            LIMIT 20';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->json($rows);
    }

    public function profileStructures(int $profileId): void
    {
        if (!Auth::canAny(['view_structure', 'view_profiles', 'edit_profiles'])) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden']);
            return;
        }
        $structures = Structure::byOwner($profileId);
        $out = [];
        foreach ($structures as $s) {
            $out[] = [
                'id' => $s->id,
                'strid' => $s->strid,
                'structure_tag' => $s->structure_tag,
                'description' => $s->description,
                'other_details' => $s->other_details,
                'tagging_images' => $s->tagging_images,
                'structure_images' => $s->structure_images,
            ];
        }
        $this->json($out);
    }

    public function notifications(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->json([]);
            return;
        }
        $list = NotificationService::getForUser($userId, 30);
        $out = [];
        foreach ($list as $n) {
            $out[] = [
                'id' => (int) $n->id,
                'type' => $n->type,
                'related_type' => $n->related_type,
                'related_id' => (int) $n->related_id,
                'message' => $n->message ?? '',
                'created_at' => $n->created_at ?? '',
                'url' => '/notifications/click/' . (int) $n->id,
            ];
        }
        $this->json($out);
    }
}

