<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Management</h2>
    <a href="/users/create" class="btn btn-primary">Add User</a>
</div>
<?php if (isset($_GET['error']) && $_GET['error'] === 'self'): ?>
<div class="alert alert-warning">You cannot delete your own account.</div>
<?php endif; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int)$u->id ?></td>
                    <td><?= htmlspecialchars($u->username) ?></td>
                    <td><?= htmlspecialchars($u->role_name ?? '') ?></td>
                    <td>
                        <a href="/users/edit/<?= (int)$u->id ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <?php if ($u->id != \Core\Auth::id()): ?>
                        <a href="/users/delete/<?= (int)$u->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Users';
$currentPage = 'users';
require __DIR__ . '/../layout/main.php';
?>
