<?php
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? ($listColumns[0] ?? '');
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? '/library';
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Library (Projects)</h2>
    <?php if (\Core\Auth::can('add_projects')): ?><a href="/library/create" class="btn btn-primary">Add Project</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('library', $key); if (!$col) continue; ?>
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
                <?php foreach ($projects as $p): ?>
                <tr>
                    <?php foreach ($listColumns as $key): ?>
                    <td><?php
                        $v = \App\ListHelper::getValue($p, $key);
                        if ($key === 'description') echo htmlspecialchars(\mb_substr((string)$v, 0, 80)) . (\mb_strlen((string)$v) > 80 ? '...' : '');
                        else echo htmlspecialchars($v ?? '-');
                    ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (\Core\Auth::can('view_projects')): ?><a href="/library/view/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_projects')): ?><a href="/library/edit/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_projects')): ?><a href="/library/delete/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this project?')">Delete</a><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($projects)): ?>
                <tr><td colspan="<?= count($listColumns) + 1 ?>" class="text-muted text-center py-4">No projects yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Library';
$currentPage = 'library';
require __DIR__ . '/../layout/main.php';
?>
