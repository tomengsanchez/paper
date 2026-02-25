# PAPS - Custom PHP MVC Framework

A lightweight PHP MVC framework with flat database tables, jQuery, and MySQL. Version 1.

**Pwedeng gamitin bilang scaffold/template** sa kahit anong sistema (parang Laravel)—may Router, MVC, migrations, at instructions. See **[docs/FRAMEWORK.md](docs/FRAMEWORK.md)** for the full framework guide (Tagalog + English).

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

This creates base tables (roles, users, app_settings, role_capabilities), user_profiles (and related), user_list_columns, user_dashboard_config, and the example `items` table. Use `php cli/migrate.php --status` to see migration status.

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
project/
├── App/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── config/
├── Core/          # Framework (Router, Database, Auth, etc.)
├── routes/        # web.php — all routes defined here
├── cli/           # migrate.php
├── database/      # migrations (migration_*.php)
├── public/        # Web root, index.php
├── docs/          # Framework docs (FRAMEWORK.md, GETTING_STARTED, etc.)
├── stubs/         # Copy-paste templates for new modules
└── bootstrap.php
```

**Documentation:** [docs/FRAMEWORK.md](docs/FRAMEWORK.md) (main), [docs/GETTING_STARTED.md](docs/GETTING_STARTED.md), [docs/STRUCTURE.md](docs/STRUCTURE.md), [docs/ROUTING.md](docs/ROUTING.md), [docs/CREATING_MODULES.md](docs/CREATING_MODULES.md). **Stubs:** [stubs/README.md](stubs/README.md).

## Features (scaffold)

- **Auth:** Login, logout, 2FA (optional), session timeout
- **Roles:** Administrator, Standard User, Coordinator (capabilities for users, roles, settings, items)
- **User Management:** Users CRUD, Roles & capabilities, User profiles
- **Settings:** General, Email (SMTP), Security (2FA, session timeout)
- **Example module:** Items — sample CRUD (see `App/Controllers/ItemController`, `App/Models/Item`, `App/Views/item/`, `docs/CREATING_MODULES.md`)

Migrations: `migration_000_initial`, `migration_001_from_eav_to_flat`, `migration_004_user_list_columns`, `migration_011_user_dashboard_config`, `migration_012_example_items`.