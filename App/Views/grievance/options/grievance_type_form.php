<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $item ? 'Edit Type of Grievance' : 'Add Type of Grievance' ?></h2>
    <a href="/grievance/options/types" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $item ? "/grievance/options/types/update/{$item->id}" : '/grievance/options/types/store' ?>">
            <?= \Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item->name ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item->description ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = $item ? 'Edit Type of Grievance' : 'Add Type of Grievance'; $currentPage = 'grievance-types'; require __DIR__ . '/../../layout/main.php'; ?>
