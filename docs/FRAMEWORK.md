# Framework Guide — Gabay sa Framework

**PAPS / Custom PHP MVC Framework** — Isang lightweight PHP framework na pwedeng gamitin bilang template sa kahit anong sistema (parang Laravel). May Router, MVC, migrations, at config.

---

## Ano ang framework na ito? (What is this framework?)

Ito ay **scaffolded PHP MVC framework** na:

- **Router** — GET/POST routes, `Controller@action`, route parameters `{id}`
- **MVC** — Controllers sa `App/Controllers/`, Models sa `App/Models/`, Views sa `App/Views/`
- **Config** — `config/database.php`, `config/app.php` (base_url, app name)
- **Migrations** — `database/migration_*.php`, `php cli/migrate.php`
- **Core** — Auth, CSRF, Logger, Database (PDO), Mailer

Pwede mong kopyahin ang buong project, palitan ang pangalan at modules, at gamitin para sa bagong system (inventory, HR, ticketing, etc.).

---

## Mga rekisitos (Requirements)

- **PHP** 8.0+
- **MySQL** 5.7+ o MariaDB 10.2+
- **Apache** na may mod_rewrite (o nginx equivalent)

---

## Mabilis na simula (Quick start)

1. **Config**  
   - Kopyahin: `config/database-sample.php` → `config/database.php`  
   - Ilagay: host, dbname, username, password  
   - Opsyonal: `config/app-sample.php` → `config/app.php` (base_url kung naka-subfolder)

2. **Database**  
   - Gumawa ng database sa MySQL (pareho ng `dbname` sa config).

3. **Migrations**  
   - Sa project root: `php cli/migrate.php`  
   - Para sa status: `php cli/migrate.php --status`

4. **Web server**  
   - Ituro ang **document root** sa folder na `public/` (recommended).  
   - O kung naka-root ang project, siguraduhing may `.htaccess` at naka-rewrite papuntang `public/index.php`.

5. **Default login** (kung may seed ng admin)  
   - Username: `admin`  
   - Password: `admin123`

Detalyadong hakbang: [GETTING_STARTED.md](GETTING_STARTED.md).

---

## Istruktura ng project (Project structure)

```
project/
├── App/
│   ├── Controllers/     # Lahat ng controller (ControllerName.php → ControllerName)
│   ├── Models/           # Lahat ng model (table / business logic)
│   └── Views/            # PHP views per module (folder per controller/feature)
├── config/
│   ├── database.php      # MySQL credentials (huwag i-commit kung may password)
│   ├── database-sample.php
│   ├── app.php           # base_url, app name (optional)
│   └── app-sample.php
├── Core/                 # Framework core (huwag baguhin kung generic na)
│   ├── Router.php
│   ├── Controller.php    # Base controller (view, redirect, auth, csrf)
│   ├── Database.php
│   ├── Auth.php
│   ├── Logger.php
│   ├── Csrf.php
│   ├── MigrationRunner.php
│   └── ...
├── database/
│   └── migration_*.php   # Mga migration file (order by filename)
├── cli/
│   └── migrate.php       # php cli/migrate.php
├── routes/
│   └── web.php           # Lahat ng web routes (GET/POST)
├── public/               # Web root (document root dito)
│   ├── index.php         # Entry point
│   └── .htaccess
├── logs/                 # php_error, database_error, auth, app, email
├── bootstrap.php         # Autoload, ROOT, config load, Database init, Auth init
└── docs/                 # Documentation
```

Detalye: [STRUCTURE.md](STRUCTURE.md).

---

## Routing

- Routes ay nasa **routes/web.php**. Ang `$router` ay naka-inject mula sa `public/index.php`.

**Syntax:**

```php
$router->get('/path', 'ControllerName@methodName');
$router->post('/path', 'ControllerName@methodName');
```

**Route parameters:**

```php
$router->get('/user/view/{id}', 'UserController@show');   // $id mapupunta sa show($id)
$router->get('/post/{slug}/comment/{cid}', 'PostController@comment'); // comment($slug, $cid)
```

- Controller class: `App\Controllers\ControllerName` (autoload mula sa `App/Controllers/ControllerName.php`).
- Method: public function na may pangalan na tinukoy pagkatapos ng `@`.

Higit pa: [ROUTING.md](ROUTING.md).

---

## Paano magdagdag ng bagong module (Controller, Model, View, Migration)

1. **Migration** — Gumawa ng `database/migration_XXX_description.php` (hal. `migration_013_items.php`).  
   Format: `return ['name' => '...', 'up' => function(\PDO $db) { ... }, 'down' => ...];`  
   Tapos: `php cli/migrate.php`.

2. **Model** — Gumawa ng `App/Models/Item.php`, namespace `App\Models`, use `Core\Database::getInstance()` para sa PDO.

3. **Controller** — Gumawa ng `App/Controllers/ItemController.php` na naka-extend ng `Core\Controller`.  
   Gamitin: `$this->view('item/index', $data)`, `$this->redirect()`, `$this->requireAuth()`, `$this->validateCsrf()` para sa POST.

4. **Views** — Sa `App/Views/item/` (hal. `index.php`, `form.php`, `view.php`).  
   Layout: kung may `App/Views/layout/main.php`, i-include o i-extend iyon.

5. **Routes** — Sa `routes/web.php`, magdagdag ng:
   - `$router->get('/item', 'ItemController@index');`
   - `$router->get('/item/create', 'ItemController@create');`
   - `$router->post('/item/store', 'ItemController@store');`
   - atbp.

Tingnan ang [CREATING_MODULES.md](CREATING_MODULES.md) para sa step-by-step at halimbawa.

---

## Paggamit sa ibang sistema (Using this for another system)

1. **Kopyahin** ang buong project o i-clone ang repo.
2. **Palitan** ang app name sa config at sa layout (title, brand name).
3. **Tanggalin** o i-comment ang routes at controllers/views na hindi kailangan.
4. **Magdagdag** ng bagong migrations, models, controllers, views para sa bagong features.
5. **Routes** — Lahat sa `routes/web.php` (o hatiin mo sa `routes/web.php` + `routes/api.php` kung mag-add ka ng separate API loader).

Kung gusto mong **walang auth/roles**, pwede mong i-simplify: tanggalin ang `requireAuth()` / `requireCapability()` sa mga route na public, o gumawa ng bagong entry (hal. `public/demo.php`) na walang auth.

---

## Saan ang susunod?

- [GETTING_STARTED.md](GETTING_STARTED.md) — Setup at unang run  
- [STRUCTURE.md](STRUCTURE.md) — Folder at file roles  
- [ROUTING.md](ROUTING.md) — Routing syntax at halimbawa  
- [CREATING_MODULES.md](CREATING_MODULES.md) — Paggawa ng bagong module (CRUD)

---

*Framework version: 1. Compatible with PHP 8.0+ and MySQL 5.7+.*
