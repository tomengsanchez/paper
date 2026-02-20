<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Library (Projects)</h2>
    <?php if (\Core\Auth::can('add_projects')): ?><a href="/library/create" class="btn btn-primary">Add Project</a><?php endif; ?>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Project Name</th><th>Description</th><th>Coordinator</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p->name ?? '') ?></td>
                    <td><?= htmlspecialchars(\mb_substr($p->description ?? '', 0, 80)) ?><?= \mb_strlen($p->description ?? '') > 80 ? '...' : '' ?></td>
                    <td><?= htmlspecialchars($p->coordinator_name ?? '-') ?></td>
                    <td>
                        <?php if (\Core\Auth::can('edit_projects')): ?><a href="/library/edit/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_projects')): ?><a href="/library/delete/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this project?')">Delete</a><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($projects)): ?>
                <tr><td colspan="4" class="text-muted text-center py-4">No projects yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Library';
$currentPage = 'library';
require __DIR__ . '/../layout/main.php';
?>
