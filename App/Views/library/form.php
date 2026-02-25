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
            <div class="mb-3">
                <label class="form-label">Coordinator</label>
                <select name="coordinator_id" id="coordinatorSelect" class="form-select" style="width:100%">
                    <option value="">-- Select Coordinator --</option>
                    <?php if (!empty($project->coordinator_id)): ?>
                    <option value="<?= (int)$project->coordinator_id ?>" selected><?= htmlspecialchars($project->coordinator_name ?? '') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php
$scripts = "
<script>
$(function(){
    $('#coordinatorSelect').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/coordinators',
            dataType: 'json',
            delay: 250,
            data: function(params){ return { q: params.term }; },
            processResults: function(data){ return { results: data.map(function(r){ return { id: r.id, text: r.username }; }) }; }
        },
        minimumInputLength: 0,
        placeholder: 'Search coordinator...',
        allowClear: true
    });
});
</script>";
$content = ob_get_clean();
$pageTitle = $project ? 'Edit Project' : 'Add Project';
$currentPage = 'library';
require __DIR__ . '/../layout/main.php';
?>
