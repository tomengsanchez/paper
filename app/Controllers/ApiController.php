<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;

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
        $attrId = $db->prepare('SELECT id FROM eav_attributes WHERE entity_type = ? AND name = ?');
        $attrId->execute(['project', 'name']);
        $nameAttrId = $attrId->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0;

        if (!$nameAttrId) {
            $this->json([]);
            return;
        }

        $stmt = $db->prepare('SELECT e.id, v.value as name FROM eav_entities e 
            JOIN eav_values v ON e.id = v.entity_id AND v.attribute_id = ?
            WHERE e.entity_type = "project" AND v.value LIKE ?
            ORDER BY v.value LIMIT 20');
        $stmt->execute([$nameAttrId, '%' . $q . '%']);
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
        $stmt = $db->prepare('SELECT id FROM eav_attributes WHERE entity_type = ? AND name = ?');
        $stmt->execute(['profile', 'full_name']);
        $fullNameAttrId = $stmt->fetchColumn();
        $stmt->execute(['profile', 'papsid']);
        $papsidAttrId = $stmt->fetchColumn();
        if (!$fullNameAttrId && !$papsidAttrId) {
            $this->json([]);
            return;
        }
        $search = '%' . $q . '%';
        $stmt = $db->prepare('
            SELECT e.id, COALESCE(vf.value, vp.value, "") as name
            FROM eav_entities e
            LEFT JOIN eav_values vf ON e.id = vf.entity_id AND vf.attribute_id = ?
            LEFT JOIN eav_values vp ON e.id = vp.entity_id AND vp.attribute_id = ?
            WHERE e.entity_type = "profile"
            AND (? = "" OR vf.value LIKE ? OR vp.value LIKE ?)
            ORDER BY COALESCE(NULLIF(vf.value,""), vp.value)
            LIMIT 20');
        $fnId = (int) ($fullNameAttrId ?: 0);
        $psId = (int) ($papsidAttrId ?: 0);
        $stmt->execute([$fnId, $psId, $q, $search, $search]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->json($rows);
    }
}
