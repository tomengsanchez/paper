<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Perception</h2>
</div>
<div class="card">
    <div class="card-body">
        <p class="mb-0 text-muted">Perception form configuration and entries will appear here.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Perception';
$currentPage = 'forms-perception';
require __DIR__ . '/../layout/main.php';
