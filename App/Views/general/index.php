<?php
$settings = $settings ?? ['region' => '', 'timezone' => \App\GeneralSettings::DEFAULT_TIMEZONE];
$regions = $regions ?? \App\GeneralSettings::regions();
$timezones = $timezones ?? \App\GeneralSettings::timezones();
$saved = !empty($_SESSION['general_saved']);
if ($saved) {
    unset($_SESSION['general_saved']);
}
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">General</h2>
</div>

<?php if ($saved): ?>
<div class="alert alert-success alert-dismissible fade show">General settings saved.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">System general options</h5>
    </div>
    <div class="card-body">
        <form method="post" action="/system/general/save">
            <?= \Core\Csrf::field() ?>
            <div class="mb-3">
                <label for="region" class="form-label">Region</label>
                <select class="form-select" name="region" id="region">
                    <?php foreach ($regions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= ($settings['region'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Optional. Geographic region for the application.</div>
            </div>
            <div class="mb-3">
                <label for="timezone" class="form-label">Timezone</label>
                <select class="form-select" name="timezone" id="timezone">
                    <?php foreach ($timezones as $tz): ?>
                    <option value="<?= htmlspecialchars($tz) ?>" <?= ($settings['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Used for date and time display.</div>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'General';
$currentPage = 'general';
require __DIR__ . '/../layout/main.php';
