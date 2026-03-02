<?php
$settings = $settings ?? ['status_check' => false];
$saved = !empty($_SESSION['development_saved']);
if ($saved) {
    unset($_SESSION['development_saved']);
}
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Development</h2>
</div>

<?php if ($saved): ?>
<div class="alert alert-success alert-dismissible fade show">Development preferences saved.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Developer options</h5>
    </div>
    <div class="card-body">
        <form method="post" action="/system/development/save">
            <?= \Core\Csrf::field() ?>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="status_check" id="status_check" value="1" <?= !empty($settings['status_check']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="status_check">
                    <strong>System Status check</strong>
                </label>
                <p class="small text-muted mb-0 mt-1">When enabled, the footer on every page shows global system information: database query count and total time, request load time, classes loaded, user-defined functions count, PHP version, and memory usage. Helps developers spot slow or problematic requests.</p>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Development';
$currentPage = 'development';
require __DIR__ . '/../layout/main.php';
