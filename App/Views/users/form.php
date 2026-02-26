<?php
$linkedProjects = $linkedProjects ?? [];
$user = $user ?? null;
$roles = $roles ?? [];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $user ? 'Edit User' : 'Add User' ?></h2>
    <a href="/users" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $user ? "/users/update/{$user->id}" : '/users/store' ?>" id="userForm">
            <?= \Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user->username ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Display name</label>
                <input type="text" name="display_name" class="form-control" value="<?= htmlspecialchars($user->display_name ?? '') ?>" placeholder="Optional">
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email ?? '') ?>" placeholder="user@example.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Password <?= $user ? '(leave blank to keep)' : '' ?></label>
                <input type="password" name="password" class="form-control" <?= $user ? '' : 'required' ?>>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r->id ?>" <?= ($user->role_id ?? 0) == $r->id ? 'selected' : '' ?>><?= htmlspecialchars($r->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Linked Projects</label>
                <p class="text-muted small mb-2">Search and add projects; remove from the list below.</p>
                <select id="projectSelect" class="form-select" style="width: 100%; max-width: 400px;">
                    <option value="">-- Search project to add --</option>
                </select>
                <div id="linkedProjectsList" class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                    <?php foreach ($linkedProjects as $proj): ?>
                    <span class="badge bg-primary d-inline-flex align-items-center gap-1 py-2 px-2">
                        <?= htmlspecialchars($proj->name) ?>
                        <input type="hidden" name="project_ids[]" value="<?= (int)$proj->id ?>">
                        <button type="button" class="btn-remove-project border-0 bg-transparent text-white p-0 ms-1" style="font-size: 1em; line-height: 1; opacity: 0.9;" data-id="<?= (int)$proj->id ?>" aria-label="Remove">×</button>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = $user ? 'Edit User' : 'Add User';
$currentPage = 'users';
$scripts = ($scripts ?? '') . <<<'SCRIPT'
<script>
$(function() {
    var $list = $('#linkedProjectsList');
    var $select = $('#projectSelect');
    var addedIds = {};
    $list.find('input[name="project_ids[]"]').each(function() { addedIds[$(this).val()] = true; });

    $select.select2({
        placeholder: 'Search project to add',
        allowClear: true,
        ajax: {
            url: '/api/projects',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term || '' }; },
            processResults: function(data) {
                var items = (data || []).map(function(p) {
                    return { id: p.id, text: p.name || ('#' + p.id) };
                }).filter(function(p) { return !addedIds[p.id]; });
                return { results: items };
            }
        },
        minimumInputLength: 0
    });
    $select.on('select2:select', function(e) {
        var d = e.params.data;
        if (addedIds[d.id]) return;
        addedIds[d.id] = true;
        var $badge = $('<span class="badge bg-primary d-inline-flex align-items-center gap-1 py-2 px-2">' +
            escapeHtml(d.text) +
            '<input type="hidden" name="project_ids[]" value="' + d.id + '">' +
            '<button type="button" class="btn-remove-project border-0 bg-transparent text-white p-0 ms-1" style="font-size: 1em; line-height: 1; opacity: 0.9;" data-id="' + d.id + '" aria-label="Remove">×</button></span>');
        $list.append($badge);
        $badge.find('.btn-remove-project').on('click', removeProject);
        $select.val(null).trigger('change');
    });
    function removeProject() {
        var id = $(this).data('id');
        delete addedIds[id];
        $(this).closest('.badge').remove();
    }
    $list.on('click', '.btn-remove-project', removeProject);
    function escapeHtml(t) { return $('<div>').text(t).html(); }
});
</script>
SCRIPT;
require __DIR__ . '/../layout/main.php';
