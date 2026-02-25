<?php
ob_start();
$pageTitle = $pageTitle ?? 'Item';
$currentPage = $currentPage ?? 'items';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($item->name) ?></h2>
    <?php if (\Core\Auth::can('edit_items')): ?><a href="<?= BASE_URL ?>/item/edit/<?= (int) $item->id ?>" class="btn btn-primary">Edit</a><?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <p><strong>Description:</strong></p>
        <p><?= nl2br(htmlspecialchars($item->description ?? '')) ?: '—' ?></p>
    </div>
</div>
<a href="<?= BASE_URL ?>/item">&larr; Back to list</a>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/main.php';
?>
