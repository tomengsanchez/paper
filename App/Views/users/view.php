<?php
$linkedProjects = $linkedProjects ?? [];
ob_start();
?>
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
            <?php if (!empty(trim($user->display_name ?? ''))): ?>
            <dt class="col-sm-3">Display name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->display_name) ?></dd>
            <?php endif; ?>
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->email ?? '-') ?></dd>
            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->role_name ?? '-') ?></dd>
            <dt class="col-sm-3">Linked Projects</dt>
            <dd class="col-sm-9">
                <?php if (empty($linkedProjects)): ?>
                <span class="text-muted">None</span>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($linkedProjects as $proj): ?>
                    <li><?= htmlspecialchars($proj->name) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'View User'; $currentPage = 'users'; require __DIR__ . '/../layout/main.php'; ?>
