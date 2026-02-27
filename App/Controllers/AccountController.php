<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;

/**
 * Logged-in user's own profile/account page.
 */
class AccountController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        $db = Database::getInstance();
        $linkedProjects = $this->getLinkedProjects($db, $user->id);
        $this->view('account/index', ['user' => $user, 'linkedProjects' => $linkedProjects]);
    }

    private function getLinkedProjects(\PDO $db, int $userId): array
    {
        $stmt = $db->prepare('
            SELECT p.id, p.name FROM projects p
            INNER JOIN user_projects up ON up.project_id = p.id
            WHERE up.user_id = ?
            ORDER BY p.name
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
