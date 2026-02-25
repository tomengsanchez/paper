<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Preferred Languages</h2>
    <a href="/grievance/options/preferred-languages/create" class="btn btn-primary">Add Preferred Language</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items ?? [] as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i->name) ?></td>
                    <td><?= htmlspecialchars(mb_substr($i->description ?? '', 0, 80)) ?><?= mb_strlen($i->description ?? '') > 80 ? '...' : '' ?></td>
                    <td><a href="/grievance/options/preferred-languages/edit/<?= (int)$i->id ?>" class="btn btn-sm btn-outline-primary">Edit</a> <form method="post" action="/grievance/options/preferred-languages/delete/<?= (int)$i->id ?>" class="d-inline" onsubmit="return confirm('Delete?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="3" class="text-muted text-center py-4">No preferred languages yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="mt-2"><a href="/grievance">← Back to Grievance</a></p>
<?php $content = ob_get_clean(); $pageTitle = 'Preferred Languages'; $currentPage = 'grievance-preferred-languages'; require __DIR__ . '/../../layout/main.php'; ?>
