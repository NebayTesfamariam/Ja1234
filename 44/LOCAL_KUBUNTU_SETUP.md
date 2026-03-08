# Run the project on Kubuntu (local testing)

Step-by-step guide to run the app on **Kubuntu** with **WireGuard** (free) and test that **allowed sites work** and **other sites are blocked** when using the VPN.

---

## What you need

- **Kubuntu** (one machine can run everything: PHP app, WireGuard server, DNS server).
- **WireGuard** = free, open-source VPN. No paid subscription.
- **PHP**, **MySQL**, **Python 3** on the same machine.

---

## Step 1: Install prerequisites

Open a terminal and run:

```bash
sudo apt update
sudo apt install -y php php-mysql php-mbstring php-json php-curl \
  mysql-server python3 python3-pip wireguard
pip3 install --user requests
```

If you use **Apache** instead of PHP built-in server:

```bash
sudo apt install -y apache2 libapache2-mod-php
# Point Apache to your project (e.g. /var/www/44 or symlink from your project)
```

---

## Step 2: MySQL – create database and tables

Start MySQL (if not running):

```bash
sudo systemctl start mysql
# Optional: enable on boot
sudo systemctl enable mysql
```

Set a root password if you haven’t (Kubuntu often has no password by default):

```bash
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password'; FLUSH PRIVILEGES;"
```

Go to the project folder and run the setup script. Use the same password you set (or leave empty if root has no password):

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44

# If root has a password:
DB_PASS='your_password' php setup_database.php

# If root has no password:
php setup_database.php
```

You should see “Database setup complete” and tables created. Default DB name is `pornfree` (or set `DB_NAME=yourdb php setup_database.php`).

---

## Step 3: WireGuard – install and generate server keys

WireGuard is already installed from Step 1. Generate the **server** key pair (run once):

```bash
sudo mkdir -p /etc/wireguard
cd /etc/wireguard
sudo wg genkey | sudo tee server_private.key | sudo wg pubkey | sudo tee server_public.key
sudo chmod 600 server_private.key
```

Save the **server public key** somewhere – you’ll need it for the PHP app:

```bash
sudo cat /etc/wireguard/server_public.key
```

Copy the single line (base64). Also get your **LAN IP** (needed for the VPN endpoint so your phone/other PC can connect):

```bash
ip -4 route get 8.8.8.8 | grep -oP 'src \K\S+'
# Or:
hostname -I | awk '{print $1}'
```

Example: `192.168.1.100`. Remember this as `YOUR_LAN_IP`.

---

## Step 4: WireGuard server config (wg0)

Create the WireGuard interface config:

```bash
sudo nano /etc/wireguard/wg0.conf
```

Paste (replace the placeholder with the **contents of server_private.key**; replace `eth0` with your main interface if different – see below):

```ini
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = <PASTE_CONTENT_OF_server_private.key_HERE>

# Forward traffic and NAT so VPN clients can reach the internet
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

To find your main network interface (instead of `eth0`):

```bash
ip route | grep default
# Example: default via 192.168.1.1 dev enp0s3  → use enp0s3
```

Use that name in the `PostUp`/`PostDown` lines (e.g. `enp0s3` instead of `eth0`).

Start WireGuard:

```bash
sudo wg-quick up wg0
```

Check:

```bash
sudo wg show
```

You should see the interface with address 10.10.0.1/24. To start automatically on boot:

```bash
sudo systemctl enable wg-quick@wg0
```

---

## Step 5: VPN config for the PHP app (config_vpn.php)

In the project folder:

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
cp config_vpn.php.example config_vpn.php
nano config_vpn.php
```

Set these three values:

1. **VPN_SERVER_ENDPOINT** = your LAN IP and port, e.g. `192.168.1.100:51820` (use `YOUR_LAN_IP` from Step 3).
2. **VPN_SERVER_PUBLIC_KEY** = the line you copied from `sudo cat /etc/wireguard/server_public.key`.
3. **VPN_DNS** = `10.10.0.1` (already in the example).

Example:

```php
$VPN_SERVER_ENDPOINT = '192.168.1.100:51820';
$VPN_SERVER_PUBLIC_KEY = 'xYz123AbC...paste_full_base64_line...=';
$VPN_DNS = '10.10.0.1';
```

Save and exit.

---

## Step 6: DNS internal key (so DNS server can fetch whitelist)

Generate a secret and use the **same** value for PHP and the DNS server:

```bash
openssl rand -hex 32
```

Example: `a1b2c3d4e5f6...`. Set it everywhere you run the app and the DNS server.

**Option A – PHP built-in server:** export before starting PHP:

```bash
export DNS_INTERNAL_KEY='a1b2c3d4e5f6...'   # your value from above
```

**Option B – Apache:** add to the vhost or to a file loaded by Apache, e.g.:

```apache
SetEnv DNS_INTERNAL_KEY "a1b2c3d4e5f6..."
```

You’ll use the same value when starting the DNS server in Step 8.

---

## Step 7: Run the PHP app

**Option A – PHP built-in server (easiest for testing):**

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DNS_INTERNAL_KEY='your-secret-from-step6'
php -S 0.0.0.0:8080
```

Then open in the browser: **http://localhost:8080** (or **http://YOUR_LAN_IP:8080** from another device).

**Option B – Apache:**  
Ensure the document root points to the project (e.g. `44` or `public` as needed), set `DNS_INTERNAL_KEY` in the environment (SetEnv), and open **http://localhost/44** (or your vhost URL).

Create a user (e.g. register on the site) or use the admin created by setup: **admin@test.com** / **admin123** if you ran `setup_database.php` and it created the default admin.

---

## Step 8: Start the DNS whitelist server

The DNS server must run on the **same machine** that has the WireGuard interface `10.10.0.1`, and it needs the same `DNS_INTERNAL_KEY` so it can call the PHP whitelist API.

Open a **second terminal**:

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DNS_INTERNAL_KEY='your-secret-from-step6'
export HTTP_HOST=localhost
sudo -E python3 dns_whitelist_server.py
```

Leave this running. You should see: “DNS Whitelist Server started on port 53”.

To run it in the background instead:

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DNS_INTERNAL_KEY='your-secret-from-step6'
export HTTP_HOST=localhost
sudo -E nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
```

---

## Step 9: Sync WireGuard peers (so clients can connect)

When a user adds a device in the app, the app stores the client’s **public key** and **VPN IP** (e.g. 10.10.0.2). The WireGuard server must have that client as a **peer**. Run the sync script on the same machine (VPN server):

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
sudo API_BASE_URL=http://127.0.0.1:8080/api VPN_SYNC_KEY='your-secret-from-step6' ./scripts/sync_wireguard_peers.sh
```

If you use Apache and the app is at `http://localhost/44`, use:

```bash
sudo API_BASE_URL=http://localhost/44/api VPN_SYNC_KEY='your-secret-from-step6' ./scripts/sync_wireguard_peers.sh
```

If the script is not executable: `chmod +x scripts/sync_wireguard_peers.sh`.

Run this **after** you have at least one device in the app (after Step 10). You can run it again whenever you add new devices (or put it in cron every 2–5 minutes).

---

## Step 10: Create user, device, whitelist, and download config

1. In the browser, open the app: **http://localhost:8080** (or your Apache URL).
2. **Register** a new user or log in as **admin@test.com** / **admin123**.
3. **Add a device** (e.g. “My Laptop”). The browser will generate a WireGuard key pair and send the public key to the server.
4. **Add domains to the whitelist** for that device, e.g.:
   - `wikipedia.org`
   - `google.com`
   - `example.com`
5. **Download the WireGuard config** (button like “Download config” for that device). The file will contain:
   - Your server’s endpoint and public key (from config_vpn.php).
   - `DNS = 10.10.0.1`.
   - The client’s private key (injected by the browser).
6. **Run the sync script** once (Step 9) so this device is added as a peer on the VPN server.
7. **Import the config** in the WireGuard client:
   - **On this Kubuntu machine:** install WireGuard GUI or use `wg-quick up <config-file>`.
   - **On another PC/phone:** copy the `.conf` file, install WireGuard from [wireguard.com/install](https://www.wireguard.com/install/) or the app store, then “Import from file” / “Add tunnel” and select the file.
8. **Connect** the VPN.

---

## Step 11: Test that a site is allowed vs blocked

**With VPN connected:**

- **Allowed:** Open **https://wikipedia.org** (or any domain you added to the whitelist). It should load.
- **Blocked:** Open a domain you did **not** add (e.g. **https://facebook.com** or **https://reddit.com**). It should **not** resolve (browser: “site can’t be reached” / “DNS_PROBE_FINISHED_NXDOMAIN” or similar). That’s the DNS whitelist blocking it.

**Quick DNS check from the VPN client (optional):**

```bash
# From the machine connected as VPN client (or from the server, using 10.10.0.1 as DNS):
nslookup wikipedia.org 10.10.0.1   # should return an IP
nslookup facebook.com 10.10.0.1   # should return NXDOMAIN or no result
```

---

## Summary checklist

| Step | What to do |
|------|------------|
| 1 | Install PHP, MySQL, Python3, WireGuard, `requests` |
| 2 | Start MySQL; run `php setup_database.php` in project folder |
| 3 | Generate WireGuard server keys in `/etc/wireguard`; note server public key and LAN IP |
| 4 | Create `/etc/wireguard/wg0.conf` with server private key and correct interface; `sudo wg-quick up wg0` |
| 5 | Copy `config_vpn.php.example` to `config_vpn.php`; set endpoint (LAN_IP:51820), server public key, DNS=10.10.0.1 |
| 6 | Generate `DNS_INTERNAL_KEY` (e.g. `openssl rand -hex 32`); set for PHP and DNS server |
| 7 | Run PHP app (`php -S 0.0.0.0:8080` with `DNS_INTERNAL_KEY` or use Apache) |
| 8 | Start DNS server: `sudo -E DNS_INTERNAL_KEY=... HTTP_HOST=localhost python3 dns_whitelist_server.py` |
| 9 | Run sync script: `sudo API_BASE_URL=... VPN_SYNC_KEY=... ./scripts/sync_wireguard_peers.sh` (after having at least one device) |
| 10 | Register/login, add device, add whitelist domains, download config, import in WireGuard, connect |
| 11 | Test: whitelisted site loads; non-whitelisted site does not (blocked) |

---

## If something doesn’t work

- **“Permission denied” on port 53**  
  DNS server must run with `sudo` (e.g. `sudo -E python3 dns_whitelist_server.py`).

- **VPN connects but no sites load**  
  - DNS server must be running and reachable at 10.10.0.1.  
  - `DNS_INTERNAL_KEY` must be the same for PHP and the DNS server.  
  - Device must have at least one domain in the whitelist.

- **Non-whitelisted sites still load**  
  - Confirm the WireGuard config has `DNS = 10.10.0.1`.  
  - Confirm you’re actually connected to this VPN (check WireGuard status).  
  - Try `nslookup blocked-site.com 10.10.0.1` – it should return NXDOMAIN.

- **“Handshake did not complete” / VPN won’t connect**  
  Run the sync script so the client’s public key is added as a peer:  
  `sudo API_BASE_URL=... VPN_SYNC_KEY=... ./scripts/sync_wireguard_peers.sh`

- **Database connection error**  
  Check MySQL is running and that `DB_PASS` (and if used `DB_NAME`, `DB_USER`) match your setup when running `php setup_database.php` or when the app runs.

Once these steps are done, you have the project running on Kubuntu with the free WireGuard VPN and can verify that only whitelisted sites work and others are blocked.
