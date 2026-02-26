# VPN server setup – server key, config_vpn.php, and deployment

## 1. Two different key pairs (don’t mix them)

| Key pair | Where it lives | What goes where |
|----------|----------------|-----------------|
| **Server** | On the **VPN server** machine (the one running WireGuard) | **Server private key** stays on the server only. **Server public key** goes in `config_vpn.php` and in every **downloaded** client config under `[Peer] PublicKey = ...`. |
| **Client** | Generated in the **user’s browser** when they download the config | **Client private key** is injected into the downloaded `.conf` only (never sent to your server). **Client public key** is sent to your app and stored in the DB; the **VPN server** must have it as a **peer** so the client can connect. |

So:

- The **private key in the downloaded config** is the **client’s** (one per device), created in the browser.
- The **server public key** in `config_vpn.php` is from the **VPN server’s** own key pair. You create that key pair **once on the VPN server** and copy only the **public** part into `config_vpn.php`.

---

## 2. How to get the server public key (on the VPN server)

Run these on the **machine that will run WireGuard** (VPN server), not on the PHP/web server (unless they are the same).

### Option A: New setup – generate a new key pair

```bash
# Create a directory (e.g. in /etc/wireguard or your home)
mkdir -p /etc/wireguard
cd /etc/wireguard

# Generate server private key and derive public key
wg genkey | tee server_private.key | wg pubkey > server_public.key
chmod 600 server_private.key

# Show the public key (this is what you put in config_vpn.php)
cat server_public.key
```

Copy the **single line** from `server_public.key` (base64, no spaces) into `config_vpn.php` as `$VPN_SERVER_PUBLIC_KEY`.  
**Do not** put `server_private.key` anywhere except on the VPN server (and never in the PHP project).

### Option B: WireGuard already installed – read from existing config

If you already have `/etc/wireguard/wg0.conf`:

```bash
# Show the public key of the interface (if WireGuard is running)
sudo wg show wg0 public-key
```

Or open `/etc/wireguard/wg0.conf` and look at the `[Interface]` section. If you see:

- `PrivateKey = ...` → that’s the server private key (keep it secret).
- There may be a comment like `# PublicKey = ...` or you can derive it:

```bash
# If you have the private key in a file
wg pubkey < /etc/wireguard/server_private.key
```

Use that **public** key value in `config_vpn.php`.

---

## 3. How to add the server public key to the project

1. Copy the example config:
   ```bash
   cp config_vpn.php.example config_vpn.php
   ```
2. Edit `config_vpn.php`:
   - Set **`$VPN_SERVER_PUBLIC_KEY`** to the **exact** server public key (one line, base64), e.g.:
     ```php
     $VPN_SERVER_PUBLIC_KEY = 'xYz123AbC...base64...=';
     ```
   - Set **`$VPN_SERVER_ENDPOINT`** to the host (or IP) and port of the VPN server, e.g. `vpn.ja1234.com:51820` or `192.168.1.100:51820`. If you leave it empty and set `BASE_URL`, the app can auto-derive `vpn.<your-domain>:51820`.
   - Set **`$VPN_DNS`** (e.g. `10.10.0.1`) if different from the default.

After this, every **downloaded** config will contain the correct `[Peer] PublicKey = ...` and `Endpoint = ...`. The **client** private key is still generated in the browser and injected into the file when the user downloads it.

---

## 4. How the VPN server gets client peers (so users can connect)

Each user’s device has a **client public key** and an IP (e.g. `10.10.0.x`) stored in your database. The WireGuard **server** must have each of these as a **peer**; otherwise the client cannot connect.

Two ways to do that:

### A. Sync peers from the app (recommended)

Use the API and script provided in the project:

1. **On the PHP server:** set the same secret for the sync API, e.g. in env:
   ```bash
   # Use the same secret as DNS_INTERNAL_KEY or a dedicated one
   VPN_SYNC_KEY=your-secret
   ```
2. **On the VPN server** (where WireGuard runs), run the sync script periodically (e.g. cron every 1–5 minutes):

   ```bash
   sudo API_BASE_URL=https://ja1234.com/api VPN_SYNC_KEY=your-secret ./scripts/sync_wireguard_peers.sh
   ```
   If the script is not executable, run first: `chmod +x scripts/sync_wireguard_peers.sh`

   Or with `DNS_INTERNAL_KEY`:

   ```bash
   sudo API_BASE_URL=https://ja1234.com/api DNS_INTERNAL_KEY=your-secret ./scripts/sync_wireguard_peers.sh
   ```

   The script calls `api/get_active_peers.php` and runs `wg set wg0 peer <public_key> allowed-ips <ip>/32` for each active device. So the server public key stays in `config_vpn.php`; client public keys come from the DB via this sync.

### B. Manual (for testing)

On the VPN server, add one client by hand:

```bash
sudo wg set wg0 peer <CLIENT_PUBLIC_KEY> allowed-ips 10.10.0.2/32
```

You can get `CLIENT_PUBLIC_KEY` from the database (`devices.wg_public_key`) for that device.

---

## 5. Minimal WireGuard server config (for reference)

On the **VPN server**, create or edit `/etc/wireguard/wg0.conf`:

```ini
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = <paste content of server_private.key here>
# Optional: forward and NAT so clients can reach the internet
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

Replace `eth0` with your server’s main network interface if needed (`ip a` to check).  
Then:

```bash
sudo wg-quick up wg0
```

Peers (clients) can be added manually with `wg set` or via the sync script above.

---

## 6. Localhost vs deployed

### Localhost (testing)

- **PHP app:** e.g. `http://localhost/44` or `php -S localhost:8080`.
- **VPN server:** can be the same machine (Linux) or another machine/VM. If same machine, use the LAN IP or `127.0.0.1` for testing (e.g. `$VPN_SERVER_ENDPOINT = '192.168.1.100:51820'` or your LAN IP).
- **config_vpn.php:** set `$VPN_SERVER_PUBLIC_KEY` from the VPN server’s `server_public.key`; set `$VPN_SERVER_ENDPOINT` to the IP (and port) where WireGuard listens so the client can reach it (often your machine’s LAN IP, not 127.0.0.1, if the client is another device).
- **DNS server:** run `dns_whitelist_server.py` on the same machine as the VPN (so DNS `10.10.0.1` is reachable). Set `HTTP_HOST` and `DNS_INTERNAL_KEY` so it can call the PHP API.
- **Sync:** run `scripts/sync_wireguard_peers.sh` from the VPN server with `API_BASE_URL=http://your-lan-ip/44/api` (or your PHP URL) and the same key.

### Deployed (production)

- **PHP app:** e.g. `https://ja1234.com` (your web server).
- **VPN server:** e.g. a VPS or the same host. Use a hostname like `vpn.ja1234.com` and add a DNS A record to the VPN server’s IP. In `config_vpn.php` you can set `$VPN_SERVER_ENDPOINT = 'vpn.ja1234.com:51820'` or leave it empty and set `BASE_URL=https://ja1234.com` so the app uses `vpn.ja1234.com:51820`.
- **config_vpn.php:** `$VPN_SERVER_PUBLIC_KEY` = server public key from the VPN server; `$VPN_DNS` = `10.10.0.1` (unless you use another DNS IP).
- **Sync:** on the VPN server, cron the sync script with `API_BASE_URL=https://ja1234.com/api` and `VPN_SYNC_KEY` (or `DNS_INTERNAL_KEY`).

---

## 7. Quick checklist

| Step | Where | What to do |
|------|--------|------------|
| 1. Server key pair | VPN server | `wg genkey \| tee server_private.key \| wg pubkey > server_public.key` |
| 2. Server public key | PHP project | Put `cat server_public.key` output into `config_vpn.php` → `$VPN_SERVER_PUBLIC_KEY` |
| 3. Endpoint | config_vpn.php | Set `$VPN_SERVER_ENDPOINT` (e.g. `vpn.ja1234.com:51820`) or rely on auto-derive from `BASE_URL` |
| 4. WireGuard | VPN server | Create `wg0.conf` with server `PrivateKey`, bring up `wg0` |
| 5. Peers | VPN server | Run `scripts/sync_wireguard_peers.sh` with `API_BASE_URL` and `VPN_SYNC_KEY` (or `DNS_INTERNAL_KEY`) |
| 6. DNS | VPN server | Run `dns_whitelist_server.py` with `DNS_INTERNAL_KEY` and `HTTP_HOST` |

After this, the **downloaded** config file contains the correct server endpoint and server public key; the **client** private key is generated and injected in the browser, and the VPN server knows all clients via the sync script. The project is then correctly configured end to end.
