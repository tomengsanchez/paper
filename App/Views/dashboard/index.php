<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
</div>
<div class="card">
    <div class="card-body">
        <p class="text-muted mb-0">Welcome to PAPS. Use the sidebar to navigate.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
$currentPage = '';
require __DIR__ . '/../layout/main.php';
?>
