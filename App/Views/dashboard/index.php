<?php
$canUsers = \Core\Auth::can('view_users');
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
</div>

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

<?php if (!$canUsers): ?>
<div class="card">
    <div class="card-body">
        <p class="text-muted mb-0">Welcome. You don't have permissions to view dashboard widgets. Use the sidebar to navigate.</p>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
$currentPage = '';

$apiUrl = (defined('BASE_URL') && BASE_URL ? BASE_URL : '') . '/api/dashboard';
$apiUrlJson = json_encode($apiUrl);

$scripts = '';
if ($canUsers) {
    $scripts = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var apiUrl = {$apiUrlJson};
    var charts = {};

    function fetchData() {
        fetch(apiUrl, { credentials: "same-origin" })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(function (data) { renderDashboard(data || {}); })
            .catch(function () {
                var el = document.getElementById("users-by-role-list");
                if (el) el.textContent = "Failed to load";
            });
    }

    function renderDashboard(d) {
        var u = d.users || [];
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
    $scripts = str_replace('{$apiUrlJson}', $apiUrlJson, $scripts);
}

require __DIR__ . '/../layout/main.php';
