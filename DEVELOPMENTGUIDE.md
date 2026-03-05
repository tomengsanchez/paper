# PAPeR – Development Guide

This document describes the project structure, frameworks, database schema, and important conventions for developers.

---

## 1. Project structure

```
paper/
├── App/
│   ├── Controllers/       # HTTP controllers (Controller@action)
│   │   └── Api/           # API controllers (e.g. ApiController, GrievanceController, StructureController)
│   ├── Models/            # Domain models (Profile, Structure, Grievance, Project, etc.)
│   ├── Views/             # PHP view templates by module (profile/, structure/, grievance/, etc.)
│   │   ├── layout/        # main.php (master layout: sidebar or top nav)
│   │   └── partials/      # list_pagination.php, list_toolbar.php, history_sidebar.php
│   ├── Capabilities.php   # Central registry of role capabilities and menu visibility
│   ├── UserUiSettings.php # Per-user UI (theme, sidebar vs top layout) – stored in user_dashboard_config
│   ├── UserNotificationSettings.php # Per-user notification preferences – user_dashboard_config
│   ├── NotificationService.php # Create/fetch notifications; list history with filters
│   ├── AuditLog.php       # Generic entity history (created/updated/status_changed)
│   ├── UserProjects.php    # Resolve allowed project IDs for current user
│   ├── DashboardConfig.php # Grievance dashboard widget config
│   ├── ListHelper.php     # List column helpers
│   └── ...
├── Core/                  # Framework core
│   ├── Router.php         # Route registration and dispatch (Controller@action)
│   ├── Controller.php     # Base controller (view, redirect, requireAuth, requireCapability, validateCsrf)
│   ├── Auth.php           # Session auth, capabilities, login/logout
│   ├── Database.php       # PDO singleton
│   ├── Csrf.php           # CSRF token generation/validation
│   ├── MigrationRunner.php # Runs database/migration_*.php
│   ├── Logger.php         # Error logging
│   ├── Mailer.php         # SMTP mail
│   └── LoginThrottle.php  # Login attempt throttling
├── config/
│   ├── database.php       # DB credentials (copy from database-sample.php)
│   └── app.php            # Optional base_url (copy from app-sample.php)
├── database/
│   ├── migration_*.php    # Schema migrations (run via cli/migrate.php)
│   ├── seed_grievance_options.php  # Seed grievance lookup tables
│   ├── seed_profiles_structures.php
│   └── seed_grievances.php
├── cli/
│   ├── migrate.php        # Run migrations or php cli/migrate.php --status
│   ├── truncate_seed_tables.php  # Truncate notifications, audit_log, structures, profiles, projects
│   └── truncate_grievances.php  # Truncate grievance_status_log, grievances
├── public/                # Web root (DocumentRoot should point here)
│   └── index.php          # Front controller – defines all routes
├── logs/                  # php_error.log, database_error.log, auth.log
├── bootstrap.php          # Autoload, ROOT, DB init, Auth init, BASE_URL
└── index.php              # For doc root = project root: forwards to public/index.php
```

---

## 2. Frameworks and stack

- **Backend:** Custom PHP MVC (no framework). PHP 8.0+.
- **Database:** MySQL 5.7+ / MariaDB 10.2+; PDO. Use UTF-8 (utf8mb4). Avoid MySQL-only features if targeting MariaDB (see config/database-sample.php note).
- **Frontend:** Bootstrap 5.3.2, jQuery 3.7.1, Select2. Layout and theme in `App/Views/layout/main.php`.
- **Routing:** `Core\Router`: routes in `public/index.php`; pattern `Controller@action`; path params like `{id}`.
- **Auth:** Session-based; `Core\Auth` (check, user, id, can, login, logout). Roles: Administrator, Standard User, Coordinator. Capabilities in `role_capabilities`; admin bypasses checks.
- **CSRF:** `Core\Csrf::validate()`; call `$this->validateCsrf()` at start of any POST action.

---

## 3. Routing and controllers

- Routes are registered in `public/index.php` with `$router->get(...)` and `$router->post(...)`.
- Handler format: `'ControllerName@methodName'`. Controller class: `App\Controllers\ControllerName`.
- Path parameters: e.g. `/profile/view/{id}` → `ProfileController::show(int $id)`.
- Controllers extend `Core\Controller`; use `$this->view('module/viewname', $data)`, `$this->redirect()`, `$this->requireAuth()`, `$this->requireCapability('capability_name')`, `$this->validateCsrf()`.

---

## 4. Database structure

### 4.1 Core tables (migration_000)

- **roles** – id, name (e.g. Administrator, Standard User, Coordinator)
- **users** – id, username, email, password_hash, role_id, display_name (migration_015), created_at, updated_at
- **app_settings** – setting_key, setting_value, updated_at
- **role_capabilities** – id, role_id, capability (e.g. view_profiles, add_profiles); UNIQUE(role_id, capability)
- **migrations** – id, name, ran_at (used by MigrationRunner)

### 4.2 Flat entity tables (migration_001 and later)

- **projects** – id, name, description, coordinator_id, created_at, updated_at
- **profiles** – id, papsid, control_number, full_name, age, contact_number, project_id, plus many profile-specific fields (migration_003), created_at, updated_at
- **structures** – id, strid, owner_id (→ profiles.id), structure_tag, description, tagging_images, structure_images, other_details, created_at, updated_at
- **user_profiles** – legacy per-user profile (single row per user); still present. display_name moved to users (migration_015).
- **user_projects** – user_id, project_id (junction: user linked to many projects); migration_015

### 4.3 Grievance module

- **grievances** – migration_005, 009 (project_id), 012 (attachments), etc.
- **grievance_status_log** – migration_008, 014
- Lookup tables: grievance_vulnerabilities, grievance_respondent_types, grievance_grm_channels, grievance_preferred_languages, grievance_types, grievance_categories, grievance_progress_levels (migrations 005–011, etc.)

### 4.4 User preferences and notifications

- **user_dashboard_config** – user_id, module, config (JSON), updated_at. Modules: `ui` (UserUiSettings), `notification_preferences` (UserNotificationSettings), grievance dashboard widgets, list columns (migration_004), etc.
- **notifications** – id, user_id, type, related_type, related_id, project_id (migration_019), message, created_at, clicked_at (migration_019). Used for in-app notifications; bell shows unread (clicked_at IS NULL); history page shows all with filters.
- **email_queue** – id, to_email, subject, body, created_at, sent_at, status (pending/sent/failed), error_message (migration_021). Notification emails are enqueued here; sent in background by `php cli/send_queued_emails.php` (run via cron every 1–5 min).

### 4.5 Audit and history

- **audit_log** – id, entity_type, entity_id, action (e.g. created, updated, status_changed), changes (JSON), created_at, created_by. Used for Activity History on profile/structure/grievance view pages (partials/history_sidebar.php).

---

## 5. Migrations

- Format: PHP file returning `['name' => 'migration_XXX_description', 'up' => function (\PDO $db): void { ... }, 'down' => function (\PDO $db): void { ... }]`.
- Run: `php cli/migrate.php`. Status: `php cli/migrate.php --status`.
- Migrations run in filename order; applied names stored in `migrations` table.
- List (as of this guide): 000 (initial), 001 (EAV→flat), 002–014 (scalability, profile fields, list columns, grievance, status, progress levels, dashboard config, grievance attachments, indexes, etc.), 015 (display_name, user_projects), 016 (notifications), 017 (notification defaults for all users), 018 (audit_log), 019 (notifications project_id, clicked_at), 020 (api_tokens), 021 (email_queue).

---

## 6. Important conventions

- **Capabilities:** Defined in `App\Capabilities`. Menu visibility and controller access use `Auth::can('capability_name')`. Add new entities/capabilities there and seed role_capabilities if needed.
- **User projects:** `App\UserProjects::allowedProjectIds()` – null = all projects (e.g. admin), empty array = none, else list of project IDs. Used to restrict profile/structure/grievance/list and notification project filter.
- **Notifications:** `App\NotificationService` – notifyNewProfile, notifyProfileUpdated, notifyNewGrievance, notifyGrievanceStatusChange, notifyNewStructure. Preferences in UserNotificationSettings; stored in user_dashboard_config. When "Send email for project notifications" is on (Email settings), emails are queued in email_queue and sent by `php cli/send_queued_emails.php` (schedule via cron so save/update responses stay fast).
- **Audit log:** `App\AuditLog::record($entityType, $entityId, $action, $changes)`. Used in Profile, Structure, Grievance controllers for create/update/status. View via `AuditLog::for($entityType, $entityId)` and `partials/history_sidebar.php`.
- **Views:** Layout in `App/Views/layout/main.php`; `$currentPage` and `$pageTitle` for nav/title; `$content` for main body (views often use ob_start() and then require main.php).
- **List pages:** Use list_pagination.php and list_toolbar.php; pass listPagination, listBaseUrl, listExtraParams for filters (e.g. notifications page).
- **Truncate scripts:** `truncate_seed_tables.php` truncates notifications, audit_log, structures, profiles, projects (with FOREIGN_KEY_CHECKS). `truncate_grievances.php` truncates grievance_status_log, grievances. After truncate, re-seed as needed; migrations and user/preference data remain.

---

## 7. Configuration notes

- **config/database-sample.php:** Copy to `config/database.php`; set host, dbname, username, password, charset. Comment there: production may use MariaDB – keep SQL compatible.
- **config/app-sample.php:** Optional; copy to `config/app.php` and set `base_url` for subfolder installs.
- **Document root:** Prefer pointing the web server at `public/`. If document root is project root, root `index.php` should forward to `public/index.php` and rewrite rules must be correct (e.g. `.htaccess` in public).

---

## 8. Default login

- **Username:** admin  
- **Password:** admin123  

Change after first login in production.
