<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use Core\Database;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Dashboard data: Users by role (framework base).
     */
    public function index(): void
    {
        $db = Database::getInstance();

        $result = [
            'users' => $this->usersByRole($db),
        ];

        $this->json($result);
    }

    private function usersByRole(\PDO $db): array
    {
        $stmt = $db->prepare("
            SELECT r.name as role_name, COUNT(u.id) as cnt
            FROM users u
            JOIN roles r ON r.id = u.role_id
            GROUP BY r.id, r.name
            ORDER BY r.name
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        return array_map(fn($r) => ['role' => $r->role_name ?? '', 'count' => (int) ($r->cnt ?? 0)], $rows);
    }
}
