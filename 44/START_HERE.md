# 🚀 START HERE - Complete System Setup

## ⚡ Quick Start (5 Minutes)

### 1️⃣ Run Complete Setup
```bash
./setup_complete_system.sh
```

### 2️⃣ Setup Database
```bash
php setup_database.php
```

### 3️⃣ Start DNS Server
```bash
sudo ./start_dns_server.sh
```

### 4️⃣ Setup Firewall (on VPN server)
```bash
sudo ./vpn_firewall_setup.sh
```

### 5️⃣ Test Everything
```bash
./test_empty_whitelist.sh
```

---

## 📋 What Gets Set Up

### ✅ Automatic Setup
- PHP & MySQL verification
- Python & dependencies check
- File permissions
- Directory creation
- Script executability

### ✅ Database Setup
- Database creation
- Table creation
- Admin user creation
- Data verification

### ✅ DNS Server
- Python requests installation
- DNS server startup
- Port 53 verification

### ✅ Firewall
- DNS forcing rules
- DoH/DoT blocking
- QUIC blocking
- Kill-switch

---

## 🧪 Verify Setup

### Web Interface
```
http://localhost/44/QUICK_SYSTEM_TEST.html
```

### Command Line
```bash
# System Health
php api/system_check.php

# Compliance
php verify_system_compliance.php
```

---

## 📖 Documentation

- **Complete Setup**: `README_SETUP.md`
- **Technical Docs**: `TECHNICAL_DOCUMENTATION.md`
- **System Status**: `SYSTEM_STATUS.md`

---

## 🎯 Default Login

- **Email**: admin@test.com
- **Password**: admin123

**⚠️ Change in production!**

---

## ✅ Ready to Use!

After setup, the system is ready for:
- Device registration
- Whitelist management
- VPN configuration
- Full whitelist-only filtering

---

**Let's get started!** 🚀
