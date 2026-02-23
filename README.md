# PAPS - Custom PHP MVC Framework

A lightweight PHP MVC framework with flat database tables, jQuery, and MySQL.

## Setup

### 1. Database

Create a MySQL database:

```sql
CREATE DATABASE paper_db2;
```

### 2. Run Migrations

From the project root:

```bash
php cli/migrate.php
```

This creates all tables (roles, users, app_settings, role_capabilities, projects, profiles, structures, user_profiles). Use `php cli/migrate.php --status` to see migration status.

### 3. Configuration

Edit `config/database.php` with your MySQL credentials:
- host, dbname, username, password

### 4. Web Server

**Option A - Document root = `public/`** (recommended)
- Point your web server document root to the `public/` folder
- Apache: `DocumentRoot /path/to/paper/public`

**Option B - Document root = project root**
- Ensure `index.php` in root forwards to `public/index.php`
- Apache: Enable mod_rewrite and ensure `.htaccess` is in `public/` (or adjust RewriteBase)

### 5. Default Login

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
- **5 Menu Links:** Profile, Structure, Grievance, Library, Settings
- **User Management:** Admin-only CRUD
- **Library CRUD:** Projects with Coordinator dropdown (searchable via jQuery Select2)
- **Profile CRUD:** PAPSID (auto: PAPS-YEARMONTH0000000001), Control Number, Age, Contact Number, Project (searchable)


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
Create the database.
Run php cli/migrate.php.
Optionally run php database/seed_profiles_structures.php for sample data.