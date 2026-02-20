<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Structure</h2>
    <?php if (\Core\Auth::can('add_structure')): ?><a href="/structure/create" class="btn btn-primary">Add Structure</a><?php endif; ?>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Structure ID</th>
                    <th>Paps/Owner</th>
                    <th>Structure Tag #</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($structures as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s->strid ?? '') ?></td>
                    <td><?= htmlspecialchars($s->owner_name ?? '-') ?></td>
                    <td><?= htmlspecialchars($s->structure_tag ?? '') ?></td>
                    <td><?= htmlspecialchars(\mb_substr($s->description ?? '', 0, 60)) ?><?= \mb_strlen($s->description ?? '') > 60 ? '...' : '' ?></td>
                    <td>
                        <?php if (\Core\Auth::can('edit_structure')): ?><a href="/structure/edit/<?= (int)$s->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_structure')): ?><a href="/structure/delete/<?= (int)$s->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this structure?')">Delete</a><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($structures)): ?>
                <tr><td colspan="5" class="text-muted text-center py-4">No structures yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Structure';
$currentPage = 'structure';
require __DIR__ . '/../layout/main.php';
?>
