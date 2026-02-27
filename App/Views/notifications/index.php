<?php
$notifications = $notifications ?? [];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Notifications</h2>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Message</th>
                    <th>Type</th>
                    <th>Action</th>
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
                            \App\NotificationService::TYPE_NEW_GRIEVANCE => 'New Grievance',
                            \App\NotificationService::TYPE_GRIEVANCE_STATUS_CHANGE => 'Grievance Status Change',
                            default => ucfirst((string)($n->type ?? '')),
                        };
                        ?>
                        <?= htmlspecialchars($label) ?>
                    </td>
                    <td>
                        <a href="/notifications/click/<?= (int)$n->id ?>" class="btn btn-sm btn-outline-primary">Open</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($notifications)): ?>
                <tr>
                    <td colspan="4" class="text-muted text-center py-4">No notifications.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Notifications';
$currentPage = 'notifications';
require __DIR__ . '/../layout/main.php';
