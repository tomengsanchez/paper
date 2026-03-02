<?php
$canProfiles = \Core\Auth::can('view_profiles');
$canStructure = \Core\Auth::can('view_structure');
$canGrievance = \Core\Auth::can('view_grievance');
$canUsers = \Core\Auth::can('view_users');
$statusLabels = ['open' => 'Open', 'in_progress' => 'In Progress', 'closed' => 'Closed'];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
</div>

<p class="text-muted small mb-4">All data below is limited to projects linked to your account.</p>

<div class="row g-3" id="mainDashboard">
    <?php if ($canProfiles): ?>
    <!-- Profile section -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Profile</h5>
                <a href="/profile" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded bg-success bg-opacity-10 border border-success border-opacity-25">
                            <div class="text-muted small text-uppercase">New profiles created</div>
                            <div class="h4 mb-0" id="profile-created">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                            <div class="text-muted small text-uppercase">Profiles updated</div>
                            <div class="h4 mb-0" id="profile-updated">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded bg-info bg-opacity-10 border border-info border-opacity-25">
                            <div class="text-muted small text-uppercase">Structures added to profiles</div>
                            <div class="h4 mb-0" id="profile-added-structures">—</div>
                        </div>
                    </div>
                </div>
                <div class="chart-container" style="position: relative; height: 180px;">
                    <canvas id="chartProfile"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canStructure): ?>
    <!-- Structure section -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Structure</h5>
                <a href="/structure" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded bg-success bg-opacity-10 border border-success border-opacity-25">
                            <div class="text-muted small text-uppercase">Structures created</div>
                            <div class="h4 mb-0" id="structure-created">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                            <div class="text-muted small text-uppercase">Structures updated</div>
                            <div class="h4 mb-0" id="structure-updated">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded bg-info bg-opacity-10 border border-info border-opacity-25">
                            <div class="text-muted small text-uppercase">Images added</div>
                            <div class="h4 mb-0" id="structure-added-images">—</div>
                        </div>
                    </div>
                </div>
                <div class="chart-container" style="position: relative; height: 180px;">
                    <canvas id="chartStructure"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canGrievance): ?>
    <!-- Grievance section -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Grievance</h5>
                <a href="/grievance/list" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded bg-success bg-opacity-10 border border-success border-opacity-25">
                            <div class="text-muted small text-uppercase">New grievances</div>
                            <div class="h4 mb-0" id="grievance-created">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                            <div class="text-muted small text-uppercase">Updated</div>
                            <div class="h4 mb-0" id="grievance-updated">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25">
                            <div class="text-muted small text-uppercase">Status changed</div>
                            <div class="h4 mb-0" id="grievance-status-changed">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded bg-danger bg-opacity-10 border border-danger border-opacity-25">
                            <div class="text-muted small text-uppercase">Escalations</div>
                            <div class="h4 mb-0" id="grievance-escalations">—</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <h6 class="text-muted text-uppercase small mb-2">By status</h6>
                        <ul class="list-unstyled mb-0" id="grievance-by-status">
                            <li class="text-muted small">Loading…</li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="chart-container" style="position: relative; height: 200px;">
                            <canvas id="chartGrievance"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canUsers): ?>
    <!-- Users section -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Active Users Per Role</h5>
                <a href="/users" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-6">
                        <ul class="list-unstyled mb-0" id="users-by-role-list">
                            <li class="text-muted small">Loading…</li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="chart-container" style="position: relative; height: 220px;">
                            <canvas id="chartUsers"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$canProfiles && !$canStructure && !$canGrievance && !$canUsers): ?>
<div class="card">
    <div class="card-body">
        <p class="text-muted mb-0">Welcome to PAPeR. You don't have permissions to view dashboard widgets. Use the sidebar to navigate.</p>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
$currentPage = '';

$apiUrl = (defined('BASE_URL') && BASE_URL ? BASE_URL : '') . '/api/dashboard';
$apiUrlJson = json_encode($apiUrl);
$statusLabelsJson = json_encode($statusLabels);

$needsCharts = $canProfiles || $canStructure || $canGrievance || $canUsers;

$scripts = '';
if ($needsCharts) {
    $scripts = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var apiUrl = {$apiUrlJson};
    var statusLabels = {$statusLabelsJson};
    var charts = {};

    function fetchData() {
        fetch(apiUrl, { credentials: "same-origin" })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(function (data) { renderDashboard(data || {}); })
            .catch(function () {
                var els = document.querySelectorAll("#mainDashboard .text-muted.small");
                els.forEach(function(el) { if (el.textContent === "Loading…") el.textContent = "Failed to load"; });
            });
    }

    function renderDashboard(d) {
        var p = d.profile || {}, s = d.structure || {}, g = d.grievance || {}, u = d.users || [];

        if (document.getElementById("profile-created")) {
            document.getElementById("profile-created").textContent = (p.created || 0).toLocaleString();
            document.getElementById("profile-updated").textContent = (p.updated || 0).toLocaleString();
            document.getElementById("profile-added-structures").textContent = (p.added_structures || 0).toLocaleString();
            updateChartProfile(p);
        }
        if (document.getElementById("structure-created")) {
            document.getElementById("structure-created").textContent = (s.created || 0).toLocaleString();
            document.getElementById("structure-updated").textContent = (s.updated || 0).toLocaleString();
            document.getElementById("structure-added-images").textContent = (s.added_images || 0).toLocaleString();
            updateChartStructure(s);
        }
        if (document.getElementById("grievance-created")) {
            document.getElementById("grievance-created").textContent = (g.created || 0).toLocaleString();
            document.getElementById("grievance-updated").textContent = (g.updated || 0).toLocaleString();
            document.getElementById("grievance-status-changed").textContent = (g.status_changed || 0).toLocaleString();
            document.getElementById("grievance-escalations").textContent = (g.escalations || 0).toLocaleString();
            var byStatus = document.getElementById("grievance-by-status");
            if (byStatus) {
                var bs = g.by_status || [];
                byStatus.innerHTML = "";
                if (bs.length === 0) {
                    byStatus.innerHTML = "<li class=\"text-muted small\">No data.</li>";
                } else {
                    bs.forEach(function(r) {
                        var li = document.createElement("li");
                        li.className = "d-flex justify-content-between align-items-center py-1";
                        var span = document.createElement("span");
                        span.textContent = statusLabels[r.status] || r.status || "—";
                        var badge = document.createElement("span");
                        badge.className = "badge bg-secondary";
                        badge.textContent = (r.count || 0).toLocaleString();
                        li.appendChild(span);
                        li.appendChild(badge);
                        byStatus.appendChild(li);
                    });
                }
            }
            updateChartGrievance(g);
        }
        if (document.getElementById("users-by-role-list")) {
            var ul = document.getElementById("users-by-role-list");
            ul.innerHTML = "";
            if (!u.length) {
                ul.innerHTML = "<li class=\"text-muted small\">No data.</li>";
            } else {
                u.forEach(function(r) {
                    var li = document.createElement("li");
                    li.className = "d-flex justify-content-between align-items-center py-1";
                    var span = document.createElement("span");
                    span.textContent = r.role || "—";
                    var badge = document.createElement("span");
                    badge.className = "badge bg-secondary";
                    badge.textContent = (r.count || 0).toLocaleString();
                    li.appendChild(span);
                    li.appendChild(badge);
                    ul.appendChild(li);
                });
            }
            updateChartUsers(u);
        }
    }

    function updateChartProfile(p) {
        var canvas = document.getElementById("chartProfile");
        if (!canvas) return;
        if (charts.profile) charts.profile.destroy();
        var c = p.created || 0, u = p.updated || 0, a = p.added_structures || 0;
        if (c + u + a === 0) { c = 1; u = 1; a = 1; } // avoid empty chart
        charts.profile = new Chart(canvas, {
            type: "bar",
            data: {
                labels: ["Created", "Updated", "Added structures"],
                datasets: [{ label: "Count", data: [c, u, a], backgroundColor: ["#198754", "#0d6efd", "#0dcaf0"] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
    function updateChartStructure(s) {
        var canvas = document.getElementById("chartStructure");
        if (!canvas) return;
        if (charts.structure) charts.structure.destroy();
        var c = s.created || 0, u = s.updated || 0, a = s.added_images || 0;
        if (c + u + a === 0) { c = 1; u = 1; a = 1; }
        charts.structure = new Chart(canvas, {
            type: "bar",
            data: {
                labels: ["Created", "Updated", "Images added"],
                datasets: [{ label: "Count", data: [c, u, a], backgroundColor: ["#198754", "#0d6efd", "#0dcaf0"] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
    function updateChartGrievance(g) {
        var canvas = document.getElementById("chartGrievance");
        if (!canvas) return;
        if (charts.grievance) charts.grievance.destroy();
        var bs = g.by_status || [];
        var labels = bs.map(function(r) { return statusLabels[r.status] || r.status || "—"; });
        var data = bs.map(function(r) { return r.count || 0; });
        var colors = bs.map(function(r) {
            var st = r.status || "";
            if (st === "open") return "#198754";
            if (st === "in_progress") return "#0d6efd";
            return "#6c757d";
        });
        if (labels.length === 0) { labels = ["No data"]; data = [1]; colors = ["#dee2e6"]; }
        charts.grievance = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: colors }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
    function updateChartUsers(u) {
        var canvas = document.getElementById("chartUsers");
        if (!canvas) return;
        if (charts.users) charts.users.destroy();
        var labels = u.map(function(r) { return r.role || "—"; });
        var data = u.map(function(r) { return r.count || 0; });
        var colors = ["#0d6efd", "#198754", "#fd7e14", "#6f42c1", "#20c997", "#e83e8c"];
        if (labels.length === 0) { labels = ["No data"]; data = [1]; colors = ["#dee2e6"]; }
        charts.users = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: colors }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    fetchData();
})();
</script>
HTML;
}

require __DIR__ . '/../layout/main.php';
