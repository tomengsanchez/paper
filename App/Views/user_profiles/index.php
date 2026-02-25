<?php
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? ($listColumns[0] ?? '');
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? '/users/profiles';
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Profile</h2>
    <?php if (\Core\Auth::can('add_user_profiles')): ?><a href="/users/profiles/create" class="btn btn-primary">Add User Profile</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('user_profiles', $key); if (!$col) continue; ?>
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
                    <td><?= htmlspecialchars(\App\ListHelper::getValue($p, $key) ?? '-') ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (\Core\Auth::can('view_user_profiles')): ?><a href="/users/profiles/view/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_user_profiles')): ?><a href="/users/profiles/edit/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_user_profiles')): ?>
                        <form method="post" action="/users/profiles/delete/<?= (int)$p->id ?>" class="d-inline" onsubmit="return confirm('Delete this user profile?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($profiles)): ?>
                <tr><td colspan="<?= count($listColumns) + 1 ?>" class="text-muted text-center py-4">No user profiles yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'User Profile';
$currentPage = 'user-profiles';
require __DIR__ . '/../layout/main.php';
?>
