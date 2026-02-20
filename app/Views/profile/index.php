<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Profiles</h2>
    <a href="/profile/create" class="btn btn-primary">Add Profile</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>PAPSID</th><th>Control Number</th><th>Full Name</th><th>Age</th><th>Contact</th><th>Project</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p->papsid ?? '') ?></td>
                    <td><?= htmlspecialchars($p->control_number ?? '') ?></td>
                    <td><?= htmlspecialchars($p->full_name ?? '') ?></td>
                    <td><?= (int)($p->age ?? 0) ?></td>
                    <td><?= htmlspecialchars($p->contact_number ?? '') ?></td>
                    <td><?= htmlspecialchars($p->project_name ?? '-') ?></td>
                    <td>
                        <a href="/profile/edit/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="/profile/delete/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this profile?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($profiles)): ?>
                <tr><td colspan="7" class="text-muted text-center py-4">No profiles yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Profile';
$currentPage = 'profile';
require __DIR__ . '/../layout/main.php';
?>
