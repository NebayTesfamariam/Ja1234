const API = (path) => "../api/" + path;

let token = localStorage.getItem("token") || "";

const $ = (id) => document.getElementById(id);

// Copy link to clipboard with toast notification
function copyLinkToClipboard(url) {
  navigator.clipboard.writeText(url).then(() => {
    if (typeof Toast !== 'undefined') {
      Toast.success('✅ Link gekopieerd naar klembord!');
    } else {
      alert('✅ Link gekopieerd naar klembord!');
    }
  }).catch(err => {
    console.error('Failed to copy:', err);
    if (typeof Toast !== 'undefined') {
      Toast.error('❌ Kon link niet kopiëren');
    } else {
      alert('❌ Kon link niet kopiëren');
    }
  });
}

function setMsg(el, text, type) {
  if (!el) return;
  el.textContent = text || "";
  if (type) {
    el.className = `msg ${type}`;
  }
}

async function apiFetch(path, opts = {}) {
  token = localStorage.getItem("token") || "";
  
  const headers = opts.headers || {};
  headers["Content-Type"] = "application/json";
  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }
  
  const res = await fetch(API(path), { ...opts, headers });
  const data = await res.json().catch(() => ({}));
  
  if (!res.ok) {
    if (res.status === 401) {
      logout();
      throw new Error(data.message || "Sessie verlopen");
    }
    throw new Error(data.message || `HTTP ${res.status}`);
  }
  return data;
}

function showAdmin(show) {
  $("loginCard").classList.toggle("hidden", show);
  $("adminCard").classList.toggle("hidden", !show);
}

async function checkAdmin() {
  try {
    const data = await apiFetch("admin_check.php");
    if ($("adminLine")) $("adminLine").textContent = `Ingelogd als ${data.user.email} (Admin)`;
    return true;
  } catch (e) {
    setMsg($("loginMsg"), "Geen admin rechten: " + e.message);
    return false;
  }
}

async function loadStats() {
  try {
    const data = await apiFetch("admin_stats.php");
    const stats = data.stats;
    
    // Whitelist-only system
    
    if ($("statsSection")) $("statsSection").innerHTML = `
      <div class="stat-card">
        <div class="stat-label">Totaal Gebruikers</div>
        <div class="stat-value">${stats.total_users}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Admins</div>
        <div class="stat-value">${stats.total_admins}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Totaal Devices</div>
        <div class="stat-value">${stats.total_devices}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Actieve Devices</div>
        <div class="stat-value">${stats.active_devices}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Whitelist Entries</div>
        <div class="stat-value">${stats.total_whitelist || 0}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Whitelist Entries</div>
        <div class="stat-value">${stats.total_whitelist}</div>
      </div>
    `;
  } catch (e) {
    console.error("Stats error:", e);
  }
}

async function addUser() {
  setMsg($("userMsg"), "");
  const email = $("newUserEmail").value.trim();
  const password = $("newUserPassword").value;
  const is_admin = $("newUserAdmin").checked;
  
  if (!email || !password) {
    setMsg($("userMsg"), "Email en password zijn verplicht");
    return;
  }
  
  try {
    await apiFetch("admin_users.php", {
      method: "POST",
      body: JSON.stringify({ email, password, is_admin })
    });
    $("newUserEmail").value = "";
    $("newUserPassword").value = "";
    $("newUserAdmin").checked = false;
    setMsg($("userMsg"), "Gebruiker toegevoegd ✅");
    await loadUsers();
    await loadStats();
  } catch (e) {
    setMsg($("userMsg"), e.message);
  }
}

let allUsers = [];
let selectedUsers = new Set();
let selectedDevices = new Set();

// Enhanced user loading with checkboxes
async function loadUsers() {
  try {
    const data = await apiFetch("admin_users.php");
    const users = data.users || [];
    allUsers = users; // Store for filtering
    
    const list = $("usersList");
    list.innerHTML = "";
    
    // Populate user select for subscriptions
    const userSelect = $("newSubUser");
    if (userSelect) {
      userSelect.innerHTML = '<option value="">Selecteer gebruiker...</option>';
      users.forEach(u => {
        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = `${u.email} (ID: ${u.id})`;
        userSelect.appendChild(opt);
      });
    }
    
    // Populate user select for devices
    const deviceUserSelect = $("newDeviceUser");
    if (deviceUserSelect) {
      deviceUserSelect.innerHTML = '<option value="">Selecteer gebruiker...</option>';
      users.forEach(u => {
        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = `${u.email} (ID: ${u.id})`;
        deviceUserSelect.appendChild(opt);
      });
    }
    
    // Populate user select for automatic device addition
    const autoDeviceUserSelect = $("autoDeviceUser");
    if (autoDeviceUserSelect) {
      autoDeviceUserSelect.innerHTML = '<option value="">👤 Selecteer gebruiker...</option>';
      users.forEach(u => {
        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = `${u.email} (ID: ${u.id})`;
        autoDeviceUserSelect.appendChild(opt);
      });
      
      // Automatisch de eerste gebruiker selecteren (of de enige als er maar één is)
      if (users.length > 0) {
        autoDeviceUserSelect.value = users[0].id;
      }
    }
    
    // Populate user select for device registration links
    const linkUserSelect = $("linkUserSelect");
    if (linkUserSelect) {
      linkUserSelect.innerHTML = '<option value="">👤 Selecteer gebruiker...</option>';
      users.forEach(u => {
        const opt = document.createElement("option");
        opt.value = u.id;
        opt.textContent = `${u.email} (ID: ${u.id})`;
        linkUserSelect.appendChild(opt);
      });
    }
    
    if (users.length === 0) {
      list.innerHTML = "<div class='item'><p>Geen gebruikers gevonden</p></div>";
      return;
    }
    
    users.forEach(u => {
      const div = document.createElement("div");
      div.className = "item";
      div.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
          <input type="checkbox" class="user-checkbox" data-user-id="${u.id}" onchange="toggleUserSelection(${u.id})" />
          <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
              <b>${escapeHtml(u.email)}</b>
              ${u.is_admin ? '<span class="admin-badge">ADMIN</span>' : ''}
            </div>
            <div class="small">
              ID: ${u.id} | 
              Devices: ${u.device_count || 0} | 
              Whitelist: ${u.whitelist_count || 0} |
              Aangemaakt: ${new Date(u.created_at).toLocaleDateString('nl-NL')}
            </div>
          </div>
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button class="secondary" onclick="editUser(${u.id}, '${escapeHtml(u.email)}', ${u.is_admin ? 1 : 0})">✏️ Bewerken</button>
            <button class="secondary" onclick="resetUserPassword(${u.id}, '${escapeHtml(u.email)}')">🔑 Reset Wachtwoord</button>
            <button class="secondary danger" onclick="deleteUser(${u.id})">🗑️ Verwijder</button>
          </div>
        </div>
      `;
      list.appendChild(div);
    });
    
    updateBulkUserButton();
  } catch (e) {
    setMsg($("userMsg"), e.message);
  }
}

function toggleUserSelection(userId) {
  if (selectedUsers.has(userId)) {
    selectedUsers.delete(userId);
  } else {
    selectedUsers.add(userId);
  }
  updateBulkUserButton();
  updateBulkActionsBar();
}

function updateBulkActionsBar() {
  const bar = document.getElementById('bulkUsersBar');
  const count = document.getElementById('bulkUsersCount');
  if (bar && count) {
    const selectedCount = selectedUsers.size;
    count.textContent = selectedCount;
    if (selectedCount > 0) {
      bar.classList.add('active');
    } else {
      bar.classList.remove('active');
    }
  }
}

function clearUserSelection() {
  selectedUsers.clear();
  document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
  updateBulkUserButton();
  updateBulkActionsBar();
}

async function bulkDeleteUsers() {
  if (selectedUsers.size === 0) return;
  if (!confirm(`Weet je zeker dat je ${selectedUsers.size} gebruiker(s) wilt verwijderen?`)) return;
  
  try {
    for (const userId of selectedUsers) {
      await apiFetch("admin_users.php", {
        method: "DELETE",
        body: JSON.stringify({ user_id: userId })
      });
    }
    Toast.success(`✅ ${selectedUsers.size} gebruiker(s) verwijderd`);
    selectedUsers.clear();
    await loadUsers();
    await loadStats();
    updateBulkActionsBar();
  } catch (e) {
    Toast.error(`❌ Fout: ${e.message}`);
  }
}

async function bulkMakeAdmin() {
  if (selectedUsers.size === 0) return;
  if (!confirm(`Weet je zeker dat je ${selectedUsers.size} gebruiker(s) admin rechten wilt geven?`)) return;
  
  try {
    for (const userId of selectedUsers) {
      await apiFetch("admin_users.php", {
        method: "POST",
        body: JSON.stringify({ user_id: userId, is_admin: true })
      });
    }
    Toast.success(`✅ ${selectedUsers.size} gebruiker(s) zijn nu admin`);
    selectedUsers.clear();
    await loadUsers();
    await loadStats();
    updateBulkActionsBar();
  } catch (e) {
    Toast.error(`❌ Fout: ${e.message}`);
  }
}

function updateBulkUserButton() {
  const btn = $("bulkUserAction");
  if (btn) {
    if (selectedUsers.size > 0) {
      btn.style.display = "inline-block";
      btn.textContent = `Bulk Actie (${selectedUsers.size} geselecteerd)`;
    } else {
      btn.style.display = "none";
    }
  }
  
  // Update checkboxes
  document.querySelectorAll(".user-checkbox").forEach(cb => {
    cb.checked = selectedUsers.has(parseInt(cb.dataset.userId));
  });
}

async function bulkUserAction() {
  if (selectedUsers.size === 0) return;
  
  const action = prompt(`Wat wil je doen met ${selectedUsers.size} gebruiker(s)?\n1 = Admin maken\n2 = Admin rechten verwijderen\n3 = Verwijderen`);
  
  if (!action) return;
  
  try {
    if (action === "1") {
      // Make admin
      for (const userId of selectedUsers) {
        await apiFetch("admin_users.php", {
          method: "PUT",
          body: JSON.stringify({ user_id: userId, is_admin: true })
        });
      }
      if (window.Toast) Toast.success(`${selectedUsers.size} gebruiker(s) admin gemaakt ✅`);
      else console.log(`${selectedUsers.size} gebruiker(s) admin gemaakt ✅`);
    } else if (action === "2") {
      // Remove admin
      for (const userId of selectedUsers) {
        await apiFetch("admin_users.php", {
          method: "PUT",
          body: JSON.stringify({ user_id: userId, is_admin: false })
        });
      }
      if (window.Toast) Toast.success(`${selectedUsers.size} gebruiker(s) admin rechten verwijderd ✅`);
      else console.log(`${selectedUsers.size} gebruiker(s) admin rechten verwijderd ✅`);
    } else if (action === "3") {
      if (!confirm(`Weet je zeker dat je ${selectedUsers.size} gebruiker(s) wilt verwijderen?`)) return;
      for (const userId of selectedUsers) {
        await apiFetch("admin_users.php", {
          method: "DELETE",
          body: JSON.stringify({ user_id: userId })
        });
      }
      if (window.Toast) Toast.success(`${selectedUsers.size} gebruiker(s) verwijderd ✅`);
      else console.log(`${selectedUsers.size} gebruiker(s) verwijderd ✅`);
    }
    
    selectedUsers.clear();
    await loadUsers();
    await loadStats();
  } catch (e) {
    alert("Fout: " + e.message);
  }
}

async function exportUsers() {
  try {
    const data = await apiFetch("admin_users.php");
    const users = data.users || [];
    
    const csv = [
      ["Email", "ID", "Admin", "Devices", "Whitelist", "Aangemaakt"].join(","),
      ...users.map(u => [
        u.email,
        u.id,
        u.is_admin ? "Ja" : "Nee",
        u.device_count || 0,
        u.whitelist_count || 0,
        new Date(u.created_at).toLocaleDateString('nl-NL')
      ].join(","))
    ].join("\n");
    
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `users_export_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    if (window.Toast) Toast.success("Gebruikers geëxporteerd ✅");
    else console.log("Gebruikers geëxporteerd ✅");
  } catch (e) {
    alert("Export mislukt: " + e.message);
  }
}

function filterUsers() {
  const search = ($("userSearch")?.value || "").toLowerCase();
  const adminFilter = $("userFilterAdmin")?.value || "";
  
  const items = document.querySelectorAll("#usersList .item");
  
  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    const isAdmin = item.querySelector(".admin-badge") !== null;
    
    const matchSearch = !search || text.includes(search);
    const matchAdmin = !adminFilter || 
      (adminFilter === "1" && isAdmin) || 
      (adminFilter === "0" && !isAdmin);
    
    item.style.display = (matchSearch && matchAdmin) ? "" : "none";
  });
}

function filterDevices() {
  const search = ($("deviceSearch")?.value || "").toLowerCase();
  const statusFilter = $("deviceFilterStatus")?.value || "";
  
  const items = document.querySelectorAll("#devicesList .item");
  
  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    const statusMatch = !statusFilter || text.includes(statusFilter);
    const searchMatch = !search || text.includes(search);
    
    item.style.display = (statusMatch && searchMatch) ? "" : "none";
  });
}

async function exportDevices() {
  try {
    const data = await apiFetch("admin_devices.php");
    const devices = data.devices || [];
    
    const csv = [
      ["Naam", "Gebruiker", "IP", "Status", "WireGuard Key", "Aangemaakt"].join(","),
      ...devices.map(d => [
        d.name,
        d.user_email || "",
        d.wg_ip || "",
        d.status || "",
        d.wg_public_key?.substring(0, 20) + "..." || "",
        new Date(d.created_at).toLocaleDateString('nl-NL')
      ].join(","))
    ].join("\n");
    
    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `devices_export_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    if (window.Toast) Toast.success("Devices geëxporteerd ✅");
    else console.log("Devices geëxporteerd ✅");
  } catch (e) {
    alert("Export mislukt: " + e.message);
  }
}

// Quick Actions
async function quickAction(action) {
  try {
    switch(action) {
      case 'checkExpired':
        await checkExpiredSubscriptions();
        break;
      case 'activateAllDevices':
        if (!confirm("Weet je zeker dat je alle geblokkeerde devices wilt activeren?")) return;
        const devicesData = await apiFetch("admin_devices.php");
        const blockedDevices = (devicesData.devices || []).filter(d => d.status === 'blocked' && !d.permanent_blocked);
        for (const device of blockedDevices) {
          await apiFetch("admin_devices.php", {
            method: "POST",
            body: JSON.stringify({ device_id: device.id, status: 'active' })
          });
        }
        if (window.Toast) Toast.success(`${blockedDevices.length} device(s) geactiveerd ✅`);
        else console.log(`${blockedDevices.length} device(s) geactiveerd ✅`);
        await loadDevices();
        await loadStats();
        break;
      case 'refreshAll':
        await loadStats();
        await loadUsers();
        await loadDevices();
        await loadSubscriptions();
        await loadPayments();
        await loadWhitelist();
        if (window.Toast) Toast.success("Alles ververst ✅");
        else console.log("Alles ververst ✅");
        break;
      case 'exportAll':
        await exportUsers();
        await exportDevices();
        break;
    }
  } catch (e) {
    alert("Fout: " + e.message);
  }
}

async function deleteUser(userId) {
  if (!confirm("Weet je zeker dat je deze gebruiker wilt verwijderen?")) return;
  
  try {
    await apiFetch("admin_users.php", {
      method: "DELETE",
      body: JSON.stringify({ user_id: userId })
    });
    await loadUsers();
    await loadStats();
  } catch (e) {
    alert(e.message);
  }
}

async function editUser(userId, currentEmail, isAdmin) {
  const newEmail = prompt("Nieuw e-mailadres:", currentEmail);
  if (!newEmail || newEmail === currentEmail) return;
  
  const newIsAdmin = confirm(`Admin rechten ${isAdmin ? 'verwijderen' : 'toevoegen'}?`);
  
  try {
    await apiFetch("admin_users.php", {
      method: "PUT",
      body: JSON.stringify({ 
        user_id: userId, 
        email: newEmail,
        is_admin: newIsAdmin ? 1 : 0
      })
    });
    await loadUsers();
    await loadStats();
  } catch (e) {
    alert(e.message);
  }
}

async function resetUserPassword(userId, email) {
  const newPassword = prompt(`Nieuw wachtwoord voor ${email}:`, "");
  if (!newPassword || newPassword.length < 6) {
    alert("Wachtwoord moet minimaal 6 karakters lang zijn");
    return;
  }
  
  if (!confirm(`Wachtwoord resetten voor ${email}?`)) return;
  
  try {
    await apiFetch("admin_users.php", {
      method: "PUT",
      body: JSON.stringify({ 
        user_id: userId, 
        password: newPassword
      })
    });
    alert(`Wachtwoord gereset voor ${email}`);
    await loadUsers();
  } catch (e) {
    alert(e.message);
  }
}

function filterUsers() {
  const search = $("userSearch").value.toLowerCase();
  const items = document.querySelectorAll("#usersList .item");
  
  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(search) ? "" : "none";
  });
}

async function loadDevices() {
  try {
    const data = await apiFetch("admin_devices.php");
    const devices = data.devices || [];
    
    const list = $("devicesList");
    list.innerHTML = "";
    
    if (devices.length === 0) {
      list.innerHTML = `<div class="item"><span class="badge">Geen devices</span></div>`;
      return;
    }
    
    for (const d of devices) {
      const div = document.createElement("div");
      div.className = "item";
      const isBlocked = d.status === 'blocked';
      const isActive = d.status === 'active';
      
      const isAutoCreated = d.auto_created === true || d.auto_created === 1;
      const isPermanentBlocked = d.permanent_blocked === true || d.permanent_blocked === 1;
      const isAdminCreated = d.admin_created === true || d.admin_created === 1;
      
      div.innerHTML = `
        <div style="flex: 1;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
            <b style="font-size: 1.1em;">${escapeHtml(d.name)}</b>
            ${isAdminCreated ? '<span class="badge" style="background: #10b981; color: white; font-weight: bold;">🔒 PERMANENT TOEGANG</span>' : ''}
            ${isPermanentBlocked ? '<span class="badge" style="background: #ef4444; color: white; font-weight: bold;">🔒 PERMANENT GEBLOKKEERD</span>' : ''}
            ${isAutoCreated && !isPermanentBlocked && !isAdminCreated ? '<span class="badge" style="background: #f59e0b; color: white; font-weight: bold;">⚠️ AUTO</span>' : ''}
            ${isBlocked && !isPermanentBlocked ? '<span class="badge" style="background: var(--danger); color: white; font-weight: bold;">🚫 GEBLOKKEERD</span>' : ''}
            ${isActive && !isBlocked ? '<span class="badge success">✓ Actief</span>' : ''}
            ${!isBlocked && !isActive ? `<span class="badge">${d.status}</span>` : ''}
            <span class="badge" style="background: rgba(79, 125, 249, 0.15); color: var(--primary);">👤 ${escapeHtml(d.user_email)}</span>
          </div>
          <div class="small">
            IP: <span class="code">${escapeHtml(d.wg_ip)}</span> | 
            Whitelist: ${d.whitelist_count} entries |
            Aangemaakt: ${new Date(d.created_at).toLocaleDateString('nl-NL')}
            ${isAdminCreated ? ' | <strong style="color: #10b981;">🔒 PERMANENT TOEGANG - werkt altijd, ook zonder abonnement - kan NOOIT worden verwijderd</strong>' : ''}
            ${isBlocked ? ' | <strong style="color: var(--danger);">⚠️ Geen internet toegang</strong>' : ''}
            ${isPermanentBlocked ? ' | <strong style="color: #ef4444;">🔒 PERMANENT GEBLOKKEERD door admin - kan NOOIT worden deblokkeerd</strong>' : ''}
            ${isBlocked && !isPermanentBlocked && !isAdminCreated ? ' | <strong style="color: #f59e0b;">⚠️ Geblokkeerd - abonnement gestopt (wordt automatisch deblokkeerd bij betaling)</strong>' : ''}
            ${isAutoCreated && !isPermanentBlocked && !isBlocked && !isAdminCreated ? ' | <strong style="color: #f59e0b;">⚠️ Automatisch aangemaakt</strong>' : ''}
          </div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
          ${isAdminCreated ? 
            // Admin-created devices zijn ALTIJD actief - geen block/unblock knoppen
            `<span class="badge" style="background: rgba(16, 185, 129, 0.3); color: #10b981; padding: 8px 12px; border: 2px solid #10b981; font-weight: bold;">🔒 ALTIJD ACTIEF - Kan niet worden geblokkeerd</span>` :
            (isPermanentBlocked ? 
              `<span class="badge" style="background: rgba(239, 68, 68, 0.3); color: var(--danger); padding: 8px 12px; border: 2px solid var(--danger); font-weight: bold;">PERMANENT - NOOIT DEBLOKKEEREN</span>` :
              (isBlocked ? 
                `<button class="secondary" style="background: var(--success); color: white; font-weight: bold;" data-id="${d.id}" data-action="unblock">✅ Deblokkeren</button>` :
                `<button class="secondary danger" style="font-weight: bold;" data-id="${d.id}" data-action="block">🚫 Blokkeren</button>`
              )
            )
          }
          ${!isPermanentBlocked && !isAdminCreated ? 
            `<button class="secondary" style="background: #ef4444; color: white; font-weight: bold;" data-id="${d.id}" data-action="permanent-block" title="Permanent blokkeren - kan NOOIT worden deblokkeerd">🔒 Permanent Blokkeren</button>` :
            ''
          }
          <select class="statusSelect" data-id="${d.id}" ${isPermanentBlocked || isAdminCreated ? 'disabled style="opacity: 0.5; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--line); background: var(--card-bg); color: var(--text);"' : 'style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--line); background: var(--card-bg); color: var(--text);"'}>
            <option value="active" ${d.status === 'active' ? 'selected' : ''}>Active</option>
            <option value="inactive" ${d.status === 'inactive' ? 'selected' : ''} ${isAdminCreated ? 'disabled' : ''}>Inactive</option>
            <option value="pending" ${d.status === 'pending' ? 'selected' : ''} ${isAdminCreated ? 'disabled' : ''}>Pending</option>
            <option value="blocked" ${d.status === 'blocked' ? 'selected' : ''} ${isPermanentBlocked || isAdminCreated ? 'disabled' : ''}>Blocked</option>
          </select>
          <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 8px 12px; border: 1px solid #10b981; font-weight: bold;">
            🔒 PERMANENT - Kan niet worden verwijderd
          </span>
        </div>
      `;
      
      // Block/Unblock button (only if not admin_created)
      const blockBtn = div.querySelector('button[data-action="block"]');
      const unblockBtn = div.querySelector('button[data-action="unblock"]');
      if (blockBtn && !isAdminCreated) {
        blockBtn.onclick = () => toggleBlockDevice(d.id, false);
      }
      if (unblockBtn && !isAdminCreated) {
        unblockBtn.onclick = () => toggleBlockDevice(d.id, true);
      }
      
      // Permanent block button (only if not admin_created)
      const permanentBlockBtn = div.querySelector('button[data-action="permanent-block"]');
      if (permanentBlockBtn && !isAdminCreated) {
        permanentBlockBtn.onclick = () => permanentBlockDevice(d.id, d.name);
      }
      
      // Status select (disabled for admin_created devices - they are always active)
      const statusSelect = div.querySelector(".statusSelect");
      if (statusSelect && !isPermanentBlocked && !isAdminCreated) {
        statusSelect.onchange = (e) => updateDeviceStatus(d.id, e.target.value);
      }
      
      // PERMANENT: Delete functionaliteit is uitgeschakeld - alle devices zijn permanent
      // Geen delete knop meer - devices kunnen niet worden verwijderd
      
      list.appendChild(div);
    }
  } catch (e) {
    console.error("Devices error:", e);
    const list = $("devicesList");
    if (list) {
      list.innerHTML = `<div class="item"><p style="padding: 20px; text-align: center; color: var(--danger);">❌ Fout bij laden devices: ${escapeHtml(e.message)}</p></div>`;
    }
  }
}

// Generate device registration link
async function generateDeviceLink() {
  const userId = parseInt($("linkUserSelect").value);
  const expiresDays = parseInt($("linkExpiresDays").value) || 7;
  const maxUses = parseInt($("linkMaxUses").value) || 1;
  const resultEl = $("linkResult");
  
  if (!userId || userId <= 0) {
    resultEl.innerHTML = '<div class="msg error">Selecteer eerst een gebruiker</div>';
    return;
  }
  
  try {
    const btn = $("generateLinkBtn");
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Genereren...";
    
    const data = await apiFetch("generate_device_link.php", {
      method: "POST",
      body: JSON.stringify({
        user_id: userId,
        expires_in_days: expiresDays,
        max_uses: maxUses
      })
    });
    
    const expiresDate = new Date(data.expires_at);
    const now = new Date();
    const daysLeft = Math.ceil((expiresDate - now) / (1000 * 60 * 60 * 24));
    
    resultEl.innerHTML = `
      <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(79, 125, 249, 0.15) 100%); padding: 24px; border-radius: 16px; border: 2px solid rgba(16, 185, 129, 0.4); box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2); animation: fadeIn 0.3s ease;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
          <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
            ✅
          </div>
          <div style="flex: 1;">
            <h4 style="margin: 0 0 4px 0; font-size: 1.3em; font-weight: 700; color: var(--text-bright);">Link Succesvol Gegenereerd!</h4>
            <p style="margin: 0; color: var(--muted); font-size: 0.9em;">De registratielink is klaar om te delen</p>
          </div>
        </div>
        
        <div style="background: var(--card); padding: 20px; border-radius: 12px; border: 2px solid var(--line); margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9em; color: var(--text-bright);">🔗 Registratielink</label>
          <div style="display: flex; gap: 8px; align-items: center;">
            <div style="flex: 1; background: var(--bg); padding: 14px 16px; border-radius: 8px; border: 2px solid var(--line); word-break: break-all; font-family: 'Monaco', 'Menlo', 'Courier New', monospace; font-size: 0.9em; color: var(--text); line-height: 1.5;">
              ${escapeHtml(data.url)}
            </div>
            <button onclick="copyLinkToClipboard('${data.url.replace(/'/g, "\\'")}')" class="btn-pro btn-pro-secondary" style="padding: 14px 20px; font-weight: 600; white-space: nowrap; min-width: auto;">
              <span class="btn-icon">📋</span>
              <span>Kopieer</span>
            </button>
          </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
          <div style="background: var(--card); padding: 16px; border-radius: 12px; border: 2px solid var(--line);">
            <div style="font-size: 0.85em; color: var(--muted); margin-bottom: 6px; font-weight: 600;">👤 Gebruiker</div>
            <div style="font-size: 1.05em; font-weight: 600; color: var(--text-bright);">${escapeHtml(data.user_email || 'Onbekend')}</div>
          </div>
          <div style="background: var(--card); padding: 16px; border-radius: 12px; border: 2px solid var(--line);">
            <div style="font-size: 0.85em; color: var(--muted); margin-bottom: 6px; font-weight: 600;">⏰ Verloopt</div>
            <div style="font-size: 1.05em; font-weight: 600; color: var(--text-bright);">${expiresDate.toLocaleDateString('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
            <div style="font-size: 0.8em; color: var(--muted); margin-top: 4px;">${daysLeft} dag${daysLeft !== 1 ? 'en' : ''} geldig</div>
          </div>
          <div style="background: var(--card); padding: 16px; border-radius: 12px; border: 2px solid var(--line);">
            <div style="font-size: 0.85em; color: var(--muted); margin-bottom: 6px; font-weight: 600;">🔢 Max gebruik</div>
            <div style="font-size: 1.05em; font-weight: 600; color: var(--text-bright);">${data.max_uses}x</div>
            <div style="font-size: 0.8em; color: var(--muted); margin-top: 4px;">${data.uses_remaining !== undefined ? `${data.uses_remaining} over` : 'Nog niet gebruikt'}</div>
          </div>
        </div>
        
        <div style="background: rgba(79, 125, 249, 0.1); padding: 16px; border-radius: 12px; border: 2px solid rgba(79, 125, 249, 0.2);">
          <div style="display: flex; align-items: start; gap: 12px;">
            <div style="font-size: 20px; line-height: 1;">💡</div>
            <div style="flex: 1;">
              <strong style="display: block; margin-bottom: 6px; color: var(--text-bright);">Hoe te gebruiken:</strong>
              <ul style="margin: 0; padding-left: 20px; color: var(--muted); font-size: 0.9em; line-height: 1.8;">
                <li>Deel deze link via email, SMS of WhatsApp</li>
                <li>Gebruikers kunnen de link openen om direct een device toe te voegen</li>
                <li>Geen login vereist - de link bevat alle benodigde informatie</li>
                <li>Na gebruik wordt het device automatisch geactiveerd</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    `;
    
    btn.disabled = false;
    btn.textContent = originalText;
  } catch (e) {
    resultEl.innerHTML = `
      <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%); padding: 24px; border-radius: 16px; border: 2px solid rgba(239, 68, 68, 0.4); box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2); animation: fadeIn 0.3s ease;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
            ❌
          </div>
          <div style="flex: 1;">
            <h4 style="margin: 0 0 4px 0; font-size: 1.2em; font-weight: 700; color: var(--text-bright);">Fout bij Genereren</h4>
            <p style="margin: 0; color: var(--muted); font-size: 0.95em;">${escapeHtml(e.message)}</p>
          </div>
        </div>
      </div>
    `;
    const btn = $("generateLinkBtn");
    btn.disabled = false;
    btn.textContent = "🔗 Link Genereren";
  }
}

async function addDevice() {
  const msgEl = $("deviceMsg");
  setMsg(msgEl, "");
  
  const userId = parseInt($("newDeviceUser").value);
  let name = $("newDeviceName").value.trim();
  
  if (!userId || userId <= 0) {
    msgEl.className = "msg error";
    setMsg(msgEl, "❌ Selecteer eerst een gebruiker");
    return;
  }
  
  // Auto-detect device name if not provided
  if (!name) {
    const userAgent = navigator.userAgent || '';
    if (userAgent.includes('iPhone')) {
      name = 'iPhone';
    } else if (userAgent.includes('iPad')) {
      name = 'iPad';
    } else if (userAgent.includes('Android')) {
      name = 'Android Device';
    } else if (userAgent.includes('Windows')) {
      name = 'Windows PC';
    } else if (userAgent.includes('Mac') || userAgent.includes('macOS')) {
      name = 'Mac';
    } else if (userAgent.includes('Linux')) {
      name = 'Linux PC';
    } else {
      name = 'Device';
    }
    
    // Add number if user already has devices with this name
    // This will be handled server-side, but we can add a suffix here too
    const devicesData = await apiFetch("admin_devices.php");
    const userDevices = (devicesData.devices || []).filter(d => d.user_id === userId && d.name.startsWith(name));
    if (userDevices.length > 0) {
      name = `${name} ${userDevices.length + 1}`;
    }
  }
  
  try {
    const btn = $("addDeviceBtn");
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Automatisch toevoegen...";
    
    // AUTOMATISCH: Alles wordt automatisch gegenereerd
    // Device naam wordt automatisch gegenereerd als leeg (server-side)
    // WireGuard key en IP worden automatisch gegenereerd (server-side)
    // admin_created wordt automatisch op 1 gezet (server-side)
    const data = await apiFetch("admin_devices.php", {
      method: "PUT",
      body: JSON.stringify({
        user_id: userId,
        name: name, // Leeg is OK - wordt automatisch gegenereerd server-side
        // WireGuard key en IP worden automatisch gegenereerd server-side
        // admin_created wordt automatisch op 1 gezet server-side
      })
    });
    
    if (data.status === 'exists') {
      // Device already exists
      msgEl.className = "msg info";
      setMsg(msgEl, `ℹ️ ${data.message || 'Device bestaat al voor deze gebruiker'}`);
      Toast.info(data.message || 'Device bestaat al');
    } else {
      // New device created
      // Clear form
      $("newDeviceUser").value = "";
      $("newDeviceName").value = "";
      
      // Show complete information - everything is automatically configured
      const autoInfo = data.auto_configured || {};
      msgEl.className = "msg success";
      setMsg(msgEl, `✅ Device "${data.device_name || name}" AUTOMATISCH toegevoegd en DIRECT klaar voor gebruik!\n\n` +
        `🔧 AUTOMATISCH INGESTELD:\n` +
        `✅ Status: ${data.status || 'active'} (automatisch)\n` +
        `✅ WireGuard Key: ${autoInfo.wireguard_key || 'Automatisch gegenereerd'}\n` +
        `✅ IP Adres: ${data.wg_ip || 'Automatisch toegewezen'}\n` +
        `✅ Permanent Toegang: ${autoInfo.permanent_access ? 'Ja' : 'Ja'} (werkt altijd)\n` +
        `✅ Geen Abonnement Nodig: ${autoInfo.no_subscription_required ? 'Ja' : 'Ja'}\n` +
        `✅ Systeem Klaar: ${autoInfo.system_ready ? 'Ja' : 'Ja'}\n\n` +
        `🚀 Dit device kan DIRECT het systeem gebruiken - geen extra configuratie nodig!\n` +
        `🔒 Dit device kan NOOIT worden verwijderd (permanent toegang).`);
      Toast.success(`✅ Device "${data.device_name || name}" automatisch toegevoegd en direct klaar!`);
    }
    
    btn.disabled = false;
    btn.textContent = originalText;
    await loadDevices();
    await loadStats();
  } catch (e) {
    msgEl.className = "msg error";
    setMsg(msgEl, "❌ " + e.message);
    Toast.error("Fout: " + e.message);
    
    const btn = $("addDeviceBtn");
    btn.disabled = false;
    btn.textContent = "➕ Automatisch Device Toevoegen";
  }
}

// Automatic device addition - super simple, no name needed
async function autoAddDevice() {
  const userId = parseInt($("autoDeviceUser").value);
  const msgEl = $("deviceMsg");
  
  if (!userId) {
    setMsg(msgEl, "❌ Selecteer eerst een gebruiker");
    Toast.error("Selecteer eerst een gebruiker");
    return;
  }
  
  try {
    const btn = $("autoAddDeviceBtn");
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Toevoegen...";
    
    // Get user's existing devices to generate a unique name
    const usersData = await apiFetch("admin_users.php");
    const user = (usersData.users || []).find(u => u.id === userId);
    const deviceCount = (user && user.device_count) ? user.device_count : 0;
    
    // Auto-generate device name
    const deviceNames = ['iPhone', 'Android', 'Windows PC', 'MacBook', 'iPad', 'Tablet', 'Laptop'];
    const randomName = deviceNames[Math.floor(Math.random() * deviceNames.length)];
    const deviceName = deviceCount > 0 ? `${randomName} ${deviceCount + 1}` : randomName;
    
    const data = await apiFetch("admin_devices.php", {
      method: "PUT",
      body: JSON.stringify({
        user_id: userId,
        name: deviceName,
        // WireGuard key and IP are automatically generated server-side
        admin_created: true
      })
    });
    
    if (data.status === 'exists') {
      msgEl.className = "msg info";
      setMsg(msgEl, `ℹ️ ${data.message || 'Device bestaat al voor deze gebruiker'}`);
      Toast.info(data.message || 'Device bestaat al');
    } else {
      // Clear form
      $("autoDeviceUser").value = "";
      
      // Show complete information - everything is automatically configured
      const autoInfo = data.auto_configured || {};
      msgEl.className = "msg success";
      setMsg(msgEl, `✅ Device "${data.device_name || deviceName}" AUTOMATISCH toegevoegd en DIRECT klaar voor gebruik!\n\n` +
        `🔧 AUTOMATISCH INGESTELD:\n` +
        `✅ Status: ${data.status || 'active'} (automatisch)\n` +
        `✅ WireGuard Key: ${autoInfo.wireguard_key || 'Automatisch gegenereerd'}\n` +
        `✅ IP Adres: ${data.wg_ip || 'Automatisch toegewezen'}\n` +
        `✅ Permanent Toegang: ${autoInfo.permanent_access ? 'Ja' : 'Ja'} (werkt altijd)\n` +
        `✅ Geen Abonnement Nodig: ${autoInfo.no_subscription_required ? 'Ja' : 'Ja'}\n` +
        `✅ Systeem Klaar: ${autoInfo.system_ready ? 'Ja' : 'Ja'}\n\n` +
        `🚀 Dit device kan DIRECT het systeem gebruiken - geen extra configuratie nodig!\n` +
        `🔒 Dit device kan NOOIT worden verwijderd (permanent toegang).`);
      Toast.success(`✅ Device "${data.device_name || deviceName}" automatisch toegevoegd en direct klaar!`);
    }
    
    btn.disabled = false;
    btn.textContent = originalText;
    await loadDevices();
    await loadStats();
  } catch (e) {
    msgEl.className = "msg error";
    setMsg(msgEl, "❌ " + e.message);
    Toast.error("Fout: " + e.message);
    
    const btn = $("autoAddDeviceBtn");
    btn.disabled = false;
    btn.textContent = "⚡ Automatisch Toevoegen";
  }
}

async function toggleBlockDevice(deviceId, isBlocked) {
  try {
    // Check if device is admin_created before blocking
    const devicesData = await apiFetch("admin_devices.php");
    const device = (devicesData.devices || []).find(d => d.id === deviceId);
    
    // Admin-created devices kunnen NOOIT worden geblokkeerd - ze zijn ALTIJD actief
    if (device && (device.admin_created === true || device.admin_created === 1)) {
      alert('⚠️ PERMANENTE TOEGANG - ALTIJD ACTIEF\n\nDit device is aangemaakt door admin en heeft PERMANENTE TOEGANG.\n\n🔒 ALTIJD ACTIEF:\n- Werkt altijd, ook zonder abonnement\n- Kan NOOIT worden geblokkeerd\n- Kan NOOIT worden gedeactiveerd\n- Kan NOOIT worden verwijderd\n- Status is ALTIJD "active"\n\nDit device blijft altijd actief in het systeem.');
      // Force reload to show correct status
      await loadDevices();
      return;
    }
    
    const newStatus = isBlocked ? 'active' : 'blocked';
    await apiFetch("admin_devices.php", {
      method: "POST",
      body: JSON.stringify({ device_id: deviceId, status: newStatus })
    });
    await loadDevices();
    await loadStats();
      if (window.Toast) Toast.success(isBlocked ? "Device geactiveerd ✅" : "Device geblokkeerd ✅");
      else console.log(isBlocked ? "Device geactiveerd ✅" : "Device geblokkeerd ✅");
  } catch (e) {
    if (e.message && (e.message.includes('permanent') || e.message.includes('admin_created'))) {
      alert('⚠️ ' + e.message + '\n\nDit device heeft permanente toegang en is ALTIJD actief. Het kan NOOIT worden geblokkeerd.');
      // Force reload to show correct status
      await loadDevices();
    } else {
      alert(e.message);
    }
  }
}

async function permanentBlockDevice(deviceId, deviceName) {
  if (!confirm(`⚠️ WAARSCHUWING: Permanent blokkeren\n\nWeet je zeker dat je "${deviceName}" PERMANENT wilt blokkeren?\n\nDit device kan NOOIT meer worden deblokkeerd, zelfs niet door jou als admin!\n\nDit is onomkeerbaar!`)) {
    return;
  }
  
  if (!confirm(`Laatste bevestiging:\n\nJe staat op het punt "${deviceName}" PERMANENT te blokkeren.\n\nDit kan NOOIT worden teruggedraaid!\n\nDoorgaan?`)) {
    return;
  }
  
  try {
    await apiFetch("admin_devices.php", {
      method: "POST",
      body: JSON.stringify({ 
        device_id: deviceId, 
        status: 'blocked',
        permanent_block: true
      })
    });
    alert(`✅ "${deviceName}" is nu PERMANENT geblokkeerd.\n\nDit device kan NOOIT meer worden deblokkeerd.`);
    await loadDevices();
    await loadStats();
  } catch (e) {
    alert('Fout: ' + e.message);
  }
}

async function updateDeviceStatus(deviceId, newStatus) {
  try {
    await apiFetch("admin_devices.php", {
      method: "POST",
      body: JSON.stringify({ device_id: deviceId, status: newStatus })
    });
    await loadDevices();
    await loadStats();
  } catch (e) {
    if (e.message && e.message.includes('permanent')) {
      alert('⚠️ ' + e.message + '\n\nDit device is permanent geblokkeerd en kan NOOIT worden deblokkeerd.');
      // Reload to reset dropdown
      await loadDevices();
    } else {
      alert(e.message);
    }
  }
}

async function updateDeviceStatus(deviceId, status) {
  try {
    // Check if device is admin_created before changing status
    const devicesData = await apiFetch("admin_devices.php");
    const device = (devicesData.devices || []).find(d => d.id === deviceId);
    
    // Admin-created devices kunnen NOOIT worden geblokkeerd - ze zijn ALTIJD actief
    if (device && (device.admin_created === true || device.admin_created === 1) && status !== 'active') {
      alert('⚠️ PERMANENTE TOEGANG - ALTIJD ACTIEF\n\nDit device is aangemaakt door admin en heeft PERMANENTE TOEGANG.\n\n🔒 ALTIJD ACTIEF:\n- Status is ALTIJD "active"\n- Kan NOOIT worden geblokkeerd\n- Kan NOOIT worden gedeactiveerd\n- Kan NOOIT worden verwijderd\n\nDit device blijft altijd actief in het systeem.');
      // Force reload to show correct status
      await loadDevices();
      return;
    }
    
    await apiFetch("admin_devices.php", {
      method: "POST",
      body: JSON.stringify({ device_id: deviceId, status })
    });
    await loadDevices();
    await loadStats();
  } catch (e) {
    if (e.message && (e.message.includes('permanent') || e.message.includes('admin_created'))) {
      alert('⚠️ ' + e.message + '\n\nDit device heeft permanente toegang en is ALTIJD actief. De status kan niet worden gewijzigd.');
      // Force reload to show correct status
      await loadDevices();
    } else {
      alert(e.message);
    }
  }
}

async function deleteDevice(deviceId) {
  // PERMANENT SYSTEEM: Devices kunnen NOOIT worden verwijderd
  // Alle devices zijn permanent in het systeem voor stabiliteit en continuïteit
  alert('⚠️ PERMANENT SYSTEEM - VERWIJDEREN NIET MOGELIJK\n\n' +
    'Alle devices zijn PERMANENT in het systeem.\n\n' +
    '🔒 SYSTEEM REGEL:\n' +
    '- Devices kunnen NOOIT worden verwijderd\n' +
    '- Devices kunnen wel worden geblokkeerd/deblokkeerd\n' +
    '- Dit zorgt voor systeem stabiliteit\n' +
    '- Alle devices blijven beschikbaar voor het systeem\n\n' +
    '💡 Tip: Gebruik "Blokkeren" om een device tijdelijk uit te schakelen.');
}

async function loadWhitelist() {
  try {
    // Get all devices first, then get whitelist for each
    const devicesData = await apiFetch("admin_devices.php");
    const devices = devicesData.devices || [];
    
    const list = $("whitelistList");
    list.innerHTML = "";
    
    if (devices.length === 0) {
      list.innerHTML = `<div class="item"><span class="badge">Geen devices</span></div>`;
      return;
    }
    
    for (const device of devices) {
      try {
        const wlData = await apiFetch(`get_whitelist.php?device_id=${device.id}`);
        const entries = wlData.entries || [];
        
        if (entries.length > 0) {
          for (const entry of entries) {
            const div = document.createElement("div");
            div.className = "item";
            div.innerHTML = `
              <div>
                <div>
                  <b>${escapeHtml(entry.domain)}</b>
                  <span class="badge">${entry.enabled ? 'enabled' : 'disabled'}</span>
                </div>
                <div class="small">
                  Device: ${escapeHtml(device.name)} (${escapeHtml(device.user_email)}) | 
                  ${entry.comment ? 'Comment: ' + escapeHtml(entry.comment) : ''}
                </div>
              </div>
            `;
            list.appendChild(div);
          }
        }
      } catch (e) {
        // Skip devices without whitelist access
      }
    }
    
    if (list.innerHTML === "") {
      list.innerHTML = `<div class="item"><span class="badge">Geen whitelist entries</span></div>`;
    }
  } catch (e) {
    console.error("Whitelist error:", e);
  }
}

async function login() {
  setMsg($("loginMsg"), "");
  const email = $("email").value.trim();
  const password = $("password").value;
  
  if (!email || !password) {
    setMsg($("loginMsg"), "Email en password zijn verplicht");
    return;
  }
  
  try {
    const headers = { "Content-Type": "application/json" };
    const res = await fetch(API("login.php"), {
      method: "POST",
      headers,
      body: JSON.stringify({ email, password })
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
    
    token = data.token;
    localStorage.setItem("token", token);
    
    const isAdmin = await checkAdmin();
    if (isAdmin) {
      showAdmin(true);
      await loadStats();
      await loadUsers();
      await loadDevices();
      await loadSubscriptions();
      await loadWhitelist();
    }
  } catch (e) {
    setMsg($("loginMsg"), e.message);
  }
}

function logout() {
  token = "";
  localStorage.removeItem("token");
  stopAutoRefresh();
  showAdmin(false);
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (m) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
  }[m]));
}

// Tabs
document.querySelectorAll(".tab").forEach(tab => {
  tab.onclick = () => {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
    tab.classList.add("active");
    
    // Load dashboard if dashboard tab is clicked
    if (tab.dataset.tab === 'dashboard' && window.dashboard) {
      window.dashboard.loadStats();
    }
    
    // Handle tab names with dashes
    const tabName = tab.dataset.tab;
    let tabId;
    if (tabName.includes('-')) {
      tabId = tabName.split('-').map((part, i) => 
        i === 0 ? part.charAt(0).toUpperCase() + part.slice(1) : part.charAt(0).toUpperCase() + part.slice(1)
      ).join('');
    } else {
      tabId = tabName.charAt(0).toUpperCase() + tabName.slice(1);
    }
    
    const tabElement = $(`tab${tabId}`);
    if (tabElement) {
      tabElement.classList.add("active");
      
      // Load data when switching to specific tabs
      if (tabName === 'dashboard' && window.dashboard) {
        window.dashboard.loadStats();
      // Activity tab removed - whitelist-only system
      } else if (tabName === 'payments') {
        loadPayments();
      } else if (tabName === 'settings') {
        loadSettings();
      } else if (tabName === 'health') {
        runHealthCheck();
        loadNotificationSettings();
        loadBackupSettings();
      } else if (tabName === 'subscriptions') {
        setDefaultSubscriptionDates();
      }
    }
  };
});

// Whitelist-only system

// Activity Logs functions removed - whitelist-only system

// Subscription functions removed - whitelist-only system

// Subscription management functions
async function loadSubscriptions() {
  try {
    const data = await apiFetch("admin_subscriptions.php");
    const subscriptions = data.subscriptions || [];
    
    const list = $("subscriptionsList");
    list.innerHTML = "";
    
    if (subscriptions.length === 0) {
      list.innerHTML = `<div class="item"><span class="badge">Geen abonnementen</span></div>`;
      return;
    }
    
    for (const s of subscriptions) {
      const div = document.createElement("div");
      div.className = "item";
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const startDate = new Date(s.start_date);
      const endDate = new Date(s.end_date);
      const isActive = s.status === 'active' && startDate <= today && endDate >= today;
      const planNames = { basic: 'Basic (2 devices)', family: 'Family (5 devices)', premium: 'Premium (10 devices)' };
      
      div.innerHTML = `
        <div style="flex: 1;">
          <div>
            <b>${escapeHtml(s.user_email)}</b>
            <span class="badge ${isActive ? 'success' : ''}">${s.status}</span>
            <span class="badge">${planNames[s.plan] || s.plan}</span>
            ${isActive ? `<span class="badge">${s.device_count}/${s.max_devices} devices</span>` : ''}
            ${isActive ? '<span class="badge success">✓ Direct actief</span>' : ''}
            ${s.stripe_subscription_id ? '<span class="badge" style="background: #635bff;">Stripe: ' + s.stripe_subscription_id.substring(0, 20) + '...</span>' : ''}
          </div>
          <div class="small">
            Start: ${new Date(s.start_date).toLocaleDateString('nl-NL')} | 
            Eind: ${new Date(s.end_date).toLocaleDateString('nl-NL')} |
            ${isActive ? '<strong style="color: var(--success);">Actief - werkt direct</strong>' : 'Niet actief'}
            ${s.stripe_customer_id ? ' | Stripe Customer: ' + s.stripe_customer_id.substring(0, 20) + '...' : ''}
          </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
          <button class="secondary" onclick="editSubscription(${s.id}, '${s.plan}', '${s.start_date}', '${s.end_date}', '${s.status}')">✏️ Bewerken</button>
          ${isActive ? '<button class="secondary danger" onclick="cancelSubscription(' + s.id + ')">🚫 Stopzetten</button>' : '<button class="secondary" onclick="activateSubscription(' + s.id + ')">✅ Activeren</button>'}
        </div>
      `;
      list.appendChild(div);
    }
  } catch (e) {
    setMsg($("subMsg"), e.message);
  }
}

async function addSubscription() {
  setMsg($("subMsg"), "");
  const user_id = parseInt($("newSubUser").value, 10);
  const plan = $("newSubPlan").value;
  let start_date = $("newSubStartDate").value;
  let end_date = $("newSubEndDate").value;
  
  if (!user_id || !plan) {
    setMsg($("subMsg"), "Gebruiker en plan zijn verplicht");
    return;
  }
  
  // If no start_date, use today (so it works immediately)
  if (!start_date) {
    start_date = new Date().toISOString().split('T')[0];
    $("newSubStartDate").value = start_date;
  }
  
  // If no end_date, calculate 1 month from start
  if (!end_date) {
    const end = new Date(start_date);
    end.setMonth(end.getMonth() + 1);
    end_date = end.toISOString().split('T')[0];
    $("newSubEndDate").value = end_date;
  }
  
  try {
    const data = await apiFetch("admin_subscriptions.php", {
      method: "POST",
      body: JSON.stringify({ user_id, plan, status: 'active', start_date, end_date })
    });
    $("newSubUser").value = "";
    
    if (data.auto_device_created && data.device) {
      setMsg($("subMsg"), `✅ Abonnement actief → ✅ Device "${data.device.device_name}" automatisch geregistreerd → ✅ Direct beschermd!`);
    } else {
      setMsg($("subMsg"), `Abonnement toegevoegd ✅ - Direct actief! Klant kan nu ${data.max_devices} device(s) toevoegen.`);
    }
    
    await loadSubscriptions();
    await loadStats();
  } catch (e) {
    setMsg($("subMsg"), e.message);
  }
}

async function cancelSubscription(subscription_id) {
  if (!confirm("Weet je zeker dat je dit abonnement wilt stopzetten?")) return;
  
  try {
    await apiFetch("admin_subscriptions.php", {
      method: "DELETE",
      body: JSON.stringify({ subscription_id })
    });
    await loadSubscriptions();
    await loadStats();
  } catch (e) {
    alert(e.message);
  }
}

async function editSubscription(subId, currentPlan, currentStart, currentEnd, currentStatus) {
  const newPlan = prompt("Nieuw plan (basic/family/premium):", currentPlan);
  if (!newPlan || !['basic', 'family', 'premium'].includes(newPlan)) return;
  
  const newStart = prompt("Start datum (YYYY-MM-DD):", currentStart);
  if (!newStart) return;
  
  const newEnd = prompt("Eind datum (YYYY-MM-DD):", currentEnd);
  if (!newEnd) return;
  
  try {
    await apiFetch("admin_subscriptions.php", {
      method: "PUT",
      body: JSON.stringify({
        subscription_id: subId,
        plan: newPlan,
        start_date: newStart,
        end_date: newEnd,
        status: currentStatus
      })
    });
    await loadSubscriptions();
    await loadStats();
  } catch (e) {
    alert(e.message);
  }
}

async function activateSubscription(subscription_id) {
  if (!confirm("Dit abonnement activeren?")) return;
  
  try {
    await apiFetch("admin_subscriptions.php", {
      method: "PUT",
      body: JSON.stringify({
        subscription_id,
        status: 'active'
      })
    });
    await loadSubscriptions();
    await loadStats();
  } catch (e) {
    alert(e.message);
  }
}

function filterSubscriptions() {
  const search = $("subscriptionSearch").value.toLowerCase();
  const items = document.querySelectorAll("#subscriptionsList .item");
  
  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(search) ? "" : "none";
  });
}

// Wire up
if ($("loginBtn")) $("loginBtn").onclick = login;
if ($("logoutBtn")) $("logoutBtn").onclick = logout;
if ($("addUserBtn")) $("addUserBtn").onclick = addUser;
// Whitelist-only system
if ($("addDeviceBtn")) {
  $("addDeviceBtn").onclick = addDevice;
  if ($("generateLinkBtn")) $("generateLinkBtn").onclick = generateDeviceLink;
}
if ($("autoAddDeviceBtn")) {
  $("autoAddDeviceBtn").onclick = autoAddDevice;
}
// Permanent blocklist buttons removed - read-only
// $("addPermBtn").onclick = addPermanentBlocklist;
// $("importPermBtn").onclick = importPermanentBlocklist;
if ($("addSubBtn")) $("addSubBtn").onclick = addSubscription;
// Subscription blocklist button removed - whitelist-only system
// Activity logs buttons removed - whitelist-only system
if ($("exportDbBtn")) {
  $("exportDbBtn").onclick = exportDatabase;
}
if ($("dbStatsBtn")) {
  $("dbStatsBtn").onclick = loadDatabaseStats;
}
if ($("checkExpiredSubsBtn")) {
  $("checkExpiredSubsBtn").onclick = checkExpiredSubscriptions;
}
if ($("cleanupLogsBtn")) {
  $("cleanupLogsBtn").onclick = cleanupOldLogs;
}
if ($("runHealthCheckBtn")) {
  $("runHealthCheckBtn").onclick = runHealthCheck;
}
if ($("saveNotificationsBtn")) {
  $("saveNotificationsBtn").onclick = saveNotificationSettings;
}
if ($("saveBackupSettingsBtn")) {
  $("saveBackupSettingsBtn").onclick = saveBackupSettings;
}
if ($("paymentSearch")) {
  $("paymentSearch").oninput = () => loadPayments();
  if ($("paymentStatus")) $("paymentStatus").onchange = () => loadPayments();
}

// Auto-refresh every 30 seconds
let autoRefreshInterval = null;

function startAutoRefresh() {
  if (autoRefreshInterval) clearInterval(autoRefreshInterval);
  
  autoRefreshInterval = setInterval(async () => {
    try {
      await loadStats();
      // Only refresh current tab data
      const activeTab = document.querySelector(".tab.active");
      if (activeTab) {
        const tabName = activeTab.dataset.tab;
        if (tabName === 'users') await loadUsers();
        else if (tabName === 'devices') await loadDevices();
        else if (tabName === 'subscriptions') await loadSubscriptions();
        else if (tabName === 'payments') await loadPayments();
        // Activity tab removed - whitelist-only system
        else if (tabName === 'whitelist') await loadWhitelist();
        // Whitelist-only system
      }
    } catch (e) {
      console.error("Auto-refresh error:", e);
    }
  }, 30000); // 30 seconds
}

function stopAutoRefresh() {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
    autoRefreshInterval = null;
  }
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
  // Ctrl/Cmd + R = Refresh all
  if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
    e.preventDefault();
    quickAction('refreshAll');
  }
  
  // Ctrl/Cmd + E = Export
  if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
    e.preventDefault();
    const activeTab = document.querySelector(".tab.active");
    if (activeTab) {
      const tabName = activeTab.dataset.tab;
      if (tabName === 'users') exportUsers();
      else if (tabName === 'devices') exportDevices();
      // Activity export removed - whitelist-only system
    }
  }
  
  // Escape = Clear selections
  if (e.key === 'Escape') {
    selectedUsers.clear();
    selectedDevices.clear();
    updateBulkUserButton();
  }
});

// Auto-login if token exists
(async () => {
  token = localStorage.getItem("token") || "";
  if (!token) return;
  
  try {
    const isAdmin = await checkAdmin();
    if (isAdmin) {
      showAdmin(true);
      await loadStats();
      await loadUsers();
      await loadDevices();
      await loadSubscriptions();
      await loadPayments();
      await loadWhitelist();
      await loadSettings();
      
      // Set default dates for subscription form
      setDefaultSubscriptionDates();
      
      // Start auto-refresh
      startAutoRefresh();
    }
  } catch (e) {
    logout();
  }
})();

// Set default dates for new subscription form
function setDefaultSubscriptionDates() {
  const startDateInput = $("newSubStartDate");
  const endDateInput = $("newSubEndDate");
  
  if (startDateInput && endDateInput) {
    const today = new Date();
    const nextMonth = new Date(today);
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    
    // Format as YYYY-MM-DD
    const formatDate = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
    
    startDateInput.value = formatDate(today);
    endDateInput.value = formatDate(nextMonth);
  }
}

// Load Stripe Payments
async function loadPayments() {
  try {
    const data = await apiFetch("admin_subscriptions.php");
    const subscriptions = data.subscriptions || [];
    
    // Filter by search and status
    const search = ($("paymentSearch")?.value || "").toLowerCase();
    const statusFilter = $("paymentStatus")?.value || "";
    
    let filtered = subscriptions.filter(s => {
      const matchSearch = !search || 
        s.user_email?.toLowerCase().includes(search) ||
        s.stripe_subscription_id?.toLowerCase().includes(search) ||
        s.stripe_customer_id?.toLowerCase().includes(search);
      const matchStatus = !statusFilter || s.status === statusFilter;
      return matchSearch && matchStatus;
    });
    
    // Count Stripe subscriptions
    const stripeSubs = subscriptions.filter(s => s.stripe_subscription_id);
    const stripeCustomers = new Set(subscriptions.filter(s => s.stripe_customer_id).map(s => s.stripe_customer_id));
    
    if ($("stripeSubCount")) {
      $("stripeSubCount").textContent = stripeSubs.length;
    }
    if ($("stripeCustomerCount")) {
      $("stripeCustomerCount").textContent = stripeCustomers.size;
    }
    
    const list = $("paymentsList");
    if (!list) return;
    
    list.innerHTML = "";
    
    if (filtered.length === 0) {
      list.innerHTML = "<div class='item'><p>Geen Stripe subscriptions gevonden</p></div>";
      return;
    }
    
    filtered.forEach(s => {
      const div = document.createElement("div");
      div.className = "item";
      div.innerHTML = `
        <div>
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <b>${escapeHtml(s.user_email || 'Onbekend')}</b>
            <span class="badge" style="background: ${s.status === 'active' ? 'var(--success)' : s.status === 'expired' ? 'var(--warning)' : 'var(--muted)'}; color: white;">
              ${s.status === 'active' ? '✅ Actief' : s.status === 'expired' ? '⚠️ Verlopen' : '❌ Geannuleerd'}
            </span>
            ${s.stripe_subscription_id ? '<span class="badge" style="background: #635bff; color: white;">Stripe: ' + s.stripe_subscription_id.substring(0, 20) + '...</span>' : ''}
          </div>
          <div class="small">
            Plan: <strong>${escapeHtml(s.plan_name || s.plan || 'Onbekend')}</strong> | 
            Devices: <strong>${s.device_count || 0}/${s.max_devices || 0}</strong> | 
            Van: <strong>${new Date(s.start_date).toLocaleDateString('nl-NL')}</strong> tot <strong>${new Date(s.end_date).toLocaleDateString('nl-NL')}</strong>
            ${s.stripe_customer_id ? '<br>Stripe Customer: ' + s.stripe_customer_id.substring(0, 30) + '...' : ''}
          </div>
        </div>
      `;
      list.appendChild(div);
    });
  } catch (e) {
    console.error("Payments error:", e);
    if ($("paymentsList")) {
      $("paymentsList").innerHTML = `<div class='item'><p class='error'>Fout bij laden: ${e.message}</p></div>`;
    }
  }
}

// Load Settings
async function loadSettings() {
  try {
    // Load subscription plans
    const data = await apiFetch("admin_subscriptions.php");
    const plans = data.plans || [];
    
    const plansList = $("plansList");
    if (plansList) {
      plansList.innerHTML = "";
      plans.forEach(plan => {
        const div = document.createElement("div");
        div.className = "item";
        div.innerHTML = `
          <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
              <b>${escapeHtml(plan.name || 'Onbekend')}</b>
              <span class="badge">${plan.max_devices || 0} devices</span>
            </div>
            <div class="small">
              Prijs: <strong>€${(parseFloat(plan.price_monthly) || 0).toFixed(2)}/maand</strong><br>
              ${escapeHtml(plan.description || 'Geen beschrijving')}
            </div>
          </div>
        `;
        plansList.appendChild(div);
      });
    }
  } catch (e) {
    console.error("Settings error:", e);
  }
}

// Export Database
async function exportDatabase() {
  try {
    const res = await fetch(API("admin_export_db.php"), {
      headers: { "Authorization": `Bearer ${token}` }
    });
    
    if (!res.ok) {
      throw new Error("Export mislukt");
    }
    
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `pornfree_backup_${new Date().toISOString().split('T')[0]}.sql`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    if (window.Toast) Toast.success("Database geëxporteerd ✅");
    else console.log("Database geëxporteerd ✅");
  } catch (e) {
    alert("Export mislukt: " + e.message);
  }
}

// Load Database Stats
async function loadDatabaseStats() {
  try {
    const data = await apiFetch("admin_db_stats.php");
    const stats = $("dbStats");
    if (stats) {
      stats.style.display = "block";
      stats.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
          ${Object.entries(data.stats || {}).map(([key, value]) => `
            <div class="stat-card">
              <div class="stat-label">${key.replace(/_/g, ' ')}</div>
              <div class="stat-value">${value}</div>
            </div>
          `).join('')}
        </div>
      `;
    }
  } catch (e) {
    if ($("dbStats")) {
      $("dbStats").innerHTML = `<p class='error'>Fout: ${e.message}</p>`;
      $("dbStats").style.display = "block";
    }
  }
}

// Check Expired Subscriptions
async function checkExpiredSubscriptions() {
  const msg = $("maintenanceMsg");
  if (msg) setMsg(msg, "Controleren...");
  
  try {
    const data = await apiFetch("check_expired_subscriptions.php");
    if (msg) setMsg(msg, data.message || "Check voltooid ✅", "ok");
    await loadSubscriptions();
    await loadStats();
  } catch (e) {
    if (msg) setMsg(msg, "Fout: " + e.message, "error");
  }
}

// Cleanup Old Logs
async function cleanupOldLogs() {
  if (!confirm("Weet je zeker dat je oude logs wilt opruimen? (ouder dan 90 dagen)")) return;
  
  const msg = $("maintenanceMsg");
  if (msg) setMsg(msg, "Opruimen...");
  
  try {
    const data = await apiFetch("admin_cleanup_logs.php", { method: "POST" });
    if (msg) setMsg(msg, data.message || "Logs opgeruimd ✅", "ok");
    // Activity logs removed - whitelist-only system
  } catch (e) {
    if (msg) setMsg(msg, "Fout: " + e.message, "error");
  }
}

// System Health Check
async function runHealthCheck() {
  try {
    console.log("Running health check...");
    const data = await apiFetch("admin_health.php");
    console.log("Health check response:", data);
    const health = data;
    
    if (!health || !health.checks) {
      throw new Error("Invalid health check response");
    }
    
    // Update health cards
    if (health.checks.database) {
      updateHealthCard("healthDb", health.checks.database);
    }
    if (health.checks.api) {
      updateHealthCard("healthApi", health.checks.api);
    }
    if (health.checks.disk) {
      updateHealthCard("healthDisk", health.checks.disk);
    }
    if (health.checks.backup) {
      updateHealthCard("healthBackup", health.checks.backup);
    }
    
    // Generate detailed report
    const report = $("healthReport");
    if (report) {
      let html = `<div style="display: grid; gap: 10px;">`;
      
      Object.entries(health.checks || {}).forEach(([key, check]) => {
        const statusIcon = check.status === 'ok' ? '✅' : check.status === 'warning' ? '⚠️' : '❌';
        const statusColor = check.status === 'ok' ? '#10b981' : check.status === 'warning' ? '#f59e0b' : '#ef4444';
        
        html += `
          <div style="padding: 12px; background: var(--card); border: 1px solid var(--line); border-radius: 8px; border-left: 4px solid ${statusColor};">
            <div style="display: flex; align-items: center; gap: 10px;">
              <span style="font-size: 20px;">${statusIcon}</span>
              <div>
                <strong>${key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ')}</strong>
                <div class="small" style="margin-top: 4px;">${check.message || 'OK'}</div>
              </div>
            </div>
          </div>
        `;
      });
      
      html += `</div>`;
      report.innerHTML = html;
    }
    
    // Update overall status
    // Count errors vs warnings
    let errorCount = 0;
    let warningCount = 0;
    Object.values(health.checks || {}).forEach(check => {
      if (check.status === 'error') errorCount++;
      else if (check.status === 'warning') warningCount++;
    });
    
    if (health.status === 'unhealthy' || errorCount > 0) {
      if (window.Toast) {
        Toast.error(`❌ System Health: ${errorCount} kritieke probleem(en) gevonden`);
      } else {
        console.error(`❌ System Health: ${errorCount} kritieke probleem(en) gevonden`);
      }
    } else if (warningCount > 0) {
      if (window.Toast) {
        Toast.warning(`⚠️ System Health: ${warningCount} waarschuwing(en) - systeem werkt normaal`);
      } else {
        console.warn(`⚠️ System Health: ${warningCount} waarschuwing(en) - systeem werkt normaal`);
      }
    } else {
      if (window.Toast) {
        Toast.success("✅ System Health: Alles OK");
      } else {
        console.log("✅ System Health: Alles OK");
      }
    }
  } catch (e) {
    console.error("Health check error:", e);
    if (window.Toast) {
      Toast.error("❌ Health check mislukt: " + e.message);
    } else {
      console.error("❌ Health check mislukt: " + e.message);
    }
  }
}

function updateHealthCard(cardId, check) {
  const card = $(cardId);
  if (!card) {
    console.warn('Health card not found:', cardId);
    return;
  }
  
  if (!check || typeof check !== 'object') {
    console.warn('Invalid check data for', cardId, check);
    card.querySelector('.stat-value').innerHTML = '<span style="color: #ef4444;">❌ Error</span>';
    return;
  }
  
  const statusIcon = check.status === 'ok' ? '✅' : check.status === 'warning' ? '⚠️' : '❌';
  const statusText = check.status === 'ok' ? 'OK' : check.status === 'warning' ? 'Warning' : 'Error';
  const statusColor = check.status === 'ok' ? '#10b981' : check.status === 'warning' ? '#f59e0b' : '#ef4444';
  
  const valueEl = card.querySelector('.stat-value');
  if (valueEl) {
    valueEl.innerHTML = `<span style="color: ${statusColor}; font-size: 20px;">${statusIcon} ${statusText}</span>`;
    
    // Add message if available
    if (check.message) {
      const messageEl = card.querySelector('.stat-message') || document.createElement('div');
      if (!card.querySelector('.stat-message')) {
        messageEl.className = 'stat-message';
        messageEl.style.cssText = 'font-size: 12px; color: var(--text-secondary); margin-top: 8px;';
        card.appendChild(messageEl);
      }
      messageEl.textContent = check.message;
    }
  }
}

// Load Notification Settings
async function loadNotificationSettings() {
  try {
    const data = await apiFetch("admin_notifications.php");
    const settings = data.settings || {};
    
    if ($("adminEmail")) $("adminEmail").value = settings.admin_email || '';
    if ($("notifyNewUser")) $("notifyNewUser").checked = settings.notify_new_user !== false;
    if ($("notifyNewDevice")) $("notifyNewDevice").checked = settings.notify_new_device !== false;
    if ($("notifyExpiredSub")) $("notifyExpiredSub").checked = settings.notify_expired_sub !== false;
    if ($("notifyErrors")) $("notifyErrors").checked = settings.notify_errors !== false;
  } catch (e) {
    console.error("Load notifications error:", e);
  }
}

// Save Notification Settings
async function saveNotificationSettings() {
  const msg = $("notificationMsg");
  if (msg) setMsg(msg, "Opslaan...");
  
  try {
    const data = await apiFetch("admin_notifications.php", {
      method: "POST",
      body: JSON.stringify({
        admin_email: $("adminEmail")?.value || '',
        notify_new_user: $("notifyNewUser")?.checked || false,
        notify_new_device: $("notifyNewDevice")?.checked || false,
        notify_expired_sub: $("notifyExpiredSub")?.checked || false,
        notify_errors: $("notifyErrors")?.checked || false
      })
    });
    
    if (msg) setMsg(msg, data.message || "Instellingen opgeslagen ✅", "ok");
    if (window.Toast) Toast.success("Notificatie instellingen opgeslagen ✅");
    else console.log("Notificatie instellingen opgeslagen ✅");
  } catch (e) {
    if (msg) setMsg(msg, "Fout: " + e.message, "error");
  }
}

// Load Backup Settings
async function loadBackupSettings() {
  try {
    const data = await apiFetch("admin_backup_settings.php");
    const settings = data.settings || {};
    
    if ($("autoBackupEnabled")) $("autoBackupEnabled").checked = settings.enabled || false;
    if ($("backupTime")) $("backupTime").value = settings.time || '02:00';
  } catch (e) {
    console.error("Load backup settings error:", e);
  }
}

// Save Backup Settings
async function saveBackupSettings() {
  const msg = $("backupMsg");
  if (msg) setMsg(msg, "Opslaan...");
  
  try {
    const data = await apiFetch("admin_backup_settings.php", {
      method: "POST",
      body: JSON.stringify({
        enabled: $("autoBackupEnabled")?.checked || false,
        time: $("backupTime")?.value || '02:00'
      })
    });
    
    if (msg) setMsg(msg, data.message || "Backup instellingen opgeslagen ✅", "ok");
    if (window.Toast) Toast.success("Backup instellingen opgeslagen ✅");
    else console.log("Backup instellingen opgeslagen ✅");
  } catch (e) {
    if (msg) setMsg(msg, "Fout: " + e.message, "error");
  }
}



