<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $item ? 'Edit In Progress Stage' : 'Add In Progress Stage' ?></h2>
    <a href="/grievance/options/progress-levels" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $item ? "/grievance/options/progress-levels/update/{$item->id}" : '/grievance/options/progress-levels/store' ?>">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item->name ?? '') ?>" required placeholder="e.g. Level 1">
            </div>
            <div class="mb-3">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($item->sort_order ?? 0) ?>" min="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Days to Address</label>
                <input type="number" name="days_to_address" class="form-control" value="<?= isset($item->days_to_address) ? (int)$item->days_to_address : '' ?>" min="0" required>
                <div class="form-text">Number of calendar days expected to address grievances at this stage.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item->description ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = $item ? 'Edit In Progress Stage' : 'Add In Progress Stage'; $currentPage = 'grievance-progress-levels'; require __DIR__ . '/../../layout/main.php'; ?>
