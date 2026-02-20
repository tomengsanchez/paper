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
        $projects = Project::all();
        $this->view('library/index', ['projects' => $projects]);
    }

    public function create(): void
    {
        $this->view('library/form', ['project' => null]);
    }

    public function store(): void
    {
        $id = Project::create([
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'coordinator_id' => (int) ($_POST['coordinator_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/library');
    }

    public function edit(int $id): void
    {
        $project = Project::find($id);
        if (!$project) {
            $this->redirect('/library');
            return;
        }
        $this->view('library/form', ['project' => $project]);
    }

    public function update(int $id): void
    {
        Project::update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'coordinator_id' => (int) ($_POST['coordinator_id'] ?? 0) ?: null,
        ]);
        $this->redirect('/library');
    }

    public function delete(int $id): void
    {
        Project::delete($id);
        $this->redirect('/library');
    }
}
