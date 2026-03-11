<?php
$settings = $settings ?? ['region' => '', 'timezone' => \App\GeneralSettings::DEFAULT_TIMEZONE];
$regions = $regions ?? \App\GeneralSettings::regions();
$timezones = $timezones ?? \App\GeneralSettings::timezones();
$branding = $branding ?? \App\Models\AppSettings::getBrandingConfig();
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logoPath = $branding->logo_path ?? '';
$logoUrl = $logoPath !== '' ? $baseUrl . '/serve/app-logo' : '';
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
        <h5 class="mb-0">Branding (App &amp; Company)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">
            Configure the application name, company name, and logo. The logo is also used as the favicon.
        </p>
        <form method="post" action="/system/general/save" enctype="multipart/form-data">
            <?= \Core\Csrf::field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">App name</label>
                    <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($branding->app_name ?? 'PAPeR') ?>" maxlength="100">
                    <small class="text-muted d-block mt-1">Shown in the header and as the default part of the page title.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Company / organization name</label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($branding->company_name ?? '') ?>" maxlength="150">
                    <small class="text-muted d-block mt-1">Optional. When set, it is appended to the page title.</small>
                </div>
            </div>
            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Logo (also used as favicon)</label>
                    <input type="file" name="app_logo" class="form-control" accept=".png,.jpg,.jpeg,.ico,image/png,image/jpeg,image/x-icon">
                    <small class="text-muted d-block mt-1">
                        Recommended: square PNG (at least 64x64). Uploading a new logo replaces the existing one.
                    </small>
                </div>
                <div class="col-md-6 d-flex flex-column align-items-start justify-content-center">
                    <label class="form-label fw-semibold mb-2">Current logo preview</label>
                    <?php if ($logoUrl !== ''): ?>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="App logo" style="width:48px;height:48px;border-radius:8px;border:1px solid #e5e7eb;object-fit:contain;">
                            <span class="text-muted small"><?= htmlspecialchars($logoPath) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="text-muted small">No logo uploaded yet. The browser will use the default icon (if any).</span>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">System general options</h6>
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
