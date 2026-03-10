<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;
use App\ListConfig;
use App\ListHelper;
use App\Models\Employee;

class EmployeeController extends Controller
{
    private const LIST_BASE = '/hr/employees';
    private const LIST_MODULE = 'employees';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_employees');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'asc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM employees ORDER BY id');
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $rows = ListHelper::search($rows, $search, $columns, self::LIST_MODULE);
        $rows = ListHelper::sort($rows, $sort ?: ($columns[0] ?? 'id'), $order, $columns, self::LIST_MODULE);
        $pagination = ListHelper::paginate($rows, $page, $perPage);

        $this->view('employees/index', [
            'employees' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort ?: ($columns[0] ?? 'id'),
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::LIST_MODULE),
        ]);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_employees');
        $employee = Employee::find($id);
        if (!$employee) {
            $this->redirect('/hr/employees');
            return;
        }
        $linkedUser = null;
        if (!empty($employee->user_id)) {
            $linkedUser = Employee::findUserById((int) $employee->user_id);
        }
        $this->view('employees/view', ['employee' => $employee, 'linkedUser' => $linkedUser]);
    }

    public function create(): void
    {
        $this->requireCapability('add_employees');
        $db = Database::getInstance();
        $roles = $db->query('SELECT * FROM roles')->fetchAll(\PDO::FETCH_OBJ);
        $this->view('employees/form', ['employee' => null, 'roles' => $roles, 'linkedUser' => null]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireCapability('add_employees');
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $this->redirect('/hr/employees/create?error=1');
            return;
        }
        $isSystemUser = !empty($_POST['is_system_user']);
        $userId = null;

        if ($isSystemUser) {
            $username = trim($_POST['user_username'] ?? '');
            $password = $_POST['user_password'] ?? '';
            $roleId = (int) ($_POST['user_role_id'] ?? 0);
            if (empty($username) || empty($password) || !$roleId) {
                $_SESSION['employee_user_error'] = 'Username, password and role are required when creating a system user.';
                $this->redirect('/hr/employees/create?error=user');
                return;
            }
            $policyError = \App\PasswordPolicy::validate($password);
            if ($policyError !== null) {
                $_SESSION['employee_user_error'] = $policyError;
                $this->redirect('/hr/employees/create?error=policy');
                return;
            }
            $userId = $this->createOrUpdateUserForEmployee(null, [
                'username' => $username,
                'password' => $password,
                'role_id' => $roleId,
                'display_name' => $name,
                'email' => trim($_POST['email'] ?? ''),
            ], true);
            if ($userId === null) {
                $_SESSION['employee_user_error'] = 'Username already exists. Please choose another.';
                $this->redirect('/hr/employees/create?error=username');
                return;
            }
        }

        $id = Employee::create([
            'name' => $name,
            'email' => $_POST['email'] ?? '',
            'department' => $_POST['department'] ?? '',
            'position' => $_POST['position'] ?? '',
            'is_system_user' => $isSystemUser ? 1 : 0,
            'user_id' => $userId,
        ]);
        $this->redirect('/hr/employees/view/' . $id);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_employees');
        $employee = Employee::find($id);
        if (!$employee) {
            $this->redirect('/hr/employees');
            return;
        }
        $linkedUser = null;
        if (!empty($employee->user_id)) {
            $linkedUser = Employee::findUserById((int) $employee->user_id);
        }
        $db = Database::getInstance();
        $roles = $db->query('SELECT * FROM roles')->fetchAll(\PDO::FETCH_OBJ);
        $this->view('employees/form', ['employee' => $employee, 'roles' => $roles, 'linkedUser' => $linkedUser]);
    }

    public function update(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('edit_employees');
        $employee = Employee::find($id);
        if (!$employee) {
            $this->redirect('/hr/employees');
            return;
        }
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $this->redirect('/hr/employees/edit/' . $id . '?error=1');
            return;
        }

        $isSystemUser = !empty($_POST['is_system_user']);
        $userId = null;
        $linkedUser = !empty($employee->user_id) ? Employee::findUserById((int) $employee->user_id) : null;

        if ($isSystemUser) {
            $username = trim($_POST['user_username'] ?? '');
            $password = $_POST['user_password'] ?? '';
            $roleId = (int) ($_POST['user_role_id'] ?? 0);
            if (empty($username) || !$roleId) {
                $_SESSION['employee_user_error'] = 'Username and role are required when creating a system user.';
                $this->redirect('/hr/employees/edit/' . $id . '?error=user');
                return;
            }
            if (!$linkedUser && empty($password)) {
                $_SESSION['employee_user_error'] = 'Password is required when creating a new system user.';
                $this->redirect('/hr/employees/edit/' . $id . '?error=user');
                return;
            }
            if (!empty($password)) {
                $policyError = $linkedUser
                    ? \App\PasswordPolicy::validateForUser($password, (int) $linkedUser->id)
                    : \App\PasswordPolicy::validate($password);
                if ($policyError !== null) {
                    $_SESSION['employee_user_error'] = $policyError;
                    $this->redirect('/hr/employees/edit/' . $id . '?error=policy');
                    return;
                }
            }
            $userId = $this->createOrUpdateUserForEmployee($linkedUser, [
                'username' => $username,
                'password' => $password,
                'role_id' => $roleId,
                'display_name' => $name,
                'email' => trim($_POST['email'] ?? ''),
            ], true);
            if ($userId === null) {
                $_SESSION['employee_user_error'] = 'Username already exists. Please choose another.';
                $this->redirect('/hr/employees/edit/' . $id . '?error=username');
                return;
            }
        } else {
            if ($linkedUser) {
                $this->setUserInactive((int) $linkedUser->id);
            }
        }

        Employee::update($id, [
            'name' => $name,
            'email' => $_POST['email'] ?? '',
            'department' => $_POST['department'] ?? '',
            'position' => $_POST['position'] ?? '',
            'is_system_user' => $isSystemUser ? 1 : 0,
            'user_id' => $userId ?? $employee->user_id,
        ]);
        $this->redirect('/hr/employees/view/' . $id);
    }

    private function createOrUpdateUserForEmployee(?object $existingUser, array $data, bool $activate): ?int
    {
        $db = Database::getInstance();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $roleId = (int) ($data['role_id'] ?? 0);
        $displayName = trim($data['display_name'] ?? '') ?: null;
        $email = trim($data['email'] ?? '') ?: null;

        if ($existingUser) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stmt->execute([$username, $existingUser->id]);
            if ($stmt->fetch()) {
                return null;
            }
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare('UPDATE users SET email = ?, display_name = ?, password_hash = ?, role_id = ?, is_active = ?, password_changed_at = NOW() WHERE id = ?')
                    ->execute([$email, $displayName, $hash, $roleId, $activate ? 1 : 0, $existingUser->id]);
                \App\PasswordPolicy::recordPasswordChange((int) $existingUser->id, $hash);
            } else {
                $db->prepare('UPDATE users SET email = ?, display_name = ?, role_id = ?, is_active = ? WHERE id = ?')
                    ->execute([$email, $displayName, $roleId, $activate ? 1 : 0, $existingUser->id]);
            }
            return (int) $existingUser->id;
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return null;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare('INSERT INTO users (username, email, display_name, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$username, $email, $displayName, $hash, $roleId, $activate ? 1 : 0]);
        $userId = (int) $db->lastInsertId();
        \App\PasswordPolicy::recordPasswordChange($userId, $hash);
        return $userId;
    }

    private function setUserInactive(int $userId): void
    {
        $db = Database::getInstance();
        $db->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$userId]);
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_employees');
        Employee::delete($id);
        $this->redirect('/hr/employees');
    }
}
