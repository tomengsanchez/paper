<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\NotificationService;

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

        $this->view('notifications/index', [
            'notifications' => $result['items'],
            'filters'       => $filters,
            'pagination'    => $result,
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
