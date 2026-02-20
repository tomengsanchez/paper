<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Roles &amp; Capabilities</h2>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Role</th><th>Capabilities</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r->name) ?></td>
                    <td>
                        <?php if (!empty($r->capabilities)): ?>
                        <?php foreach ($r->capabilities as $c): ?>
                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($c) ?></span>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <span class="text-muted">None</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/users/roles/edit/<?= (int)$r->id ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'User Roles & Capabilities';
$currentPage = 'user-roles';
require __DIR__ . '/../layout/main.php';
?>
