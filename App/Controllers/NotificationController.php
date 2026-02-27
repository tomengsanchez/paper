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
        $notifications = NotificationService::getForUser($userId, 200);
        $this->view('notifications/index', ['notifications' => $notifications]);
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
