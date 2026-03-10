<?php
$employee = $employee ?? null;
$roles = $roles ?? [];
$linkedUser = $linkedUser ?? null;
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $employee ? 'Edit Employee' : 'Add Employee' ?></h2>
    <a href="/hr/employees" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $employee ? "/hr/employees/update/{$employee->id}" : '/hr/employees/store' ?>">
            <?= \Core\Csrf::field() ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === '1'): ?>
            <div class="alert alert-warning">Name is required.</div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['employee_user_error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['employee_user_error']) ?></div>
            <?php unset($_SESSION['employee_user_error']); endif; ?>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($employee->name ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($employee->email ?? '') ?>" placeholder="email@example.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($employee->department ?? '') ?>" placeholder="e.g. IT, HR">
            </div>
            <div class="mb-3">
                <label class="form-label">Position</label>
                <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($employee->position ?? '') ?>" placeholder="e.g. Developer, Manager">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_system_user" value="1" class="form-check-input" id="is_system_user"
                    <?= !empty($employee->is_system_user) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_system_user">Is system user</label>
                <small class="form-text text-muted d-block">If checked, the employee can log in. A user account will be created or linked.</small>
            </div>
            <div id="user-fields" class="border rounded p-3 mb-3" style="display: <?= !empty($employee->is_system_user) ? 'block' : 'none' ?>;">
                <h6 class="mb-3">User account</h6>
                <?php if ($linkedUser): ?>
                <p class="text-muted small mb-2">Existing user: <strong><?= htmlspecialchars($linkedUser->username ?? '') ?></strong>
                    (<?= !empty($linkedUser->is_active) ? 'active' : 'inactive' ?>)</p>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="user_username" class="form-control" value="<?= htmlspecialchars($linkedUser->username ?? '') ?>"
                        placeholder="Login username" <?= $linkedUser ? 'readonly' : '' ?>>
                    <?php if ($linkedUser): ?><small class="text-muted">Username cannot be changed here.</small><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password <?= $linkedUser ? '(leave blank to keep)' : '' ?></label>
                    <input type="password" name="user_password" class="form-control" placeholder="<?= $linkedUser ? 'Leave blank to keep current' : 'Required for new user' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="user_role_id" class="form-select">
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r->id ?>" <?= ($linkedUser->role_id ?? 0) == $r->id ? 'selected' : '' ?>><?= htmlspecialchars($r->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<script>
document.getElementById('is_system_user').addEventListener('change', function() {
    document.getElementById('user-fields').style.display = this.checked ? 'block' : 'none';
});
</script>
<?php
$content = ob_get_clean();
$pageTitle = $employee ? 'Edit Employee' : 'Add Employee';
$currentPage = 'employees';
require __DIR__ . '/../layout/main.php';
?>
