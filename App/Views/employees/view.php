<?php
$linkedUser = $linkedUser ?? null;
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View Employee</h2>
    <div>
        <a href="/hr/employees" class="btn btn-outline-secondary">Back</a>
        <?php if (\Core\Auth::can('edit_employees')): ?><a href="/hr/employees/edit/<?= (int)$employee->id ?>" class="btn btn-primary">Edit</a><?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9"><?= (int)$employee->id ?></dd>
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($employee->name ?? '') ?></dd>
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($employee->email ?? '-') ?></dd>
            <dt class="col-sm-3">Department</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($employee->department ?? '-') ?></dd>
            <dt class="col-sm-3">Position</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($employee->position ?? '-') ?></dd>
            <dt class="col-sm-3">System user</dt>
            <dd class="col-sm-9">
                <?php if (!empty($employee->is_system_user) && $linkedUser): ?>
                    <a href="/users/view/<?= (int)$linkedUser->id ?>"><?= htmlspecialchars($linkedUser->username ?? '') ?></a>
                    <span class="badge bg-<?= !empty($linkedUser->is_active) ? 'success' : 'secondary' ?>"><?= !empty($linkedUser->is_active) ? 'active' : 'inactive' ?></span>
                <?php elseif (!empty($linkedUser)): ?>
                    <span class="text-muted"><?= htmlspecialchars($linkedUser->username ?? '') ?></span>
                    <span class="badge bg-secondary">inactive</span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </dd>
            <dt class="col-sm-3">Created</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($employee->created_at ?? '-') ?></dd>
            <dt class="col-sm-3">Updated</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($employee->updated_at ?? '-') ?></dd>
        </dl>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'View Employee'; $currentPage = 'employees'; require __DIR__ . '/../layout/main.php'; ?>
