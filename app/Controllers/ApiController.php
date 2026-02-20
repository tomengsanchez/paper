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
}
