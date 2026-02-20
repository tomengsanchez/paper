<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PAPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { max-width: 380px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,.5); }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body p-4">
            <h4 class="mb-4 text-center">PAPS Login</h4>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === '2fa_expired'): ?>
            <div class="alert alert-warning">Verification session expired. Please log in again.</div>
            <?php endif; ?>
            <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-info">You were logged out due to inactivity. Please sign in again.</div>
            <?php endif; ?>
            <form method="post" action="/login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
