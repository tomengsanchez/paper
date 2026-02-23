<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Email Settings (SMTP)</h2>
</div>
<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">Email settings saved successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['test']) && $_GET['test'] === 'success'): ?>
<div class="alert alert-success">Test email sent successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['test']) && $_GET['test'] === 'error'): ?>
<div class="alert alert-danger">Test email failed: <?= htmlspecialchars($_GET['msg'] ?? 'Unknown error') ?></div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
<div class="alert alert-warning">Please enter a valid email address for testing.</div>
<?php endif; ?>
<div class="card">
    <div class="card-body">
        <?php if (\Core\Auth::can('manage_email_settings')): ?>
        <form method="post" action="/settings/email/update">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($config->smtp_host) ?>" placeholder="smtp.example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-control" value="<?= (int)$config->smtp_port ?>" placeholder="587" min="1" max="65535">
                        <small class="text-muted">Common: 587 (TLS), 465 (SSL), 25</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Encryption</label>
                        <select name="smtp_encryption" class="form-select">
                            <option value="" <?= $config->smtp_encryption === '' ? 'selected' : '' ?>>None</option>
                            <option value="tls" <?= $config->smtp_encryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $config->smtp_encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars($config->smtp_username) ?>" placeholder="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-control" value="" placeholder="••••••••" autocomplete="new-password">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($config->from_email) ?>" placeholder="noreply@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-control" value="<?= htmlspecialchars($config->from_name) ?>" placeholder="PAPS System">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Email Settings</button>
        </form>
        <hr class="my-4">
        <h5 class="mb-3">Test Email</h5>
        <form method="post" action="/settings/email/test" class="d-flex gap-2 align-items-end flex-wrap">
            <div class="flex-grow-1" style="min-width: 200px;">
                <label class="form-label">Send test email to</label>
                <input type="email" name="test_email" class="form-control" placeholder="test@example.com" required>
            </div>
            <button type="submit" class="btn btn-outline-secondary">Send Test Mail</button>
        </form>
        <?php else: ?>
        <div class="alert alert-info">You do not have permission to manage email settings.</div>
        <dl class="row mb-0">
            <dt class="col-sm-3">SMTP Host</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($config->smtp_host ?: '-') ?></dd>
            <dt class="col-sm-3">SMTP Port</dt>
            <dd class="col-sm-9"><?= (int)$config->smtp_port ?: '-' ?></dd>
            <dt class="col-sm-3">Encryption</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($config->smtp_encryption ?: 'None') ?></dd>
        </dl>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Email Settings';
$currentPage = 'email-settings';
require __DIR__ . '/../layout/main.php';
?>
