<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Role: <?= htmlspecialchars($role->name) ?></h2>
    <a href="/users/roles" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <?php if (!empty($isLocked)): ?>
        <div class="alert alert-info">Administrator capabilities are locked and cannot be changed.</div>
        <?php endif; ?>
        <form method="post" action="/users/roles/update/<?= (int)$role->id ?>">
            <div class="mb-3">
                <label class="form-label">Role Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($role->name) ?>" required <?= !empty($isLocked) ? 'readonly' : '' ?>>
            </div>
            <div class="mb-3">
                <label class="form-label">Capabilities</label>
                <small class="text-muted d-block mb-2">Capabilities are defined by system modules. Add modules in app/Capabilities.php.</small>
                <div class="row">
                    <?php foreach ($allCapabilities as $capKey => $capLabel): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check">
                            <input type="checkbox" name="capabilities[]" value="<?= htmlspecialchars($capKey) ?>" class="form-check-input" id="cap_<?= htmlspecialchars($capKey) ?>"
                                <?= in_array($capKey, $role->capabilities) ? 'checked' : '' ?>
                                <?= !empty($isLocked) ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="cap_<?= htmlspecialchars($capKey) ?>"><?= htmlspecialchars($capLabel) ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (empty($isLocked)): ?>
            <button type="submit" class="btn btn-primary">Save</button>
            <?php else: ?>
            <a href="/users/roles" class="btn btn-outline-secondary">Back</a>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Edit Role';
$currentPage = 'user-roles';
require __DIR__ . '/../layout/main.php';
?>
