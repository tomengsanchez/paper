<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Security Settings</h2>
</div>
<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">Security settings saved successfully.</div>
<?php endif; ?>
<div class="card">
    <div class="card-body">
        <?php if (\Core\Auth::can('manage_security_settings')): ?>
        <form method="post" action="/settings/security/update" id="securityForm">
            <?= \Core\Csrf::field() ?>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="enable_email_2fa" value="1" class="form-check-input" id="enable2fa"
                        <?= $config->enable_email_2fa ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable2fa">Enable email 2FA</label>
                </div>
                <small class="text-muted">When enabled, users receive a one-time code via email during login.</small>
            </div>
            <div class="mb-3" id="expirationWrap">
                <label class="form-label" for="2fa_expiration">2FA expiration (minutes)</label>
                <input type="number" name="2fa_expiration_minutes" id="2fa_expiration" class="form-control"
                    value="<?= (int)$config->{'2fa_expiration_minutes'} ?>" min="1" max="1440" placeholder="15"
                    <?= !$config->enable_email_2fa ? 'disabled' : '' ?>>
                <small class="text-muted">How long the 2FA code remains valid (1–1440 minutes).</small>
            </div>
            <div class="mb-3">
                <label class="form-label" for="user_logout_after">User logout after (minutes)</label>
                <input type="number" name="user_logout_after_minutes" id="user_logout_after" class="form-control"
                    value="<?= (int)$config->user_logout_after_minutes ?>" min="0" max="10080" placeholder="30">
                <small class="text-muted">Automatically log out users after this many minutes of inactivity. Use 0 to disable.</small>
            </div>
            <button type="submit" class="btn btn-primary">Save Security Settings</button>
        </form>
        <script>
        $(function(){
            $('#enable2fa').on('change', function(){
                $('#2fa_expiration').prop('disabled', !$(this).is(':checked'));
            });
        });
        </script>
        <?php else: ?>
        <div class="alert alert-info">You do not have permission to manage security settings.</div>
        <dl class="row mb-0">
            <dt class="col-sm-4">Enable email 2FA</dt>
            <dd class="col-sm-8"><?= $config->enable_email_2fa ? 'Yes' : 'No' ?></dd>
            <dt class="col-sm-4">2FA expiration (minutes)</dt>
            <dd class="col-sm-8"><?= $config->enable_email_2fa ? (int)$config->{'2fa_expiration_minutes'} : '-' ?></dd>
            <dt class="col-sm-4">User logout after (minutes)</dt>
            <dd class="col-sm-8"><?= (int)$config->user_logout_after_minutes ? (int)$config->user_logout_after_minutes : 'Disabled' ?></dd>
        </dl>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Security Settings';
$currentPage = 'security-settings';
require __DIR__ . '/../layout/main.php';
?>
