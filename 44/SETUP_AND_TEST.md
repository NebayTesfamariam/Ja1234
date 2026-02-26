# Setup and test guide

**For how to get the server public key and set up config_vpn.php (and how the VPN server gets client peers), see [VPN_SERVER_SETUP.md](VPN_SERVER_SETUP.md).**

---

## 1. How does the VPN know the server / domain?

**It’s in the downloaded config file.**

When a user downloads the WireGuard config (e.g. `wireguard-MyDevice.conf`), the file contains:

- **`Endpoint = ...`** – Your VPN server address (hostname or IP) and port, e.g. `vpn.ja1234.com:51820` or `192.168.1.100:51820`.
- **`PublicKey = ...`** – Your VPN server’s WireGuard public key.
- **`DNS = 10.10.0.1`** – DNS server (usually the same machine as the VPN).

You set these **once** in `config_vpn.php` (or env). After that, every downloaded file has the correct server and domain. The user does not type the server; they just import the file and connect.

So: **the “running website” (e.g. ja1234.com) is the web app.** The **VPN server** can be the same machine or another (e.g. vpn.ja1234.com). The config file tells the client which VPN server to use.

---

## 2. Who adds the allowed-websites list (whitelist)?

**Users** add domains for **their own devices** in the dashboard:

- Log in → select device → add domains (e.g. `google.com`, `wikipedia.org`). Only those domains (and subdomains) will resolve when the VPN is on.

**Admins** can:

- Manage users and devices (admin panel).
- See and manage whitelists for any user/device if the admin UI supports it.

So the **whitelist is per device and controlled by the user** (and optionally by admin). The DNS server uses that list to allow only those domains; everything else (and all porn/unwanted patterns) is blocked.

**Bug fix applied:** The DNS server could not fetch the whitelist before (no auth). It now uses a shared secret: set **`DNS_INTERNAL_KEY`** to the same value on the PHP server and when starting the DNS server so the DNS script can call the whitelist API.

---

## 3. Is the VPN free? Do we need to add anything?

**WireGuard is free and open source.** You run your own VPN server; there is no subscription to “WireGuard the company.”

You need:

- A server (VPS or your own machine) with a public IP or hostname.
- WireGuard installed on that server.
- The DNS whitelist script (`dns_whitelist_server.py`) running on the same server (or reachable at the IP you put in the config as DNS).
- `config_vpn.php` (or env) filled with your server’s endpoint and public key.

No extra paid VPN service is required. You only pay for the server (if it’s a VPS).

---

## 4. How to test from localhost

### A. Install WireGuard

**On Linux (VPN server + DNS, e.g. same machine as PHP or a test VM):**

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install wireguard

# Generate server keys (once)
cd /etc/wireguard
sudo wg genkey | tee server_private.key | wg pubkey > server_public.key
sudo chmod 600 server_private.key
```

**On the client (your laptop/PC) – to test the downloaded config:**

- **Linux:** `sudo apt install wireguard`
- **Windows:** Download from https://www.wireguard.com/install/
- **macOS:** App Store “WireGuard”
- **Android/iOS:** WireGuard app from store

### B. Minimal WireGuard server config (for testing)

Create `/etc/wireguard/wg0.conf` on the **server**:

```ini
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = <paste content of server_private.key>
# Allow DNS and forwarding
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

Replace `eth0` with your server’s main interface if needed (e.g. `ens3`).

Start WireGuard:

```bash
sudo wg-quick up wg0
```

Add peers (clients) manually for testing, or use your PHP app to assign IPs (e.g. 10.10.0.2, 10.10.0.3) and use the dashboard to download the config.

### C. PHP app and config_vpn.php

1. Run the PHP app (e.g. `php -S localhost:8080` in the project root or use Apache).
2. Copy `config_vpn.php.example` to `config_vpn.php`.
3. In `config_vpn.php` set:
   - `$VPN_SERVER_ENDPOINT` = your server’s IP or hostname and port, e.g. `192.168.1.100:51820` or `vpn.local:51820`.
   - `$VPN_SERVER_PUBLIC_KEY` = content of `server_public.key` from the server.
   - `$VPN_DNS` = `10.10.0.1` (same as WireGuard server `Address`).

### D. DNS internal key (required for whitelist)

So the DNS server can fetch the whitelist from PHP:

1. **PHP server:** set env var `DNS_INTERNAL_KEY` to a long random string (e.g. `openssl rand -hex 32`).
2. **When starting the DNS server:** set the same value, e.g.:

   ```bash
   export DNS_INTERNAL_KEY="your-same-secret-here"
   sudo -E python3 dns_whitelist_server.py
   ```

   Or in `start_dns_server.sh`, export `DNS_INTERNAL_KEY` before running the Python script.

### E. Start DNS server (on the VPN server machine)

Must run on the machine that has the VPN interface `10.10.0.1` so DNS is reachable at that IP.

```bash
cd /path/to/44
export HTTP_HOST=localhost   # or your domain
export DNS_INTERNAL_KEY="same-secret-as-php"
sudo python3 dns_whitelist_server.py
# Or: sudo ./start_dns_server.sh
```

Ensure the PHP app is reachable from this machine at `http://localhost/44/api` (or the URL you set via `HTTP_HOST`).

### F. Database and first user/device

1. Run `php setup_database.php` (or import your schema).
2. Create a user and a device (or use the dashboard to register and add a device).
3. Add a few domains to the whitelist for that device (e.g. `google.com`, `wikipedia.org`).

### G. Download config and test

1. Log in to the dashboard.
2. Download the WireGuard config for the device. The file should contain:
   - Your server’s `Endpoint` and `PublicKey`.
   - `DNS = 10.10.0.1`.
   - The client’s private key (injected by the browser).
2. Import the file into the WireGuard client and connect.
3. Test:
   - Allowed site (e.g. google.com) should open.
   - Non-whitelisted site should not resolve (or get NXDOMAIN).
   - Porn/unwanted domains (from the blocklist) should be blocked even if someone added them to the whitelist.

### H. Optional: firewall / kill-switch

On the VPN server, scripts like `vpn_firewall_setup.sh` can force DNS through the VPN and block DoH/DoT/QUIC. Use them if you want stricter testing or production behaviour.

### I. Syncing clients to the VPN server

So that users (clients) can connect, the WireGuard server must have each client’s public key as a peer. Either:

- **Sync script (recommended):** On the VPN server, run periodically (e.g. cron every 2–5 minutes):
  ```bash
  sudo API_BASE_URL=https://ja1234.com/api VPN_SYNC_KEY=your-secret ./scripts/sync_wireguard_peers.sh
  ```
  See [VPN_SERVER_SETUP.md](VPN_SERVER_SETUP.md) and `api/get_active_peers.php`.

- **Manual:** Add each client with `sudo wg set wg0 peer <client_public_key> allowed-ips 10.10.0.x/32` (client public key and IP are in the database).

---

## 5. Summary checklist

| Item | Where / how |
|------|-------------|
| VPN server address | In downloaded config (`Endpoint`). Set once in `config_vpn.php`. |
| **Server public key** | Generated on VPN server: `wg genkey \| tee server_private.key \| wg pubkey > server_public.key` → put `cat server_public.key` in `config_vpn.php`. See [VPN_SERVER_SETUP.md](VPN_SERVER_SETUP.md). |
| Whitelist (allowed sites) | User (and optionally admin) in dashboard, per device. |
| Blocklist (porn/unwanted) | In `dns_whitelist_server.py` and `config_porn_block.php`. |
| DNS ↔ PHP | Set **`DNS_INTERNAL_KEY`** on both PHP and DNS server (same value). |
| Clients on VPN server | Run **`scripts/sync_wireguard_peers.sh`** on VPN server (cron) with `API_BASE_URL` and `VPN_SYNC_KEY`. |
| VPN cost | Free (WireGuard). You only need a server. |

---

## 6. Localhost vs deployed (summary)

| Environment | PHP app | VPN server | config_vpn.php |
|-------------|---------|------------|----------------|
| **Localhost** | e.g. `http://localhost/44` or `php -S` | Same machine or VM; use LAN IP for Endpoint so the client can reach it | `$VPN_SERVER_ENDPOINT` = LAN IP or `192.168.x.x:51820`; `$VPN_SERVER_PUBLIC_KEY` from `wg pubkey < server_private.key` on VPN server |
| **Deployed** | e.g. `https://ja1234.com` | VPS or same host; use hostname e.g. `vpn.ja1234.com` | Set `$VPN_SERVER_ENDPOINT` or leave empty (auto `vpn.ja1234.com:51820`); same server public key |

Run the **sync script** on the VPN server (cron) so new devices from the app get added as WireGuard peers. See [VPN_SERVER_SETUP.md](VPN_SERVER_SETUP.md).

---

## 7. If something doesn’t work

- **Config has placeholder server/key:** Fill `config_vpn.php` (or env) and re-download the config.
- **No sites load with VPN on:** DNS server must be running on `10.10.0.1` and reachable; user must have domains in the whitelist; `DNS_INTERNAL_KEY` must match so the DNS can fetch the whitelist.
- **Porn/unwanted sites still load:** Ensure DNS is really used (config has `DNS = 10.10.0.1`) and the DNS script is running and calling the PHP API with the internal key. Check DNS server logs.
