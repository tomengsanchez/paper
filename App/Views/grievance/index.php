<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Grievance</h2>
</div>
<div class="card">
    <div class="card-body">
        <p class="text-muted mb-0">Grievance module placeholder.</p>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'Grievance'; $currentPage = 'grievance'; require __DIR__ . '/../layout/main.php'; ?>
