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
        $stmt = $db->query('
            SELECT u.*, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.id
        ');
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
        $this->view('users/form', ['user' => null, 'roles' => $roles]);
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

        if (empty($username) || empty($password) || !$roleId) {
            $this->redirect('/users/create?error=1');
            return;
        }

        $policyError = \App\PasswordPolicy::validate($password);
        if ($policyError !== null) {
            $_SESSION['user_password_error'] = $policyError;
            $this->redirect('/users/create?error=policy');
            return;
        }

        $db = Database::getInstance();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, email, display_name, password_hash, role_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $email ?: null, $displayName ?: null, $passwordHash, $roleId]);
        $userId = (int) $db->lastInsertId();
        \App\PasswordPolicy::recordPasswordChange($userId, $passwordHash);
        \App\AuditLog::record('user', $userId, 'created');
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
        \App\AuditLog::record('user', (int) $id, 'viewed');
        $this->view('users/view', ['user' => $user]);
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
        $this->view('users/form', ['user' => $user, 'roles' => $roles]);
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

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT username, email, display_name, role_id FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $old = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$old) {
            $this->redirect('/users');
            return;
        }
        if (!empty($password)) {
            $policyError = \App\PasswordPolicy::validateForUser($password, (int) $id);
            if ($policyError !== null) {
                $_SESSION['user_password_error'] = $policyError;
                $this->redirect('/users/edit/' . $id . '?error=policy');
                return;
            }
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, display_name = ?, password_hash = ?, role_id = ?, password_changed_at = NOW() WHERE id = ?');
            $stmt->execute([$username, $email ?: null, $displayName ?: null, $passwordHash, $roleId, $id]);
            \App\PasswordPolicy::recordPasswordChange((int) $id, $passwordHash);
        } else {
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, display_name = ?, role_id = ? WHERE id = ?');
            $stmt->execute([$username, $email ?: null, $displayName ?: null, $roleId, $id]);
        }
        $changes = [];
        if ((string)($old->username ?? '') !== (string)$username) {
            $changes['username'] = ['from' => $old->username, 'to' => $username];
        }
        if ((string)($old->email ?? '') !== (string)$email) {
            $changes['email'] = ['from' => $old->email, 'to' => $email];
        }
        if ((string)($old->display_name ?? '') !== (string)$displayName) {
            $changes['display_name'] = ['from' => $old->display_name, 'to' => $displayName];
        }
        if ((int)($old->role_id ?? 0) !== $roleId) {
            $changes['role_id'] = ['from' => $old->role_id, 'to' => $roleId];
        }
        if (!empty($changes)) {
            \App\AuditLog::record('user', (int) $id, 'updated', $changes);
        }
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
}
