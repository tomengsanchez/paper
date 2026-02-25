<?php
$listColumns = $listColumns ?? [];
$levelNameById = [];
foreach ($progressLevels ?? [] as $pl) { $levelNameById[(int)$pl->id] = $pl->name; }
$listSort = $listSort ?? ($listColumns[0] ?? '');
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? '/grievance/list';
$filterStatus = $filterStatus ?? '';
$filterProjectId = $filterProjectId ?? 0;
$filterStageId = $filterStageId ?? 0;
$filterNeedsEscalation = $filterNeedsEscalation ?? '';
$projects = $projects ?? [];

// Base query for sort links, including active filters
$baseQuery = '?q=' . urlencode($listSearch ?? '')
    . '&columns=' . urlencode(implode(',', $listColumns))
    . '&per_page=' . (int)($listPagination['per_page'] ?? 15)
    . '&status=' . urlencode($filterStatus)
    . '&project_id=' . (int)$filterProjectId
    . '&progress_level=' . (int)$filterStageId
    . '&needs_escalation=' . urlencode($filterNeedsEscalation);

// Extra params for shared toolbar/pagination partials
$listExtraParams = [
    'status' => $filterStatus,
    'project_id' => $filterProjectId ?: '',
    'progress_level' => $filterStageId ?: '',
    'needs_escalation' => $filterNeedsEscalation,
];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Grievances</h2>
    <?php if (\Core\Auth::can('add_grievance')): ?><a href="/grievance/create" class="btn btn-primary">Add Grievance</a><?php endif; ?>
</div>

<form method="get" action="<?= htmlspecialchars($listBaseUrl) ?>" class="row g-2 align-items-end mb-3">
    <input type="hidden" name="columns" value="<?= htmlspecialchars(implode(',', $listColumns)) ?>">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
    <input type="hidden" name="order" value="<?= htmlspecialchars($listOrder) ?>">
    <input type="hidden" name="per_page" value="<?= (int)($listPagination['per_page'] ?? 15) ?>">
    <input type="hidden" name="q" value="<?= htmlspecialchars($listSearch ?? '') ?>">
    <div class="col-6 col-md-3">
        <label class="form-label form-label-sm mb-1 small">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="open" <?= $filterStatus === 'open' ? 'selected' : '' ?>>Open</option>
            <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label form-label-sm mb-1 small">Project</label>
        <select name="project_id" class="form-select form-select-sm">
            <option value="0">All projects</option>
            <?php foreach ($projects as $proj): ?>
            <option value="<?= (int)$proj->id ?>" <?= (int)$filterProjectId === (int)$proj->id ? 'selected' : '' ?>><?= htmlspecialchars($proj->name ?? ('#' . $proj->id)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label form-label-sm mb-1 small">Stage</label>
        <select name="progress_level" class="form-select form-select-sm">
            <option value="0">All stages</option>
            <?php foreach ($progressLevels ?? [] as $pl): ?>
            <option value="<?= (int)$pl->id ?>" <?= (int)$filterStageId === (int)$pl->id ? 'selected' : '' ?>><?= htmlspecialchars($pl->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label form-label-sm mb-1 small">Needs escalation</label>
        <select name="needs_escalation" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="1" <?= $filterNeedsEscalation === '1' ? 'selected' : '' ?>>Needs escalation / close</option>
        </select>
    </div>
    <div class="col-12 d-flex gap-2 mt-1">
        <button type="submit" class="btn btn-sm btn-primary">Apply filters</button>
        <a href="<?= htmlspecialchars($listBaseUrl) ?>?columns=<?= htmlspecialchars(implode(',', $listColumns)) ?>&sort=<?= htmlspecialchars($listSort) ?>&order=<?= htmlspecialchars($listOrder) ?>&per_page=<?= (int)($listPagination['per_page'] ?? 15) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
    </div>
</form>

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
                    <th>Escalation</th>
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
                            $pl = $g->progress_level ?? 0;
                            $levelLabel = $pl && isset($levelNameById[(int)$pl]) ? ' ' . $levelNameById[(int)$pl] : ($pl ? ' L' . (int)$pl : '');
                            $v = $s === 'open' ? 'Open' : ($s === 'closed' ? 'Closed' : 'In Progress' . $levelLabel);
                        }
                        if ($key === 'respondent_name' && \Core\Auth::can('view_grievance')) {
                            echo '<a href="/grievance/view/' . (int)$g->id . '" class="text-decoration-none">' . htmlspecialchars($v ?? '-') . '</a>';
                        } else {
                            echo htmlspecialchars($v ?? '-');
                        }
                    ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (!empty($g->escalation_message)): ?>
                            <div><span class="badge bg-danger"><?= htmlspecialchars($g->escalation_message) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($g->level_started_at)): ?>
                            <div class="small text-muted mt-1">
                                <?= 'Last change: ' . date('M j, Y H:i', strtotime($g->level_started_at)) ?>
                            </div>
                        <?php elseif (empty($g->escalation_message)): ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (\Core\Auth::can('view_grievance')): ?><a href="/grievance/view/<?= (int)$g->id ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_grievance')): ?><a href="/grievance/edit/<?= (int)$g->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_grievance')): ?>
                        <form method="post" action="/grievance/delete/<?= (int)$g->id ?>" class="d-inline" onsubmit="return confirm('Delete this grievance?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($grievances)): ?>
                <tr><td colspan="<?= count($listColumns) + 2 ?>" class="text-muted text-center py-4">No grievances yet.</td></tr>
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
