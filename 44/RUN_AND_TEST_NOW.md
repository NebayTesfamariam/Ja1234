# Run the project and test VPN (database already done)

Use this after `setup_database.php` has run successfully. All commands from project root: `/home/dev/Documents/projects/php-project/Ja1234/44`.

---

## 1. WireGuard server keys (once)

```bash
sudo bash -c 'mkdir -p /etc/wireguard && cd /etc/wireguard && wg genkey | tee server_private.key | wg pubkey > server_public.key && chmod 600 server_private.key && cat server_public.key'
```

Copy the **server public key** (you’ll need it for step 3):

```bash
sudo cat /etc/wireguard/server_public.key
```

Get your **LAN IP** (for VPN endpoint):

```bash
hostname -I | awk '{print $1}'
```

---

## 2. WireGuard interface (wg0.conf)

```bash
sudo nano /etc/wireguard/wg0.conf
```

Paste this and edit:

- Replace `<PASTE_SERVER_PRIVATE_KEY>` with the **full content** of `sudo cat /etc/wireguard/server_private.key`.
- Replace `eth0` with your main interface if different (check with `ip route | grep default` — e.g. `enp0s3`).

```ini
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = <PASTE_SERVER_PRIVATE_KEY>

PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

Start WireGuard:

```bash
sudo wg-quick up wg0
```

---

## 3. VPN config for the PHP app

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
cp config_vpn.php.example config_vpn.php
nano config_vpn.php
```

Set:

- **VPN_SERVER_ENDPOINT** = `YOUR_LAN_IP:51820` (e.g. `192.168.1.100:51820`)
- **VPN_SERVER_PUBLIC_KEY** = the line from `server_public.key`
- **VPN_DNS** = `10.10.0.1`

Save and exit.

---

## 4. One secret for DNS + sync

```bash
openssl rand -hex 32
```

Use this same value in steps 5, 6 and 7 (e.g. set `MY_SECRET=output_of_above`).

---

## 5. Start the PHP app (terminal 1)

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DB_PASS=localdev
export DNS_INTERNAL_KEY='YOUR_SECRET_FROM_STEP_4'
php -S 0.0.0.0:8080
```

Leave it running. Open **http://localhost:8080** in the browser.

---

## 6. Start the DNS server (terminal 2)

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
export DNS_INTERNAL_KEY='YOUR_SECRET_FROM_STEP_4'
export HTTP_HOST=localhost
sudo -E .venv/bin/python3 dns_whitelist_server.py
```

If `.venv` doesn’t exist:

```bash
python3 -m venv .venv
.venv/bin/pip install -r requirements.txt
```

Then run the `sudo -E .venv/bin/python3 ...` line again. Leave this terminal open.

---

## 7. Sync WireGuard peers (after you have a device — run once or after adding devices)

```bash
cd /home/dev/Documents/projects/php-project/Ja1234/44
sudo API_BASE_URL=http://127.0.0.1:8080/api VPN_SYNC_KEY='YOUR_SECRET_FROM_STEP_4' ./scripts/sync_wireguard_peers.sh
```

If the script isn’t executable: `chmod +x scripts/sync_wireguard_peers.sh`

---

## 8. Use the app and get the VPN config

1. In the browser go to **http://localhost:8080**.
2. **Register** a new account or log in as **admin@test.com** / **admin123**.
3. **Add a device** (e.g. “My PC”). The site will generate a WireGuard key for it.
4. **Add domains to the whitelist** for that device, e.g. `wikipedia.org`, `google.com`.
5. **Download the WireGuard config** for that device (button like “Download config”).
6. Run the **sync script** (step 7) so this device is added as a peer on the VPN server.
7. **Import** the downloaded `.conf` into the WireGuard app (on this PC or phone) and **connect**.

---

## 9. Test blocking

With the VPN **connected**:

- Open **https://wikipedia.org** (or another whitelisted domain) → should load.
- Open a site **not** in the whitelist (e.g. **https://facebook.com**) → should not resolve (blocked).

Optional DNS check:

```bash
nslookup wikipedia.org 10.10.0.1   # should return an IP
nslookup facebook.com 10.10.0.1   # should return NXDOMAIN or fail
```

---

## Quick reference

| What            | Command / where |
|-----------------|------------------|
| PHP app         | `DB_PASS=localdev DNS_INTERNAL_KEY=... php -S 0.0.0.0:8080` |
| DNS server      | `sudo -E DNS_INTERNAL_KEY=... HTTP_HOST=localhost .venv/bin/python3 dns_whitelist_server.py` |
| Sync peers      | `sudo API_BASE_URL=http://127.0.0.1:8080/api VPN_SYNC_KEY=... ./scripts/sync_wireguard_peers.sh` |
| WireGuard up    | `sudo wg-quick up wg0` |
| WireGuard down  | `sudo wg-quick down wg0` |
