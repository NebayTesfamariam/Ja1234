# 👨‍💼 SUPERADMIN QUICK GUIDE

## 🎯 WHAT IS SUPERADMIN?

**Super Admin** = First administrator account with full system access.

**Note:** There's no technical difference between "super admin" and "admin" - both have `is_admin = 1` in the database. "Super admin" just means the first admin user.

---

## 🚀 CREATE SUPERADMIN

### Method 1: Registration Page (Easiest)

1. **Go to:** `http://localhost/44/register_super_admin.html`
2. **Enter:** Email + Password
3. **Click:** "Registreer als Super Admin"
4. **Result:** 
   - If first user → Becomes admin ✅
   - If admin exists → Becomes normal user

### Method 2: Make Existing User Admin

**Via Browser:**
```
http://localhost/44/api/make_admin.php?email=user@example.com
```

**Via Terminal:**
```bash
curl "http://localhost/44/api/make_admin.php?email=user@example.com"
```

**Via Database:**
```sql
UPDATE users SET is_admin = 1 WHERE email = 'user@example.com';
```

---

## 🔑 LOGIN AS SUPERADMIN

### Step 1: Go to Login Page

**URL:** `http://localhost/44/superadmin_login.html`

### Step 2: Enter Credentials

- Email: Your admin email
- Password: Your admin password

### Step 3: Click Login

- If admin → Redirected to admin panel ✅
- If not admin → Error message ❌

---

## 📊 ADMIN PANEL FEATURES

### Access Admin Panel

**URL:** `http://localhost/44/admin/index.html`

**Features:**
- ✅ User Management
- ✅ Device Management
- ✅ Subscription Management
- ✅ Statistics Dashboard
- ✅ Database Backups
- ✅ System Health

---

## 🔒 ADMIN PERMISSIONS

### What Admin Can Do:

✅ **Users:**
- View all users
- Create/edit/delete users
- Make users admin
- Bulk operations

✅ **Devices:**
- View all devices
- Create/edit devices
- Block/unblock devices
- Generate registration links

✅ **Subscriptions:**
- View all subscriptions
- Create/edit subscriptions
- Manage expiration dates

✅ **System:**
- View statistics
- Create backups
- View logs
- System health check

---

## 🛡️ SECURITY

### IP Whitelist (Optional)

**Enable:** Via admin panel → Settings → IP Whitelist

**Add IP:**
```
POST /api/admin_ip_whitelist.php
{
  "ip": "192.168.1.100",
  "description": "Office IP"
}
```

### Activity Logs

**View Logs:**
```
GET /api/admin_activity_logs.php
```

**Logged Events:**
- Login attempts
- User creation/deletion
- Device operations
- Admin actions

---

## 📋 COMMON TASKS

### Create New Admin User

**Via Admin Panel:**
1. Go to Admin Panel → Users Tab
2. Click "Add User"
3. Enter email + password
4. Check "Admin" checkbox
5. Click "Add"

**Via API:**
```bash
curl -X POST http://localhost/44/api/admin_users.php \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123","is_admin":true}'
```

### View All Users

**Via Admin Panel:**
1. Go to Admin Panel → Users Tab
2. See list of all users

**Via API:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/44/api/admin_users.php
```

### Create Database Backup

**Via Admin Panel:**
1. Go to Admin Panel → Backups Tab
2. Click "Create Backup"
3. Download backup file

**Via API:**
```bash
curl -X POST http://localhost/44/api/backup_db.php \
  -H "Authorization: Bearer TOKEN"
```

---

## 🆘 TROUBLESHOOTING

### Can't Login as Admin

**Check:**
```sql
SELECT id, email, is_admin FROM users WHERE email = 'admin@example.com';
```

**Fix:**
```sql
UPDATE users SET is_admin = 1 WHERE email = 'admin@example.com';
```

### Admin Panel Shows "Access Denied"

**Check:**
1. Token expired → Re-login
2. IP not whitelisted → Add IP
3. `is_admin` not 1 → Update database

**Fix:**
```bash
# Check admin status
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/44/api/admin_check.php
```

---

## ✅ QUICK REFERENCE

| Task | Method |
|------|--------|
| Create Super Admin | `register_super_admin.html` |
| Make User Admin | `api/make_admin.php?email=...` |
| Login as Admin | `superadmin_login.html` |
| Admin Panel | `admin/index.html` |
| Check Admin Status | `api/admin_check.php` |

---

**Last Update:** 2026-01-29
