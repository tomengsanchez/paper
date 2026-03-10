<?php
$user = $user ?? null;
if (!$user) {
    header('Location: /login');
    exit;
}
$displayLabel = !empty(trim($user->display_name ?? '')) ? $user->display_name : $user->username;
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Profile</h2>
    <div>
        <?php if (\Core\Auth::can('edit_users')): ?>
        <a href="/users/edit/<?= (int)$user->id ?>" class="btn btn-primary">Edit</a>
        <?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
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
        </dl>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'My Profile';
$currentPage = 'account';
require __DIR__ . '/../layout/main.php';
