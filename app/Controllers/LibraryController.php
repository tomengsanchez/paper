<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\Project;

class LibraryController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->requireCapability('view_projects');
        $projects = Project::all();
        $this->view('library/index', ['projects' => $projects]);
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
