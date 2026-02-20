<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Profile</h2>
    <?php if (\Core\Auth::can('add_user_profiles')): ?><a href="/users/profiles/create" class="btn btn-primary">Add User Profile</a><?php endif; ?>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Name</th><th>Role</th><th>Linked User</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p->name ?? '') ?></td>
                    <td><?= htmlspecialchars($p->role_name ?? '-') ?></td>
                    <td><?= htmlspecialchars($p->username ?? '-') ?></td>
                    <td>
                        <?php if (\Core\Auth::can('edit_user_profiles')): ?><a href="/users/profiles/edit/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_user_profiles')): ?><a href="/users/profiles/delete/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user profile?')">Delete</a><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($profiles)): ?>
                <tr><td colspan="4" class="text-muted text-center py-4">No user profiles yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'User Profile';
$currentPage = 'user-profiles';
require __DIR__ . '/../layout/main.php';
?>
