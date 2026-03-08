# WireGuard: Server public key and env-only setup

This guide explains how to use **environment variables** for the VPN server public key and related settings so that:

- **Users** only need to download the config file and import it in the WireGuard app (browser or mobile). No manual editing.
- **You** configure everything once on the server (and optionally on the VPN server); no `config_vpn.php` file required if you prefer env-only.

---

## 1. Which “public key” goes in the env?

The key you store in the environment is the **VPN server’s public key**, not the user’s.

| Key | Who generates it | Where it lives | Purpose |
|-----|------------------|----------------|---------|
| **Server key pair** | You, on the VPN server (once) | Private key: VPN server only. **Public key: env `VPN_SERVER_PUBLIC_KEY`** | Every downloaded client config gets `[Peer] PublicKey = <this value>` so the client can connect to your VPN. |
| **Client key pair** | User’s browser (per device) | Private key: only in the downloaded .conf (injected by the app). Public key: your DB + VPN server as peer | Identifies the device and allows the VPN server to accept the connection. |

So: **create the server key pair on the VPN server**, then put only the **server public key** in `VPN_SERVER_PUBLIC_KEY`. The app uses it when generating the config file that users download.

---

## 2. Create the server key (on the VPN server)

Run this on the **machine that runs WireGuard** (VPN server), not on the PHP/web server (unless they are the same):

```bash
sudo mkdir -p /etc/wireguard
cd /etc/wireguard
wg genkey | tee server_private.key | wg pubkey > server_public.key
chmod 600 server_private.key
cat server_public.key
```

- **Keep `server_private.key`** on the VPN server only; use it in `/etc/wireguard/wg0.conf` under `[Interface]` as `PrivateKey = ...`.
- **Copy the single line** from `server_public.key` (base64) — this is the value for `VPN_SERVER_PUBLIC_KEY`.

If WireGuard is already running:

```bash
sudo wg show wg0 public-key
```

Use that output as `VPN_SERVER_PUBLIC_KEY`.

---

## 3. Environment variables (all in one place)

Set these where your PHP app runs (e.g. Apache `SetEnv`, php-fpm `env`, systemd `Environment=`, or `export` before `php -S`). The app reads them **even when `config_vpn.php` does not exist**.

| Variable | Required | Example | Purpose |
|----------|----------|---------|---------|
| **VPN_SERVER_PUBLIC_KEY** | Yes | `xYz123AbC...base64...=` | Server public key from step 2. Used in every downloaded config under `[Peer] PublicKey`. |
| **VPN_SERVER_ENDPOINT** | Recommended | `vpn.ja1234.com:51820` or `192.168.1.100:51820` | Host (or IP) and port where WireGuard listens. If unset, app can derive `vpn.<HTTP_HOST>:51820` when not localhost. |
| **VPN_DNS** | Optional | `10.10.0.1` | DNS IP in the config (default `10.10.0.1`). Should be the VPN server’s tunnel IP so DNS whitelist is used. |

Optional for auto-endpoint and production detection:

| Variable | Purpose |
|----------|---------|
| **BASE_URL** | e.g. `https://ja1234.com`. If `VPN_SERVER_ENDPOINT` is empty, endpoint becomes `vpn.<host>:51820` from this (or `HTTP_HOST`). |
| **HTTP_HOST** | Used for production vs localhost detection (e.g. DNS script and API URL). |

For sync and DNS (VPN server and PHP app):

| Variable | Purpose |
|----------|---------|
| **VPN_SYNC_KEY** or **DNS_INTERNAL_KEY** | Secret for sync script (`get_active_peers.php`) and for DNS server to call PHP API. Set the same on PHP server and when running the sync script / DNS server. |

---

## 4. Deployed (production) setup

- **PHP / web server** (e.g. ja1234.com):
  - Set env: `VPN_SERVER_PUBLIC_KEY`, `VPN_SERVER_ENDPOINT` (e.g. `vpn.ja1234.com:51820`), `VPN_DNS=10.10.0.1`, and optionally `BASE_URL=https://ja1234.com`, `DNS_INTERNAL_KEY`, `VPN_SYNC_KEY`.
  - You do **not** need `config_vpn.php` if all of these are in the environment.

- **VPN server** (e.g. vpn.ja1234.com):
  - Install WireGuard; use `server_private.key` in `wg0.conf`; set `Address = 10.10.0.1/24`, `ListenPort = 51820`.
  - Run **peer sync** (cron):  
    `sudo API_BASE_URL=https://ja1234.com/api VPN_SYNC_KEY=your-secret ./scripts/sync_wireguard_peers.sh`
  - Run **DNS whitelist server**:  
    `sudo HTTP_HOST=ja1234.com DNS_INTERNAL_KEY=your-secret python3 dns_whitelist_server.py`  
    (or your existing start script).

- **User flow**: Log in → add device (browser generates client key pair and sends public key to your app) → download config (app uses `VPN_SERVER_PUBLIC_KEY` and endpoint from env; browser injects client private key) → import in WireGuard → connect. No manual editing of the config.

---

## 5. Localhost (testing) setup

- **PHP app**: e.g. `http://localhost/44` or `php -S localhost:8080` with env set:
  - `VPN_SERVER_PUBLIC_KEY` = same server public key (from VPN server).
  - `VPN_SERVER_ENDPOINT` = address where the client can reach WireGuard, e.g. **LAN IP** (e.g. `192.168.1.100:51820`), not `127.0.0.1`, if the WireGuard client runs on another device.
  - `VPN_DNS=10.10.0.1`.

- **VPN server**: Can be the same machine or another. If same machine, use the machine’s LAN IP for `VPN_SERVER_ENDPOINT` so phones/other PCs can connect. Run WireGuard, sync script, and DNS script as above, with:
  - `API_BASE_URL=http://your-lan-ip/44/api` (or `http://localhost/44/api` if sync runs on same host).

- **Testing**: Create a device in the app, download the config (it will contain the server public key and endpoint from env), import in WireGuard on the client device, connect. Ensure the VPN server has the client as peer (sync script or manual `wg set`).

---

## 6. How the downloaded config is built

1. User requests the config (e.g. “Download WireGuard config”).
2. Server (PHP) sends a **template** with:
   - `[Interface]`: `Address = <device VPN IP>/32`, `DNS = <VPN_DNS>`, `PrivateKey = YOUR_PRIVATE_KEY_HERE`
   - `[Peer]`: `PublicKey = <VPN_SERVER_PUBLIC_KEY from env>`, `Endpoint = <VPN_SERVER_ENDPOINT>`, `AllowedIPs = 0.0.0.0/0`, etc.
3. The **browser** replaces `YOUR_PRIVATE_KEY_HERE` with the client’s private key (generated and stored in the browser when the device was added).
4. User saves the file and imports it in WireGuard. No need to edit anything if env vars are set correctly.

---

## 7. Checklist

- [ ] On VPN server: `wg genkey | tee server_private.key | wg pubkey > server_public.key`; use private key in `wg0.conf`.
- [ ] Set **VPN_SERVER_PUBLIC_KEY** (and optionally **VPN_SERVER_ENDPOINT**, **VPN_DNS**) in the environment of the PHP app.
- [ ] VPN server: WireGuard running; sync script in cron; DNS whitelist server running with **DNS_INTERNAL_KEY** / **HTTP_HOST**.
- [ ] User: add device → download config → import in WireGuard → connect.

After this, the “public key” you store in the environment is the server’s; the app uses it so every downloaded config is ready to import without manual editing.
