<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $profile ? 'Edit User Profile' : 'Add User Profile' ?></h2>
    <a href="/users/profiles" class="btn btn-outline-secondary">Back</a>
</div>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $profile ? "/users/profiles/update/{$profile->id}" : '/users/profiles/store' ?>">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($profile->name ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select">
                    <option value="">-- Select Role --</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r->id ?>" <?= ($profile->role_id ?? 0) == $r->id ? 'selected' : '' ?>><?= htmlspecialchars($r->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Linked User</label>
                <select name="user_id" class="form-select">
                    <option value="">-- Select User (optional, 1 user = 1 profile) --</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u->id ?>" <?= ($profile->user_id ?? 0) == $u->id ? 'selected' : '' ?>><?= htmlspecialchars($u->username) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Each user can be linked to one profile only. Already-linked users are excluded.</small>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = $profile ? 'Edit User Profile' : 'Add User Profile';
$currentPage = 'user-profiles';
require __DIR__ . '/../layout/main.php';
?>
