<?php
$visible = $visibleWidgets ?? [];
$allWidgets = $dashboardWidgets ?? [
    'total',
    'status_breakdown',
    'trend',
    'chart_status',
    'chart_trend',
    'chart_by_project',
    'chart_in_progress',
    'by_category',
    'by_type',
    'by_project',
    'in_progress_levels',
    'recent',
];
$statusBreakdown = $statusBreakdown ?? [];
$byProject = $byProject ?? [];
$byCategory = $byCategory ?? [];
$byType = $byType ?? [];
$monthlyTrend = $monthlyTrend ?? [];
$inProgressLevels = $inProgressLevels ?? [];
$chartOptions = $chartOptions ?? ['trend_months' => 12, 'trend_type' => 'bar'];
$projects = $projects ?? [];
$selectedProjectId = (int)($selectedProjectId ?? 0);
$statusLabels = ['open' => 'Open', 'in_progress' => 'In Progress', 'closed' => 'Closed'];
$levelNameById = [];
foreach ($progressLevels ?? [] as $pl) { $levelNameById[(int)$pl->id] = $pl->name; }
$needsEscalationByLevel = $needsEscalationByLevel ?? [];

$lastProgressLevelId = null;
if (!empty($progressLevels)) {
    $orderedLevels = $progressLevels;
    usort($orderedLevels, function ($a, $b) {
        $sa = (int)($a->sort_order ?? 0);
        $sb = (int)($b->sort_order ?? 0);
        if ($sa === $sb) {
            return (int)($a->id ?? 0) <=> (int)($b->id ?? 0);
        }
        return $sa <=> $sb;
    });
    $last = end($orderedLevels);
    if ($last && isset($last->id)) {
        $lastProgressLevelId = (int)$last->id;
    }
}
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <h2 class="mb-0">Grievance Dashboard</h2>
        <form method="get" class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0" for="dashboardProjectFilter">Project</label>
            <select id="dashboardProjectFilter" name="project_id" class="form-select form-select-sm">
                <option value="">All projects</option>
                <?php foreach ($projects as $proj): ?>
                <option value="<?= (int)$proj->id ?>" <?= $selectedProjectId === (int)$proj->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($proj->name) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Apply</button>
        </form>
    </div>
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

    <?php if (in_array('by_category', $visible) && !empty($byCategory)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">By Category of Grievance</h6>
            </div>
            <div class="card-body pt-0">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($byCategory as $row):
                        if ((int)($row->cnt ?? 0) <= 0) continue;
                    ?>
                    <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                        <span><?= htmlspecialchars($row->name ?? '—') ?></span>
                        <span class="badge bg-secondary"><?= number_format((int)$row->cnt) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('by_type', $visible) && !empty($byType)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">By Type of Grievance</h6>
            </div>
            <div class="card-body pt-0">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($byType as $row):
                        if ((int)($row->cnt ?? 0) <= 0) continue;
                    ?>
                    <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                        <span><?= htmlspecialchars($row->name ?? '—') ?></span>
                        <span class="badge bg-secondary"><?= number_format((int)$row->cnt) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
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

    <?php if (in_array('chart_status', $visible)): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="text-muted text-uppercase small mb-0">Status distribution</h6>
            </div>
            <div class="card-body pt-0">
                <div class="chart-container" style="position: relative; height: 220px;">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('chart_trend', $visible)): ?>
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="text-muted text-uppercase small mb-0">Grievances over time</h6>
            </div>
            <div class="card-body pt-0">
                <div class="chart-container" style="position: relative; height: 260px;">
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('chart_by_project', $visible) && !empty($byProject)): ?>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">By project</h6>
            </div>
            <div class="card-body pt-0">
                <div class="chart-container" style="position: relative; height: 280px;">
                    <canvas id="chartByProject"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('chart_in_progress', $visible) && !empty($inProgressLevels)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">In progress by stage</h6>
            </div>
            <div class="card-body pt-0">
                <div class="chart-container" style="position: relative; height: 240px;">
                    <canvas id="chartInProgress"></canvas>
                </div>
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
                <?php
                $hasEscalations = false;
                foreach ($needsEscalationByLevel as $cnt) {
                    if ((int)$cnt > 0) { $hasEscalations = true; break; }
                }
                ?>
                <?php if ($hasEscalations): ?>
                <hr class="my-3">
                <h6 class="text-muted text-uppercase small mb-2">Needs escalation / closure</h6>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($progressLevels as $pl):
                        $id = (int)$pl->id;
                        $cnt = (int)($needsEscalationByLevel[$id] ?? 0);
                        if ($cnt <= 0) continue;
                        $isLast = $lastProgressLevelId !== null && $id === $lastProgressLevelId;
                        $label = $isLast
                            ? sprintf('%s grievances need to close', $pl->name)
                            : sprintf('%s grievances need escalation', $pl->name);
                    ?>
                    <li class="d-flex justify-content-between align-items-center py-1">
                        <span><?= htmlspecialchars($label) ?></span>
                        <span class="badge bg-danger"><?= number_format($cnt) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
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
                <?= \Core\Csrf::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="dashboardDesignerLabel">Customize dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Select which widgets and charts to show on your grievance dashboard.</p>
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
                        <span class="list-group-item text-muted small pt-2">Charts</span>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="chart_status" class="form-check-input" <?= in_array('chart_status', $visible) ? 'checked' : '' ?>>
                            <span>Chart: Status distribution (doughnut)</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="chart_trend" class="form-check-input" <?= in_array('chart_trend', $visible) ? 'checked' : '' ?>>
                            <span>Chart: Grievances over time</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="chart_by_project" class="form-check-input" <?= in_array('chart_by_project', $visible) ? 'checked' : '' ?>>
                            <span>Chart: By project (bar)</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="chart_in_progress" class="form-check-input" <?= in_array('chart_in_progress', $visible) ? 'checked' : '' ?>>
                            <span>Chart: In progress by stage (bar)</span>
                        </label>
                        <span class="list-group-item text-muted small pt-2">Tables &amp; lists</span>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="by_category" class="form-check-input" <?= in_array('by_category', $visible) ? 'checked' : '' ?>>
                            <span>By Category of Grievance</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="by_type" class="form-check-input" <?= in_array('by_type', $visible) ? 'checked' : '' ?>>
                            <span>By Type of Grievance</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="by_project" class="form-check-input" <?= in_array('by_project', $visible) ? 'checked' : '' ?>>
                            <span>By Project (table)</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="in_progress_levels" class="form-check-input" <?= in_array('in_progress_levels', $visible) ? 'checked' : '' ?>>
                            <span>In Progress by Stage (list)</span>
                        </label>
                        <label class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <input type="checkbox" name="widgets[]" value="recent" class="form-check-input" <?= in_array('recent', $visible) ? 'checked' : '' ?>>
                            <span>Recent Grievances</span>
                        </label>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="small text-uppercase text-muted mb-2">Trend chart options</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Period</label>
                                <select name="chart_trend_months" class="form-select form-select-sm">
                                    <option value="6" <?= (int)($chartOptions['trend_months'] ?? 12) === 6 ? 'selected' : '' ?>>Last 6 months</option>
                                    <option value="12" <?= (int)($chartOptions['trend_months'] ?? 12) === 12 ? 'selected' : '' ?>>Last 12 months</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Chart type</label>
                                <select name="chart_trend_type" class="form-select form-select-sm">
                                    <option value="bar" <?= ($chartOptions['trend_type'] ?? 'bar') === 'bar' ? 'selected' : '' ?>>Bar</option>
                                    <option value="line" <?= ($chartOptions['trend_type'] ?? 'bar') === 'line' ? 'selected' : '' ?>>Line</option>
                                </select>
                            </div>
                        </div>
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
$scripts = '';
if (in_array('chart_status', $visible) || in_array('chart_trend', $visible) || in_array('chart_by_project', $visible) || in_array('chart_in_progress', $visible)) {
    $byStatusForChart = [];
    foreach (['open', 'in_progress', 'closed'] as $st) {
        $cnt = 0;
        foreach ($statusBreakdown as $r) {
            if (($r->status ?? '') === $st) { $cnt = (int)$r->cnt; break; }
        }
        $byStatusForChart[] = ['label' => $statusLabels[$st] ?? $st, 'count' => $cnt];
    }
    $trendMonths = (int)($chartOptions['trend_months'] ?? 12);
    $monthlyTrendSliced = $trendMonths === 6 ? array_slice($monthlyTrend, -6) : $monthlyTrend;
    $scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
    $scripts .= '<script>window.GRIEVANCE_CHARTS = {';
    $scripts .= 'status: ' . json_encode($byStatusForChart) . ',';
    $scripts .= 'trend: ' . json_encode($monthlyTrendSliced) . ',';
    $scripts .= 'trendType: ' . json_encode($chartOptions['trend_type'] ?? 'bar') . ',';
    $scripts .= 'byProject: ' . json_encode(array_map(function($r) { return ['name' => $r->project_name ?: '— No project —', 'count' => (int)$r->cnt]; }, $byProject)) . ',';
    $scripts .= 'inProgress: ' . json_encode(array_map(function($r) { return ['name' => $r->level_name ?? '—', 'count' => (int)$r->cnt]; }, $inProgressLevels));
    $scripts .= '};</script>';
    $scripts .= '<script>
(function() {
    var d = window.GRIEVANCE_CHARTS;
    if (!d) return;
    var statusColors = { open: "#22c55e", in_progress: "#3b82f6", closed: "#64748b" };
    var defaultColors = ["#22c55e", "#3b82f6", "#64748b"];
    if (document.getElementById("chartStatus") && d.status) {
        new Chart(document.getElementById("chartStatus"), {
            type: "doughnut",
            data: {
                labels: d.status.map(function(x) { return x.label; }),
                datasets: [{ data: d.status.map(function(x) { return x.count; }), backgroundColor: defaultColors, borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
        });
    }
    if (document.getElementById("chartTrend") && d.trend && d.trend.length) {
        var type = (d.trendType === "line") ? "line" : "bar";
        new Chart(document.getElementById("chartTrend"), {
            type: type,
            data: {
                labels: d.trend.map(function(x) { return x.label; }),
                datasets: [{ label: "Grievances", data: d.trend.map(function(x) { return x.count; }), backgroundColor: "rgba(59,130,246,0.6)", borderColor: "#3b82f6", borderWidth: type === "line" ? 2 : 0, fill: type === "line", tension: 0.3 }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
        });
    }
    if (document.getElementById("chartByProject") && d.byProject && d.byProject.length) {
        new Chart(document.getElementById("chartByProject"), {
            type: "bar",
            data: {
                labels: d.byProject.map(function(x) { return x.name.length > 20 ? x.name.substring(0, 18) + "…" : x.name; }),
                datasets: [{ label: "Count", data: d.byProject.map(function(x) { return x.count; }), backgroundColor: "rgba(59,130,246,0.6)", borderColor: "#3b82f6", borderWidth: 1 }]
            },
            options: { indexAxis: "y", responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
        });
    }
    if (document.getElementById("chartInProgress") && d.inProgress && d.inProgress.length) {
        new Chart(document.getElementById("chartInProgress"), {
            type: "bar",
            data: {
                labels: d.inProgress.map(function(x) { return x.name; }),
                datasets: [{ label: "Count", data: d.inProgress.map(function(x) { return x.count; }), backgroundColor: "rgba(59,130,246,0.6)", borderColor: "#3b82f6", borderWidth: 1 }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
        });
    }
})();
</script>';
}
require __DIR__ . '/../layout/main.php';
