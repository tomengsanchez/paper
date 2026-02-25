<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(\Core\Csrf::token()) ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'PAPS') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        :root { --sidebar-width: 240px; --header-height: 56px; font-size: 13px; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f5f6fa; }
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-width); background: #1e293b; color: #94a3b8; z-index: 1000; }
        .sidebar .brand { padding: 1rem 1.25rem; font-weight: 700; color: #f8fafc; border-bottom: 1px solid #334155; }
        .sidebar nav a { display: block; padding: 0.75rem 1.25rem; color: #94a3b8; text-decoration: none; transition: all 0.2s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: #334155; color: #f8fafc; }
        .sidebar .nav-parent { padding: 0.75rem 1.25rem; color: #94a3b8; cursor: pointer; transition: all 0.2s; user-select: none; }
        .sidebar .nav-parent:hover, .sidebar .nav-parent.open { background: #334155; color: #f8fafc; }
        .sidebar .nav-sub { display: none; background: #0f172a; }
        .sidebar .nav-parent.open + .nav-sub { display: block; }
        .sidebar .nav-sub a { padding: 0.6rem 1.25rem 0.6rem 2.5rem; font-size: 0.9em; }
        .sidebar .nav-sub .sidebar-nav-label { display: block; padding: 0.5rem 1.25rem 0.25rem 2.5rem; font-size: 0.75em; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
        .sidebar .nav-sub .nav-sub a { padding-left: 3.5rem; font-size: 0.85em; }
        .main-wrap { margin-left: var(--sidebar-width); min-height: 100vh; }
        .header { height: var(--header-height); background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; }
        .content { padding: 1.5rem; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="brand">PAPS</div>
        <nav class="py-2">
            <?php if (\Core\Auth::can('view_profiles')): ?>
            <a href="/profile" class="<?= ($currentPage ?? '') === 'profile' ? 'active' : '' ?>">Profile</a>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_structure')): ?>
            <a href="/structure" class="<?= ($currentPage ?? '') === 'structure' ? 'active' : '' ?>">Structure</a>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_grievance') || \Core\Auth::can('manage_grievance_options')): ?>
            <?php $grievanceActive = in_array($currentPage ?? '', ['grievance', 'grievance-dashboard', 'grievance-list', 'grievance-vulnerabilities', 'grievance-respondent-types', 'grievance-grm-channels', 'grievance-preferred-languages', 'grievance-types', 'grievance-categories', 'grievance-progress-levels']); ?>
            <div class="nav-parent <?= $grievanceActive ? 'open' : '' ?>">Grievance</div>
            <div class="nav-sub">
                <?php if (\Core\Auth::can('view_grievance')): ?>
                <a href="/grievance" class="<?= ($currentPage ?? '') === 'grievance-dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="/grievance/list" class="<?= ($currentPage ?? '') === 'grievance-list' ? 'active' : '' ?>">Grievances</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('manage_grievance_options')): ?>
                <span class="sidebar-nav-label">Options Library</span>
                <a href="/grievance/options/vulnerabilities" class="<?= ($currentPage ?? '') === 'grievance-vulnerabilities' ? 'active' : '' ?>">Vulnerabilities</a>
                <a href="/grievance/options/respondent-types" class="<?= ($currentPage ?? '') === 'grievance-respondent-types' ? 'active' : '' ?>">Respondent Type</a>
                <a href="/grievance/options/grm-channels" class="<?= ($currentPage ?? '') === 'grievance-grm-channels' ? 'active' : '' ?>">GRM Channel</a>
                <a href="/grievance/options/preferred-languages" class="<?= ($currentPage ?? '') === 'grievance-preferred-languages' ? 'active' : '' ?>">Preferred Language</a>
                <a href="/grievance/options/types" class="<?= ($currentPage ?? '') === 'grievance-types' ? 'active' : '' ?>">Type of Grievances</a>
                <a href="/grievance/options/categories" class="<?= ($currentPage ?? '') === 'grievance-categories' ? 'active' : '' ?>">Category of Grievance</a>
                <a href="/grievance/options/progress-levels" class="<?= ($currentPage ?? '') === 'grievance-progress-levels' ? 'active' : '' ?>">In Progress Stages</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_projects')): ?>
            <div class="nav-parent <?= ($currentPage ?? '') === 'library' ? 'open' : '' ?>">Library</div>
            <div class="nav-sub">
                <a href="/library" class="<?= ($currentPage ?? '') === 'library' ? 'active' : '' ?>">Project</a>
            </div>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_settings') || \Core\Auth::can('view_email_settings') || \Core\Auth::can('view_security_settings')): ?>
            <?php $settingsActive = in_array($currentPage ?? '', ['settings', 'email-settings', 'security-settings']); ?>
            <div class="nav-parent <?= $settingsActive ? 'open' : '' ?>">Settings</div>
            <div class="nav-sub">
                <?php if (\Core\Auth::can('view_settings')): ?>
                <a href="/settings" class="<?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">General</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_email_settings')): ?>
                <a href="/settings/email" class="<?= ($currentPage ?? '') === 'email-settings' ? 'active' : '' ?>">Email Settings</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_security_settings')): ?>
                <a href="/settings/security" class="<?= ($currentPage ?? '') === 'security-settings' ? 'active' : '' ?>">Security</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_user_profiles') || \Core\Auth::can('view_users') || \Core\Auth::can('view_roles')): ?>
            <?php $umActive = in_array($currentPage ?? '', ['user-profiles', 'users', 'user-roles']); ?>
            <div class="nav-parent <?= $umActive ? 'open' : '' ?>">User Management</div>
            <div class="nav-sub">
                <?php if (\Core\Auth::can('view_user_profiles')): ?>
                <a href="/users/profiles" class="<?= ($currentPage ?? '') === 'user-profiles' ? 'active' : '' ?>">User Profile</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_users')): ?>
                <a href="/users" class="<?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>">Users</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_roles')): ?>
                <a href="/users/roles" class="<?= ($currentPage ?? '') === 'user-roles' ? 'active' : '' ?>">User Roles &amp; Capabilities</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="main-wrap">
        <header class="header">
            <span class="text-muted"><?= htmlspecialchars(\Core\Auth::user()->username ?? '') ?> (<?= htmlspecialchars(\Core\Auth::user()->role_name ?? '') ?>)</span>
            <a href="/logout" class="btn btn-outline-secondary btn-sm">Logout</a>
        </header>
        <main class="content">
            <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
            <div class="alert alert-warning alert-dismissible fade show">Invalid or expired request. Please try again. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(function(){ $('.nav-parent').on('click', function(){ $(this).toggleClass('open'); }); });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
