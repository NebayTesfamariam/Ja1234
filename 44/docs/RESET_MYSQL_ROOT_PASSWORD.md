# Reset MySQL root password (when you don't know it)

If you get `Access denied for user 'root'@'localhost'` and you never set a password (or forgot it), reset it like this.

---

## Method A: Config file (recommended on Ubuntu/Debian)

### 1. Stop MySQL

```bash
sudo systemctl stop mysql
```

(If that fails, try `sudo systemctl stop mysqld`.)

### 2. Enable skip-grant-tables

```bash
sudo sed -i 's/^\[mysqld\]$/\[mysqld\]\nskip-grant-tables/' /etc/mysql/mysql.conf.d/mysqld.cnf
```

If that file doesn't exist, try:

```bash
echo "skip-grant-tables" | sudo tee -a /etc/mysql/mysql.conf.d/mysqld.cnf
```

Or edit by hand: `sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf` and under `[mysqld]` add a line: `skip-grant-tables`, then save.

### 3. Start MySQL

```bash
sudo systemctl start mysql
```

### 4. Set new password

```bash
mysql -u root -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'localdev'; FLUSH PRIVILEGES;"
```

### 5. Remove skip-grant-tables

```bash
sudo sed -i '/skip-grant-tables/d' /etc/mysql/mysql.conf.d/mysqld.cnf
```

### 6. Restart MySQL normally

```bash
sudo systemctl restart mysql
```

### 7. Test and run setup

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
DB_PASS=localdev php setup_database.php
```

---

## Method B: mysqld_safe (if Method A doesn't work)

### 1. Stop MySQL

```bash
sudo systemctl stop mysql
```

### 2. Start MySQL without permission checks

```bash
sudo mysqld_safe --skip-grant-tables &
```

Wait a few seconds, then press Enter.

### 3. Connect and set a new password

```bash
mysql -u root -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'localdev'; FLUSH PRIVILEGES;"
```

### 4. Stop the safe process and start MySQL normally

```bash
sudo pkill mysqld_safe
sudo systemctl start mysql
```

### 5. Run setup

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
DB_PASS=localdev php setup_database.php
```

---

Done. Use `DB_PASS=localdev` when running the PHP app.
