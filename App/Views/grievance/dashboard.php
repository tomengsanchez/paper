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
                <h3 class="mb-0" id="dg-total-count">
                    <span class="text-muted small">Loading…</span>
                </h3>
                <a href="/grievance/list" class="small text-decoration-none mt-2 d-inline-block">View all →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('by_category', $visible)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">By Category of Grievance</h6>
            </div>
            <div class="card-body pt-0">
                <ul class="list-unstyled mb-0" id="dg-by-category-list">
                    <li class="text-muted small">Loading…</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('by_type', $visible)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">By Type of Grievance</h6>
            </div>
            <div class="card-body pt-0">
                <ul class="list-unstyled mb-0" id="dg-by-type-list">
                    <li class="text-muted small">Loading…</li>
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
                <ul class="list-unstyled mb-0" id="dg-status-list">
                    <li class="text-muted small">Loading…</li>
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
                <h3 class="mb-1" id="dg-this-month">
                    <span class="text-muted small">Loading…</span>
                </h3>
                <p class="small text-muted mb-0" id="dg-last-month">
                    <span class="text-muted small">Loading…</span>
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

    <?php if (in_array('chart_by_project', $visible)): ?>
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

    <?php if (in_array('chart_in_progress', $visible)): ?>
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

    <?php if (in_array('by_project', $visible)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="text-muted text-uppercase small mb-0">By Project</h6>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Project</th><th class="text-end">Count</th></tr></thead>
                        <tbody id="dg-by-project-body">
                            <tr><td colspan="2" class="text-muted small">Loading…</td></tr>
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
                <div id="dg-in-progress-container">
                    <p class="text-muted small mb-0">Loading…</p>
                </div>
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
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Case Number</th><th>Respondent</th><th>Status</th><th>Date Recorded</th><th></th></tr></thead>
                        <tbody id="dg-recent-body">
                            <tr><td colspan="5" class="text-muted small">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
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

$apiUrl = (defined('BASE_URL') && BASE_URL ? BASE_URL : '') . '/api/grievance/dashboard';
$apiUrlJson = json_encode($apiUrl);
$selectedProjectIdJson = json_encode($selectedProjectId);
$statusLabelsJson = json_encode($statusLabels);
$progressLevelsArray = array_map(function ($pl) {
    return [
        'id' => (int)($pl->id ?? 0),
        'name' => $pl->name ?? '',
        'sort_order' => (int)($pl->sort_order ?? 0),
    ];
}, $progressLevels ?? []);
$progressLevelsJson = json_encode($progressLevelsArray);
$lastProgressLevelIdJson = json_encode($lastProgressLevelId);
$trendMonths = (int)($chartOptions['trend_months'] ?? 12);
$trendType = $chartOptions['trend_type'] ?? 'bar';
$trendTypeJson = json_encode($trendType);

$scripts = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var apiUrl = {$apiUrlJson};
    var selectedProjectId = {$selectedProjectIdJson};
    var statusLabels = {$statusLabelsJson};
    var progressLevels = {$progressLevelsJson};
    var lastProgressLevelId = {$lastProgressLevelIdJson};

    function fetchData() {
        var url = apiUrl;
        if (selectedProjectId && selectedProjectId > 0) {
            url += "?project_id=" + encodeURIComponent(selectedProjectId);
        }
        fetch(url, { credentials: "same-origin" })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(function (data) { renderDashboard(data || {}); })
            .catch(function () {
                var els = document.querySelectorAll("#dashboardWidgets .text-muted.small");
                els.forEach(function(el) { el.textContent = "Failed to load data"; });
            });
    }

    function renderDashboard(d) {
        // Total
        var totalEl = document.getElementById("dg-total-count");
        if (totalEl && typeof d.totalGrievances !== "undefined") {
            totalEl.textContent = (d.totalGrievances || 0).toLocaleString();
        }

        // Status breakdown
        var statusUl = document.getElementById("dg-status-list");
        if (statusUl && Array.isArray(d.statusBreakdown)) {
            statusUl.innerHTML = "";
            var byStatus = {};
            d.statusBreakdown.forEach(function(r) {
                if (!r || typeof r.status === "undefined") return;
                byStatus[r.status] = (r.cnt || 0);
            });
            [["open","success"],["in_progress","primary"],["closed","secondary"]].forEach(function(pair) {
                var st = pair[0], color = pair[1];
                var cnt = byStatus[st] || 0;
                var li = document.createElement("li");
                li.className = "d-flex justify-content-between align-items-center py-1";
                var spanLabel = document.createElement("span");
                spanLabel.textContent = statusLabels[st] || st;
                var spanBadge = document.createElement("span");
                spanBadge.className = "badge bg-" + color;
                spanBadge.textContent = cnt.toLocaleString();
                li.appendChild(spanLabel);
                li.appendChild(spanBadge);
                statusUl.appendChild(li);
            });
        }

        // This month vs last month
        var tmEl = document.getElementById("dg-this-month");
        var lmEl = document.getElementById("dg-last-month");
        if (tmEl && lmEl) {
            var thisMonth = d.thisMonth || 0;
            var lastMonth = d.lastMonth || 0;
            tmEl.textContent = thisMonth.toLocaleString();
            var pctText = "";
            if (lastMonth > 0 && thisMonth !== lastMonth) {
                var pct = Math.round((thisMonth - lastMonth) / lastMonth * 100);
                pctText = " (" + (pct >= 0 ? "+" : "") + pct + "%)";
            }
            lmEl.innerHTML = "Last month: " + lastMonth.toLocaleString() +
                (pctText ? " <span class='" + (thisMonth >= lastMonth ? "text-success" : "text-danger") + "'>" + pctText + "</span>" : "");
        }

        // By category
        var catUl = document.getElementById("dg-by-category-list");
        if (catUl && Array.isArray(d.byCategory)) {
            catUl.innerHTML = "";
            var hasAny = false;
            d.byCategory.forEach(function(row) {
                var cnt = (row && row.cnt) ? row.cnt : 0;
                if (cnt <= 0) return;
                hasAny = true;
                var li = document.createElement("li");
                li.className = "d-flex justify-content-between align-items-center py-1 border-bottom border-light";
                var spanName = document.createElement("span");
                spanName.textContent = row.name || "—";
                var spanBadge = document.createElement("span");
                spanBadge.className = "badge bg-secondary";
                spanBadge.textContent = cnt.toLocaleString();
                li.appendChild(spanName);
                li.appendChild(spanBadge);
                catUl.appendChild(li);
            });
            if (!hasAny) {
                catUl.innerHTML = "<li class=\"text-muted small\">No data.</li>";
            }
        }

        // By type
        var typeUl = document.getElementById("dg-by-type-list");
        if (typeUl && Array.isArray(d.byType)) {
            typeUl.innerHTML = "";
            var hasAnyT = false;
            d.byType.forEach(function(row) {
                var cnt = (row && row.cnt) ? row.cnt : 0;
                if (cnt <= 0) return;
                hasAnyT = true;
                var li = document.createElement("li");
                li.className = "d-flex justify-content-between align-items-center py-1 border-bottom border-light";
                var spanName = document.createElement("span");
                spanName.textContent = row.name || "—";
                var spanBadge = document.createElement("span");
                spanBadge.className = "badge bg-secondary";
                spanBadge.textContent = cnt.toLocaleString();
                li.appendChild(spanName);
                li.appendChild(spanBadge);
                typeUl.appendChild(li);
            });
            if (!hasAnyT) {
                typeUl.innerHTML = "<li class=\"text-muted small\">No data.</li>";
            }
        }

        // By project table
        var projBody = document.getElementById("dg-by-project-body");
        if (projBody && Array.isArray(d.byProject)) {
            projBody.innerHTML = "";
            if (!d.byProject.length) {
                projBody.innerHTML = "<tr><td colspan=\"2\" class=\"text-muted small\">No data.</td></tr>";
            } else {
                d.byProject.forEach(function(r) {
                    var tr = document.createElement("tr");
                    var tdName = document.createElement("td");
                    tdName.textContent = (r.project_name && r.project_name.length) ? r.project_name : "— No project —";
                    var tdCnt = document.createElement("td");
                    tdCnt.className = "text-end";
                    tdCnt.textContent = (r.cnt || 0).toLocaleString();
                    tr.appendChild(tdName);
                    tr.appendChild(tdCnt);
                    projBody.appendChild(tr);
                });
            }
        }

        // In-progress levels & escalation
        var inProgContainer = document.getElementById("dg-in-progress-container");
        if (inProgContainer && Array.isArray(d.inProgressLevels)) {
            var html = "";
            if (!d.inProgressLevels.length) {
                html = "<p class=\"text-muted small mb-0\">No grievances in progress.</p>";
            } else {
                html += "<ul class=\"list-unstyled mb-0\">";
                d.inProgressLevels.forEach(function(r) {
                    html += "<li class=\"d-flex justify-content-between align-items-center py-2 border-bottom border-light\">" +
                        "<span>" + (r.level_name || "—") + "</span>" +
                        "<span class=\"badge bg-primary\">" + (r.cnt || 0).toLocaleString() + "</span>" +
                        "</li>";
                });
                html += "</ul>";
                var needs = d.needsEscalationByLevel || {};
                var hasEsc = false;
                Object.keys(needs).forEach(function(k) {
                    if ((needs[k] || 0) > 0) { hasEsc = true; }
                });
                if (hasEsc && Array.isArray(progressLevels) && progressLevels.length) {
                    html += "<hr class=\"my-3\">";
                    html += "<h6 class=\"text-muted text-uppercase small mb-2\">Needs escalation / closure</h6>";
                    html += "<ul class=\"list-unstyled mb-0\">";
                    progressLevels.forEach(function(pl) {
                        var id = pl.id || 0;
                        var cnt = needs[id] || 0;
                        if (cnt <= 0) return;
                        var isLast = lastProgressLevelId !== null && id === lastProgressLevelId;
                        var label = isLast
                            ? (pl.name || "Level") + " grievances need to close"
                            : (pl.name || "Level") + " grievances need escalation";
                        html += "<li class=\"d-flex justify-content-between align-items-center py-1\">" +
                            "<span>" + label + "</span>" +
                            "<span class=\"badge bg-danger\">" + cnt.toLocaleString() + "</span>" +
                            "</li>";
                    });
                    html += "</ul>";
                }
            }
            inProgContainer.innerHTML = html;
        }

        // Recent grievances
        var recentBody = document.getElementById("dg-recent-body");
        if (recentBody && Array.isArray(d.recentGrievances)) {
            recentBody.innerHTML = "";
            if (!d.recentGrievances.length) {
                recentBody.innerHTML = "<tr><td colspan=\"5\" class=\"text-muted small\">No grievances yet. <a href=\"/grievance/create\">Register a grievance</a>.</td></tr>";
            } else {
                d.recentGrievances.forEach(function(g) {
                    var tr = document.createElement("tr");
                    var caseTd = document.createElement("td");
                    caseTd.textContent = (g.grievance_case_number && g.grievance_case_number.length)
                        ? g.grievance_case_number
                        : ("#" + g.id);
                    var respTd = document.createElement("td");
                    respTd.textContent = g.respondent_name || "-";
                    var statusTd = document.createElement("td");
                    var st = g.status || "open";
                    var lbl;
                    if (st === "open") lbl = "Open";
                    else if (st === "closed") lbl = "Closed";
                    else {
                        var levelName = "";
                        if (g.progress_level && Array.isArray(progressLevels)) {
                            var plObj = progressLevels.find(function(pl){ return pl.id === g.progress_level; });
                            levelName = plObj && plObj.name ? " " + plObj.name : "";
                        }
                        lbl = "In Progress" + levelName;
                    }
                    var badge = document.createElement("span");
                    badge.className = "badge " + (st === "closed" ? "bg-secondary" : (st === "in_progress" ? "bg-primary" : "bg-success"));
                    badge.textContent = lbl;
                    statusTd.appendChild(badge);
                    var dateTd = document.createElement("td");
                    dateTd.textContent = g.date_recorded || "-";
                    var actionTd = document.createElement("td");
                    var a = document.createElement("a");
                    a.className = "btn btn-sm btn-outline-secondary";
                    a.href = "/grievance/view/" + (g.id || 0);
                    a.textContent = "View";
                    actionTd.appendChild(a);
                    tr.appendChild(caseTd);
                    tr.appendChild(respTd);
                    tr.appendChild(statusTd);
                    tr.appendChild(dateTd);
                    tr.appendChild(actionTd);
                    recentBody.appendChild(tr);
                });
            }
        }

        // Charts
        var byStatusForChart = [];
        if (Array.isArray(d.statusBreakdown)) {
            var tmp = {};
            d.statusBreakdown.forEach(function(r) {
                if (!r || typeof r.status === "undefined") return;
                tmp[r.status] = (r.cnt || 0);
            });
            ["open","in_progress","closed"].forEach(function(st) {
                byStatusForChart.push({ label: statusLabels[st] || st, count: tmp[st] || 0 });
            });
        }
        var trendData = Array.isArray(d.monthlyTrend) ? d.monthlyTrend.slice() : [];
        var trendMonths = {$trendMonths};
        if (trendMonths === 6 && trendData.length > 6) {
            trendData = trendData.slice(-6);
        }
        var byProjectForChart = Array.isArray(d.byProject) ? d.byProject.map(function(r) {
            return { name: (r.project_name && r.project_name.length) ? r.project_name : "— No project —", count: r.cnt || 0 };
        }) : [];
        var inProgressForChart = Array.isArray(d.inProgressLevels) ? d.inProgressLevels.map(function(r) {
            return { name: r.level_name || "—", count: r.cnt || 0 };
        }) : [];

        if (document.getElementById("chartStatus") && byStatusForChart.length) {
            new Chart(document.getElementById("chartStatus"), {
                type: "doughnut",
                data: {
                    labels: byStatusForChart.map(function(x) { return x.label; }),
                    datasets: [{ data: byStatusForChart.map(function(x) { return x.count; }), backgroundColor: ["#22c55e","#3b82f6","#64748b"], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
            });
        }
        if (document.getElementById("chartTrend") && trendData.length) {
            var type = {$trendTypeJson};
            if (type !== "line" && type !== "bar") type = "bar";
            new Chart(document.getElementById("chartTrend"), {
                type: type,
                data: {
                    labels: trendData.map(function(x) { return x.label; }),
                    datasets: [{ label: "Grievances", data: trendData.map(function(x) { return x.count; }), backgroundColor: "rgba(59,130,246,0.6)", borderColor: "#3b82f6", borderWidth: type === "line" ? 2 : 0, fill: type === "line", tension: 0.3 }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
            });
        }
        if (document.getElementById("chartByProject") && byProjectForChart.length) {
            new Chart(document.getElementById("chartByProject"), {
                type: "bar",
                data: {
                    labels: byProjectForChart.map(function(x) { return x.name.length > 20 ? x.name.substring(0, 18) + "…" : x.name; }),
                    datasets: [{ label: "Count", data: byProjectForChart.map(function(x) { return x.count; }), backgroundColor: "rgba(59,130,246,0.6)", borderColor: "#3b82f6", borderWidth: 1 }]
                },
                options: { indexAxis: "y", responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
            });
        }
        if (document.getElementById("chartInProgress") && inProgressForChart.length) {
            new Chart(document.getElementById("chartInProgress"), {
                type: "bar",
                data: {
                    labels: inProgressForChart.map(function(x) { return x.name; }),
                    datasets: [{ label: "Count", data: inProgressForChart.map(function(x) { return x.count; }), backgroundColor: "rgba(59,130,246,0.6)", borderColor: "#3b82f6", borderWidth: 1 }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", fetchData);
    } else {
        fetchData();
    }
})();
</script>
HTML;

require __DIR__ . '/../layout/main.php';
