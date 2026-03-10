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
<?php
$listCanExport = \Core\Auth::can('export_projects');
require __DIR__ . '/../partials/list_toolbar.php';
?>
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
                        if ($key === 'description') {
                            echo htmlspecialchars(\mb_substr((string)$v, 0, 80)) . (\mb_strlen((string)$v) > 80 ? '...' : '');
                        } elseif ($key === 'linked_users_count') {
                            $count = (int) $v;
                            if ($count > 0) {
                                ?>
                                <button type="button"
                                        class="btn btn-link btn-sm p-0 js-project-users"
                                        data-project-id="<?= (int)$p->id ?>"
                                        data-project-name="<?= htmlspecialchars($p->name ?? '') ?>">
                                    <?= $count ?> user<?= $count !== 1 ? 's' : '' ?>
                                </button>
                                <?php
                            } else {
                                ?>
                                <button type="button"
                                        class="btn btn-link btn-sm p-0 text-muted js-project-users"
                                        data-project-id="<?= (int)$p->id ?>"
                                        data-project-name="<?= htmlspecialchars($p->name ?? '') ?>">
                                    0 users
                                </button>
                                <?php
                            }
                        } else {
                            echo htmlspecialchars($v ?? '-');
                        }
                    ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (\Core\Auth::can('view_projects')): ?><a href="/library/view/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_projects')): ?><a href="/library/edit/<?= (int)$p->id ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                        <?php if (\Core\Auth::can('delete_projects')): ?>
                        <form method="post" action="/library/delete/<?= (int)$p->id ?>" class="d-inline" onsubmit="return confirm('Delete this project?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                        <?php endif; ?>
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

<div class="modal fade" id="projectUsersModal" tabindex="-1" aria-labelledby="projectUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectUsersModalLabel">Linked users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="project-users-loading" class="text-center py-4">
                    <div class="spinner-border text-secondary" role="status" aria-hidden="true"></div>
                    <div class="mt-2 small text-muted">Loading linked users...</div>
                </div>
                <div id="project-users-content" class="d-none">
                    <div id="project-users-empty" class="text-muted small d-none">
                        No users are currently linked to this project.
                    </div>
                    <ul id="project-users-list" class="list-group list-group-flush small d-none"></ul>
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
        var $modal = $('#projectUsersModal');
        var modal = null;
        if (window.bootstrap && window.bootstrap.Modal) {
            modal = new bootstrap.Modal($modal[0]);
        }

        function openProjectUsersModal(projectId, projectName) {
            if (!projectId) {
                return;
            }
            $('#projectUsersModalLabel').text('Linked users for: ' + (projectName || ('Project #' + projectId)));
            $('#project-users-loading').removeClass('d-none');
            $('#project-users-content').addClass('d-none');
            $('#project-users-empty').addClass('d-none');
            $('#project-users-list').empty().addClass('d-none');

            if (modal) {
                modal.show();
            } else {
                $modal.modal('show');
            }

            $.getJSON('/api/projects/' + projectId + '/users')
                .done(function (data) {
                    data = data || [];
                    $('#project-users-loading').addClass('d-none');
                    $('#project-users-content').removeClass('d-none');
                    if (!data.length) {
                        $('#project-users-empty').removeClass('d-none');
                        $('#project-users-list').addClass('d-none');
                        return;
                    }
                    var $list = $('#project-users-list');
                    data.forEach(function (u) {
                        var name = u.display_name || u.username || ('User #' + (u.id || ''));
                        var email = u.email || '';
                        var role = u.role_name || '';
                        var meta = [];
                        if (email) meta.push(email);
                        if (role) meta.push(role);
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
                    $('#project-users-loading').addClass('d-none');
                    $('#project-users-content').removeClass('d-none');
                    $('#project-users-empty')
                        .removeClass('d-none')
                        .text('Unable to load linked users. Please try again.');
                    $('#project-users-list').addClass('d-none');
                });
        }

        $(document).on('click', '.js-project-users', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var projectId = $btn.data('project-id');
            var projectName = $btn.data('project-name') || '';
            openProjectUsersModal(projectId, projectName);
        });
    });
</script>
<?php $scripts = ($scripts ?? '') . ob_get_clean(); ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Library';
$currentPage = 'library';
require __DIR__ . '/../layout/main.php';
?>
