<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;

class UserController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_users');
        $db = Database::getInstance();
        $stmt = $db->query('SELECT u.*, r.name as role_name FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id');
        $users = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $this->view('users/index', ['users' => $users]);
    }

    public function create(): void
    {
        $this->requireCapability('add_users');
        $roles = Database::getInstance()->query('SELECT * FROM roles')->fetchAll(\PDO::FETCH_OBJ);
        $this->view('users/form', ['user' => null, 'roles' => $roles]);
    }

    public function store(): void
    {
        $this->requireCapability('add_users');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);

        if (empty($username) || empty($password) || !$roleId) {
            $this->redirect('/users/create?error=1');
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $roleId]);
        $this->redirect('/users');
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
        $this->requireCapability('edit_users');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $db = Database::getInstance();
        if (!empty($password)) {
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, role_id = ? WHERE id = ?');
            $stmt->execute([$username, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $roleId, $id]);
        } else {
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?');
            $stmt->execute([$username, $email ?: null, $roleId, $id]);
        }
        $this->redirect('/users');
    }

    public function delete(int $id): void
    {
        $this->requireCapability('delete_users');
        if ($id === Auth::id()) {
            $this->redirect('/users?error=self');
            return;
        }
        Database::getInstance()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $this->redirect('/users');
    }
}
