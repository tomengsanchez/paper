# PAPeR - Project Affected Profiles and Redress

Custom PHP MVC framework for PAPeR.

A lightweight PHP MVC framework with flat database tables, jQuery, and MySQL. Version 1.

**Requirements:** PHP 8.0+, MySQL 5.7+ (or MariaDB 10.2+), Apache with mod_rewrite (or nginx equivalent).

## Setup

### 1. Configuration

Copy the sample config and edit with your MySQL credentials:

```bash
cp config/database-sample.php config/database.php
```

Edit `config/database.php`:
- host, dbname, username, password

Optional (for subfolder installs): `cp config/app-sample.php config/app.php` and set `base_url` (e.g. `/paper`).

### 2. Database

Create a MySQL database:

```sql
CREATE DATABASE paper_db2;
```

(Use the same name as in `config/database.php`.)

### 3. Run Migrations

From the project root:

```bash
php cli/migrate.php
```

This creates all tables (roles, users, app_settings, role_capabilities, projects, profiles, structures, user_profiles) and runs migration_003 for profile fields. Use `php cli/migrate.php --status` to see migration status.

### 4. Seed Grievance Options (Recommended)

Seed commonly used default data for the Grievance module (vulnerabilities, respondent types, GRM channels, preferred languages, grievance types, categories):

```bash
php database/seed_grievance_options.php
```

Safe to re-run: skips tables that already have data.

**Option A - Document root = `public/`** (recommended)
- Point your web server document root to the `public/` folder
- Apache: `DocumentRoot /path/to/paper/public`

**Option B - Document root = project root**
- Ensure `index.php` in root forwards to `public/index.php`
- Apache: Enable mod_rewrite and ensure `.htaccess` is in `public/` (or adjust RewriteBase)

### 6. Default Login

- **Username:** admin  
- **Password:** admin123

## Structure

```
paper/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── config/
├── core/          # Framework (Router, Database, Auth, etc.)
├── cli/           # migrate.php
├── database/      # migrations (migration_*.php)
├── public/        # Web root, index.php
└── bootstrap.php
```

## Features

- **Roles:** Administrator, Standard User, Coordinator
- **Menu Links:** Profile, Structure, Grievance, Library, Settings
- **Profile:** PAPSID, Control Number, Full Name, Age, Contact Number, Project; Relevant Information (residing in project affected, structure owners); Additional Information (own property elsewhere, availed gov't housing, HH Income); attachments (images + PDF)
- **Structure:** CRUD with tagging/structure images, linked to profiles
- **Library:** Projects with Coordinator dropdown (searchable)
- **Settings:** Email (SMTP), Security (2FA, session timeout)
- **User Management:** Admin-only CRUD for users, roles, user profiles


Database refactor: EAV → flat tables
1. Migration files
database/migration_000_initial.php – Creates base tables (roles, users, app_settings, role_capabilities) and seeds admin user and capabilities.
database/migration_001_from_eav_to_flat.php – Creates flat tables (projects, profiles, structures, user_profiles) with created_at/updated_at, migrates existing EAV data, and drops EAV tables.
Migration format for future use:
return [    'name' => 'migration_XXX_description',    'up' => function (\PDO $db): void { /* ... */ },    'down' => function (\PDO $db): void { /* optional rollback */ },];
2. Migration runner
core/MigrationRunner.php – Loads migration_*.php files and runs pending migrations.
cli/migrate.php – CLI for migrations:
php cli/migrate.php – Run pending migrations
php cli/migrate.php --status – List migration status
3. Flat table models
Profile, Structure, Project, UserProfile – Refactored to use flat tables instead of EAV.
Pagination now uses direct queries on flat tables instead of joins on eav_entities/eav_values.
4. ApiController
projects() and profiles() updated to query flat tables instead of EAV.
5. SQL files removed
beforeseed.sql, schema.sql, add_app_settings.sql, add_user_email.sql, fix_admin_password.sql, seed_administrator_capabilities.sql
6. README
Installation steps updated to use migrations instead of importing SQL.
New installation flow
1. Create the database.
2. Run php cli/migrate.php.
3. (Recommended) Run php database/seed_grievance_options.php for commonly used grievance options (vulnerabilities, respondent types, GRM channels, etc.).
4. Optionally run php database/seed_profiles_structures.php for sample profiles/structures.

testing dev to main