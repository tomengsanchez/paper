<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;
use App\ListConfig;
use App\ListHelper;

class UserController extends Controller
{
    private const LIST_BASE = '/users';
    private const LIST_MODULE = 'users';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_users');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $db = Database::getInstance();
        $stmt = $db->query('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id');
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $rows = ListHelper::search($rows, $search, $columns, self::LIST_MODULE);
        $rows = ListHelper::sort($rows, $sort ?: ($columns[0] ?? 'id'), $order, $columns, self::LIST_MODULE);
        $pagination = ListHelper::paginate($rows, $page, $perPage);

        $this->view('users/index', [
            'users' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort ?: ($columns[0] ?? ''),
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::LIST_MODULE),
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_users');
        $roles = Database::getInstance()->query('SELECT * FROM roles')->fetchAll(\PDO::FETCH_OBJ);
        $this->view('users/form', ['user' => null, 'roles' => $roles, 'linkedProjects' => []]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_users');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $projectIds = isset($_POST['project_ids']) && is_array($_POST['project_ids'])
            ? array_map('intval', array_filter($_POST['project_ids'])) : [];

        if (empty($username) || empty($password) || !$roleId) {
            $this->redirect('/users/create?error=1');
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO users (username, email, display_name, password_hash, role_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $email ?: null, $displayName ?: null, password_hash($password, PASSWORD_DEFAULT), $roleId]);
        $userId = (int) $db->lastInsertId();
        $this->saveUserProjects($db, $userId, $projectIds);
        $this->redirect('/users/view/' . $userId);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_users');
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$user) {
            $this->redirect('/users');
            return;
        }
        $linkedProjects = $this->getLinkedProjects($db, $id);
        $this->view('users/view', ['user' => $user, 'linkedProjects' => $linkedProjects]);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_users');
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$user) {
            $this->redirect('/users');
            return;
        }
        $roles = $db->query('SELECT * FROM roles')->fetchAll(\PDO::FETCH_OBJ);
        $linkedProjects = $this->getLinkedProjects($db, $id);
        $this->view('users/form', ['user' => $user, 'roles' => $roles, 'linkedProjects' => $linkedProjects]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_users');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $projectIds = isset($_POST['project_ids']) && is_array($_POST['project_ids'])
            ? array_map('intval', array_filter($_POST['project_ids'])) : [];

        $db = Database::getInstance();
        if (!empty($password)) {
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, display_name = ?, password_hash = ?, role_id = ? WHERE id = ?');
            $stmt->execute([$username, $email ?: null, $displayName ?: null, password_hash($password, PASSWORD_DEFAULT), $roleId, $id]);
        } else {
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, display_name = ?, role_id = ? WHERE id = ?');
            $stmt->execute([$username, $email ?: null, $displayName ?: null, $roleId, $id]);
        }
        $this->saveUserProjects($db, $id, $projectIds);
        $this->redirect('/users/view/' . $id);
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_users');
        if ($id === Auth::id()) {
            $this->redirect('/users?error=self');
            return;
        }
        Database::getInstance()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $this->redirect('/users');
    }

    private function getLinkedProjects(\PDO $db, int $userId): array
    {
        $stmt = $db->prepare('
            SELECT p.id, p.name
            FROM user_projects up
            JOIN projects p ON p.id = up.project_id
            WHERE up.user_id = ?
            ORDER BY p.name
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    private function saveUserProjects(\PDO $db, int $userId, array $projectIds): void
    {
        $db->prepare('DELETE FROM user_projects WHERE user_id = ?')->execute([$userId]);
        if (empty($projectIds)) {
            return;
        }
        $stmt = $db->prepare('INSERT INTO user_projects (user_id, project_id) VALUES (?, ?)');
        foreach (array_unique($projectIds) as $pid) {
            if ($pid > 0) {
                $stmt->execute([$userId, $pid]);
            }
        }
    }
}
