# What This Project Is — And The Full Workflow

## In one sentence

**This is a “porn-free internet” system:** the website lets people sign up, log in, and get a **VPN config file**. When they use that VPN on their phone or computer, **only websites you explicitly allow (whitelist) work** — everything else (including all porn) is blocked at the network level.

---

## The big idea (no WireGuard jargon first)

- **Goal:** Block porn (and everything else) on devices that use this system, in a way that’s hard to bypass.
- **How:**  
  - **Default = block everything.**  
  - **Only domains you add to a “whitelist” are allowed.**  
  - So: no whitelist entry = site doesn’t load (no block page, no “adult content” message — the site simply doesn’t resolve).
- **Why not just a browser extension or app?**  
  - Extensions and apps can be removed or bypassed.  
  - Here, blocking happens on the **network**: all traffic goes through **your** VPN server and **your** DNS. If a domain isn’t on the whitelist, the DNS says “this domain doesn’t exist” (NXDOMAIN). Apps and browsers can’t get around that without breaking the VPN.

So the flow is:

1. User signs up / logs in on **your website**.
2. User gets a **device** and downloads a **.config file** (VPN configuration).
3. User **installs the VPN (WireGuard)** on their device and imports that .config file.
4. User (or you) adds **allowed domains** (e.g. google.com, wikipedia.org) in the website’s whitelist for that device.
5. User **turns the VPN on** on the device.
6. From then on: only whitelisted domains work; everything else (including porn) is blocked.

---

## What is WireGuard? (Simple version)

- **WireGuard** is a **VPN** (Virtual Private Network): it sends all internet traffic from the device through a **tunnel** to **your server**.
- On the device you install the **WireGuard app** and add a **config** (the .config file you downloaded). When the user turns the VPN **on**:
  - All traffic goes: **Device → Your VPN server → Internet** (and back).
  - DNS (which resolves site names like google.com) is forced to go to **your** DNS server (e.g. `10.10.0.1`), which only allows domains that are on the whitelist for that device.
- So:
  - **WireGuard** = the “tunnel” that forces traffic through your system.
  - **The .config file** = the settings for that tunnel (server address, keys, “use this DNS”, “send all traffic through VPN”).  
  Without the VPN (and this config), the device would use normal internet and your blocking wouldn’t apply.

---

## Full workflow (what happens step by step)

### 1. You (admin) set up the system

- **Website** (this PHP project): users can register, log in, see their devices, manage whitelist.
- **Database**: stores users, devices, whitelist per device, subscriptions.
- **VPN server** (separate machine): runs WireGuard, gives each device an IP (e.g. `10.10.0.x`).
- **DNS server** (on or next to VPN server): runs `dns_whitelist_server.py`. It answers DNS only for domains that are on the whitelist for the device asking (identified by VPN IP).
- **Firewall** (on VPN server): makes sure VPN clients can’t bypass your DNS (e.g. block DoH/DoT, force DNS to your resolver).

For “everything fixed easily and simply” for the end user, **you** must have this server side in place (VPN + DNS + firewall). The website alone is the “control panel”; the actual blocking happens on the VPN/DNS server.

### 2. User signs up / subscribes

- User goes to the site (e.g. subscribe page or register).
- Chooses a plan (Basic / Family / Premium), enters email and password.
- After signup, they can log in.

### 3. User logs in and gets a “device”

- User logs in on the website.
- The site can **auto-create a device** for this browser/device, or the user goes to “Devices” and a device is created.
- Each “device” = one phone, laptop, tablet, etc. that will use the VPN. Each has its own whitelist.

### 4. User downloads the .config file

- On the website, under **Devices**, user clicks **“Download WireGuard Config”** (or similar) for their device.
- The browser downloads a **.conf** file (WireGuard config).  
  That file tells the WireGuard app:
  - **Where** your VPN server is (endpoint).
  - **Which IP** this device gets in the VPN (e.g. `10.10.0.x`).
  - **Use your DNS** (e.g. `10.10.0.1`).
  - **Send all traffic through the VPN** (full tunnel).

So: **the .config file is the VPN settings for that one device.** Nothing is “fixed” or “blocked” until the user actually installs WireGuard and uses this config.

### 5. What the user must do after downloading the .config file

For blocking to work on that device, the user (or you) must:

1. **Install the WireGuard app** on that device (phone, PC, Mac, etc.).
2. **Import the .config file** in the app (e.g. “Create from file” / “Import” and select the downloaded file).
3. **Add allowed sites** for that device on the website (Whitelist): e.g. `google.com`, `wikipedia.org`, `youtube.com`, etc. If the whitelist is empty, **no** websites will work when the VPN is on (by design).
4. **Turn the VPN on** in the WireGuard app.

After that:

- All traffic from that device goes through your VPN server.
- DNS goes to your DNS server.
- Your DNS server asks the website API: “What’s the whitelist for this device?” (identified by VPN IP).
- Only domains on that whitelist get a real DNS answer; all others get “domain doesn’t exist” → **no porn, no unlisted sites.**

So: **downloading the .config is only step 1 of “using” the system. Installing WireGuard, importing the config, adding whitelist domains, and turning the VPN on is what makes “no pornographic content at all” work.**

---

## Summary table

| Step | Who | What |
|------|-----|------|
| 1 | Admin | Deploy website + VPN server + DNS server + firewall. |
| 2 | User | Sign up / subscribe on the website. |
| 3 | User | Log in; a “device” is created (or they open “Devices”). |
| 4 | User | Download the **.config file** for that device from the website. |
| 5 | User | Install **WireGuard** on the device and **import** the .config file. |
| 6 | User / Admin | On the website, add **whitelist domains** for that device (e.g. google.com, youtube.com). |
| 7 | User | Turn **VPN on** in the WireGuard app. |
| 8 | — | From then on: only whitelisted sites work; porn and everything else are blocked. |

---

## What you have running now (local)

- **Website (PHP)** — running with `./run.sh` or `php -S localhost:8000 -t 44`.
- **Database** — users, devices, whitelist, subscriptions.

So right now you can:

- Register / log in.
- See devices and download a **.config file** for each device.

The .config file will point to a **VPN server** (e.g. `your-vpn-server.com:51820`). That VPN server is **not** running on your laptop; it’s meant to be a separate server (VPS or another machine) where you:

- Run WireGuard.
- Run the DNS whitelist server (`dns_whitelist_server.py`).
- Run the firewall scripts so DNS can’t be bypassed.

So:

- **For local testing:** The website and “download .config” work; you can test login, devices, and whitelist. Actual blocking won’t happen until a real VPN + DNS server is set up and the .config points to it.
- **For “everything fixed so they see no porn at all”:** You need that VPN server + DNS + firewall in production, and users must do the full flow: download .config → install WireGuard → import config → add whitelist → turn VPN on.

---

## Quick answers to your questions

- **What is the project about?**  
  A system to give users “porn-free internet” by forcing their traffic through your VPN and only allowing domains you put on a whitelist; everything else (including porn) is blocked at DNS/network level.

- **What is the workflow?**  
  Sign up → Log in → Get a device → Download .config → Install WireGuard → Import .config → Add allowed sites (whitelist) on the website → Turn VPN on → Only those sites work; no porn.

- **What is WireGuard / the .config file?**  
  WireGuard is the VPN. The .config file is the VPN settings for one device (server address, keys, “use our DNS”, “send all traffic through VPN”). Without installing WireGuard and turning the VPN on, blocking doesn’t apply.

- **After downloading the .config file, what needs to be done?**  
  1) Install WireGuard on the device.  
  2) Import the .config in WireGuard.  
  3) On the website, add whitelist domains for that device.  
  4) Turn the VPN on in WireGuard.  
  Then that device only sees whitelisted sites and no porn.

- **How is “no porn at all” achieved?**  
  By “whitelist-only” + VPN: only domains you add are allowed; all other domains (including every porn site) get “domain doesn’t exist” from your DNS. So it’s not “filtering porn” — it’s “allowing only a fixed list of sites.”
