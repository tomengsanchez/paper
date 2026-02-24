<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>In Progress Stages</h2>
    <a href="/grievance/options/progress-levels/create" class="btn btn-primary">Add Stage</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Sort Order</th><th>Days to Address</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items ?? [] as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i->name) ?></td>
                    <td><?= (int)$i->sort_order ?></td>
                    <td><?= isset($i->days_to_address) && $i->days_to_address !== null ? (int)$i->days_to_address : '' ?></td>
                    <td><?= htmlspecialchars(mb_substr($i->description ?? '', 0, 80)) ?><?= mb_strlen($i->description ?? '') > 80 ? '...' : '' ?></td>
                    <td><a href="/grievance/options/progress-levels/edit/<?= (int)$i->id ?>" class="btn btn-sm btn-outline-primary">Edit</a> <a href="/grievance/options/progress-levels/delete/<?= (int)$i->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this stage? Grievances using it may show an unknown level.')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="5" class="text-muted text-center py-4">No stages yet. Add Level 1, Level 2, Level 3 or custom stages.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="mt-2"><a href="/grievance">← Back to Grievance</a></p>
<?php $content = ob_get_clean(); $pageTitle = 'In Progress Stages'; $currentPage = 'grievance-progress-levels'; require __DIR__ . '/../../layout/main.php'; ?>
