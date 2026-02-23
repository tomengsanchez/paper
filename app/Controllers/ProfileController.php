<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\Profile;
use App\Models\Structure;
use App\ListConfig;

class ProfileController extends Controller
{
    private const BASE_URL = '/profile';
    private const MODULE = 'profile';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_profiles');
        $columns = ListConfig::resolveFromRequest(self::MODULE);
        $_SESSION['list_columns'][self::MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? ($columns[0] ?? 'id');
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'asc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));
        $afterId = !empty($_GET['after_id']) ? (int) $_GET['after_id'] : null;
        $beforeId = !empty($_GET['before_id']) ? (int) $_GET['before_id'] : null;

        $pagination = Profile::listPaginated($search, $columns, $sort, $order, $page, $perPage, $afterId, $beforeId);

        $this->view('profile/index', [
            'profiles' => $pagination['items'],
            'listModule' => self::MODULE,
            'listBaseUrl' => self::BASE_URL,
            'listSearch' => $search,
            'listSort' => $sort,
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::MODULE),
            'listPagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_profiles');
        $this->view('profile/form', ['profile' => null]);
    }

    public function store(): void
    {
        $this->requireCapability('add_profiles');
        $id = Profile::create([
            'control_number' => trim($_POST['control_number'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'age' => (int) ($_POST['age'] ?? 0),
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'project_id' => (int) ($_POST['project_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/profile/view/' . $id);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_profiles');
        $profile = Profile::find($id);
        if (!$profile) {
            $this->redirect('/profile');
            return;
        }
        $structures = \Core\Auth::can('view_structure') ? Structure::byOwner($profile->id) : [];
        $this->view('profile/view', ['profile' => $profile, 'structures' => $structures]);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_profiles');
        $profile = Profile::find($id);
        if (!$profile) {
            $this->redirect('/profile');
            return;
        }
        $this->view('profile/form', ['profile' => $profile]);
    }

    public function update(int $id): void
    {
        $this->requireCapability('edit_profiles');
        Profile::update($id, [
            'papsid' => trim($_POST['papsid'] ?? ''),
            'control_number' => trim($_POST['control_number'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'age' => (int) ($_POST['age'] ?? 0),
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'project_id' => (int) ($_POST['project_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/profile/view/' . $id);
    }

    public function delete(int $id): void
    {
        $this->requireCapability('delete_profiles');
        Profile::delete($id);
        $this->redirect('/profile');
    }
}
