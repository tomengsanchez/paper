<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\Project;
use App\ListConfig;

class LibraryController extends Controller
{
    private const LIST_BASE = '/library';
    private const LIST_MODULE = 'library';

    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_projects');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $_SESSION['list_columns'][self::LIST_MODULE] = $columns;
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? ($columns[0] ?? 'id');
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));

        $pagination = Project::listPaginated($search, $columns, $sort, $order, $page, $perPage);

        $this->view('library/index', [
            'projects' => $pagination['items'],
            'listModule' => self::LIST_MODULE,
            'listBaseUrl' => self::LIST_BASE,
            'listSearch' => $search,
            'listSort' => $sort,
            'listOrder' => $order,
            'listColumns' => $columns,
            'listAllColumns' => ListConfig::getColumns(self::LIST_MODULE),
            'listPagination' => $pagination,
            'listHasCustomColumns' => ListConfig::hasCustomColumns(self::LIST_MODULE),
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_projects');
        $this->view('library/form', ['project' => null]);
    }

    public function store(): void
    {
        $this->requireCapability('add_projects');
        $id = Project::create([
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'coordinator_id' => (int) ($_POST['coordinator_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/library/view/' . $id);
    }

    public function show(int $id): void
    {
        $this->requireCapability('view_projects');
        $project = Project::find($id);
        if (!$project) {
            $this->redirect('/library');
            return;
        }
        $this->view('library/view', ['project' => $project]);
    }

    public function edit(int $id): void
    {
        $this->requireCapability('edit_projects');
        $project = Project::find($id);
        if (!$project) {
            $this->redirect('/library');
            return;
        }
        $this->view('library/form', ['project' => $project]);
    }

    public function update(int $id): void
    {
        $this->requireCapability('edit_projects');
        Project::update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'coordinator_id' => (int) ($_POST['coordinator_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/library/view/' . $id);
    }

    public function delete(int $id): void
    {
        $this->requireCapability('delete_projects');
        Project::delete($id);
        $this->redirect('/library');
    }
}
