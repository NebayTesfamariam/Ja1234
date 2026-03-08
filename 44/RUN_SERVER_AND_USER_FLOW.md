# Run the server & user flow (VPN config → import → connect)

Two parts: **1) Commands to run the server** (you), **2) What the user does** (install WireGuard → login → download config → import → connect). The config file contains everything; no editing.

---

# Part 1: Run the server (testing)

Assume one-time setup is done: database, WireGuard keys in `/etc/wireguard`, `wg0.conf`, `config_vpn.php`, and a secret for DNS/sync.

Project root: `cd /home/dev/Documents/projects/php-project/Ja1234/44`

Pick a secret once and use it everywhere below (e.g. `MY_SECRET=abc123...` from `openssl rand -hex 32`).

---

## Order: run these in sequence

### 1. MySQL (if not already running)

```bash
sudo systemctl start mysql
```

### 2. WireGuard VPN server

```bash
sudo wg-quick up wg0
```

### 3. PHP app (Terminal 1 – leave open)

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DB_PASS=localdev
export DNS_INTERNAL_KEY='YOUR_SECRET'
php -S 0.0.0.0:8080
```

Users will use: **http://localhost:8080** (or your LAN IP, e.g. **http://192.168.100.18:8080**).

### 4. DNS whitelist server (Terminal 2 – leave open)

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DNS_INTERNAL_KEY='YOUR_SECRET'
export HTTP_HOST=localhost
sudo -E .venv/bin/python3 dns_whitelist_server.py
```

If `.venv` is missing: `python3 -m venv .venv && .venv/bin/pip install -r requirements.txt` then run the block above again.

### 5. Sync peers (after a user has added a device – run once or when new devices are added)

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
chmod +x scripts/sync_wireguard_peers.sh
sudo API_BASE_URL=http://127.0.0.1:8080/api VPN_SYNC_KEY='YOUR_SECRET' ./scripts/sync_wireguard_peers.sh
```

---

## Summary: server run order

| Step | Command |
|------|---------|
| 1 | `sudo systemctl start mysql` |
| 2 | `sudo wg-quick up wg0` |
| 3 | Terminal 1: `export DB_PASS=localdev DNS_INTERNAL_KEY='SECRET'; php -S 0.0.0.0:8080` (from project root) |
| 4 | Terminal 2: `export DNS_INTERNAL_KEY='SECRET' HTTP_HOST=localhost; sudo -E .venv/bin/python3 dns_whitelist_server.py` (from project root) |
| 5 | After first device: run sync script with `API_BASE_URL` and `VPN_SYNC_KEY='SECRET'` |

---

# Part 2: User flow (what the end‑user does)

The VPN is **WireGuard** (free). The user never edits the config; they only import the file they download.

---

## Step 1: Install WireGuard (once per device)

- **Windows / macOS / Linux:** https://www.wireguard.com/install/
- **Android / iOS:** WireGuard app from the store

No account or payment; install and open the app.

---

## Step 2: Open your site and log in

- Go to your site (e.g. **http://localhost:8080** or **http://192.168.100.18:8080**).
- Log in (or register, then log in).

---

## Step 3: Add a device and download the config

- In the dashboard, **add a device** (e.g. “My laptop” or “Phone”). The site generates a WireGuard key for this device.
- **Add domains to the whitelist** for that device (e.g. `wikipedia.org`, `google.com`) so those sites work over the VPN.
- Click **Download config** (or similar) for that device. A `.conf` file is downloaded.

The downloaded file already contains:

- Your **server’s public key** and **endpoint** (from `config_vpn.php` or env).
- **DNS = 10.10.0.1** (your DNS whitelist server).
- The **client’s private key** (injected by the browser when downloading).

The user does **not** need to type or change anything.

---

## Step 4: Import the config in WireGuard

- In the WireGuard app: **Import from file** (or “Add tunnel” → “Import from file”).
- Choose the downloaded `.conf` file.
- The tunnel appears in the list (e.g. “My laptop”).

---

## Step 5: Connect

- Turn the tunnel **On** / **Connect**.
- When connected, all traffic goes through your server; only whitelisted domains resolve; everything else is blocked.

---

## User flow in one line

**Install WireGuard → open site → login → add device → add whitelist domains → download config → import file in WireGuard → connect.**

No manual editing of the config; the file has all required keys and settings.

---

# One-page “run server” cheat sheet

From project root: `/home/dev/Documents/projects/php-project/Ja1234/44`

```bash
# 1. MySQL
sudo systemctl start mysql

# 2. WireGuard
sudo wg-quick up wg0

# 3. PHP app (Terminal 1 – keep running)
export DB_PASS=localdev DNS_INTERNAL_KEY='YOUR_SECRET'
php -S 0.0.0.0:8080

# 4. DNS server (Terminal 2 – keep running)
export DNS_INTERNAL_KEY='YOUR_SECRET' HTTP_HOST=localhost
sudo -E .venv/bin/python3 dns_whitelist_server.py

# 5. After a user has added a device, sync peers (run when needed):
sudo API_BASE_URL=http://127.0.0.1:8080/api VPN_SYNC_KEY='YOUR_SECRET' ./scripts/sync_wireguard_peers.sh
```

Replace `YOUR_SECRET` with the same value everywhere (e.g. from `openssl rand -hex 32`).
