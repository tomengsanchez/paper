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
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_user_profiles');
        $profiles = UserProfile::all();
        $this->view('user_profiles/index', ['profiles' => $profiles]);
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
