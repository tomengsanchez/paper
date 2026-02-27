<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\NotificationService;
use App\Models\Project;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->redirect('/login');
            return;
        }

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $filters = [
            'from'       => $_GET['from'] ?? '',
            'to'         => $_GET['to'] ?? '',
            'module'     => $_GET['module'] ?? '',
            'project_id' => isset($_GET['project_id']) ? (int) $_GET['project_id'] : null,
        ];

        $result = NotificationService::listForUser($userId, $filters, $page, 20);
        $projects = Project::all();

        $this->view('notifications/index', [
            'notifications' => $result['items'],
            'filters'       => $filters,
            'pagination'    => $result,
            'projects'      => $projects,
        ]);
    }

    public function click(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->redirect('/login');
            return;
        }
        $url = NotificationService::clickAndGetUrl($id, $userId);
        $this->redirect($url ?? '/');
    }
}
