<?php
$ui = \App\UserUiSettings::get();
$uiTheme = $ui['theme'] ?? \App\UserUiSettings::THEME_DEFAULT;
$uiLayout = $ui['layout'] ?? \App\UserUiSettings::LAYOUT_SIDEBAR;
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(\Core\Csrf::token()) ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'PAPeR - Project Affected Profiles and Redress') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        :root { --sidebar-width: 240px; --header-height: 56px; font-size: 13px;
            --nav-bg: #1e293b; --nav-border: #334155; --nav-text: #94a3b8; --nav-text-hover: #f8fafc; --nav-active-bg: #334155; --nav-sub-bg: #0f172a; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f5f6fa; }
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-width); background: var(--nav-bg); color: var(--nav-text); z-index: 1000; }
        .sidebar .brand { padding: 1rem 1.25rem; font-weight: 700; color: var(--nav-text-hover); border-bottom: 1px solid var(--nav-border); }
        .sidebar nav a { display: block; padding: 0.75rem 1.25rem; color: var(--nav-text); text-decoration: none; transition: all 0.2s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: var(--nav-active-bg); color: var(--nav-text-hover); }
        .sidebar .nav-parent { padding: 0.75rem 1.25rem; color: var(--nav-text); cursor: pointer; transition: all 0.2s; user-select: none; }
        .sidebar .nav-parent:hover, .sidebar .nav-parent.open { background: var(--nav-active-bg); color: var(--nav-text-hover); }
        .sidebar .nav-sub { display: none; background: var(--nav-sub-bg); }
        .sidebar .nav-parent.open + .nav-sub { display: block; }
        .sidebar .nav-sub a { padding: 0.6rem 1.25rem 0.6rem 2.5rem; font-size: 0.9em; }
        .sidebar .nav-sub .sidebar-nav-label { display: block; padding: 0.5rem 1.25rem 0.25rem 2.5rem; font-size: 0.75em; color: var(--nav-text); text-transform: uppercase; letter-spacing: 0.05em; }
        .sidebar .nav-sub .nav-sub a { padding-left: 3.5rem; font-size: 0.85em; }
        .main-wrap { margin-left: var(--sidebar-width); min-height: 100vh; }
        .main-wrap.ui-layout-top { margin-left: 0; padding-top: var(--header-height); }
        .header { height: var(--header-height); background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; }
        .content { padding: 1.5rem; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
        /* Top menu */
        .topnav { position: fixed; top: 0; left: 0; right: 0; height: var(--header-height); background: var(--nav-bg); color: var(--nav-text); z-index: 1000; display: flex; align-items: center; padding: 0 1rem; gap: 0.5rem; border-bottom: 1px solid var(--nav-border); }
        .topnav .brand { font-weight: 700; color: var(--nav-text-hover); margin-right: 1rem; text-decoration: none; }
        .topnav .nav-link { color: var(--nav-text); text-decoration: none; padding: 0.5rem 0.75rem; border-radius: 4px; }
        .topnav .nav-link:hover, .topnav .nav-link.active { background: var(--nav-active-bg); color: var(--nav-text-hover); }
        .topnav .dropdown-menu { background: var(--nav-sub-bg); border: 1px solid var(--nav-border); }
        .topnav .dropdown-item { color: var(--nav-text); }
        .topnav .dropdown-item:hover { background: var(--nav-active-bg); color: var(--nav-text-hover); }
        .topnav .dropdown-item.active { background: var(--nav-active-bg); color: var(--nav-text-hover); }
        .topnav .nav-spacer { flex: 1; }
        .user-dropdown .dropdown-toggle { color: var(--nav-text); text-decoration: none; padding: 0.5rem 0.75rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.25rem; }
        .user-dropdown .dropdown-toggle:hover { background: var(--nav-active-bg); color: var(--nav-text-hover); }
        .user-dropdown .dropdown-menu { min-width: 160px; }
        .header-user-dropdown .dropdown-toggle { color: #6b7280; text-decoration: none; padding: 0.35rem 0.75rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.25rem; }
        .header-user-dropdown .dropdown-toggle:hover { background: #f3f4f6; color: #374151; }
        /* Notification icon */
        .notification-dropdown .dropdown-toggle { color: var(--nav-text); text-decoration: none; padding: 0.5rem; border-radius: 4px; display: inline-flex; align-items: center; position: relative; }
        .notification-dropdown .dropdown-toggle:hover { background: var(--nav-active-bg); color: var(--nav-text-hover); }
        .topnav .notification-dropdown .dropdown-toggle { color: var(--nav-text); }
        .topnav .notification-dropdown .dropdown-toggle:hover { color: var(--nav-text-hover); }
        .header .notification-dropdown .dropdown-toggle { color: #6b7280; }
        .header .notification-dropdown .dropdown-toggle:hover { background: #f3f4f6; color: #374151; }
        .notification-dropdown .dropdown-menu { min-width: 280px; max-height: 360px; overflow-y: auto; }
        .notification-dropdown .dropdown-header { font-size: 0.8rem; font-weight: 600; }
        .notification-badge { position: absolute; top: 4px; right: 3px; min-width: 16px; padding: 0 4px; font-size: 10px; line-height: 16px; border-radius: 999px; background: #ef4444; color: #f9fafb; text-align: center; display: none; }
        /* Theme overrides */
        body.ui-theme-green { --nav-bg: #064e3b; --nav-border: #047857; --nav-active-bg: #047857; --nav-sub-bg: #022c22; }
        body.ui-theme-violet { --nav-bg: #4c1d95; --nav-border: #6d28d9; --nav-active-bg: #6d28d9; --nav-sub-bg: #2e1065; }
        body.ui-theme-amber { --nav-bg: #78350f; --nav-border: #b45309; --nav-active-bg: #b45309; --nav-sub-bg: #451a03; }
        body.ui-theme-slate { --nav-bg: #334155; --nav-border: #475569; --nav-active-bg: #475569; --nav-sub-bg: #1e293b; }
    </style>
</head>
<body class="ui-theme-<?= htmlspecialchars($uiTheme) ?> ui-layout-<?= htmlspecialchars($uiLayout) ?>">
<?php if ($uiLayout === 'top'): ?>
    <nav class="topnav">
        <a href="/" class="brand">PAPeR</a>
        <?php if (\Core\Auth::can('view_profiles')): ?>
        <a href="/profile" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">Profile</a>
        <?php endif; ?>
        <?php if (\Core\Auth::can('view_structure')): ?>
        <a href="/structure" class="nav-link <?= $currentPage === 'structure' ? 'active' : '' ?>">Structure</a>
        <?php endif; ?>
        <?php if (\Core\Auth::can('view_grievance') || \Core\Auth::can('manage_grievance_options')): ?>
        <?php $grievanceActive = in_array($currentPage, ['grievance', 'grievance-dashboard', 'grievance-list', 'grievance-vulnerabilities', 'grievance-respondent-types', 'grievance-grm-channels', 'grievance-preferred-languages', 'grievance-types', 'grievance-categories', 'grievance-progress-levels']); ?>
        <div class="dropdown">
            <a href="#" class="nav-link dropdown-toggle <?= $grievanceActive ? 'active' : '' ?>" data-bs-toggle="dropdown">Grievance</a>
            <ul class="dropdown-menu">
                <?php if (\Core\Auth::can('view_grievance')): ?>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-dashboard' ? 'active' : '' ?>" href="/grievance">Dashboard</a></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-list' ? 'active' : '' ?>" href="/grievance/list">Grievances</a></li>
                <?php endif; ?>
                <?php if (\Core\Auth::can('manage_grievance_options')): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-vulnerabilities' ? 'active' : '' ?>" href="/grievance/options/vulnerabilities">Vulnerabilities</a></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-respondent-types' ? 'active' : '' ?>" href="/grievance/options/respondent-types">Respondent Type</a></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-grm-channels' ? 'active' : '' ?>" href="/grievance/options/grm-channels">GRM Channel</a></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-preferred-languages' ? 'active' : '' ?>" href="/grievance/options/preferred-languages">Preferred Language</a></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-types' ? 'active' : '' ?>" href="/grievance/options/types">Type of Grievances</a></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-categories' ? 'active' : '' ?>" href="/grievance/options/categories">Category of Grievance</a></li>
                <li><a class="dropdown-item <?= $currentPage === 'grievance-progress-levels' ? 'active' : '' ?>" href="/grievance/options/progress-levels">In Progress Stages</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if (\Core\Auth::can('view_projects')): ?>
        <div class="dropdown">
            <a href="#" class="nav-link dropdown-toggle <?= $currentPage === 'library' ? 'active' : '' ?>" data-bs-toggle="dropdown">Library</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item <?= $currentPage === 'library' ? 'active' : '' ?>" href="/library">Project</a></li>
            </ul>
        </div>
        <?php endif; ?>
        <?php if (\Core\Auth::can('view_settings') || \Core\Auth::can('view_email_settings') || \Core\Auth::can('view_security_settings')): ?>
        <?php $settingsActive = in_array($currentPage, ['settings', 'email-settings', 'security-settings']); ?>
        <div class="dropdown">
            <a href="#" class="nav-link dropdown-toggle <?= $settingsActive ? 'active' : '' ?>" data-bs-toggle="dropdown">Settings</a>
            <ul class="dropdown-menu">
                <?php if (\Core\Auth::can('view_settings')): ?>
                <li><a class="dropdown-item <?= $currentPage === 'settings' ? 'active' : '' ?>" href="/settings">General</a></li>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_email_settings')): ?>
                <li><a class="dropdown-item <?= $currentPage === 'email-settings' ? 'active' : '' ?>" href="/settings/email">Email Settings</a></li>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_security_settings')): ?>
                <li><a class="dropdown-item <?= $currentPage === 'security-settings' ? 'active' : '' ?>" href="/settings/security">Security</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if (\Core\Auth::can('view_users') || \Core\Auth::can('view_roles')): ?>
        <?php $umActive = in_array($currentPage, ['users', 'user-roles']); ?>
        <div class="dropdown">
            <a href="#" class="nav-link dropdown-toggle <?= $umActive ? 'active' : '' ?>" data-bs-toggle="dropdown">User Management</a>
            <ul class="dropdown-menu">
                <?php if (\Core\Auth::can('view_users')): ?>
                <li><a class="dropdown-item <?= $currentPage === 'users' ? 'active' : '' ?>" href="/users">Users</a></li>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_roles')): ?>
                <li><a class="dropdown-item <?= $currentPage === 'user-roles' ? 'active' : '' ?>" href="/users/roles">User Roles &amp; Capabilities</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        <span class="nav-spacer"></span>
        <div class="dropdown notification-dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/></svg>
                <span class="notification-badge" id="notification-count">0</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" id="notification-dropdown-menu">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li id="notification-list"><span class="dropdown-item text-muted text-center py-3">Loading...</span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center small" href="/notifications">View all notifications</a></li>
            </ul>
        </div>
        <div class="dropdown user-dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <?= htmlspecialchars(\Core\Auth::user()->display_name ?: \Core\Auth::user()->username ?? '') ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/account">My Profile</a></li>
                <li><a class="dropdown-item" href="/notifications">Notifications</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/logout">Logout</a></li>
            </ul>
        </div>
    </nav>
<?php else: ?>
    <aside class="sidebar">
        <div class="brand">PAPeR</div>
        <nav class="py-2">
            <?php if (\Core\Auth::can('view_profiles')): ?>
            <a href="/profile" class="<?= $currentPage === 'profile' ? 'active' : '' ?>">Profile</a>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_structure')): ?>
            <a href="/structure" class="<?= $currentPage === 'structure' ? 'active' : '' ?>">Structure</a>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_grievance') || \Core\Auth::can('manage_grievance_options')): ?>
            <?php $grievanceActive = in_array($currentPage, ['grievance', 'grievance-dashboard', 'grievance-list', 'grievance-vulnerabilities', 'grievance-respondent-types', 'grievance-grm-channels', 'grievance-preferred-languages', 'grievance-types', 'grievance-categories', 'grievance-progress-levels']); ?>
            <div class="nav-parent <?= $grievanceActive ? 'open' : '' ?>">Grievance</div>
            <div class="nav-sub">
                <?php if (\Core\Auth::can('view_grievance')): ?>
                <a href="/grievance" class="<?= $currentPage === 'grievance-dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="/grievance/list" class="<?= $currentPage === 'grievance-list' ? 'active' : '' ?>">Grievances</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('manage_grievance_options')): ?>
                <span class="sidebar-nav-label">Options Library</span>
                <a href="/grievance/options/vulnerabilities" class="<?= $currentPage === 'grievance-vulnerabilities' ? 'active' : '' ?>">Vulnerabilities</a>
                <a href="/grievance/options/respondent-types" class="<?= $currentPage === 'grievance-respondent-types' ? 'active' : '' ?>">Respondent Type</a>
                <a href="/grievance/options/grm-channels" class="<?= $currentPage === 'grievance-grm-channels' ? 'active' : '' ?>">GRM Channel</a>
                <a href="/grievance/options/preferred-languages" class="<?= $currentPage === 'grievance-preferred-languages' ? 'active' : '' ?>">Preferred Language</a>
                <a href="/grievance/options/types" class="<?= $currentPage === 'grievance-types' ? 'active' : '' ?>">Type of Grievances</a>
                <a href="/grievance/options/categories" class="<?= $currentPage === 'grievance-categories' ? 'active' : '' ?>">Category of Grievance</a>
                <a href="/grievance/options/progress-levels" class="<?= $currentPage === 'grievance-progress-levels' ? 'active' : '' ?>">In Progress Stages</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_projects')): ?>
            <div class="nav-parent <?= $currentPage === 'library' ? 'open' : '' ?>">Library</div>
            <div class="nav-sub">
                <a href="/library" class="<?= $currentPage === 'library' ? 'active' : '' ?>">Project</a>
            </div>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_settings') || \Core\Auth::can('view_email_settings') || \Core\Auth::can('view_security_settings')): ?>
            <?php $settingsActive = in_array($currentPage, ['settings', 'email-settings', 'security-settings']); ?>
            <div class="nav-parent <?= $settingsActive ? 'open' : '' ?>">Settings</div>
            <div class="nav-sub">
                <?php if (\Core\Auth::can('view_settings')): ?>
                <a href="/settings" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">General</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_email_settings')): ?>
                <a href="/settings/email" class="<?= $currentPage === 'email-settings' ? 'active' : '' ?>">Email Settings</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_security_settings')): ?>
                <a href="/settings/security" class="<?= $currentPage === 'security-settings' ? 'active' : '' ?>">Security</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (\Core\Auth::can('view_users') || \Core\Auth::can('view_roles')): ?>
            <?php $umActive = in_array($currentPage, ['users', 'user-roles']); ?>
            <div class="nav-parent <?= $umActive ? 'open' : '' ?>">User Management</div>
            <div class="nav-sub">
                <?php if (\Core\Auth::can('view_users')): ?>
                <a href="/users" class="<?= $currentPage === 'users' ? 'active' : '' ?>">Users</a>
                <?php endif; ?>
                <?php if (\Core\Auth::can('view_roles')): ?>
                <a href="/users/roles" class="<?= $currentPage === 'user-roles' ? 'active' : '' ?>">User Roles &amp; Capabilities</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </nav>
    </aside>
<?php endif; ?>
    <div class="main-wrap <?= $uiLayout === 'top' ? 'ui-layout-top' : '' ?>">
        <?php if ($uiLayout !== 'top'): ?>
        <header class="header">
            <span class="text-muted"></span>
            <div class="d-flex align-items-center gap-1">
                <div class="dropdown notification-dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/></svg>
                        <span class="notification-badge" id="notification-count">0</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" id="notification-dropdown-menu">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notification-list"><span class="dropdown-item text-muted text-center py-3">Loading...</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="/notifications">View all notifications</a></li>
                    </ul>
                </div>
                <div class="dropdown header-user-dropdown">
                <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= htmlspecialchars(\Core\Auth::user()->display_name ?: \Core\Auth::user()->username ?? '') ?>
                    <small class="text-muted">(<?= htmlspecialchars(\Core\Auth::user()->role_name ?? '') ?>)</small>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/account">My Profile</a></li>
                    <li><a class="dropdown-item" href="/notifications">Notifications</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/logout">Logout</a></li>
                </ul>
            </div>
            </div>
        </header>
        <?php endif; ?>
        <main class="content">
            <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
            <div class="alert alert-warning alert-dismissible fade show">Invalid or expired request. Please try again. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(function(){
        $('.nav-parent').on('click', function(){ $(this).toggleClass('open'); });

        function updateNotificationBadge(count) {
            var $badge = $('#notification-count');
            if (!$badge.length) return;
            if (count > 0) {
                $badge.text(count > 99 ? '99+' : count);
                $badge.show();
            } else {
                $badge.hide();
            }
        }

        function loadNotificationsList() {
            var $list = $('#notification-list');
            $list.html('<span class="dropdown-item text-muted text-center py-2">Loading...</span>');
            $.getJSON('/api/notifications').done(function(data){
                if (!data || data.length === 0) {
                    $list.html('<span class="dropdown-item text-muted text-center py-3">No new notifications</span>');
                    updateNotificationBadge(0);
                    return;
                }
                var html = '';
                data.forEach(function(n){
                    var url = n.url || '/notifications/click/' + n.id;
                    html += '<a class="dropdown-item py-2 d-block" href="' + url + '">' + (n.message || 'Notification') + '<br><small class="text-muted">' + (n.created_at || '') + '</small></a>';
                });
                $list.html(html);
                updateNotificationBadge(data.length);
            }).fail(function(){
                $list.html('<span class="dropdown-item text-muted text-center py-3">Unable to load notifications</span>');
            });
        }

        function pollNotificationCount() {
            $.getJSON('/api/notifications').done(function(data){
                updateNotificationBadge(data && data.length ? data.length : 0);
            });
        }

        $('.notification-dropdown').on('show.bs.dropdown', function(){
            loadNotificationsList();
        });

        // Initial poll + interval for near-realtime updates
        pollNotificationCount();
        setInterval(pollNotificationCount, 15000);
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
