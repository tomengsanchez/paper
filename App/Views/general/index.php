<?php
$settings = $settings ?? ['region' => '', 'timezone' => \App\GeneralSettings::DEFAULT_TIMEZONE];
$branding = $branding ?? (object) ['company_name' => '', 'app_name' => 'PAPeR'];
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

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Company &amp; app branding</h5>
    </div>
    <div class="card-body">
        <form method="post" action="/system/general/save" enctype="multipart/form-data">
            <?= \Core\Csrf::field() ?>
            <?php if (!empty($_SESSION['general_error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['general_error']) ?></div>
            <?php unset($_SESSION['general_error']); endif; ?>
            <div class="mb-3">
                <label for="company_logo" class="form-label">Company logo</label>
                <p class="text-muted small mb-2">Square images only (e.g. 200×200). Max 2 MB. JPEG, PNG, GIF, or WebP.</p>
                <?php if (!empty($branding->company_logo)): ?>
                <?php $base = defined('BASE_URL') && BASE_URL ? rtrim(BASE_URL, '/') : ''; $logoUrl = $base . '/serve/company-logo?t=' . time(); ?>
                <div class="mb-2 d-flex align-items-center gap-3">
                    <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Company logo" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                    <label class="form-check mb-0">
                        <input type="checkbox" name="remove_logo" value="1" class="form-check-input"> Remove logo
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" class="form-control" name="company_logo" id="company_logo" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <div class="mb-3">
                <label for="company_name" class="form-label">Company name</label>
                <input type="text" class="form-control" name="company_name" id="company_name" value="<?= htmlspecialchars($branding->company_name ?? '') ?>" placeholder="e.g. Acme Corporation">
                <div class="form-text">Your organization or company name. Used in branding and login screen.</div>
            </div>
            <div class="mb-3">
                <label for="app_name" class="form-label">App name</label>
                <input type="text" class="form-control" name="app_name" id="app_name" value="<?= htmlspecialchars($branding->app_name ?? 'PAPeR') ?>" placeholder="PAPeR" required>
                <div class="form-text">Application name shown in the navigation bar and page titles.</div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">Regional settings</h6>
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
