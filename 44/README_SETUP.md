# 🚀 Complete System Setup Guide

## Quick Start (5 Minutes)

### Step 1: Run Setup Script
```bash
chmod +x setup_complete_system.sh
./setup_complete_system.sh
```

### Step 2: Setup Database
```bash
php setup_database.php
```

### Step 3: Start DNS Server
```bash
sudo ./start_dns_server.sh
```

### Step 4: Setup Firewall (on VPN server)
```bash
sudo ./vpn_firewall_setup.sh
```

### Step 5: Test System
```bash
./test_empty_whitelist.sh
```

---

## Detailed Setup

### 1. Prerequisites

#### Required Software
- ✅ PHP 7.4+ (XAMPP includes this)
- ✅ MySQL/MariaDB (XAMPP includes this)
- ✅ Python 3.6+
- ✅ Root access (for DNS server and firewall)

#### Install Python Dependencies
```bash
pip3 install requests
```

---

### 2. Database Setup

#### Option A: Automatic Setup
```bash
php setup_database.php
```

This will:
- Create database `pornfree`
- Create all required tables
- Create default admin user (admin@test.com / admin123)

#### Option B: Manual Setup
```bash
# Create database
mysql -u root -e "CREATE DATABASE pornfree CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Import SQL file
mysql -u root pornfree < ALL_DATABASE.sql
```

---

### 3. DNS Server Setup

#### Start DNS Server
```bash
sudo ./start_dns_server.sh
```

**Important:**
- Must run as root (port 53 requires root)
- Must run on VPN server (10.10.0.1)
- Keeps running until stopped (Ctrl+C)

#### Verify DNS Server
```bash
# Check if running
sudo netstat -tuln | grep :53

# Test DNS query
nslookup google.com 10.10.0.1
```

---

### 4. Firewall Setup (VPN Server)

#### Run Firewall Setup
```bash
sudo ./vpn_firewall_setup.sh
```

This will:
- Force DNS to VPN resolver (10.10.0.1)
- Block DoH (DNS-over-HTTPS)
- Block DoT (DNS-over-TLS)
- Block QUIC (UDP 443)
- Enable kill-switch

#### Verify Firewall Rules
```bash
sudo iptables -S FORWARD | grep "10.10.0.0/24"
```

---

### 5. System Testing

#### Run All Tests
```bash
# Test 1: Empty Whitelist
./test_empty_whitelist.sh

# Test 2: Single Domain
./test_single_domain.sh

# Test 3: VPN Kill-Switch
./test_vpn_killswitch.sh

# Test 4: DNS Bypass
./test_dns_bypass.sh
```

#### Web Interface Tests
- **Quick Test**: `http://localhost/44/QUICK_SYSTEM_TEST.html`
- **System Health**: `http://localhost/44/api/system_check.php`
- **Compliance**: `http://localhost/44/verify_compliance.html`

---

## Verification Checklist

### ✅ Code & Files
- [x] All API endpoints exist
- [x] All scripts are executable
- [x] DNS server script exists
- [x] Firewall scripts exist

### ✅ Database
- [ ] Database `pornfree` exists
- [ ] Tables created (users, devices, whitelist, subscriptions)
- [ ] Admin user exists
- [ ] Database connection works

### ✅ DNS Server
- [ ] Python requests library installed
- [ ] DNS server script is executable
- [ ] DNS server is running (port 53)
- [ ] DNS server can query API

### ✅ Firewall
- [ ] Firewall scripts executed
- [ ] DNS forcing rules active
- [ ] DoH/DoT blocking active
- [ ] QUIC blocking active
- [ ] Kill-switch active

### ✅ VPN
- [ ] WireGuard server running
- [ ] VPN clients can connect
- [ ] `AllowedIPs = 0.0.0.0/0` configured
- [ ] `DNS = 10.10.0.1` configured

---

## Troubleshooting

### Database Connection Failed
```bash
# Start XAMPP MySQL
# Or start MySQL service
sudo service mysql start

# Check MySQL status
mysql -u root -e "SELECT 1"
```

### DNS Server Won't Start
```bash
# Check if port 53 is available
sudo netstat -tuln | grep :53

# Check Python
python3 --version

# Install requests
pip3 install requests
```

### Firewall Rules Not Active
```bash
# Check current rules
sudo iptables -S FORWARD

# Re-run setup
sudo ./vpn_firewall_setup.sh
```

---

## Default Credentials

### Admin User
- **Email**: admin@test.com
- **Password**: admin123

**⚠️ Change this in production!**

---

## Next Steps

1. **Change Admin Password**: Update in database
2. **Add Real Users**: Via admin panel
3. **Configure VPN**: Generate WireGuard configs
4. **Add Whitelist Domains**: Via admin panel
5. **Monitor System**: Use monitoring dashboard

---

## Support

- **Documentation**: `TECHNICAL_DOCUMENTATION.md`
- **Status Check**: `SYSTEM_STATUS.md`
- **Quick Test**: `QUICK_SYSTEM_TEST.html`

---

**Setup Complete!** 🎉
