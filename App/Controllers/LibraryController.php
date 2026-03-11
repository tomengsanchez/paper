<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\Project;
use App\ListConfig;
use App\CsvExporter;

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
            'listExportColumns' => ListConfig::getExportColumns(self::LIST_MODULE),
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
        $this->validateCsrf();
        $this->requireCapability('add_projects');
        $id = Project::create([
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
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
        $this->validateCsrf();
        $this->requireCapability('edit_projects');
        Project::update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ]);
        $this->redirect('/library/view/' . $id);
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $this->requireCapability('delete_projects');
        Project::delete($id);
        $this->redirect('/library');
    }

    public function export(): void
    {
        $this->requireCapability('export_projects');
        $columns = ListConfig::resolveFromRequest(self::LIST_MODULE);
        $search = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? ($columns[0] ?? 'id');
        $order = in_array(strtolower($_GET['order'] ?? ''), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';

        $scope = $_GET['scope'] ?? 'filtered';
        $selectedCols = $_GET['col'] ?? [];
        if (!is_array($selectedCols) || empty($selectedCols)) {
            $selectedCols = array_column(ListConfig::getExportColumns(self::LIST_MODULE), 'key');
        }

        $exportCols = ListConfig::getExportColumns(self::LIST_MODULE);
        $validKeys = [];
        $headers = [];
        foreach ($exportCols as $col) {
            if (in_array($col['key'], $selectedCols, true)) {
                $validKeys[] = $col['key'];
                $headers[] = $col['label'];
            }
        }
        if (empty($validKeys)) {
            $validKeys = array_column($exportCols, 'key');
            $headers = array_column($exportCols, 'label');
        }

        if ($scope === 'page') {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 15)));
        } else {
            $page = 1;
            $perPage = 10000;
        }

        $pagination = Project::listPaginated($search, $columns, $sort, $order, $page, $perPage);
        $rows = $pagination['items'] ?? [];

        CsvExporter::stream('projects', $headers, $rows, $validKeys);
    }
}
