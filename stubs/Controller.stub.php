<?php
namespace App\Controllers;

use App\Models\ResourceName;
use Core\Controller;

/**
 * Copy to App/Controllers/ResourceNameController.php
 * Replace ResourceName with your resource (e.g. Item → ItemController, Item model).
 */
class ResourceNameController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $items = ResourceName::all();
        $this->view('resource_name/index', [
            'items' => $items,
            'pageTitle' => 'ResourceName',
            'currentPage' => 'resource_name',
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->view('resource_name/form', [
            'item' => null,
            'pageTitle' => 'New ResourceName',
            'currentPage' => 'resource_name',
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        // Read POST, validate, then:
        // ResourceName::create([...]);
        $this->redirect('/resource_name');
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $item = ResourceName::find($id);
        if (!$item) {
            $this->redirect('/resource_name');
            return;
        }
        $this->view('resource_name/view', [
            'item' => $item,
            'pageTitle' => $item->name ?? 'View',
            'currentPage' => 'resource_name',
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $item = ResourceName::find($id);
        if (!$item) {
            $this->redirect('/resource_name');
            return;
        }
        $this->view('resource_name/form', [
            'item' => $item,
            'pageTitle' => 'Edit ResourceName',
            'currentPage' => 'resource_name',
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $id = (int) $id;
        $item = ResourceName::find($id);
        if (!$item) {
            $this->redirect('/resource_name');
            return;
        }
        // Read POST, validate, then ResourceName::update($id, [...]);
        $this->redirect('/resource_name/view/' . $id);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $id = (int) $id;
        ResourceName::delete($id);
        $this->redirect('/resource_name');
    }
}
