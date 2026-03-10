<?php
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? ($listColumns[0] ?? '');
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? '/users';
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Users</h2>
    <?php if (\Core\Auth::can('add_users')): ?><a href="/users/create" class="btn btn-primary">Add User</a><?php endif; ?>
</div>
<?php if (isset($_GET['error']) && $_GET['error'] === 'self'): ?>
<div class="alert alert-warning">You cannot delete your own account.</div>
<?php endif; ?>
<?php
$listCanExport = \Core\Auth::can('export_users');
require __DIR__ . '/../partials/list_toolbar.php';
?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('users', $key); if (!$col) continue; ?>
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
                <?php foreach ($users as $u): ?>
                <tr>
                    <?php foreach ($listColumns as $key): ?>
                    <td><?php
                        $v = \App\ListHelper::getValue($u, $key);
                        if ($key === 'linked_projects_count') {
                            $count = (int) $v;
                            if ($count > 0) {
                                ?>
                                <button type="button"
                                        class="btn btn-link btn-sm p-0 js-user-projects"
                                        data-user-id="<?= (int)$u->id ?>"
                                        data-user-name="<?= htmlspecialchars($u->display_name ?: $u->username ?: ('User #' . (int)$u->id)) ?>">
                                    <?= $count ?> project<?= $count !== 1 ? 's' : '' ?>
                                </button>
                                <?php
                            } else {
                                ?>
                                <button type="button"
                                        class="btn btn-link btn-sm p-0 text-muted js-user-projects"
                                        data-user-id="<?= (int)$u->id ?>"
                                        data-user-name="<?= htmlspecialchars($u->display_name ?: $u->username ?: ('User #' . (int)$u->id)) ?>">
                                    0 projects
                                </button>
                                <?php
                            }
                        } else {
                            echo htmlspecialchars($v ?? '-');
                        }
                    ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (\Core\Auth::can('view_users')): ?><a href="/users/view/<?= (int)$u->id ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_users')): ?><a href="/users/edit/<?= (int)$u->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_users') && $u->id != \Core\Auth::id()): ?>
                        <form method="post" action="/users/delete/<?= (int)$u->id ?>" class="d-inline" onsubmit="return confirm('Delete this user?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="<?= count($listColumns) + 1 ?>" class="text-muted text-center py-4">No users yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>

<div class="modal fade" id="userProjectsModal" tabindex="-1" aria-labelledby="userProjectsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userProjectsModalLabel">Linked projects</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="user-projects-loading" class="text-center py-4">
                    <div class="spinner-border text-secondary" role="status" aria-hidden="true"></div>
                    <div class="mt-2 small text-muted">Loading linked projects...</div>
                </div>
                <div id="user-projects-content" class="d-none">
                    <div id="user-projects-empty" class="text-muted small d-none">
                        No projects are currently linked to this user.
                    </div>
                    <ul id="user-projects-list" class="list-group list-group-flush small d-none"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
    $(function () {
        var $modal = $('#userProjectsModal');
        var modal = null;
        if (window.bootstrap && window.bootstrap.Modal) {
            modal = new bootstrap.Modal($modal[0]);
        }

        function openUserProjectsModal(userId, userName) {
            if (!userId) {
                return;
            }
            $('#userProjectsModalLabel').text('Linked projects for: ' + (userName || ('User #' + userId)));
            $('#user-projects-loading').removeClass('d-none');
            $('#user-projects-content').addClass('d-none');
            $('#user-projects-empty').addClass('d-none');
            $('#user-projects-list').empty().addClass('d-none');

            if (modal) {
                modal.show();
            } else {
                $modal.modal('show');
            }

            $.getJSON('/api/users/' + userId + '/projects')
                .done(function (data) {
                    data = data || [];
                    $('#user-projects-loading').addClass('d-none');
                    $('#user-projects-content').removeClass('d-none');
                    if (!data.length) {
                        $('#user-projects-empty').removeClass('d-none');
                        $('#user-projects-list').addClass('d-none');
                        return;
                    }
                    var $list = $('#user-projects-list');
                    data.forEach(function (p) {
                        var name = p.name || ('Project #' + (p.id || ''));
                        var meta = [];
                        if (p.id) {
                            meta.push('ID: ' + p.id);
                        }
                        var $item = $('<li class="list-group-item d-flex justify-content-between align-items-start"></li>');
                        var $body = $('<div class="me-3"></div>');
                        $('<div class="fw-semibold"></div>').text(name).appendTo($body);
                        if (meta.length) {
                            $('<div class="small text-muted"></div>').text(meta.join(' \u2022 ')).appendTo($body);
                        }
                        $item.append($body);
                        $list.append($item);
                    });
                    $list.removeClass('d-none');
                })
                .fail(function () {
                    $('#user-projects-loading').addClass('d-none');
                    $('#user-projects-content').removeClass('d-none');
                    $('#user-projects-empty')
                        .removeClass('d-none')
                        .text('Unable to load linked projects. Please try again.');
                    $('#user-projects-list').addClass('d-none');
                });
        }

        $(document).on('click', '.js-user-projects', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var userId = $btn.data('user-id');
            var userName = $btn.data('user-name') || '';
            openUserProjectsModal(userId, userName);
        });
    });
</script>
<?php $scripts = ($scripts ?? '') . ob_get_clean(); ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Users';
$currentPage = 'users';
require __DIR__ . '/../layout/main.php';
?>
