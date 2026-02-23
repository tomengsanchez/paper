<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View User Profile</h2>
    <div>
        <a href="/users/profiles" class="btn btn-outline-secondary">Back</a>
        <?php if (\Core\Auth::can('edit_user_profiles')): ?><a href="/users/profiles/edit/<?= (int)$profile->id ?>" class="btn btn-primary">Edit</a><?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->name ?? '') ?></dd>
            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->role_name ?? '-') ?></dd>
            <dt class="col-sm-3">Linked User</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($profile->username ?? '-') ?></dd>
        </dl>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'View User Profile'; $currentPage = 'user-profiles'; require __DIR__ . '/../layout/main.php'; ?>
