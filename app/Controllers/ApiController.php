<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;
use App\Models\Structure;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function coordinators(): void
    {
        if (!Auth::canAny(['view_projects', 'add_projects', 'edit_projects'])) {
            $this->json([]);
            return;
        }
        $q = $_GET['q'] ?? '';
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT u.id, u.username FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.name = "Coordinator" AND u.username LIKE ? 
            ORDER BY u.username LIMIT 20');
        $stmt->execute(['%' . $q . '%']);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->json($rows);
    }

    public function projects(): void
    {
        if (!Auth::canAny(['view_projects', 'add_projects', 'edit_projects', 'view_profiles', 'add_profiles', 'edit_profiles'])) {
            $this->json([]);
            return;
        }
        $q = $_GET['q'] ?? '';
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, name FROM projects WHERE name LIKE ? ORDER BY name LIMIT 20');
        $stmt->execute(['%' . $q . '%']);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->json($rows);
    }

    public function profiles(): void
    {
        if (!\Core\Auth::canAny(['view_structure', 'add_structure', 'edit_structure', 'view_profiles'])) {
            $this->json([]);
            return;
        }
        $q = trim($_GET['q'] ?? '');
        $db = Database::getInstance();
        $search = '%' . $q . '%';
        $stmt = $db->prepare('
            SELECT id, COALESCE(NULLIF(full_name,""), papsid, "") as name
            FROM profiles
            WHERE ? = "" OR full_name LIKE ? OR papsid LIKE ?
            ORDER BY COALESCE(NULLIF(full_name,""), papsid)
            LIMIT 20');
        $stmt->execute([$q, $search, $search]);
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
}
