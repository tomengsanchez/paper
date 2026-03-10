<?php
namespace App\Controllers\Api;

use Core\Controller;
use Core\Auth;
use App\NotificationService;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
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
