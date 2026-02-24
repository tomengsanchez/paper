<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Respondent Types</h2>
    <a href="/grievance/options/respondent-types/create" class="btn btn-primary">Add Respondent Type</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Type</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items ?? [] as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i->name) ?></td>
                    <td><?= htmlspecialchars($i->type ?? '') ?><?= ($i->type ?? '') === 'Others' && $i->type_specify ? ' (' . htmlspecialchars($i->type_specify) . ')' : '' ?></td>
                    <td><?= htmlspecialchars(mb_substr($i->description ?? '', 0, 50)) ?><?= mb_strlen($i->description ?? '') > 50 ? '...' : '' ?></td>
                    <td><a href="/grievance/options/respondent-types/edit/<?= (int)$i->id ?>" class="btn btn-sm btn-outline-primary">Edit</a> <a href="/grievance/options/respondent-types/delete/<?= (int)$i->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="4" class="text-muted text-center py-4">No respondent types yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="mt-2"><a href="/grievance">← Back to Grievance</a></p>
<?php $content = ob_get_clean(); $pageTitle = 'Respondent Types'; $currentPage = 'grievance-respondent-types'; require __DIR__ . '/../../layout/main.php'; ?>
