<?php
$module = $module ?? 'general';

function help_module_title(string $module): string {
    switch ($module) {
        case 'dashboard': return 'Dashboard';
        case 'profile': return 'Profile';
        case 'structure': return 'Structure';
        case 'grievance': return 'Grievance';
        case 'grievance-dashboard': return 'Grievance Dashboard';
        case 'library': return 'Library';
        case 'settings': return 'Settings';
        case 'user-management': return 'User Management';
        case 'notifications': return 'Notifications';
        case 'audit-trail': return 'Audit Trail';
        case 'debug-log': return 'Debug Log';
        case 'development': return 'Development';
        case 'account': return 'My Profile / Account';
        default: return 'PAPeR (Overall)';
    }
}

$pageTitle = 'Help - ' . help_module_title($module);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Help: <?= htmlspecialchars(help_module_title($module)) ?></h2>
        <?php if ($module !== 'general'): ?>
            <p class="text-muted small mb-0">Context-sensitive help based on the page where you clicked Help.</p>
        <?php else: ?>
            <p class="text-muted small mb-0">Overall help for PAPeR.</p>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($module !== 'general'): ?>
            <a href="/help" class="btn btn-outline-secondary btn-sm">View overall help</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($module === 'profile' || $module === 'account'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-2">
                The Profile / Account module shows your user information (username, display name, email, role)
                and the projects that are linked to your account.
            </p>
            <p class="mb-0 text-muted">
                Use this page to review your access and, when allowed, jump to the Users module to edit your details.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Open the account menu (top-right) and click <strong>My Profile</strong>.</li>
                <li>Review your username, display name, email, and role.</li>
                <li>Check the list of linked projects to see which projects your access is scoped to.</li>
                <li>If you need changes and you have permission, click <strong>Edit</strong> to go to the Users module; otherwise contact an administrator.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Header</strong> – Shows the page title and an Edit button (when you have <code>edit_users</code> capability).</li>
                <li><strong>User details</strong> – Username, display name, email, and role.</li>
                <li><strong>Linked projects</strong> – List of projects your account is associated with, or <em>None</em> if not yet linked.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>Why don’t I see an Edit button?</strong> – Your role does not include permission to edit users. Contact an administrator.</li>
                <li><strong>Why are my linked projects empty?</strong> – Your account has not been linked to any project yet, or links were removed during cleanup.</li>
                <li><strong>Why is my email required?</strong> – Email is used for notifications and features like 2FA.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'structure'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Structure module manages physical structures associated with profiles (e.g., houses, establishments)
                and their related images.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Use the list to search or filter existing structures.</li>
                <li>Click <strong>Create</strong> to add a new structure, filling in required details.</li>
                <li>Attach images and link the structure to the correct profile when needed.</li>
                <li>Use <strong>Edit</strong> to correct or update structure information.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Filters / search</strong> – Narrow down the list of structures.</li>
                <li><strong>Table of structures</strong> – Shows key fields like ID, profile, location, and status.</li>
                <li><strong>Actions</strong> – View, edit, or delete a structure (based on your permissions).</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>I can’t edit a structure.</strong> – Your role might be read-only for this module.</li>
                <li><strong>Images are not loading.</strong> – Check that uploads are allowed and that the file type is supported (image or PDF where applicable).</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'grievance-dashboard'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the dashboard</h5>
            <p class="mb-0">
                The Grievance Dashboard shows filtered totals, breakdowns, and charts for grievances. All widgets
                respect the date range and project filters at the top, as well as your allowed projects.
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Filters</h5>
            <ul class="mb-0">
                <li><strong>Date From / To</strong> – Limits all cards, tables, and charts to grievances whose <em>date recorded</em> falls within the selected range.</li>
                <li><strong>Project</strong> – Limits data to a single project. For non-admin users, only projects you are linked to are available; other project IDs are ignored.</li>
                <li><strong>User project scope</strong> – Even with “All projects”, non-admins only see grievances for projects linked to their account.</li>
            </ul>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">How the widgets and charts are calculated</h5>
            <ul class="mb-0">
                <li>
                    <strong>Total Grievances</strong> – Count of all grievances in the filtered set (project + date range + your allowed projects).
                </li>
                <li>
                    <strong>By Status</strong> – Counts how many grievances are <em>Open</em>, <em>In Progress</em>, and <em>Closed</em>
                    in the filtered set. The “Status distribution” doughnut chart uses the same numbers.
                </li>
                <li>
                    <strong>This Month vs Last Month</strong> –
                    Uses the month part of <em>date recorded</em> to count how many grievances were recorded in
                    the current month and the previous month, then shows the percentage change.
                </li>
                <li>
                    <strong>Grievances over time</strong> –
                    Groups grievances by month (based on <em>date recorded</em>) to build a trend of counts per month.
                    If you set a date range, only months inside that range are shown; otherwise it shows the last 12 months.
                </li>
                <li>
                    <strong>By project (chart and table)</strong> –
                    Groups grievances by project and counts how many belong to each project in the filtered set.
                    “No project” rows represent grievances not linked to a project.
                </li>
                <li>
                    <strong>By Category / By Type</strong> –
                    Uses the grievance’s stored categories and types (JSON arrays) and counts how many grievances
                    include each category or type, within the current project and date filters.
                </li>
                <li>
                    <strong>In progress by stage (chart and list)</strong> –
                    Counts grievances with status <em>In Progress</em>, grouped by their current progress level (stage).
                </li>
                <li>
                    <strong>Needs escalation / closure</strong> –
                    For each in-progress stage that has a configured <em>days to address</em>, compares how many days
                    have passed since the grievance entered that stage. If the limit is exceeded, it is counted under
                    “needs escalation” (for intermediate stages) or “needs to close” (for the final stage).
                </li>
                <li>
                    <strong>Recent grievances</strong> –
                    Shows the 10 most recently recorded grievances in the filtered set, including status, date,
                    and a quick link to the detail page.
                </li>
            </ul>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Customization</h5>
            <ul class="mb-0">
                <li>
                    <strong>Customize dashboard</strong> – Opens a dialog where you can choose which widgets (cards,
                    tables, charts) appear on your grievance dashboard.
                </li>
                <li>
                    <strong>Trend chart type</strong> – Inside the customization dialog, you can change the
                    “Grievances over time” chart between a bar chart and a line chart.
                </li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>Why is the dashboard empty?</strong> – There may be no grievances that match your date range and project filters, or your account has no linked projects.</li>
                <li><strong>Why do counts not match the Grievance list?</strong> – Make sure you are using the same date range and project filters on both the dashboard and the list.</li>
                <li><strong>Why do I not see a specific project in the filter?</strong> – You may not be linked to that project; ask an administrator to link your account if needed.</li>
                <li><strong>What does “needs escalation / needs to close” mean?</strong> – The grievance has stayed longer in that stage than the configured number of days to address.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'grievance'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Grievance module records, tracks, and manages grievances including their status, categories, and history.
                It includes a dashboard, list view, detailed view, and an options library.
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>
                    From the navigation, open <strong>Grievance</strong> and choose either
                    <em>Dashboard</em> or <em>Grievances</em>.
                </li>
                <li>
                    On the <strong>Grievances</strong> list, use filters (status, project, date, etc.) to find existing grievances,
                    or click <strong>Create</strong> to log a new grievance.
                </li>
                <li>
                    When creating a grievance, fill in complainant details, choose the correct category, channel, and
                    preferred language, and describe the issue clearly.
                </li>
                <li>
                    Attach any relevant files (e.g., documents, images) if the form allows it.
                </li>
                <li>
                    As work progresses, open the grievance detail page to:
                    update status, add notes or history entries, and record escalations when required.
                </li>
                <li>
                    Administrators or users with configuration rights can open the
                    <strong>Options Library</strong> to maintain lookup values such as vulnerabilities,
                    respondent types, GRM channels, preferred languages, grievance types, categories,
                    and in-progress stages.
                </li>
                <li>
                    Use the <strong>Dashboard</strong> to monitor overall grievance trends and workload
                    (by status, category, etc.).
                </li>
            </ol>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li>
                    <strong>Dashboard</strong> – High-level counts and charts for grievances, showing open/in-progress/closed
                    and other key metrics.
                </li>
                <li>
                    <strong>Grievance list</strong> – Searchable, filterable table of grievances with quick access
                    to view or edit, depending on your permissions.
                </li>
                <li>
                    <strong>Grievance detail</strong> – Full information for a single grievance, including history and
                    attachments, where you can change status and add updates.
                </li>
                <li>
                    <strong>Options Library</strong> – Configuration screens for lookup data:
                    vulnerabilities, respondent types, GRM channels, preferred languages, grievance types,
                    categories, and in-progress stages.
                </li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li>
                    <strong>I can’t see the Options Library.</strong> –
                    You need the <code>manage_grievance_options</code> capability to configure lookup values.
                </li>
                <li>
                    <strong>Why can’t I delete a grievance?</strong> –
                    Deletion may be restricted for audit reasons. Instead, close the grievance with the appropriate status.
                </li>
                <li>
                    <strong>Why is the dashboard empty?</strong> –
                    There may be no grievances matching your linked projects or you may lack permission to view them.
                </li>
                <li>
                    <strong>Why can’t I change the status?</strong> –
                    Your role might be read-only for this stage, or the grievance may already be closed.
                </li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'library'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Library module manages project records, which are then linked to profiles, grievances, and users.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Open <strong>Library</strong> &rarr; <em>Project</em> from the navigation.</li>
                <li>Use filters or search to find an existing project.</li>
                <li>Click <strong>Create</strong> to add a new project and fill in the required details.</li>
                <li>Use <strong>Edit</strong> to update project information when names or statuses change.</li>
                <li>Confirm that projects are correctly linked to profiles and users where applicable.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Filters and search</strong> – Narrow down projects by keywords or other fields.</li>
                <li><strong>Project list</strong> – Shows project identifiers, names, and statuses.</li>
                <li><strong>Actions</strong> – View, edit, or delete (if allowed) individual projects.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>Why do I see no projects?</strong> – You may not have permission to view projects or none have been created yet.</li>
                <li><strong>Can I rename a project?</strong> – Yes, if you have edit rights; use the Edit action on the project list.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'settings'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Settings module controls application-level configuration such as general settings, email (SMTP),
                security options, and UI preferences.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Open <strong>Settings</strong> from the main navigation.</li>
                <li>Review the available sections (General, Email, Security, UI) depending on your permissions.</li>
                <li>Change values carefully and click <strong>Save</strong> on each section you modify.</li>
                <li>Use the <strong>Test email</strong> function in Email settings to verify SMTP configuration.</li>
                <li>After changes, confirm that logins, notifications, and security features still work as expected.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>General</strong> – Application name, base URL, and other global options.</li>
                <li><strong>Email (SMTP)</strong> – SMTP host, port, encryption, and credentials; includes a test mail action.</li>
                <li><strong>Security</strong> – Password policies, 2FA, and other hardening options.</li>
                <li><strong>UI / Notifications</strong> – User interface preferences and notification defaults.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>My changes did not apply.</strong> – Ensure you clicked Save on the correct section and have the required capability.</li>
                <li><strong>Test email fails.</strong> – Double-check SMTP credentials, server, and firewall rules from the PAPeR server.</li>
                <li><strong>I can’t see Security settings.</strong> – Only users with security-related capabilities can view or edit that section.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'user-management'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The User Management module manages user accounts, roles, and capabilities that control what each user
                can see and do in PAPeR.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Open <strong>User Management</strong> and choose either <em>Users</em> or <em>User Roles &amp; Capabilities</em>.</li>
                <li>In <strong>Users</strong>, search for an existing user or click <strong>Create</strong> to add a new account.</li>
                <li>Assign an appropriate role and, if needed, link the user to specific projects.</li>
                <li>In <strong>User Roles &amp; Capabilities</strong>, adjust which modules and actions each role is allowed to access.</li>
                <li>Save changes and confirm that users can access the intended modules (and not more than necessary).</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Users</strong> – List and manage individual user accounts (login, name, email, role, status).</li>
                <li><strong>User Roles &amp; Capabilities</strong> – Configure which actions and modules each role can use.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>Why can’t I delete my own account?</strong> – For safety, users cannot delete themselves.</li>
                <li><strong>A user cannot access a module.</strong> – Check their role, capabilities, and linked projects.</li>
                <li><strong>Why am I seeing too many options?</strong> – Your role might be too powerful; consider splitting roles by responsibility.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'notifications'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Notifications page and the bell icon help you see recent events that require your attention,
                such as new grievances, profile updates, or system messages.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Click the bell icon in the header or top navigation to open recent notifications.</li>
                <li>Click on an item in the dropdown to navigate directly to the related record.</li>
                <li>To see all notifications, use the <strong>View all notifications</strong> link or open the <strong>Notifications</strong> page from the navigation.</li>
                <li>Use filters on the Notifications page (if available) to focus on unread or specific types of notifications.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Bell icon</strong> – Shows a badge when there are new notifications.</li>
                <li><strong>Dropdown list</strong> – Quick view of the most recent items with one-click navigation.</li>
                <li><strong>Notifications page</strong> – Full list of notifications, often with filters and pagination.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>The badge never clears.</strong> – Some items may be considered “unread” until you open the related record.</li>
                <li><strong>I don’t get notifications for some actions.</strong> – Those events may not be configured to generate notifications or you may lack permission for the related module.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'audit-trail'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Audit Trail shows a history of important actions taken in PAPeR, helping with accountability,
                troubleshooting, and compliance.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Open <strong>System</strong> &rarr; <strong>Audit Trail</strong> from the navigation.</li>
                <li>Use filters (date range, user, action, module) to focus on the records you need.</li>
                <li>Click on specific entries when a detail view is available (e.g., to see field-level changes).</li>
                <li>Export or copy relevant rows if you need to share them for investigation or reporting.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Filters</strong> – Narrow the audit log to a particular period, user, or module.</li>
                <li><strong>Audit table</strong> – Lists who did what, when, and (often) where in the system.</li>
                <li><strong>Details</strong> – Depending on implementation, may show before/after values.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>Why is an action missing?</strong> – Not all operations may be logged; check system configuration and version notes.</li>
                <li><strong>Can I delete audit entries?</strong> – Typically, deleting audit logs is restricted to preserve history; consult your administrator.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'debug-log'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Debug Log shows technical error and debug messages useful for developers and administrators
                to diagnose issues.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Open <strong>System</strong> &rarr; <strong>Debug Log</strong> from the navigation.</li>
                <li>Filter or search logs (if available) by date, level, or keyword related to the error.</li>
                <li>Review stack traces and messages to identify misconfiguration, code errors, or external issues (e.g., SMTP failures).</li>
                <li>After fixing an issue, clear or rotate the log if appropriate and supported.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Log entries</strong> – Timestamped records including severity, message, and sometimes stack trace.</li>
                <li><strong>Filters/search</strong> – Quickly narrow down to relevant log lines.</li>
                <li><strong>Maintenance actions</strong> – Options to clear or download logs if implemented.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>I see repeated errors.</strong> – An underlying configuration or code issue is likely still present; address the root cause instead of just clearing the log.</li>
                <li><strong>Some errors mention external services.</strong> – Check connectivity and credentials for databases, SMTP, or other integrations.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'development'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Development page is meant for administrators and developers to configure development-time options,
                such as simulated dates and performance diagnostics.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Open <strong>System</strong> &rarr; <strong>Development</strong> from the navigation.</li>
                <li>Review current development settings, such as simulated time and status checks.</li>
                <li>Adjust settings only in coordination with your technical team, then save.</li>
                <li>Verify in the footer/system status that performance and simulated date behave as expected.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Simulated time</strong> – Allows testing how the app behaves on a different date/time.</li>
                <li><strong>Status checks</strong> – Controls whether system status information is displayed.</li>
                <li><strong>Other dev toggles</strong> – Additional switches used during development or troubleshooting.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>Users see “Simulated” in the header.</strong> – The app date is being simulated for testing; turn off simulated time after use.</li>
                <li><strong>Why don’t I see Development?</strong> – Access is typically limited to administrators or developers.</li>
            </ul>
        </div>
    </div>

<?php elseif ($module === 'dashboard'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of the module</h5>
            <p class="mb-0">
                The Dashboard gives a summary of activity across Profiles, Structures, Grievances, and Users,
                limited to the projects linked to your account.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Review each section (Profile, Structure, Grievance, Users) for recent counts and trends.</li>
                <li>Hover on charts where supported to see exact values.</li>
                <li>Use the <strong>View All</strong> buttons to jump into the detailed module screens.</li>
                <li>If a section shows “No data”, confirm your project links and permissions.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Profile section</strong> – New and updated profiles, plus structures added to profiles.</li>
                <li><strong>Structure section</strong> – Structures created, updated, and images added.</li>
                <li><strong>Grievance section</strong> – Counts by status and activity, with charts.</li>
                <li><strong>Users section</strong> – Active users per role.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>Why do I see “No data”?</strong> – You may not have permission to that module or no activity exists yet for your linked projects.</li>
                <li><strong>Numbers look wrong.</strong> – Confirm the date range and that your projects are correctly linked to the underlying records.</li>
            </ul>
        </div>
    </div>

<?php else: ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Overview of PAPeR</h5>
            <p class="mb-0">
                PAPeR is used to manage project-affected profiles and their grievances, including structures,
                projects, users, notifications, and system configuration.
            </p>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Steps</h5>
            <ol class="mb-0">
                <li>Start at the Dashboard to get an overview of your data.</li>
                <li>Use the navigation to go into Profiles, Structure, Grievance, Library, or Settings as needed.</li>
                <li>Use your account menu for My Profile, Notifications, Help, and Logout.</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2">Parts</h5>
            <ul class="mb-0">
                <li><strong>Main navigation</strong> – Access all modules in the system.</li>
                <li><strong>Account menu</strong> – My Profile, Notifications, Help, Logout.</li>
                <li><strong>Notifications</strong> – Quick view of recent events relevant to your account.</li>
            </ul>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">FAQs</h5>
            <ul class="mb-0">
                <li><strong>I don’t see some menu items.</strong> – Menus are filtered by your role and capabilities.</li>
                <li><strong>My data looks incomplete.</strong> – Check that your account is linked to the correct projects.</li>
                <li><strong>I see an error message.</strong> – Capture a screenshot or the exact text and share it with your administrator.</li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h5 class="mb-2">Overall help for PAPeR</h5>
        <p class="mb-2">
            Regardless of which module you are using, the following tips apply across the system:
        </p>
        <ul class="mb-2">
            <li><strong>Permissions</strong> – What you can see and do depends on your role and capabilities.</li>
            <li><strong>Projects</strong> – Most data is scoped to the projects linked to your account.</li>
            <li><strong>Audit trail</strong> – Many actions are recorded for accountability; avoid deleting data unless necessary.</li>
        </ul>
        <p class="mb-1">
            If you still need help, contact your system administrator or PAPeR focal person.
        </p>
        <p class="mb-0 text-muted">
            Include what you were doing, the exact time, the module, and any error messages so they can assist faster.
        </p>
    </div>
</div>
                              ,,,,,,,,,,,
<?php
$content = ob_get_clean();
$currentPage = '';
require __DIR__ . '/../layout/main.php';

