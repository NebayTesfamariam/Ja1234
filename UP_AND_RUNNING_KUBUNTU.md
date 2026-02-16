# Up and Running on Kubuntu

Copy-paste these commands to install dependencies and run the project. All steps assume you are in a terminal.

---

## Step 1: Install PHP and MySQL

```bash
sudo apt update
sudo apt install -y php php-mysql php-mbstring php-json php-xml mysql-server
```

This installs:
- **PHP** and extensions needed for the app (MySQL driver, JSON, etc.)
- **MySQL server** (MariaDB on recent Kubuntu)

---

## Step 2: Start MySQL and allow root login from PHP

On Kubuntu/Ubuntu, MySQL `root` often only allows login via the system user. We set a password so PHP can connect.

```bash
sudo systemctl start mysql
sudo systemctl enable mysql
```

Then set the root password to `root` (only for local development):

```bash
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root'; FLUSH PRIVILEGES;"
```

If you see “Unknown user”, try (MariaDB 10.6+):

```bash
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'root'; FLUSH PRIVILEGES;"
```

---

## Step 3: Go to the project and create the database

Use the **`44`** folder (that’s the app). Set DB user/password so the scripts can connect.

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DB_USER=root
export DB_PASS=root
php setup_database.php
```

You should see “Database setup complete!” and “Admin user created: admin@test.com / admin123”.

If it says “Connection failed”, check that MySQL is running: `sudo systemctl status mysql`.

---

## Step 4: Start the app (like `npm run dev`)

**Option A – From repo root (recommended):**

```bash
cd /home/dev/Documents/projects/php-project/Ja1234
./run.sh
```

**Option B – From the 44 folder:**

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DB_USER=root
export DB_PASS=root
php -S localhost:8000
```

Leave the terminal open. You should see: `Development Server (http://localhost:8000) started`. If you start from the wrong folder you’ll get “404 - No such file or directory” for `/`; use `./run.sh` from the repo root or run `php -S localhost:8000` from inside `44/`.

---

## Step 5: Open the app in your browser

- **Main site:** [http://localhost:8000](http://localhost:8000)
- **API health:** [http://localhost:8000/api/health.php](http://localhost:8000/api/health.php)

**Login:** `admin@test.com` / `admin123` (change in production.)

---

## One-time “run” script (optional)

To avoid retyping the exports, you can use a small script. Create `44/run.sh`:

```bash
#!/bin/bash
cd "$(dirname "$0")"
export DB_USER=root
export DB_PASS=root
php -S localhost:8000
```

Then:

```bash
chmod +x /home/dev/Documents/projects/php-project/Ja1234/44/run.sh
/home/dev/Documents/projects/php-project/Ja1234/44/run.sh
```

---

## Summary (minimal copy-paste)

Run in order:

```bash
# 1. Install
sudo apt update
sudo apt install -y php php-mysql php-mbstring php-json php-xml mysql-server

# 2. Start MySQL and set root password
sudo systemctl start mysql
sudo systemctl enable mysql
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root'; FLUSH PRIVILEGES;"

# 3. Setup database (once)
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DB_USER=root
export DB_PASS=root
php setup_database.php

# 4. Start the app
php -S localhost:8000
```

Then open **http://localhost:8000** in your browser.

---

## If something fails

| Problem | Fix |
|--------|-----|
| `php: command not found` | Run Step 1 again: `sudo apt install -y php php-mysql php-mbstring php-json php-xml` |
| `Connection failed` / Access denied | Run the `ALTER USER` command from Step 2 again. Check MySQL: `sudo systemctl status mysql` |
| `Address already in use` | Port 8000 is in use. Use another: `php -S localhost:8080` |
| Blank page or 500 | Run `php setup_database.php` again with `export DB_USER=root DB_PASS=root`. Check: [http://localhost:8000/api/health.php](http://localhost:8000/api/health.php) |

---

## Dependencies (what was installed)

| Package | Purpose |
|---------|--------|
| `php` | Run the app and API |
| `php-mysql` | Talk to MySQL from PHP |
| `php-mbstring` | String handling |
| `php-json` | JSON output |
| `php-xml` | XML/HTML handling (some scripts may use it) |
| `mysql-server` | Database (MariaDB on Kubuntu) |

No Node.js, npm, Composer, or XAMPP are required for this setup.
