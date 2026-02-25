<?php
ob_start();
$pageTitle = $pageTitle ?? ($item ? 'Edit Item' : 'New Item');
$currentPage = $currentPage ?? 'items';
$isEdit = isset($item) && $item;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $isEdit ? 'Edit Item' : 'New Item' ?></h2>
</div>
<?php if (!empty($_GET['error'])): ?><div class="alert alert-warning">Please fill required fields.</div><?php endif; ?>
<form method="post" action="<?= BASE_URL ?>/item/<?= $isEdit ? 'update/' . (int)$item->id : 'store' ?>">
    <?= \Core\Csrf::field() ?>
    <div class="mb-2">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= $item ? htmlspecialchars($item->name) : '' ?>" required>
    </div>
    <div class="mb-2">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= $item ? htmlspecialchars($item->description ?? '') : '' ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= BASE_URL ?>/item" class="btn btn-secondary">Cancel</a>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/main.php';
?>
