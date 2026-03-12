<?php
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? ($listColumns[0] ?? '');
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? '/profile';
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Profiles</h2>
    <div class="d-flex gap-2">
        <?php if (\Core\Auth::can('add_profiles')): ?>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#profileImportModal">Import</button>
            <a href="/profile/create" class="btn btn-primary">Add Profile</a>
        <?php endif; ?>
    </div>
</div>
<?php
$listCanExport = \Core\Auth::can('export_profiles');
require __DIR__ . '/../partials/list_toolbar.php';
?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('profile', $key); if (!$col) continue; ?>
                    <th>
                        <?php if (!empty($col['sortable'])): ?><a href="<?= htmlspecialchars($listBaseUrl . $baseQuery . '&sort=' . urlencode($key) . '&order=' . (($listSort === $key && $listOrder === 'asc') ? 'desc' : 'asc')) ?>" class="text-decoration-none"><?php endif; ?>
                        <?= htmlspecialchars($col['label']) ?>
                        <?php if ($listSort === $key): ?><span class="ms-1"><?= $listOrder === 'asc' ? '↑' : '↓' ?></span><?php endif; ?>
                        <?php if (!empty($col['sortable'])): ?></a><?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $p): ?>
                <tr>
                    <?php foreach ($listColumns as $key): ?>
                    <td><?php
                        if ($key === 'other_details') {
                            $val = function($v) { return '<strong>' . htmlspecialchars($v) . '</strong>'; };
                            $lines = [];
                            $lines[] = 'Structures: ' . $val((string)(int)($p->structure_count ?? 0));
                            $lines[] = 'Residing in project affected: ' . $val(!empty($p->residing_in_project_affected) ? 'Yes' : 'No');
                            $lines[] = 'Structure owner: ' . $val(!empty($p->structure_owners) ? 'Yes' : 'No');
                            if (empty($p->structure_owners) && !empty(trim($p->if_not_structure_owner_what ?? ''))) {
                                $lines[] = 'If not owner: ' . $val(\mb_substr($p->if_not_structure_owner_what, 0, 50) . (\mb_strlen($p->if_not_structure_owner_what) > 50 ? '…' : ''));
                            }
                            $lines[] = 'Own property elsewhere: ' . $val(!empty($p->own_property_elsewhere) ? 'Yes' : 'No');
                            $lines[] = 'Availed gov\'t housing: ' . $val(!empty($p->availed_government_housing) ? 'Yes' : 'No');
                            $lines[] = 'HH Income: ' . $val(isset($p->hh_income) && $p->hh_income !== '' && $p->hh_income !== null ? number_format((float)$p->hh_income, 2) : '-');
                            $fullHtmlLines = $lines;
                            $valNote = function($v) { return '<strong>' . nl2br(htmlspecialchars(trim($v))) . '</strong>'; };
                            if (!empty(trim($p->residing_in_project_affected_note ?? ''))) $fullHtmlLines[] = 'Residing note: ' . $valNote($p->residing_in_project_affected_note);
                            if (!empty(trim($p->structure_owners_note ?? ''))) $fullHtmlLines[] = 'Owner note: ' . $valNote($p->structure_owners_note);
                            if (!empty(trim($p->own_property_elsewhere_note ?? ''))) $fullHtmlLines[] = 'Property elsewhere note: ' . $valNote($p->own_property_elsewhere_note);
                            if (!empty(trim($p->availed_government_housing_note ?? ''))) $fullHtmlLines[] = 'Availed housing note: ' . $valNote($p->availed_government_housing_note);
                            $shortText = implode(' | ', array_slice(array_map('strip_tags', $lines), 0, 3));
                            $fullHtml = implode('<br>', $fullHtmlLines);
                            $expandId = 'profile-detail-' . (int)$p->id;
                            ?><div class="profile-other-details" data-expand-id="<?= $expandId ?>" title="Click to expand/collapse" style="cursor:pointer;">
                                <div class="profile-other-summary small text-secondary"><?= htmlspecialchars($shortText) ?></div>
                                <div id="<?= $expandId ?>" class="profile-other-expanded small d-none"><?= $fullHtml ?></div>
                            </div><?php
                        } elseif ($key === 'full_name' && \Core\Auth::can('view_profiles')) {
                            $v = \App\ListHelper::getValue($p, $key);
                            echo '<a href="/profile/view/' . (int)$p->id . '" class="text-decoration-none">' . htmlspecialchars($v ?? '-') . '</a>';
                        } else {
                            $v = \App\ListHelper::getValue($p, $key);
                            if ($key === 'age') echo (int)$v;
                            else echo htmlspecialchars($v ?? '-');
                        }
                    ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (\Core\Auth::can('view_profiles')): ?><a href="/profile/view/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_profiles')): ?><a href="/profile/edit/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_profiles')): ?>
                        <form method="post" action="/profile/delete/<?= (int)$p->id ?>" class="d-inline" onsubmit="return confirm('Delete this profile?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($profiles)): ?>
                <tr><td colspan="<?= count($listColumns) + 1 ?>" class="text-muted text-center py-4">No profiles yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>

<div class="modal fade" id="profileImportModal" tabindex="-1" aria-labelledby="profileImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileImportModalLabel">Import Profiles from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="profile-import-form" action="/profile/import" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <?= \Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label for="profiles_file" class="form-label">CSV file</label>
                        <input type="file" name="profiles_file" id="profiles_file" class="form-control" accept=".csv" required>
                        <div class="form-text">
                            Expected columns (header row, case-insensitive):
                            <code>papsid</code> (optional), <code>control_number</code> (optional, but either PAPSID or control_number is required),
                            <code>full_name</code> (required), <code>age</code>, <code>contact_number</code>, <code>project_id</code> (numeric project ID).
                            Rows with unknown <code>project_id</code> will fail and be listed in the error summary.
                        </div>
                    </div>
                    <div id="profile-import-result" class="alert alert-info small d-none" role="status"></div>
                    <div id="profile-import-errors" class="alert alert-warning small d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="profile-import-submit" class="btn btn-primary">Validate &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Profile';
$currentPage = 'profile';
$scripts = <<<HTML
<script>
$(function(){
    $(document).on("click", ".profile-other-details", function(e){
        e.preventDefault();
        var wrap = $(this);
        wrap.find(".profile-other-expanded").toggleClass("d-none");
        wrap.find(".profile-other-summary").toggleClass("d-none");
    });

    var \$importForm = $('#profile-import-form');
    if (\$importForm.length) {
        \$importForm.on('submit', function(e){
            e.preventDefault();
            var form = this;
            var formData = new FormData(form);
            $('#profile-import-result').removeClass('d-none').text('Validating and importing, please wait...');
            $('#profile-import-errors').addClass('d-none').empty();
            $('#profile-import-submit').prop('disabled', true);

            $.ajax({
                url: form.action,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(resp){
                if (!resp || !resp.status) {
                    $('#profile-import-result').text('Unexpected response from server.');
                    return;
                }
                var summary = 'Import completed. Total rows: ' + (resp.total_rows || 0)
                    + ', Inserted: ' + (resp.inserted || 0)
                    + ', Updated: ' + (resp.updated || 0)
                    + ', Failed: ' + (resp.failed || 0) + '.';
                $('#profile-import-result').text(summary);

                if (resp.errors && resp.errors.length) {
                    var html = '<h6>Row errors</h6><ul class="mb-0">';
                    resp.errors.forEach(function(err){
                        var msg = (err.messages || []).join('; ');
                        html += '<li><strong>Row ' + (err.row || '?') + ':</strong> ' + $('<div>').text(msg).html() + '</li>';
                    });
                    html += '</ul>';
                    $('#profile-import-errors').html(html).removeClass('d-none');
                } else {
                    // If no errors, refresh page to show new/updated records
                    setTimeout(function(){ window.location.reload(); }, 1500);
                }
            }).fail(function(xhr){
                var msg = 'Import failed.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg += ' ' + xhr.responseJSON.message;
                }
                $('#profile-import-result').text(msg);
            }).always(function(){
                $('#profile-import-submit').prop('disabled', false);
            });
        });
    }
});
</script>
HTML;
require __DIR__ . '/../layout/main.php';
?>
