<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification - PAPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { max-width: 380px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,.5); }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body p-4">
            <h4 class="mb-4 text-center">Two-Factor Verification</h4>
            <p class="text-muted text-center small mb-3">Enter the 6-digit code sent to your email.</p>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
            <div class="alert alert-warning">Invalid or expired request. Please try again.</div>
            <?php endif; ?>
            <form method="post" action="/login/2fa/verify">
                <?= \Core\Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label">Verification Code</label>
                    <input type="text" name="code" class="form-control text-center" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus
                        style="letter-spacing: 0.5em; font-size: 1.25rem;">
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify</button>
            </form>
            <p class="text-center mt-3 mb-0">
                <a href="/login" class="text-muted small">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>
