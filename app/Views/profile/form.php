<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $profile ? 'Edit Profile' : 'Add Profile' ?></h2>
    <a href="/profile" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $profile ? "/profile/update/{$profile->id}" : '/profile/store' ?>">
            <div class="mb-3">
                <label class="form-label">PAPSID</label>
                <input type="text" name="papsid" class="form-control" value="<?= htmlspecialchars(!empty($profile) ? $profile->papsid : ($papsid ?? '')) ?>" required readonly>
                <small class="text-muted">Auto-generated (PAPS-YEARMONTH0000000001)</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Control Number</label>
                <input type="text" name="control_number" class="form-control" value="<?= htmlspecialchars($profile->control_number ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($profile->full_name ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Age</label>
                <input type="number" name="age" class="form-control" value="<?= (int)($profile->age ?? 0) ?>" min="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($profile->contact_number ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Project</label>
                <select name="project_id" id="projectSelect" class="form-select" style="width:100%">
                    <option value="">-- Select Project --</option>
                    <?php if (!empty($profile->project_id)): ?>
                    <option value="<?= (int)$profile->project_id ?>" selected><?= htmlspecialchars($profile->project_name ?? '') ?></option>
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
    $('#projectSelect').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/projects',
            dataType: 'json',
            delay: 250,
            data: function(params){ return { q: params.term || '' }; },
            processResults: function(data){ return { results: data.map(function(r){ return { id: r.id, text: r.name }; }) }; }
        },
        minimumInputLength: 0,
        placeholder: 'Search project...',
        allowClear: true
    });
});
</script>";
$content = ob_get_clean();
$pageTitle = $profile ? 'Edit Profile' : 'Add Profile';
$currentPage = 'profile';
require __DIR__ . '/../layout/main.php';
?>
