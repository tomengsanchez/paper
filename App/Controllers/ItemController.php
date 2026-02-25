<?php
namespace App\Controllers;

use App\Models\Item;
use Core\Controller;

/**
 * Example CRUD module - use as reference for new features. See docs/CREATING_MODULES.md.
 */
class ItemController extends Controller
{
    public function index(): void
    {
        $this->requireCapability('view_items');
        $items = Item::all();
        $this->view('item/index', [
            'items' => $items,
            'pageTitle' => 'Items',
            'currentPage' => 'items',
        ]);
    }

    public function create(): void
    {
        $this->requireCapability('add_items');
        $this->view('item/form', [
            'item' => null,
            'pageTitle' => 'New Item',
            'currentPage' => 'items',
        ]);
    }

    public function store(): void
    {
        $this->requireCapability('add_items');
        $this->validateCsrf();
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            $this->redirect(BASE_URL . '/item/create?error=name');
            return;
        }
        Item::create(['name' => $name, 'description' => $description]);
        $this->redirect(BASE_URL . '/item');
    }

    public function show(string $id): void
    {
        $this->requireCapability('view_items');
        $id = (int) $id;
        $item = Item::find($id);
        if (!$item) {
            $this->redirect(BASE_URL . '/item');
            return;
        }
        $this->view('item/view', [
            'item' => $item,
            'pageTitle' => $item->name,
            'currentPage' => 'items',
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireCapability('edit_items');
        $id = (int) $id;
        $item = Item::find($id);
        if (!$item) {
            $this->redirect(BASE_URL . '/item');
            return;
        }
        $this->view('item/form', [
            'item' => $item,
            'pageTitle' => 'Edit Item',
            'currentPage' => 'items',
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCapability('edit_items');
        $this->validateCsrf();
        $id = (int) $id;
        $item = Item::find($id);
        if (!$item) {
            $this->redirect(BASE_URL . '/item');
            return;
        }
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            $this->redirect(BASE_URL . '/item/edit/' . $id . '?error=name');
            return;
        }
        Item::update($id, ['name' => $name, 'description' => $description]);
        $this->redirect(BASE_URL . '/item/view/' . $id);
    }

    public function delete(string $id): void
    {
        $this->requireCapability('delete_items');
        $this->validateCsrf();
        $id = (int) $id;
        Item::delete($id);
        $this->redirect(BASE_URL . '/item');
    }
}
