<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View Project</h2>
    <div>
        <a href="/library" class="btn btn-outline-secondary">Back</a>
        <?php if (\Core\Auth::can('edit_projects')): ?><a href="/library/edit/<?= (int)$project->id ?>" class="btn btn-primary">Edit</a><?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Project Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($project->name ?? '') ?></dd>
            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($project->description ?? '-')) ?></dd>
        </dl>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'View Project'; $currentPage = 'library'; require __DIR__ . '/../layout/main.php'; ?>
