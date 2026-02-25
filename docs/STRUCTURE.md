# Project Structure — Istruktura ng Project

Papaliwanag ng bawat folder at importanteng file.

---

## Root

| File / Folder   | Purpose |
|-----------------|--------|
| **bootstrap.php** | Autoload (PSR-4 style by path), `ROOT` constant, log dir, `Logger::init()`, `Database::init()`, `Auth::init()`, `BASE_URL` from config. Dapat i-require muna bago lahat. |
| **public/**     | Web-accessible. Dito ang **document root**. |
| **App/**        | Application code (Controllers, Models, Views). |
| **Core/**       | Framework core. Pwedeng i-reuse sa ibang project. |
| **config/**     | database.php, app.php. Huwag i-commit ang may password kung public repo. |
| **routes/**     | Route definitions. `web.php` ang ginagamit ng `public/index.php`. |
| **database/**   | Migration files `migration_*.php`. |
| **cli/**        | CLI scripts (e.g. `migrate.php`). |
| **logs/**       | Log files. Auto-created. Dapat naka-.gitignore kung ayaw i-commit logs. |
| **docs/**       | Documentation. |

---

## public/

| File           | Purpose |
|----------------|--------|
| **index.php**  | Entry point. Requires bootstrap, creates Router, requires `routes/web.php` (which calls `$router->dispatch()`). |
| **.htaccess** | Rewrite: lahat ng request (except existing files/dirs) papuntang `index.php`. |
| **uploads/**   | User uploads (e.g. attachments). Dapat writable. |

---

## App/

| Folder         | Purpose |
|----------------|--------|
| **Controllers/** | One class per “resource” or feature. Extend `Core\Controller`. Method name = action (e.g. `index`, `show`, `store`). |
| **Models/**      | Domain logic, DB access. Use `Core\Database::getInstance()` for PDO. Usually one model per main table. |
| **Views/**       | PHP templates. Organized by feature (e.g. `profile/index.php`, `profile/form.php`). Layout sa `Views/layout/`. |

Convention: **ControllerName** sa route → class `App\Controllers\ControllerName` → file `App/Controllers/ControllerName.php`.

---

## Core/

Framework components. Huwag baguhin kung gagamitin ang same framework sa ibang app.

| File              | Purpose |
|-------------------|--------|
| **Router.php**    | GET/POST registration, dispatch by URI and method, `Controller@action`, route params `{id}`. |
| **Controller.php** | Base controller: `view()`, `redirect()`, `json()`, `auth()`, `requireAuth()`, `requireCapability()`, `validateCsrf()`. |
| **Database.php**  | PDO singleton. `init(config)`, `getInstance()`. |
| **Auth.php**      | Session-based auth. `init()`, `check()`, `user()`, `can()`, `canAny()`, `login()`, `logout()`. |
| **Logger.php**    | File logging: `log()`, `php()`, `database()`, `auth()`, `app()`, `email()`. |
| **Csrf.php**      | CSRF token generate/validate. |
| **MigrationRunner.php** | Reads `database/migration_*.php`, runs pending, tracks in `migrations` table. |
| **Helpers.php**, **Mailer.php**, **LoginThrottle.php**, etc. | Extra utilities. |

---

## config/

| File                | Purpose |
|---------------------|--------|
| **database.php**    | MySQL: host, dbname, username, password, charset. Required. |
| **database-sample.php** | Template. Copy to `database.php` and edit. |
| **app.php**         | Optional: `base_url`, app name. |
| **app-sample.php**  | Template for app.php. |

---

## routes/

| File       | Purpose |
|------------|--------|
| **web.php** | All web routes. `$router` is defined in `public/index.php` then this file is required; it registers routes and calls `$router->dispatch()`. |

Future: Pwedeng mag-add ng `api.php` at i-require from a separate entry (e.g. `public/api.php`) kung may API.

---

## database/

- **migration_000_*.php**, **migration_001_*.php**, … — Migration files. Sorted by filename. Each returns `['name' => '...', 'up' => callable, 'down' => callable?]`.
- **seed_*.php** — Optional seed scripts (e.g. sample data). Hindi automatic; i-run manually kung kailangan.

---

## cli/

- **migrate.php** — Run migrations. Usage: `php cli/migrate.php` (run pending), `php cli/migrate.php --status` (list status). Must run from project root.

---

## Autoloading

Sa **bootstrap.php**, class name → file path:

- `App\Controllers\FooController` → `App/Controllers/FooController.php`
- `App\Models\User` → `App/Models/User.php`
- `Core\Router` → `Core/Router.php`

Namespace at folder structure ay dapat mag-match.

---

Susunod: [ROUTING.md](ROUTING.md) para sa routing details, o [CREATING_MODULES.md](CREATING_MODULES.md) para sa pag-add ng bagong module.
