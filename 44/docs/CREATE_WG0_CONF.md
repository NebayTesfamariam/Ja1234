# Create wg0.conf when the file doesn't exist

If `sudo wg-quick up wg0` says **wg0.conf does not exist**, create it once.

---

## Option A: Run the script with `bash` (no execute bit needed)

```bash
sudo bash /home/dev/Documents/projects/php-project/Ja1234/44/scripts/create_wg0_conf.sh
```

Then:

```bash
sudo wg-quick up wg0
```

---

## Option B: One-liner (no script file needed)

Requires the private key in `/etc/wireguard/server_private.key`. Run this **whole block** (one paste):

```bash
sudo bash -c 'PRIV=$(cat /etc/wireguard/server_private.key); IFACE=$(ip route show default 2>/dev/null | awk "/default/ {print \$5}"); [ -z "$IFACE" ] && IFACE=eth0; cat > /etc/wireguard/wg0.conf << EOF
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = $PRIV

PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o $IFACE -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o $IFACE -j MASQUERADE
EOF
chmod 600 /etc/wireguard/wg0.conf; echo "Created /etc/wireguard/wg0.conf (interface: $IFACE)"'
```

Then:

```bash
sudo wg-quick up wg0
```

---

## Option C: Manual (copy-paste into nano)

1. Get your private key (one line):
   ```bash
   sudo cat /etc/wireguard/server_private.key
   ```
2. Get your network interface:
   ```bash
   ip route | grep default
   ```
   Use the word after `dev` (e.g. `enp0s3` or `eth0`).

3. Create the config:
   ```bash
   sudo nano /etc/wireguard/wg0.conf
   ```
   Paste the block below. Replace **PASTE_PRIVATE_KEY_HERE** with the key from step 1, and **eth0** with your interface from step 2 if different.

```ini
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = PASTE_PRIVATE_KEY_HERE

PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

Save (Ctrl+O, Enter) and exit (Ctrl+X), then:

```bash
sudo wg-quick up wg0
```
