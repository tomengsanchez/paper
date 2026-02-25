<?php
ob_start();
$pageTitle = $pageTitle ?? 'Items';
$currentPage = $currentPage ?? 'items';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Items (example)</h2>
    <?php if (\Core\Auth::can('add_items')): ?><a href="<?= BASE_URL ?>/item/create" class="btn btn-primary">Add Item</a><?php endif; ?>
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
                        <?php if (\Core\Auth::can('view_items')): ?><a href="<?= BASE_URL ?>/item/view/<?= (int) $item->id ?>" class="btn btn-sm btn-outline-secondary">View</a> <?php endif; ?>
                        <?php if (\Core\Auth::can('edit_items')): ?><a href="<?= BASE_URL ?>/item/edit/<?= (int) $item->id ?>" class="btn btn-sm btn-outline-primary">Edit</a> <?php endif; ?>
                        <?php if (\Core\Auth::can('delete_items')): ?>
                        <form method="post" action="<?= BASE_URL ?>/item/delete/<?= (int) $item->id ?>" class="d-inline" onsubmit="return confirm('Delete this item?');">
                            <?= \Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="3" class="text-muted text-center py-4">No items yet. Add one to try the example CRUD.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/main.php';
?>
