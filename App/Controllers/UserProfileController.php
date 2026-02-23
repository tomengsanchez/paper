<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\UserProfile;
use Core\Database;
use App\ListConfig;
use App\ListHelper;

class UserProfileController extends Controller
{
    private const LIST_BASE = '/users/profiles';
    private const LIST_MODULE = 'user_profiles';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_user_profiles');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $rows = UserProfile::all();
        $rows = ListHelper::search($rows, $search, $columns, self::LIST_MODULE);
        $rows = ListHelper::sort($rows, $sort ?: ($columns[0] ?? 'id'), $order, $columns, self::LIST_MODULE);
        $pagination = ListHelper::paginate($rows, $page, $perPage);

        $this->view('user_profiles/index', [
            'profiles' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort ?: ($columns[0] ?? ''),
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_user_profiles');
        $roles = Database::getInstance()->query('SELECT * FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_OBJ);
        $users = UserProfile::getUsersForDropdown(null);
        $this->view('user_profiles/form', ['profile' => null, 'roles' => $roles, 'users' => $users]);
    }

    public function store(): void
    {
        $this->requireCapability('add_user_profiles');
        try {
            $id = UserProfile::create([
                'name' => trim($_POST['name'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? 0) ?: null,
                'user_id' => (int)($_POST['user_id'] ?? 0) ?: null,
            ]);
            $this->redirect('/users/profiles/view/' . $id);
        } catch (\RuntimeException $e) {
            $roles = Database::getInstance()->query('SELECT * FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_OBJ);
            $users = UserProfile::getUsersForDropdown(null);
            $this->view('user_profiles/form', [
                'profile' => null,
                'roles' => $roles,
                'users' => $users,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_user_profiles');
        $profile = UserProfile::find($id);
        if (!$profile) {
            $this->redirect('/users/profiles');
            return;
        }
        $this->view('user_profiles/view', ['profile' => $profile]);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_user_profiles');
        $profile = UserProfile::find($id);
        if (!$profile) {
            $this->redirect('/users/profiles');
            return;
        }
        $roles = Database::getInstance()->query('SELECT * FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_OBJ);
        $users = UserProfile::getUsersForDropdown($id);
        $this->view('user_profiles/form', ['profile' => $profile, 'roles' => $roles, 'users' => $users]);
    }

    public function update(int $id): void
    {
        $this->requireCapability('edit_user_profiles');
        try {
            UserProfile::update($id, [
                'name' => trim($_POST['name'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? 0) ?: null,
                'user_id' => (int)($_POST['user_id'] ?? 0) ?: null,
            ]);
            $this->redirect('/users/profiles/view/' . $id);
        } catch (\RuntimeException $e) {
            $profile = UserProfile::find($id);
            $roles = Database::getInstance()->query('SELECT * FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_OBJ);
            $users = UserProfile::getUsersForDropdown($id);
            $this->view('user_profiles/form', [
                'profile' => $profile,
                'roles' => $roles,
                'users' => $users,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(int $id): void
    {
        $this->requireCapability('delete_user_profiles');
        UserProfile::delete($id);
        $this->redirect('/users/profiles');
    }
}
