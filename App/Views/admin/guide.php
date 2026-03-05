<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Administrator Guide</h2>
        <p class="text-muted small mb-0">
            This page summarizes how to operate, configure, and maintain PAPeR from an administrator perspective.
        </p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">1. System foundation and overview</h5>
        <p class="mb-2">
            PAPeR (<em>Project Affected Profiles and Redress</em>) is a web application for managing project-affected
            people (PAPs), their structures, and grievances, with full audit history and role-based access control.
        </p>
        <ul class="mb-2">
            <li><strong>Intended audience</strong> – System administrators, safeguards coordinators with Administrator role,
                and IT staff responsible for hosting and maintenance.</li>
            <li><strong>Key responsibilities</strong> – Managing users and roles, configuring projects and system settings,
                monitoring grievances and audit trails, and coordinating with IT on backups and upgrades.</li>
        </ul>
        <p class="mb-2"><strong>Architecture (high-level)</strong></p>
        <ul class="mb-2">
            <li><strong>Backend</strong> – Custom PHP 8+ MVC framework (no Laravel/CodeIgniter), running on a web server
                such as Apache or Nginx with PHP-FPM.</li>
            <li><strong>Database</strong> – MySQL/MariaDB accessed via PDO. Schema is managed through PHP migration
                scripts under <code>database/migration_*.php</code>.</li>
            <li><strong>Frontend</strong> – Server-rendered PHP views with Bootstrap 5, jQuery, and Select2.</li>
            <li><strong>Entry point</strong> – All requests enter through <code>public/index.php</code>, which registers routes
                and dispatches them via <code>Core\Router</code> to controllers under <code>App\Controllers\...</code>.</li>
        </ul>
        <p class="mb-2"><strong>Environment and deployment (typical)</strong></p>
        <ul class="mb-2">
            <li><strong>Web server</strong> – Apache or Nginx pointing the document root to the <code>public/</code> folder.</li>
            <li><strong>PHP</strong> – Version 8.0 or later with PDO/MySQL enabled.</li>
            <li><strong>Database</strong> – MySQL 5.7+ or MariaDB 10.2+ with UTF-8 (utf8mb4) encoding.</li>
            <li><strong>Configuration files</strong> – <code>config/database.php</code> for DB credentials and
                <code>config/app.php</code> for optional base URL when running under a subfolder.</li>
            <li><strong>Logs</strong> – Application and error logs are written under the <code>logs/</code> directory
                (e.g. <code>php_error.log</code>, <code>database_error.log</code>, <code>auth.log</code>).</li>
        </ul>
        <p class="mb-2"><strong>Glossary</strong></p>
        <ul class="mb-0">
            <li><strong>PAP</strong> – Project Affected Person/People whose profile and structures are managed in the system.</li>
            <li><strong>GRM / Grievance</strong> – Grievance Redress Mechanism; complaints managed through the Grievance module.</li>
            <li><strong>Coordinator</strong> – Role typically responsible for monitoring project-level data and grievance follow-up.</li>
            <li><strong>Administrator</strong> – Role with full access to configuration, system settings, and audit trail.</li>
            <li><strong>Capability</strong> – A named permission (e.g. <code>view_profiles</code>, <code>manage_grievance_options</code>)
                attached to a role via the <code>role_capabilities</code> table.</li>
            <li><strong>Project</strong> – A program or investment under which PAPs, structures, and grievances are recorded.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">2. Access and identity management</h5>
        <p class="mb-2">
            Access is controlled through roles and capabilities. Administrators are responsible for creating users,
            assigning roles, and linking users to projects.
        </p>
        <ul class="mb-2">
            <li><strong>User accounts</strong> – Use the <strong>User Management &rarr; Users</strong> module to create,
                edit, deactivate, or delete user accounts.</li>
            <li><strong>Roles and capabilities</strong> – Use <strong>User Management &rarr; User Roles &amp; Capabilities</strong>
                to review which capabilities each role has (e.g. who can edit grievances or change global settings).</li>
            <li><strong>Project links</strong> – Link users to projects so that dashboards, lists, and grievances are
                correctly scoped to the projects they work on.</li>
        </ul>
        <p class="mb-2"><strong>Role-based access control (RBAC)</strong></p>
        <ul class="mb-2">
            <li><strong>Administrator</strong> – Full access to all modules, including <strong>System</strong> (General,
                Audit Trail, Debug Log, Development), Settings, and User Management.</li>
            <li><strong>Coordinator</strong> – Typically allowed to view and manage Profiles, Structures, Grievances,
                and use dashboards for monitoring, but without access to low-level system configuration.</li>
            <li><strong>Standard User</strong> – Limited access to the specific data and tasks assigned by capabilities
                (for example, view-only access to certain modules).</li>
        </ul>
        <p class="mb-2"><strong>Authentication and security</strong></p>
        <ul class="mb-0">
            <li><strong>Login and sessions</strong> – Users authenticate with username and password. Automatic logout
                after a period of inactivity is controlled via application settings.</li>
            <li><strong>Password policy</strong> – Administrators should enforce strong passwords when creating accounts
                and update any default credentials immediately in production.</li>
            <li><strong>Two-factor authentication (2FA)</strong> – When enabled through security settings, web logins
                can require a second factor (for example via email).</li>
            <li><strong>API access</strong> – Some integrations use API tokens issued via the REST API authentication
                endpoints; treat these tokens as sensitive credentials.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">3. Core configuration and operations</h5>
        <p class="mb-2">
            Day-to-day configuration is done through the <strong>Settings</strong>, <strong>System</strong>, and
            <strong>Library</strong> modules.
        </p>
        <ul class="mb-2">
            <li><strong>General settings</strong> – Under <strong>System &rarr; General</strong>, set the application
                region and timezone to match the deployment country.</li>
            <li><strong>Email (SMTP)</strong> – Under <strong>Settings &rarr; Email</strong>, configure SMTP so that
                system emails (notifications, password-related messages) are delivered reliably. If you enable
                &ldquo;Send email for project notifications&rdquo;, schedule <code>php cli/send_queued_emails.php</code>
                (e.g. every 2–5 minutes via cron or Task Scheduler) so queued emails are sent in the background and
                save/update pages stay fast.</li>
            <li><strong>Security settings</strong> – Under <strong>Settings &rarr; Security</strong>, review password
                and login-related options, including optional 2FA.</li>
            <li><strong>Projects library</strong> – Under <strong>Library &rarr; Project</strong>, create and maintain
                project records that profiles, structures, grievances, and users are linked to.</li>
            <li><strong>Grievance options</strong> – Under <strong>Grievance &rarr; Options Library</strong>, configure
                vulnerabilities, respondent types, channels, languages, types, categories, and in-progress stages.</li>
        </ul>
        <p class="mb-2"><strong>Operational notes</strong></p>
        <ul class="mb-0">
            <li><strong>Configuration changes</strong> – After changing any system-wide setting (General, Email,
                Security), verify that logins, dashboards, and notifications still behave as expected.</li>
            <li><strong>Environment changes</strong> – Coordinate server-level changes (PHP version, database host,
                SSL certificates) with IT and test in a staging environment before applying to production.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">4. Maintenance and resilience</h5>
        <p class="mb-2">
            Regular maintenance reduces downtime and protects against data loss. Work closely with your IT team to
            establish and follow a routine.
        </p>
        <ul class="mb-2">
            <li><strong>Database backups</strong> – Schedule automated backups (for example daily) of the PAPeR database
                using <code>mysqldump</code> or your organization’s backup tools. Test restoring backups to a separate
                environment at regular intervals.</li>
            <li><strong>File backups</strong> – Include uploaded files (for example under <code>public/uploads/</code>)
                and the <code>logs/</code> directory in the backup plan when required by policy.</li>
            <li><strong>Application updates</strong> – When deploying a new version, run the PHP migrations via
                <code>php cli/migrate.php</code> to align the database schema, then smoke-test key flows
                (login, profile/structure/grievance creation, dashboards).</li>
            <li><strong>Monitoring</strong> – Periodically review the Debug Log and web server/PHP logs for repeated
                errors or performance issues. Use the optional Development status footer (when enabled) to check
                query counts and response times during testing.</li>
        </ul>
        <p class="mb-2"><strong>Failure and recovery scenarios</strong></p>
        <ul class="mb-0">
            <li><strong>Database failure</strong> – Restore the latest known-good database backup and verify access
                before returning users to the system.</li>
            <li><strong>File system issues</strong> – If uploads or logs are lost or corrupted, restore from backup
                where available and verify that new uploads succeed.</li>
            <li><strong>Configuration mistakes</strong> – If a settings change causes errors (for example, incorrect SMTP),
                revert to a previous known-good configuration or adjust the setting and retest.</li>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">5. Support and troubleshooting</h5>
        <p class="mb-2">
            Administrators are the first line of support for end users. Use the built-in tools to investigate issues
            before escalating to technical support.
        </p>
        <ul class="mb-2">
            <li><strong>Common issues</strong></li>
        </ul>
        <ul class="mb-2">
            <li><strong>Can’t log in</strong> – Check if the user account is active, confirm the correct username,
                and review the Debug Log for authentication-related errors. Consider password reset or user unlock
                procedures as per your organization’s policy.</li>
            <li><strong>Missing menu items</strong> – Verify the user’s role and capabilities in User Management and
                ensure they are linked to the correct projects.</li>
            <li><strong>Dashboard shows “No data”</strong> – Confirm that the user is linked to projects with data,
                that filters (date range, project) are correct, and that the underlying records exist.</li>
            <li><strong>Slow performance</strong> – Use the Development status footer in a test environment to inspect
                query counts and load time, and review server resource usage with IT.</li>
        </ul>
        <ul class="mb-2">
            <li><strong>Investigation tools</strong></li>
        </ul>
        <ul class="mb-2">
            <li><strong>Audit Trail</strong> – Use <strong>System &rarr; Audit Trail</strong> to see who changed which
                records and when, especially for sensitive entities like grievances and users.</li>
            <li><strong>Debug Log</strong> – Use <strong>System &rarr; Debug Log</strong> to inspect technical errors
                and warnings. Share relevant excerpts with technical support when escalating.</li>
            <li><strong>Notifications</strong> – If users report missing notifications, verify notification preferences,
                linked projects, and SMTP connectivity.</li>
        </ul>
        <p class="mb-2"><strong>When to escalate</strong></p>
        <ul class="mb-0">
            <li><strong>Repeated system errors</strong> – If the same error appears frequently in the Debug Log or
                users report widespread issues, escalate to the technical support or development team with timestamps,
                screenshots, and log snippets.</li>
            <li><strong>Data integrity concerns</strong> – If records appear to be missing or inconsistent, pause any
                bulk operations, capture evidence (audit logs, screenshots), and involve your database administrator.</li>
            <li><strong>Security incidents</strong> – For suspected unauthorized access or data leaks, follow your
                organization’s incident response plan immediately.</li>
        </ul>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Administrator Guide';
$currentPage = 'admin-guide';
require __DIR__ . '/../layout/main.php';

