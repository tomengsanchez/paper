# Creating a New Module — Paggawa ng Bagong Module

Step-by-step para magdagdag ng bagong feature (hal. "Items" CRUD): migration, model, controller, views, routes.

---

## 1. Migration — Table sa database

Gumawa ng file: **database/migration_XXX_items.php** (palitan ang XXX ng next number, hal. 013).

```php
<?php
return [
    'name' => 'migration_013_items',
    'up' => function (PDO $db): void {
        $db->exec("
            CREATE TABLE items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS items');
    },
];
```

Tapos sa project root:

```bash
php cli/migrate.php
```

---

## 2. Model — App\Models\Item

Gumawa ng **App/Models/Item.php**:

```php
<?php
namespace App\Models;

use Core\Database;

class Item
{
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM items ORDER BY id DESC');
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];
    }

    public static function find(int $id): ?object
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO items (name, description) VALUES (?, ?)');
        $stmt->execute([$data['name'] ?? '', $data['description'] ?? '']);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE items SET name = ?, description = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$data['name'] ?? '', $data['description'] ?? '', $id]);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM items WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
```

---

## 3. Controller — App\Controllers\ItemController

Gumawa ng **App/Controllers/ItemController.php**:

```php
<?php
namespace App\Controllers;

use App\Models\Item;
use Core\Controller;

class ItemController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $items = Item::all();
        $this->view('item/index', ['items' => $items, 'pageTitle' => 'Items', 'currentPage' => 'items']);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->view('item/form', ['item' => null, 'pageTitle' => 'New Item', 'currentPage' => 'items']);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            $this->redirect('/item/create?error=name');
            return;
        }
        Item::create(['name' => $name, 'description' => $description]);
        $this->redirect('/item');
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $item = Item::find($id);
        if (!$item) {
            $this->redirect('/item');
            return;
        }
        $this->view('item/view', ['item' => $item, 'pageTitle' => $item->name, 'currentPage' => 'items']);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $item = Item::find($id);
        if (!$item) {
            $this->redirect('/item');
            return;
        }
        $this->view('item/form', ['item' => $item, 'pageTitle' => 'Edit Item', 'currentPage' => 'items']);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $id = (int) $id;
        $item = Item::find($id);
        if (!$item) {
            $this->redirect('/item');
            return;
        }
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            $this->redirect('/item/edit/' . $id . '?error=name');
            return;
        }
        Item::update($id, ['name' => $name, 'description' => $description]);
        $this->redirect('/item/view/' . $id);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $id = (int) $id;
        Item::delete($id);
        $this->redirect('/item');
    }
}
```

---

## 4. Views — App/Views/item/

### App/Views/item/index.php

```php
<?php
$pageTitle = $pageTitle ?? 'Items';
$currentPage = $currentPage ?? 'items';
require dirname(__DIR__) . '/layout/main.php';
?>
<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Items</h1>
        <a href="<?= BASE_URL ?>/item/create" class="btn btn-primary">Add Item</a>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= (int) $item->id ?></td>
                        <td><?= htmlspecialchars($item->name) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/item/view/<?= (int) $item->id ?>">View</a> |
                            <a href="<?= BASE_URL ?>/item/edit/<?= (int) $item->id ?>">Edit</a> |
                            <form method="post" action="<?= BASE_URL ?>/item/delete/<?= (int) $item->id ?>" class="d-inline" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\Csrf::token()) ?>">
                                <button type="submit" class="btn btn-link p-0 text-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (isset($footer)) echo $footer; ?>
```

### App/Views/item/form.php

```php
<?php
$pageTitle = $pageTitle ?? ($item ? 'Edit Item' : 'New Item');
$currentPage = $currentPage ?? 'items';
require dirname(__DIR__) . '/layout/main.php';
$isEdit = isset($item) && $item;
?>
<div class="content">
    <h1 class="h4 mb-3"><?= $isEdit ? 'Edit Item' : 'New Item' ?></h1>
    <form method="post" action="<?= BASE_URL ?>/item/<?= $isEdit ? 'update/' . (int)$item->id : 'store' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\Csrf::token()) ?>">
        <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?= $item ? htmlspecialchars($item->name) : '' ?>" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= $item ? htmlspecialchars($item->description) : '' ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
        <a href="<?= BASE_URL ?>/item" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php if (isset($footer)) echo $footer; ?>
```

### App/Views/item/view.php

```php
<?php
$pageTitle = $pageTitle ?? 'Item';
$currentPage = $currentPage ?? 'items';
require dirname(__DIR__) . '/layout/main.php';
?>
<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><?= htmlspecialchars($item->name) ?></h1>
        <a href="<?= BASE_URL ?>/item/edit/<?= (int) $item->id ?>" class="btn btn-primary">Edit</a>
    </div>
    <div class="card">
        <div class="card-body">
            <p><strong>Description:</strong></p>
            <p><?= nl2br(htmlspecialchars($item->description ?? '')) ?></p>
        </div>
    </div>
    <a href="<?= BASE_URL ?>/item">&larr; Back to list</a>
</div>
<?php if (isset($footer)) echo $footer; ?>
```

*(Kung ang layout mo ay naka-`$content` buffer, i-adjust ang view para sa pattern ng existing layout.)*

---

## 5. Routes — routes/web.php

Idagdag bago ang `$router->dispatch();`:

```php
// Items CRUD
$router->get('/item', 'ItemController@index');
$router->get('/item/create', 'ItemController@create');
$router->post('/item/store', 'ItemController@store');
$router->get('/item/view/{id}', 'ItemController@show');
$router->get('/item/edit/{id}', 'ItemController@edit');
$router->post('/item/update/{id}', 'ItemController@update');
$router->post('/item/delete/{id}', 'ItemController@delete');
```

---

## 6. Menu link (optional)

Kung may sidebar sa **App/Views/layout/main.php**, magdagdag ng link:

```php
<a href="<?= BASE_URL ?>/item" class="<?= ($currentPage ?? '') === 'items' ? 'active' : '' ?>">Items</a>
```

---

## Checklist

- [ ] database/migration_XXX_items.php
- [ ] php cli/migrate.php
- [ ] App/Models/Item.php
- [ ] App/Controllers/ItemController.php
- [ ] App/Views/item/index.php, form.php, view.php
- [ ] routes/web.php — item routes
- [ ] Layout menu (optional)

Pagkatapos nito, pwede mo nang i-test: `/item`, Create, View, Edit, Delete.

Tingnan din: [ROUTING.md](ROUTING.md), [FRAMEWORK.md](FRAMEWORK.md).
