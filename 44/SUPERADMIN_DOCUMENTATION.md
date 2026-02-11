# 👨‍💼 SUPERADMIN DOCUMENTATION

## 🎯 OVERVIEW

**Super Admin** is the first administrator account created in the system. There is no technical distinction between "super admin" and regular "admin" - both have `is_admin = 1` in the database. The term "super admin" refers to the first admin user who has full system access.

---

## 🔐 SUPERADMIN FEATURES

### Full System Access

Super Admins have complete access to:

1. **User Management**
   - View all users
   - Create new users
   - Edit user details
   - Delete users
   - Make users admin
   - Bulk operations

2. **Device Management**
   - View all devices from all users
   - Create devices manually
   - Edit device details
   - Block/unblock devices
   - Generate device registration links
   - View VPN IPs

3. **Whitelist Management**
   - View all whitelist entries
   - Add/remove domains for any device
   - Bulk operations

4. **Subscription Management**
   - View all subscriptions
   - Create subscriptions
   - Edit subscription plans
   - Manage expiration dates

5. **System Statistics**
   - Total users
   - Total devices
   - Active subscriptions
   - Whitelist entries
   - System health

6. **Database Operations**
   - Create database backups
   - Restore backups
   - Export database
   - View database stats

7. **System Settings**
   - IP whitelist management
   - Security settings
   - Notification settings
   - Log management

---

## 🚀 CREATING SUPERADMIN

### Method 1: Super Admin Registration Page (First User Only)

**URL:** `http://localhost/44/register_super_admin.html`

**How it works:**
1. First user to register via this page becomes admin
2. If admin already exists, user becomes normal user
3. Automatic check: `SELECT COUNT(*) FROM users WHERE is_admin = 1`

**Steps:**
1. Go to: `register_super_admin.html`
2. Enter email + password
3. Click "Registreer als Super Admin"
4. If first user → becomes admin ✅
5. If admin exists → becomes normal user

**API Endpoint:**
```
POST /api/register_super_admin.php
{
  "email": "admin@example.com",
  "password": "password123"
}

Response:
{
  "status": "created",
  "user_id": 1,
  "is_admin": true,
  "message": "Super Admin account succesvol aangemaakt!"
}
```

---

### Method 2: Make Existing User Admin

**URL:** `http://localhost/44/api/make_admin.php?email=user@example.com`

**How it works:**
- Makes any existing user an admin
- Updates `is_admin = 1` in database

**Usage:**
```bash
# Via browser
http://localhost/44/api/make_admin.php?email=user@example.com

# Via curl
curl "http://localhost/44/api/make_admin.php?email=user@example.com"
```

**Response:**
```json
{
  "success": true,
  "message": "User user@example.com is now admin",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "is_admin": true
  }
}
```

**⚠️ Security Note:** This endpoint should be secured in production!

---

### Method 3: Direct Database Update

```sql
-- Make user admin
UPDATE users SET is_admin = 1 WHERE email = 'admin@example.com';

-- Check admin status
SELECT id, email, is_admin FROM users WHERE email = 'admin@example.com';
```

---

## 🔑 SUPERADMIN LOGIN

### Login Page

**URL:** `http://localhost/44/superadmin_login.html`

**Features:**
- Special login page for admins
- Checks admin status after login
- Redirects to admin panel if admin
- Shows error if not admin

**Login Flow:**
1. Enter email + password
2. Click "Inloggen als Super Admin"
3. Backend validates credentials
4. Checks `is_admin` field
5. If admin → redirect to admin panel
6. If not admin → show error

**Code:**
```javascript
// After login, check admin status
const adminCheck = await apiFetch("admin_check.php");
if (adminCheck.is_admin && adminCheck.user) {
  // Redirect to admin panel
  window.location.href = "admin/index.html";
} else {
  // Not admin - show error
  setMsg("❌ Geen admin rechten");
}
```

---

## 🛡️ ADMIN AUTHENTICATION

### Admin Check API

**Endpoint:** `GET /api/admin_check.php`

**Headers:**
```
Authorization: Bearer TOKEN
```

**Response (Admin):**
```json
{
  "is_admin": true,
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "is_admin": true
  }
}
```

**Response (Not Admin):**
```json
{
  "message": "Access denied - admin only",
  "is_admin": false,
  "user_id": 2,
  "user_email": "user@example.com"
}
```

**Security Features:**
- JWT token validation
- IP whitelist check (optional)
- Security event logging
- Proper error handling

---

## 📊 ADMIN PANEL

### Access

**URL:** `http://localhost/44/admin/index.html`

**Requirements:**
- Must be logged in
- Must have `is_admin = 1`
- Token must be valid

### Features

#### 1. Dashboard Statistics
- Total users
- Total admins
- Total devices
- Active devices
- Whitelist entries
- Active subscriptions

#### 2. User Management Tab
- **View Users:** List all users with details
- **Add User:** Create new user (can set admin)
- **Edit User:** Modify user details
- **Delete User:** Remove user
- **Make Admin:** Grant admin rights
- **Bulk Operations:** Select multiple users

**API Endpoints:**
- `GET /api/admin_users.php` - List users
- `POST /api/admin_users.php` - Create user
- `PUT /api/admin_users.php` - Update user
- `DELETE /api/admin_users.php` - Delete user

#### 3. Device Management Tab
- **View Devices:** List all devices from all users
- **Add Device:** Create device manually
- **Edit Device:** Modify device details
- **Block Device:** Disable device
- **Generate Link:** Create registration link

**API Endpoints:**
- `GET /api/admin_devices.php` - List devices
- `POST /api/admin_devices.php` - Create device
- `PUT /api/admin_devices.php` - Update device

#### 4. Subscription Management Tab
- **View Subscriptions:** List all subscriptions
- **Create Subscription:** Assign subscription to user
- **Edit Subscription:** Modify plan/expiration
- **Cancel Subscription:** Mark as cancelled

**API Endpoints:**
- `GET /api/admin_subscriptions.php` - List subscriptions
- `POST /api/admin_subscriptions.php` - Create subscription

#### 5. Statistics Tab
- **System Stats:** Overall statistics
- **User Stats:** Per-user statistics
- **Device Stats:** Per-device statistics
- **Charts:** Visual representations

**API Endpoints:**
- `GET /api/admin_stats.php` - Basic stats
- `GET /api/admin_stats_enhanced.php` - Enhanced stats

#### 6. Database Backup Tab
- **Create Backup:** Export database
- **Restore Backup:** Import database
- **Download Backup:** Download backup file

**API Endpoints:**
- `GET /api/admin_export_db.php` - Export database
- `POST /api/backup_db.php` - Create backup

#### 7. System Health Tab
- **Health Check:** System status
- **Service Status:** Apache, MySQL, DNS
- **Database Status:** Connection, tables
- **API Status:** Endpoint availability

**API Endpoints:**
- `GET /api/admin_health.php` - System health
- `GET /api/system_check.php` - System check

---

## 🔒 SECURITY FEATURES

### 1. IP Whitelist (Optional)

**Feature:** Restrict admin access to specific IPs

**Configuration:**
- Enable in `config_security_advanced.php`
- Add IPs via admin panel
- Checked on every admin request

**API:**
```
GET /api/admin_ip_whitelist.php - List whitelisted IPs
POST /api/admin_ip_whitelist.php - Add IP
DELETE /api/admin_ip_whitelist.php - Remove IP
```

### 2. Security Event Logging

**Feature:** Log all admin actions

**Logged Events:**
- Login attempts
- Failed admin access
- User creation/deletion
- Device creation/deletion
- Database operations

**API:**
```
GET /api/admin_activity_logs.php - View logs
```

### 3. Brute Force Protection

**Feature:** Rate limiting on login

**Protection:**
- Max 5 attempts per 15 minutes
- IP-based blocking
- Automatic unlock after timeout

---

## 📋 ADMIN API ENDPOINTS

### Authentication
- `GET /api/admin_check.php` - Check admin status

### Users
- `GET /api/admin_users.php` - List users
- `POST /api/admin_users.php` - Create user
- `PUT /api/admin_users.php` - Update user
- `DELETE /api/admin_users.php` - Delete user

### Devices
- `GET /api/admin_devices.php` - List devices
- `POST /api/admin_devices.php` - Create device
- `PUT /api/admin_devices.php` - Update device

### Subscriptions
- `GET /api/admin_subscriptions.php` - List subscriptions
- `POST /api/admin_subscriptions.php` - Create subscription

### Statistics
- `GET /api/admin_stats.php` - Basic stats
- `GET /api/admin_stats_enhanced.php` - Enhanced stats
- `GET /api/admin_db_stats.php` - Database stats

### System
- `GET /api/admin_health.php` - System health
- `GET /api/admin_export_db.php` - Export database
- `POST /api/backup_db.php` - Create backup

### Logs
- `GET /api/admin_activity_logs.php` - Activity logs
- `GET /api/admin_cleanup_logs.php` - Cleanup logs

---

## 🎯 ADMIN VS REGULAR USER

### Regular User Can:
- ✅ View own devices
- ✅ Manage own whitelist
- ✅ Download own WireGuard config
- ✅ View own subscription
- ❌ View other users
- ❌ Create users
- ❌ Access admin panel

### Admin Can:
- ✅ Everything regular user can
- ✅ View all users
- ✅ Create/edit/delete users
- ✅ View all devices
- ✅ Create/edit devices
- ✅ Manage all subscriptions
- ✅ Access admin panel
- ✅ View system statistics
- ✅ Create database backups
- ✅ View system logs

---

## 🚨 IMPORTANT NOTES

### Security

1. **First Admin:** First user to register via `register_super_admin.php` becomes admin
2. **Make Admin:** Use `make_admin.php` carefully - should be secured in production
3. **IP Whitelist:** Optional but recommended for production
4. **Password:** Use strong passwords for admin accounts
5. **Token:** Admin tokens have same expiration as regular tokens

### Best Practices

1. **Limit Admin Accounts:** Only create admin accounts when needed
2. **Monitor Logs:** Regularly check activity logs
3. **Backup Regularly:** Create database backups frequently
4. **Secure Endpoints:** Protect `make_admin.php` in production
5. **Use HTTPS:** Always use HTTPS in production

---

## 📞 TROUBLESHOOTING

### Problem: Can't Login as Admin

**Check:**
1. Verify `is_admin = 1` in database
2. Check token is valid
3. Check `admin_check.php` response
4. Check browser console for errors

**Solution:**
```sql
-- Check admin status
SELECT id, email, is_admin FROM users WHERE email = 'admin@example.com';

-- Make admin if needed
UPDATE users SET is_admin = 1 WHERE email = 'admin@example.com';
```

### Problem: Admin Panel Shows "Access Denied"

**Check:**
1. Token expired → Re-login
2. IP not whitelisted → Add IP to whitelist
3. `is_admin` not 1 → Update database

**Solution:**
```bash
# Check admin status via API
curl -H "Authorization: Bearer TOKEN" http://localhost/44/api/admin_check.php
```

### Problem: Can't Create Admin Account

**Check:**
1. Admin already exists → Use `make_admin.php` instead
2. Database connection → Check MySQL is running
3. Email already registered → Use different email

**Solution:**
```bash
# Check if admin exists
mysql -u root -p -e "SELECT COUNT(*) FROM pornfree.users WHERE is_admin = 1;"

# Make existing user admin
curl "http://localhost/44/api/make_admin.php?email=user@example.com"
```

---

## ✅ QUICK REFERENCE

### Create Super Admin
```
1. Go to: register_super_admin.html
2. Register as first user → Becomes admin
```

### Make User Admin
```
GET /api/make_admin.php?email=user@example.com
```

### Login as Admin
```
1. Go to: superadmin_login.html
2. Enter admin credentials
3. Redirected to admin panel
```

### Check Admin Status
```
GET /api/admin_check.php
Authorization: Bearer TOKEN
```

### Access Admin Panel
```
http://localhost/44/admin/index.html
```

---

**Last Update:** 2026-01-29  
**Version:** 1.0
