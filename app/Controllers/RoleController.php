<?php
namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;
use App\Capabilities;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->requireCapability('manage_roles');
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $roles = $db->query('SELECT * FROM roles ORDER BY name')->fetchAll(\PDO::FETCH_OBJ);
        $capMap = [];
        $stmt = $db->query('SELECT role_id, capability FROM role_capabilities ORDER BY role_id, capability');
        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $capMap[$r['role_id']][] = $r['capability'];
        }
        foreach ($roles as $r) {
            $r->capabilities = $capMap[$r->id] ?? [];
        }
        $this->view('roles/index', ['roles' => $roles]);
    }

    public function edit(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        $role = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$role) {
            $this->redirect('/users/roles');
            return;
        }
        $stmt = $db->prepare('SELECT capability FROM role_capabilities WHERE role_id = ? ORDER BY capability');
        $stmt->execute([$id]);
        $role->capabilities = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $allCapabilities = Capabilities::all();
        $isLocked = (strcasecmp($role->name, 'Administrator') === 0);
        $this->view('roles/form', ['role' => $role, 'allCapabilities' => $allCapabilities, 'isLocked' => $isLocked]);
    }

    public function update(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, name FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        $role = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$role) {
            $this->redirect('/users/roles');
            return;
        }
        $isLocked = (strcasecmp($role->name, 'Administrator') === 0);

        $name = trim($_POST['name'] ?? '');
        if ($name && !$isLocked) {
            $db->prepare('UPDATE roles SET name = ? WHERE id = ?')->execute([$name, $id]);
        }

        if (!$isLocked) {
            $db->prepare('DELETE FROM role_capabilities WHERE role_id = ?')->execute([$id]);
            $validKeys = Capabilities::keys();
            $submitted = array_filter(array_map('trim', $_POST['capabilities'] ?? []));
            $caps = array_intersect($submitted, $validKeys);
            $ins = $db->prepare('INSERT INTO role_capabilities (role_id, capability) VALUES (?, ?)');
            foreach (array_unique($caps) as $cap) {
                $ins->execute([$id, $cap]);
            }
        }
        $this->redirect('/users/roles');
    }
}
