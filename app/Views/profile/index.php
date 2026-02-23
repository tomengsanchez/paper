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
    <?php if (\Core\Auth::can('add_profiles')): ?><a href="/profile/create" class="btn btn-primary">Add Profile</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
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
                        <?php if (\Core\Auth::can('delete_profiles')): ?><a href="/profile/delete/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this profile?')">Delete</a><?php endif; ?>
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
<?php
$content = ob_get_clean();
$pageTitle = 'Profile';
$currentPage = 'profile';
$scripts = '<script>
$(function(){
    $(document).on("click", ".profile-other-details", function(e){
        e.preventDefault();
        var wrap = $(this);
        wrap.find(".profile-other-expanded").toggleClass("d-none");
        wrap.find(".profile-other-summary").toggleClass("d-none");
    });
});
</script>';
require __DIR__ . '/../layout/main.php';
?>
