<?php
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? ($listColumns[0] ?? '');
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? '/grievance/list';
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Grievances</h2>
    <?php if (\Core\Auth::can('add_grievance')): ?><a href="/grievance/create" class="btn btn-primary">Add Grievance</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('grievance', $key); if (!$col) continue; ?>
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
                <?php foreach ($grievances as $g): ?>
                <tr>
                    <?php foreach ($listColumns as $key): ?>
                    <td><?php
                        $v = \App\ListHelper::getValue($g, $key);
                        if ($key === 'date_recorded' && $v) $v = date('M j, Y H:i', strtotime($v));
                        if ($key === 'status') {
                            $s = $g->status ?? 'open';
                            $v = $s === 'open' ? 'Open' : ($s === 'closed' ? 'Closed' : 'In Progress' . (($g->progress_level ?? 0) ? ' L' . (int)$g->progress_level : ''));
                        }
                        if ($key === 'respondent_name' && \Core\Auth::can('view_grievance')) {
                            echo '<a href="/grievance/view/' . (int)$g->id . '" class="text-decoration-none">' . htmlspecialchars($v ?? '-') . '</a>';
                        } else {
                            echo htmlspecialchars($v ?? '-');
                        }
                    ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (\Core\Auth::can('view_grievance')): ?><a href="/grievance/view/<?= (int)$g->id ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_grievance')): ?><a href="/grievance/edit/<?= (int)$g->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_grievance')): ?><a href="/grievance/delete/<?= (int)$g->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this grievance?')">Delete</a><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($grievances)): ?>
                <tr><td colspan="<?= count($listColumns) + 1 ?>" class="text-muted text-center py-4">No grievances yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Grievances';
$currentPage = 'grievance-list';
require __DIR__ . '/../layout/main.php';
?>
