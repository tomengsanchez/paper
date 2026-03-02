<?php
$settings = $settings ?? ['status_check' => false];
$simulatedOverride = $simulatedOverride ?? null;
$saved = !empty($_SESSION['development_saved']);
$message = $_SESSION['development_message'] ?? null;
if ($saved) {
    unset($_SESSION['development_saved']);
}
if ($message !== null) {
    unset($_SESSION['development_message']);
}
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Development</h2>
</div>

<?php if ($saved): ?>
<div class="alert alert-success alert-dismissible fade show"><?= $message !== null ? htmlspecialchars($message) : 'Development preferences saved.' ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Simulated date</h5>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">Makakatulong ito para sa pag-check kung nasusunod ba ang escalation kapag umaandar na ang days na kailangan ito ma-escalate, at makakatulong din sa future modules na gagawin.</p>
        <?php if ($simulatedOverride !== null): ?>
        <div class="alert alert-info py-2 mb-3">
            <strong>Current simulated date:</strong> <?= htmlspecialchars($simulatedOverride) ?>
        </div>
        <form method="post" action="/system/development/clear-simulated-time" class="d-inline">
            <?= \Core\Csrf::field() ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm">Use real date</button>
        </form>
        <?php endif; ?>
        <form method="post" action="/system/development/set-simulated-time" class="mt-2">
            <?= \Core\Csrf::field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label for="simulated_date" class="form-label small mb-0">Set simulated date</label>
                    <input type="date" name="simulated_date" id="simulated_date" class="form-control form-control-sm" value="<?= $simulatedOverride ?? '' ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Set</button>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-1">Leave empty and click Set to clear. Escalation and other date-based logic will use this date as "today".</p>
        </form>
    </div>
</div>

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
