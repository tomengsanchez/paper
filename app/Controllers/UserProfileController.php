<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\UserProfile;
use Core\Database;

class UserProfileController extends Controller
{
    public function __construct()
    {
        $this->requireCapability('manage_user_profiles');
    }

    public function index(): void
    {
        $profiles = UserProfile::all();
        $this->view('user_profiles/index', ['profiles' => $profiles]);
    }

    public function create(): void
    {
        $roles = Database::getInstance()->query('SELECT * FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_OBJ);
        $users = UserProfile::getUsersForDropdown(null);
        $this->view('user_profiles/form', ['profile' => null, 'roles' => $roles, 'users' => $users]);
    }

    public function store(): void
    {
        try {
            UserProfile::create([
                'name' => trim($_POST['name'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? 0) ?: null,
                'user_id' => (int)($_POST['user_id'] ?? 0) ?: null,
            ]);
            $this->redirect('/users/profiles');
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

    public function edit(int $id): void
    {
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
        try {
            UserProfile::update($id, [
                'name' => trim($_POST['name'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? 0) ?: null,
                'user_id' => (int)($_POST['user_id'] ?? 0) ?: null,
            ]);
            $this->redirect('/users/profiles');
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
        UserProfile::delete($id);
        $this->redirect('/users/profiles');
    }
}
