<?php
$uiTheme = $uiTheme ?? \App\UserUiSettings::THEME_DEFAULT;
$uiLayout = $uiLayout ?? \App\UserUiSettings::LAYOUT_SIDEBAR;
$notifyPrefs = $notifyPrefs ?? \App\UserNotificationSettings::get();
$themes = \App\UserUiSettings::themes();
$layouts = \App\UserUiSettings::layouts();
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Settings</h2>
</div>
<?php if (!empty($_SESSION['settings_ui_saved'])): unset($_SESSION['settings_ui_saved']); ?>
<div class="alert alert-success alert-dismissible fade show">UI preferences saved.</div>
<?php endif; ?>
<?php if (!empty($_SESSION['settings_notifications_saved'])): unset($_SESSION['settings_notifications_saved']); ?>
<div class="alert alert-success alert-dismissible fade show">Notification preferences saved.</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Custom UI</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">Customize appearance for your account. Changes are saved per user.</p>
        <form method="post" action="/settings/ui">
            <?= \Core\Csrf::field() ?>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold">Color theme</label>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <?php foreach ($themes as $key => $label): ?>
                        <label class="d-flex align-items-center gap-2 cursor-pointer">
                            <input type="radio" name="ui_theme" value="<?= htmlspecialchars($key) ?>" class="form-check-input" <?= $uiTheme === $key ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                            <?php if ($key !== 'default'): ?>
                            <span class="ui-theme-swatch ui-theme-swatch-<?= htmlspecialchars($key) ?>" title="<?= htmlspecialchars($label) ?>"></span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold">Navigation layout</label>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <?php foreach ($layouts as $key => $label): ?>
                        <label class="d-flex align-items-center gap-2">
                            <input type="radio" name="ui_layout" value="<?= htmlspecialchars($key) ?>" class="form-check-input" <?= $uiLayout === $key ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save UI preferences</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Notification Settings</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">Notify the logged-in user when the following events occur on linked projects.</p>
        <form method="post" action="/settings/notifications">
            <?= \Core\Csrf::field() ?>
            <div class="d-flex flex-column gap-2">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="notify_new_profile" value="1" class="form-check-input" <?= !empty($notifyPrefs['notify_new_profile']) ? 'checked' : '' ?>>
                    <span>New Profile on the linked projects</span>
                </label>
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="notify_new_grievance" value="1" class="form-check-input" <?= !empty($notifyPrefs['notify_new_grievance']) ? 'checked' : '' ?>>
                    <span>New Grievance on the linked projects</span>
                </label>
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="notify_grievance_status_change" value="1" class="form-check-input" <?= !empty($notifyPrefs['notify_grievance_status_change']) ? 'checked' : '' ?>>
                    <span>Grievance Change Status on the linked projects</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save notification preferences</button>
        </form>
    </div>
</div>

<style>
.ui-theme-swatch { width: 20px; height: 20px; border-radius: 4px; border: 1px solid rgba(0,0,0,.15); }
.ui-theme-swatch-green { background: #059669; }
.ui-theme-swatch-violet { background: #7c3aed; }
.ui-theme-swatch-amber { background: #d97706; }
.ui-theme-swatch-slate { background: #475569; }
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
$currentPage = 'settings';
require __DIR__ . '/../layout/main.php';
