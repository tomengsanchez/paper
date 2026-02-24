<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Grievance Dashboard</h2>
    <?php if (\Core\Auth::can('add_grievance')): ?><a href="/grievance/create" class="btn btn-primary">Register Grievance</a><?php endif; ?>
</div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="text-muted">Total Grievances</h5>
                <h3 class="mb-0"><?= (int) ($totalGrievances ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Recent Grievances</h6>
        <a href="/grievance/list" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body">
        <?php if (!empty($recentGrievances)): ?>
        <table class="table table-sm mb-0">
            <thead><tr><th>Case Number</th><th>Date Recorded</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($recentGrievances as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g->grievance_case_number ?: '-') ?></td>
                    <td><?= $g->date_recorded ? date('M j, Y H:i', strtotime($g->date_recorded)) : '-' ?></td>
                    <td><a href="/grievance/view/<?= (int)$g->id ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted mb-0">No grievances yet. <a href="/grievance/create">Register a grievance</a>.</p>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'Grievance Dashboard'; $currentPage = 'grievance-dashboard'; require __DIR__ . '/../layout/main.php'; ?>
