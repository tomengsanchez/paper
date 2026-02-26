<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $project ? 'Edit Project' : 'Add Project' ?></h2>
    <a href="/library" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $project ? "/library/update/{$project->id}" : '/library/store' ?>" id="libraryForm">
            <?= \Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Project Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($project->name ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Project Description</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($project->description ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php
$scripts = $scripts ?? '';
$content = ob_get_clean();
$pageTitle = $project ? 'Edit Project' : 'Add Project';
$currentPage = 'library';
require __DIR__ . '/../layout/main.php';
?>
