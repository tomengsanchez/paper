# Getting Started — Unang Setup

Step-by-step para ma-run ang project sa local (XAMPP, WAMP, o standalone PHP + Apache).

---

## 1. I-clone o i-copy ang project

- Ilagay sa folder na naka-serve ng web server (hal. `htdocs`, `www`).
- O kung naka-Virtual Host, ituro ang document root sa **`public`** folder.

---

## 2. Configuration

### Database

```bash
cp config/database-sample.php config/database.php
```

Edit **config/database.php**:

```php
return [
    'host'     => 'localhost',
    'dbname'   => 'your_database_name',
    'username' => 'root',
    'password' => 'your_password',
    'charset'  => 'utf8mb4',
];
```

### App (optional)

Kung naka-subfolder ang app (hal. `http://localhost/myapp/`):

```bash
cp config/app-sample.php config/app.php
```

Edit **config/app.php**:

```php
return [
    'base_url' => '/myapp',  // trailing slash optional, tatanggalin ng code
];
```

Kung naka-root (hal. `http://localhost/`), pwedeng walang `config/app.php` o `base_url` na blank.

---

## 3. Gumawa ng database

Sa MySQL (phpMyAdmin o CLI):

```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Gamitin ang **same name** sa `config/database.php` → `dbname`.

---

## 4. I-run ang migrations

Sa **project root** (hindi sa `public/`):

```bash
php cli/migrate.php
```

Makikita ang "Ran X migration(s)."

Para tingnan kung alin na ang na-run:

```bash
php cli/migrate.php --status
```

---

## 5. Web server

### Option A — Document root = `public/` (recommended)

- Apache: `DocumentRoot /path/to/project/public`
- XAMPP: Ilagay ang project sa `htdocs`, tapos sa httpd.conf o Virtual Host, ituro ang DocumentRoot sa `.../localhost/public`.

Sa ganito, ang URL ay hal. `http://localhost/` (kung isa lang ang site).

### Option B — Document root = project root

- Kung ang document root ay ang **project root** (folder na may `bootstrap.php`), kailangan lahat ng request mapunta sa `public/index.php`.
- Ilagay ang `.htaccess` sa **root** na naka-rewrite papuntang `public/index.php` (tingnan ang existing `.htaccess` sa root at sa `public/`).

---

## 6. I-test

- Buksan sa browser: `http://localhost/` (o `http://localhost/myapp/` kung may base_url).
- Kung may migration na nag-seed ng admin: **admin** / **admin123**.

Kung 404, i-check:

- Document root ay `public/` O may rewrite papuntang `public/index.php`.
- `config/database.php` ay tama at na-create na ang database.
- Na-run na ang `php cli/migrate.php`.

---

## 7. Logs

- **logs/php_error.log** — PHP errors
- **logs/database_error.log** — DB errors (from Core\Database)
- **logs/auth.log** — Auth events (from Core\Auth/Logger::auth)

Kung may error, tingnan muna ang `logs/php_error.log`.

---

Susunod: [STRUCTURE.md](STRUCTURE.md) para sa folder structure, o [FRAMEWORK.md](FRAMEWORK.md) para sa buod ng framework.
