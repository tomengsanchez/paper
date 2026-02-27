<?php
$notifications = $notifications ?? [];
$filters = $filters ?? ['from' => '', 'to' => '', 'module' => '', 'project_id' => null];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 20, 'total' => 0, 'total_pages' => 0];
$projects = $projects ?? [];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Notification History</h2>
</div>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="from" class="form-label">From date</label>
                <input type="date" id="from" name="from" class="form-control" value="<?= htmlspecialchars($filters['from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="to" class="form-label">To date</label>
                <input type="date" id="to" name="to" class="form-control" value="<?= htmlspecialchars($filters['to'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="module" class="form-label">Module</label>
                <select id="module" name="module" class="form-select">
                    <option value="">All</option>
                    <option value="<?= \App\NotificationService::RELATED_PROFILE ?>" <?= ($filters['module'] ?? '') === \App\NotificationService::RELATED_PROFILE ? 'selected' : '' ?>>Profile</option>
                    <option value="<?= \App\NotificationService::RELATED_STRUCTURE ?>" <?= ($filters['module'] ?? '') === \App\NotificationService::RELATED_STRUCTURE ? 'selected' : '' ?>>Structure</option>
                    <option value="<?= \App\NotificationService::RELATED_GRIEVANCE ?>" <?= ($filters['module'] ?? '') === \App\NotificationService::RELATED_GRIEVANCE ? 'selected' : '' ?>>Grievance</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select id="project_id" name="project_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?= (int)$p->id ?>" <?= (string)($filters['project_id'] ?? '') === (string)$p->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->name ?? ('Project #' . (int)$p->id)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply filters</button>
                <a href="/notifications" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 18%;">When</th>
                    <th>Message</th>
                    <th style="width: 16%;">Type</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $n): ?>
                <tr>
                    <td><?= htmlspecialchars($n->created_at ?? '') ?></td>
                    <td><?= htmlspecialchars($n->message ?? '') ?></td>
                    <td>
                        <?php
                        $label = match ($n->type ?? '') {
                            \App\NotificationService::TYPE_NEW_PROFILE => 'New Profile',
                            \App\NotificationService::TYPE_PROFILE_UPDATED => 'Profile Updated',
                            \App\NotificationService::TYPE_NEW_GRIEVANCE => 'New Grievance',
                            \App\NotificationService::TYPE_GRIEVANCE_STATUS_CHANGE => 'Grievance Status Change',
                            \App\NotificationService::TYPE_NEW_STRUCTURE => 'New Structure',
                            default => ucfirst((string)($n->type ?? '')),
                        };
                        ?>
                        <?= htmlspecialchars($label) ?>
                    </td>
                    <td>
                        <?php if (!empty($n->clicked_at)): ?>
                            <span class="badge bg-secondary">Opened</span>
                        <?php else: ?>
                            <span class="badge bg-success">New</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/notifications/click/<?= (int)$n->id ?>" class="btn btn-sm btn-outline-primary">Open</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($notifications)): ?>
                <tr>
                    <td colspan="5" class="text-muted text-center py-4">No notifications found for the selected filters.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body border-top">
        <?php
        $listBaseUrl = '/notifications';
        $listSearch = ''; $listColumns = []; $listSort = ''; $listOrder = 'desc';
        $listPagination = $pagination;
        $listExtraParams = [
            'from' => $filters['from'] ?? '',
            'to' => $filters['to'] ?? '',
            'module' => $filters['module'] ?? '',
            'project_id' => $filters['project_id'] ?? '',
        ];
        include __DIR__ . '/../partials/list_pagination.php';
        ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Notifications';
$currentPage = 'notifications';
require __DIR__ . '/../layout/main.php';
?>

