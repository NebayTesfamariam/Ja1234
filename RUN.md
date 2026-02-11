# How to Run This PHP Project

You’re used to **Next.js** (`npm run dev` / `npm run build`). With this **PHP + MySQL** app there’s no single “npm run dev”; you need **PHP**, a **web server**, and **MySQL**. Below is a minimal path to get it running.

---

## Which folder is the project?

- **Use the `44/` folder** — that’s the real application (PHP, HTML, CSS, JS, API).
- **Ignore `__MACOSX/`** — it’s macOS metadata from a zip, not a second copy of the app.

All steps below assume you’re working inside **`44/`**.

---

## What you need (like “dependencies”)

| Need | Purpose |
|------|--------|
| **PHP 7.4+** | Run the app and API (no Composer/npm required for basic run). |
| **MySQL or MariaDB** | Database. Dev config expects: user `root`, no password, DB name `pornfree`. |
| **Web server** | Either **Option A** (XAMPP) or **Option B** (PHP built‑in server). |
| **phpMyAdmin** | Optional; only if you want a GUI for the database. |

You do **not** need Node/npm for this project.

---

## Option A: XAMPP (easiest if you want Apache + MySQL + phpMyAdmin)

1. **Install XAMPP** (includes Apache, MySQL, PHP, phpMyAdmin):
   - Linux: [Apache Friends XAMPP](https://www.apachefriends.org/download.html) or your package manager (e.g. `sudo apt install lamp-server^` or similar).
   - Extract/install and start **Apache** and **MySQL** from the XAMPP control panel.

2. **Put the project where the web server can see it:**
   - Copy the **`44`** folder into XAMPP’s document root:
     - Typical XAMPP: `htdocs/44` (so you have `htdocs/44/index.html`, `htdocs/44/api/`, etc.).
   - Or create a symlink:  
     `ln -s /home/dev/Documents/projects/php-project/Ja1234/44 /path/to/xampp/htdocs/44`

3. **Create the database and tables** (one-time setup):
   ```bash
   cd /home/dev/Documents/projects/php-project/Ja1234/44
   php setup_database.php
   ```
   This creates the `pornfree` database (if missing), tables, and a default admin user.

4. **Open the app in the browser:**
   - Main site: **http://localhost/44/**
   - API health: **http://localhost/44/api/health.php**
   - Default admin: **admin@test.com** / **admin123** (change in production!)

If the DB connection fails, check that MySQL is running and that `config.php` dev settings match your MySQL (host `localhost`, user `root`, empty password, database `pornfree`). On Linux, the code already falls back to a TCP connection if the macOS XAMPP socket path doesn’t exist.

---

## Option B: PHP built-in server (no XAMPP, like a simple “npm run dev”)

1. **Install PHP and MySQL** (no Apache needed):
   - Ubuntu/Debian example:
     ```bash
     sudo apt update
     sudo apt install php php-mysql php-mbstring php-json mysql-server
     ```
   - Start MySQL:
     ```bash
     sudo service mysql start
     ```

2. **Create the database and tables** (one-time):
   ```bash
   cd /home/dev/Documents/projects/php-project/Ja1234/44
   php setup_database.php
   ```
   Use the same MySQL user/password as in `config.php` (dev: `root` / no password). If your MySQL has a password, set it in `44/config.php` (`$DB_PASS` in the development block).

3. **Start the PHP built-in server:**
   ```bash
   cd /home/dev/Documents/projects/php-project/Ja1234/44
   php -S localhost:8000
   ```

4. **Open in browser:**
   - Main site: **http://localhost:8000/**
   - API health: **http://localhost:8000/api/health.php**
   - Default admin: **admin@test.com** / **admin123**

The frontend detects that the URL doesn’t contain `/44/` and uses relative `api/...` paths, so no code change is needed for this setup.

---

## Quick “is it working?” checklist

1. **MySQL running**  
   - XAMPP: MySQL “Running” in control panel.  
   - Linux: `sudo service mysql status` or `systemctl status mysql`.

2. **Database and tables exist**  
   ```bash
   cd /home/dev/Documents/projects/php-project/Ja1234/44
   php setup_database.php
   ```
   You should see “Database setup complete” and no connection errors.

3. **PHP can connect**  
   - Open **http://localhost/44/api/health.php** (XAMPP) or **http://localhost:8000/api/health.php** (built-in server).  
   - You should get JSON (e.g. with `db: "ok"` or similar), not a 500 or connection error.

4. **Login**  
   - Use the main page or login page and sign in with **admin@test.com** / **admin123**.

---

## If something doesn’t work

- **“Connection refused” or “Access denied” for database**  
  - MySQL must be running.  
  - In `44/config.php`, development section: set `$DB_HOST`, `$DB_USER`, `$DB_PASS`, `$DB_NAME` to match your MySQL (default: `localhost`, `root`, `""`, `pornfree`).  
  - If your root user has a password, set `$DB_PASS` to that password.

- **Blank page or 500 on API**  
  - Run from the project directory: `php -S localhost:8000` and open **http://localhost:8000/api/health.php**.  
  - Check PHP error log or run: `php -r "require '44/config.php';"` from the repo root to see if config or DB connect fails.

- **API returns “database error”**  
  - Run `php setup_database.php` from the `44/` folder and fix any errors it prints (e.g. wrong credentials).

---

## Summary (copy-paste friendly)

**Using PHP built-in server (no XAMPP):**
```bash
# 1. Start MySQL (if not already running)
sudo service mysql start

# 2. Create DB and tables (one-time)
cd /home/dev/Documents/projects/php-project/Ja1234/44
php setup_database.php

# 3. Start PHP server (like npm run dev)
php -S localhost:8000
```
Then open **http://localhost:8000/** and log in with **admin@test.com** / **admin123**.

**Using XAMPP:**  
Put `44` in `htdocs/44`, start Apache + MySQL, run `php setup_database.php` from `44/`, then open **http://localhost/44/**.

---

## Extra (optional)

- **DNS/VPN/firewall scripts** in `44/` (e.g. `start_dns_server.sh`, `vpn_firewall_setup.sh`) are for the full “porn-free” network setup (VPN, DNS, blocking). You don’t need them just to run the website and API locally.
- **phpMyAdmin** is optional; install it only if you want a web UI for MySQL. The app works without it.
- More detail on the full system (DNS, firewall, etc.): see **`44/README_SETUP.md`**.
