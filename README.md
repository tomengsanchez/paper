# PAPS - Custom PHP MVC Framework

A lightweight PHP MVC framework with EAV database pattern, jQuery, and MySQL.

## Setup

### 1. Database

Create a MySQL database and run the schema:

```sql
CREATE DATABASE paper_db;
USE paper_db;
SOURCE database/schema.sql;
```

Or import `database/schema.sql` via phpMyAdmin/MySQL client.

### 2. Configuration

Edit `config/database.php` with your MySQL credentials:
- host, dbname, username, password

### 3. Web Server

**Option A - Document root = `public/`** (recommended)
- Point your web server document root to the `public/` folder
- Apache: `DocumentRoot /path/to/paper/public`

**Option B - Document root = project root**
- Ensure `index.php` in root forwards to `public/index.php`
- Apache: Enable mod_rewrite and ensure `.htaccess` is in `public/` (or adjust RewriteBase)

### 4. Default Login

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
├── database/      # schema.sql
├── public/        # Web root, index.php
└── bootstrap.php
```

## Features

- **Roles:** Administrator, Standard User, Coordinator
- **5 Menu Links:** Profile, Structure, Grievance, Library, Settings
- **User Management:** Admin-only CRUD
- **Library CRUD:** Projects with Coordinator dropdown (searchable via jQuery Select2)
- **Profile CRUD:** PAPSID (auto: PAPS-YEARMONTH0000000001), Control Number, Age, Contact Number, Project (searchable)
