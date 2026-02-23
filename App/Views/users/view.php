<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View User</h2>
    <div>
        <a href="/users" class="btn btn-outline-secondary">Back</a>
        <?php if (\Core\Auth::can('edit_users')): ?><a href="/users/edit/<?= (int)$user->id ?>" class="btn btn-primary">Edit</a><?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9"><?= (int)$user->id ?></dd>
            <dt class="col-sm-3">Username</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->username ?? '') ?></dd>
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->email ?? '-') ?></dd>
            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->role_name ?? '-') ?></dd>
        </dl>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'View User'; $currentPage = 'users'; require __DIR__ . '/../layout/main.php'; ?>
