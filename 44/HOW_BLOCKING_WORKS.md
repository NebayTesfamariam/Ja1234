# How Blocking Works (VPN Config vs Server)

## Short answer

- **The downloaded WireGuard `.conf` file alone does NOT block any websites.**
- **Blocking is done on the server.** The VPN server must run the DNS whitelist service. The `.conf` file only sends the user’s traffic (and DNS) to that server so the server can enforce the rules.

---

## What the `.conf` file does

When the user imports the file and connects:

1. All their traffic goes through your VPN (full-tunnel: `AllowedIPs = 0.0.0.0/0`).
2. Their DNS is set to `DNS = 10.10.0.1` (your VPN server).

So the file only **routes** traffic and DNS to your server. It does not contain a blocklist and does not block Pornhub or anything else by itself.

---

## Where blocking actually happens: the server

Blocking is done by the **DNS whitelist server** running on your **VPN server** (the machine that has the WireGuard interface and the IP `10.10.0.1` for DNS).

### On the VPN server you must run:

1. **WireGuard**  
   So clients can connect and get an IP in `10.10.0.0/24` (and so the DNS IP `10.10.0.1` is reachable).

2. **DNS whitelist server**  
   The Python script `dns_whitelist_server.py`:
   - Listens on UDP port 53 (on `10.10.0.1` or the VPN gateway).
   - For each DNS query, gets the client’s VPN IP → calls your PHP API to get **device_id** and then the **whitelist** for that device.
   - **Only whitelisted domains** are resolved; everything else gets **NXDOMAIN** (e.g. Pornhub, xvideos, etc. don’t resolve = blocked).
   - Porn-style domains are **always blocked** by the script (even if someone added them to the whitelist).

So:

- **Server maintains blocking:** the VPN server runs the DNS service that enforces “only whitelist + no porn”.
- **The file does not:** the `.conf` just makes the user use that server for DNS and traffic.

---

## End-to-end flow

```
User device (phone/laptop)
    ↓
Imports .conf → Connects to VPN → DNS = 10.10.0.1
    ↓
All DNS queries go to YOUR VPN server (10.10.0.1)
    ↓
dns_whitelist_server.py on VPN server:
  - Asks PHP API: “Which device is this IP?” → device_id
  - Asks PHP API: “What is the whitelist for this device?”
  - If domain in whitelist and not porn → resolve (e.g. via 8.8.8.8) and return IP
  - Otherwise → return NXDOMAIN (blocked)
```

So:

- **PHP app (ja1234.com):** stores users, devices, whitelists; generates the `.conf` with `DNS = 10.10.0.1`.
- **VPN server:** runs WireGuard + `dns_whitelist_server.py` so that when the user is connected, **only** allowed sites resolve; Pornhub and other unwanted sites are blocked by the server, not by the file.

---

## What you need to run where

| Where              | What to run |
|--------------------|-------------|
| **Web/PHP server** (e.g. ja1234.com) | PHP app, MySQL, `config_vpn.php` (or env) with VPN server endpoint and public key. |
| **VPN server** (the host that will be `10.10.0.1` for DNS) | 1) WireGuard server, 2) `dns_whitelist_server.py` (e.g. `sudo ./start_dns_server.sh` or `sudo python3 dns_whitelist_server.py`). Set `HTTP_HOST` (or API URL) so the DNS script can call your PHP API. |

So: **the file does not restrict unwanted/Pornhub sites by itself; the server that runs the DNS whitelist does. You must run that on the VPN server.**

---

## Quick checklist (VPN server)

- [ ] WireGuard installed and running; clients get IPs in `10.10.0.0/24`; gateway/DNS = `10.10.0.1`.
- [ ] `dns_whitelist_server.py` running on that machine (e.g. `sudo ./start_dns_server.sh`), listening on port 53 (on `10.10.0.1`).
- [ ] DNS script can reach your PHP API (`HTTP_HOST` or `API_BASE_URL` set correctly in the script’s environment).
- [ ] (Optional) Firewall/kill-switch so VPN clients can’t bypass DNS (e.g. block DoH/DoT/QUIC); see `vpn_firewall_setup.sh` if you use it.

Once this is in place, every user who connects with the downloaded file will have their DNS (and thus Pornhub and other unwanted sites) restricted by the server; the file itself only connects them to that server.
