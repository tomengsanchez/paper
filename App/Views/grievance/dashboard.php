<?php
$visible = $visibleWidgets ?? [];
$allWidgets = $dashboardWidgets ?? ['total', 'status_breakdown', 'trend', 'by_project', 'in_progress_levels', 'recent'];
$statusBreakdown = $statusBreakdown ?? [];
$byProject = $byProject ?? [];
$inProgressLevels = $inProgressLevels ?? [];
$statusLabels = ['open' => 'Open', 'in_progress' => 'In Progress', 'closed' => 'Closed'];
$levelNameById = [];
foreach ($progressLevels ?? [] as $pl) { $levelNameById[(int)$pl->id] = $pl->name; }
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0">Grievance Dashboard</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#dashboardDesignerModal" title="Choose which widgets to show">
            <span class="d-none d-sm-inline">Customize dashboard</span>
            <span class="d-inline d-sm-none">Customize</span>
        </button>
        <?php if (\Core\Auth::can('add_grievance')): ?><a href="/grievance/create" class="btn btn-primary">Register Grievance</a><?php endif; ?>
    </div>
</div>

<div class="row g-3" id="dashboardWidgets">
    <?php if (in_array('total', $visible)): ?>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">Total Grievances</h6>
                <h3 class="mb-0"><?= number_format((int)($totalGrievances ?? 0)) ?></h3>
                <a href="/grievance/list" class="small text-decoration-none mt-2 d-inline-block">View all →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('status_breakdown', $visible)): ?>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">By Status</h6>
                <ul class="list-unstyled mb-0">
                    <?php
                    $byStatus = [];
                    foreach ($statusBreakdown as $r) { $byStatus[$r->status] = (int)$r->cnt; }
                    foreach (['open' => 'success', 'in_progress' => 'primary', 'closed' => 'secondary'] as $st => $color):
                        $cnt = $byStatus[$st] ?? 0;
                    ?>
                    <li class="d-flex justify-content-between align-items-center py-1">
                        <span><?= $statusLabels[$st] ?? $st ?></span>
                        <span class="badge bg-<?= $color ?>"><?= number_format($cnt) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('trend', $visible)): ?>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-2">This Month</h6>
                <h3 class="mb-1"><?= number_format((int)($thisMonth ?? 0)) ?></h3>
                <p class="small text-muted mb-0">
                    Last month: <?= number_format((int)($lastMonth ?? 0)) ?>
                    <?php
                    $last = (int)($lastMonth ?? 0);
                    $cur = (int)($thisMonth ?? 0);
                    if ($last > 0 && $cur != $last) {
                        $pct = round(($cur - $last) / $last * 100);
                        echo ' <span class="' . ($pct >= 0 ? 'text-success' : 'text-danger') . '">(' . ($pct >= 0 ? '+' : '') . $pct . '%)</span>';
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('by_project', $visible) && !empty($byProject)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">By Project</h6>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Project</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($byProject as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r->project_name ?: '— No project —') ?></td>
                                <td class="text-end"><?= number_format((int)$r->cnt) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('in_progress_levels', $visible)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">In Progress by Stage</h6>
            </div>
            <div class="card-body pt-0">
                <?php if (!empty($inProgressLevels)): ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($inProgressLevels as $r): ?>
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                        <span><?= htmlspecialchars($r->level_name ?? '—') ?></span>
                        <span class="badge bg-primary"><?= number_format((int)$r->cnt) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted small mb-0">No grievances in progress.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('recent', $visible)): ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="text-muted text-uppercase small mb-0">Recent Grievances</h6>
                <a href="/grievance/list" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentGrievances)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Case Number</th><th>Respondent</th><th>Status</th><th>Date Recorded</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($recentGrievances as $g):
                                $st = $g->status ?? 'open';
                                $lbl = $st === 'open' ? 'Open' : ($st === 'closed' ? 'Closed' : 'In Progress' . (isset($levelNameById[(int)($g->progress_level ?? 0)]) ? ' ' . $levelNameById[(int)$g->progress_level] : ''));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($g->grievance_case_number ?: '#'.$g->id) ?></td>
                                <td><?= htmlspecialchars($g->respondent_name ?? '-') ?></td>
                                <td><span class="badge <?= $st === 'closed' ? 'bg-secondary' : ($st === 'in_progress' ? 'bg-primary' : 'bg-success') ?>"><?= htmlspecialchars($lbl) ?></span></td>
                                <td><?= $g->date_recorded ? date('M j, Y H:i', strtotime($g->date_recorded)) : '-' ?></td>
                                <td><a href="/grievance/view/<?= (int)$g->id ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">No grievances yet. <a href="/grievance/create">Register a grievance</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($visible)): ?>
<div class="alert alert-info">
    <strong>No widgets selected.</strong> <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dashboardDesignerModal">Customize dashboard</button> to choose which widgets to show.
</div>
<?php endif; ?>

<!-- Dashboard Designer Modal -->
<div class="modal fade" id="dashboardDesignerModal" tabindex="-1" aria-labelledby="dashboardDesignerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/grievance/dashboard-config">
                <div class="modal-header">
                    <h5 class="modal-title" id="dashboardDesignerLabel">Customize dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Select which widgets to show on your grievance dashboard.</p>
                    <div class="list-group list-group-flush">
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="total" class="form-check-input" <?= in_array('total', $visible) ? 'checked' : '' ?>>
                            <span>Total Grievances</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="status_breakdown" class="form-check-input" <?= in_array('status_breakdown', $visible) ? 'checked' : '' ?>>
                            <span>By Status (Open / In Progress / Closed)</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="trend" class="form-check-input" <?= in_array('trend', $visible) ? 'checked' : '' ?>>
                            <span>This Month vs Last Month</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="by_project" class="form-check-input" <?= in_array('by_project', $visible) ? 'checked' : '' ?>>
                            <span>By Project</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="in_progress_levels" class="form-check-input" <?= in_array('in_progress_levels', $visible) ? 'checked' : '' ?>>
                            <span>In Progress by Stage</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="recent" class="form-check-input" <?= in_array('recent', $visible) ? 'checked' : '' ?>>
                            <span>Recent Grievances</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Grievance Dashboard';
$currentPage = 'grievance-dashboard';
require __DIR__ . '/../layout/main.php';
