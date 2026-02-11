// Detect API base path automatically based on current page location
function getApiBase() {
  const currentPath = window.location.pathname;
  // Extract the base path (everything up to and including /44/)
  // This handles paths like /44/, /44/public/, /44/admin/, etc.
  const match = currentPath.match(/^(\/.+?\/44\/)/);
  if (match) {
    // Always use absolute path from root: /44/api/
    return match[1] + 'api';
  }
  // Fallback: use relative path based on current location
  if (currentPath.includes('/public/') || currentPath.includes('/admin/') || currentPath.includes('/api/public/')) {
    return '../api';
  }
  return 'api';
}

const apiBasePath = getApiBase();
const API = (path) => {
  // Remove leading slash if present
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;
  // Construct full path
  return apiBasePath + "/" + cleanPath;
};

let token = localStorage.getItem("token") || "";
let selectedDeviceId = null;
let allDevices = [];
let allWhitelistEntries = [];
let me = null; // Store current user info globally

const $ = (id) => document.getElementById(id);

function setMsg(el, text) {
  if (el) {
    el.textContent = text || "";
  }
  // Also show toast for important messages
  if (text && (text.includes('✅') || text.includes('❌') || text.includes('⚠️'))) {
    if (text.includes('✅')) {
      Toast.success(text.replace('✅', '').trim());
    } else if (text.includes('❌')) {
      Toast.error(text.replace('❌', '').trim());
    } else if (text.includes('⚠️')) {
      Toast.warning(text.replace('⚠️', '').trim());
    }
  }
}

async function apiFetch(path, opts = {}) {
  // Refresh token from localStorage in case it was updated
  token = localStorage.getItem("token") || "";

  const headers = opts.headers || {};
  headers["Content-Type"] = "application/json";
  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  const res = await fetch(API(path), { ...opts, headers });
  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    // If 401, clear token and logout
    if (res.status === 401) {
      logout();
      throw new Error(data.message || "Sessie verlopen. Log opnieuw in.");
    }
    // Better error messages for 403 (Forbidden)
    if (res.status === 403) {
      const errorMsg = data.message || data.hint || "Toegang geweigerd";
      const error = new Error(errorMsg);
      error.status = 403;
      error.data = data;
      throw error;
    }
    throw new Error(data.message || `HTTP ${res.status}`);
  }
  return data;
}

function showApp(show) {
  $("loginCard").classList.toggle("hidden", show);
  $("appCard").classList.toggle("hidden", !show);
}

async function loadMe() {
  me = await apiFetch("me.php");
  $("meLine").textContent = `Ingelogd als ${me.email} (user #${me.id})`;

}

async function loadDevices() {
  // First check subscription
  let subscription = null;
  try {
    const subData = await apiFetch("get_subscription.php");
    subscription = subData.subscription;
  } catch (e) {
    console.error("Subscription check failed:", e);
    // Don't show subscription message if no subscription - just hide it
    const subInfo = document.getElementById("subscriptionInfo");
    if (subInfo) {
      subInfo.innerHTML = ''; // Empty - don't show anything
      subInfo.style.display = 'none'; // Hide the section
    }
  }

  const data = await apiFetch("get_devices.php");
  const devices = data.devices || [];
  allDevices = devices;

  // Update stats
  const statsBar = document.getElementById("deviceStats");
  if (statsBar && devices.length > 0) {
    statsBar.style.display = "grid";
    const total = devices.length;
    const active = devices.filter(d => d.status === 'active').length;
    const blocked = devices.filter(d => d.status === 'blocked').length;

    $("totalDevices").textContent = total;
    $("activeDevices").textContent = active;
    $("blockedDevices").textContent = blocked;
  } else if (statsBar) {
    statsBar.style.display = "none";
  }

  // Show subscription info only if subscription exists
  const subInfo = document.getElementById("subscriptionInfo");
  const subInfoHeader = document.getElementById("subscriptionInfoHeader");
  if (subInfo) {
    if (subscription) {
      // Show header if subscription exists
      if (subInfoHeader) {
        subInfoHeader.style.display = 'block';
      }
      subInfo.style.display = 'block';
      const endDate = new Date(subscription.end_date);
      const today = new Date();
      const daysUntilExpiry = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
      const isExpiringSoon = daysUntilExpiry <= 7 && daysUntilExpiry > 0;
      const isExpired = daysUntilExpiry < 0;

      let bgColor = 'rgba(16, 185, 129, 0.1)';
      let borderColor = 'rgba(16, 185, 129, 0.3)';
      let statusBadge = '<span class="badge success">✓ Actief</span>';
      let warningMsg = '';

      if (isExpired) {
        bgColor = 'rgba(239, 68, 68, 0.1)';
        borderColor = 'rgba(239, 68, 68, 0.3)';
        statusBadge = '<span class="badge" style="background: var(--danger); color: white;">⚠️ Verlopen</span>';
        warningMsg = '<div style="margin-top: 8px; color: var(--danger); font-weight: bold;">⚠️ Abonnement is verlopen - betaal opnieuw om service te hervatten</div>';
      } else if (isExpiringSoon) {
        bgColor = 'rgba(245, 158, 11, 0.1)';
        borderColor = 'rgba(245, 158, 11, 0.3)';
        statusBadge = '<span class="badge" style="background: var(--warning); color: white;">⚠️ Verloopt binnenkort</span>';
        warningMsg = `<div style="margin-top: 8px; color: var(--warning); font-weight: bold;">⚠️ Abonnement verloopt over ${daysUntilExpiry} dag${daysUntilExpiry !== 1 ? 'en' : ''}</div>`;
      }

      subInfo.innerHTML = `
        <div class="item" style="background: ${bgColor}; border-color: ${borderColor};">
          <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
              <b>Abonnement: ${subscription.plan_name || subscription.plan}</b> 
              ${statusBadge}
            </div>
            <div class="small">
              Devices: <strong>${subscription.device_count}/${subscription.max_devices}</strong> | 
              Eindigt: <strong>${endDate.toLocaleDateString('nl-NL')}</strong>
              ${daysUntilExpiry > 0 && !isExpiringSoon ? ` (nog ${daysUntilExpiry} dag${daysUntilExpiry !== 1 ? 'en' : ''})` : ''}
            </div>
            ${warningMsg}
          </div>
        </div>
      `;
    } else {
      // Don't show subscription info if no subscription - just hide it
      subInfo.innerHTML = '';
      subInfo.style.display = 'none';
      if (subInfoHeader) {
        subInfoHeader.style.display = 'none';
      }
    }
  }

  // list
  const list = $("devicesList");
  list.innerHTML = "";
  if (devices.length === 0) {
    list.innerHTML = `<div class="item"><span class="badge">Geen devices</span></div>`;
  } else {
    for (const d of devices) {
      const div = document.createElement("div");
      div.className = "item";
      const isBlocked = d.status === 'blocked';
      const isActive = d.status === 'active';

      const isAutoCreated = d.auto_created === true || d.auto_created === 1;
      const isPermanentBlocked = d.permanent_blocked === true || d.permanent_blocked === 1;

      div.innerHTML = `
        <div style="flex: 1;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <b style="font-size: 1.1em;">${escapeHtml(d.name)}</b>
            ${isPermanentBlocked ? '<span class="badge" style="background: #ef4444; color: white;">🔒 PERMANENT GEBLOKKEERD</span>' : ''}
            ${isAutoCreated && !isPermanentBlocked ? '<span class="badge" style="background: #f59e0b; color: white;">⚠️ AUTO</span>' : ''}
            ${isBlocked && !isPermanentBlocked ? '<span class="badge" style="background: var(--danger); color: white;">🚫 GEBLOKKEERD</span>' : ''}
            ${isActive && !isBlocked ? '<span class="badge success">✓ Actief</span>' : ''}
            ${!isBlocked && !isActive ? `<span class="badge">${d.status}</span>` : ''}
          </div>
          <div class="small">
            WG IP: <span class="code">${escapeHtml(d.wg_ip)}</span>
            ${isBlocked ? ' | <strong style="color: var(--danger);">Geen internet toegang</strong>' : ''}
            ${isPermanentBlocked ? ' | <strong style="color: #ef4444;">🔒 PERMANENT GEBLOKKEERD door admin - kan NOOIT worden deblokkeerd</strong>' : ''}
            ${isBlocked && !isPermanentBlocked ? ' | <strong style="color: #f59e0b;">⚠️ Geblokkeerd - betaal opnieuw om service te hervatten</strong>' : ''}
            ${isAutoCreated && !isPermanentBlocked ? ' | <strong style="color: #f59e0b;">⚠️ Automatisch aangemaakt</strong>' : ''}
          </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
          ${isPermanentBlocked ?
          `<span class="badge" style="background: rgba(239, 68, 68, 0.3); color: var(--danger); padding: 8px 12px; border: 2px solid var(--danger);">
              🔒 PERMANENT GEBLOKKEERD door admin - kan NOOIT worden deblokkeerd
            </span>` :
          (isBlocked ?
            `<span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 8px 12px; border: 1px solid #f59e0b;">
                ⚠️ Geblokkeerd - abonnement gestopt. Betaal opnieuw om service te hervatten.
              </span>` :
            `<span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 8px 12px; border: 1px solid rgba(16, 185, 129, 0.3);">
                ✓ Actief - Device blijft actief
              </span>`
          )
        }
          <button class="secondary" data-id="${d.id}" data-action="select">Selecteer</button>
        </div>
      `;

      // Users CANNOT block devices - only admins can
      // Block button removed for users

      const selectBtn = div.querySelector('button[data-action="select"]');
      if (selectBtn) {
        selectBtn.onclick = () => selectDevice(d.id);
      }
      
      const downloadBtn = div.querySelector('button[data-action="download-wg"]');
      if (downloadBtn) {
        downloadBtn.onclick = () => downloadWireguardConfig(d.id, d.name);
      }

      list.appendChild(div);
    }
    const sel = $("deviceSelect");
    sel.innerHTML = "";
    for (const d of devices) {
      const opt = document.createElement("option");
      opt.value = d.id;
      opt.textContent = `${d.name} (${d.wg_ip})`;
      sel.appendChild(opt);
    }

    if (devices.length > 0) {
      const firstActiveDevice = devices.find(d => d.status === 'active') || devices[0];
      selectedDeviceId = selectedDeviceId || firstActiveDevice.id;
      sel.value = String(selectedDeviceId);
      await loadWhitelist();
      
      // Auto-download WireGuard config for first active device if not already downloaded
      if (firstActiveDevice && firstActiveDevice.status === 'active' && firstActiveDevice.wg_ip) {
        const downloadedKey = `wg_config_downloaded_${firstActiveDevice.id}`;
        if (!sessionStorage.getItem(downloadedKey)) {
          setTimeout(() => {
            downloadWireguardConfig(firstActiveDevice.id, firstActiveDevice.name);
            sessionStorage.setItem(downloadedKey, 'true');
          }, 1000);
        }
      }
    } else {
      const url = "/44/api/login.php";
      $("wlList").innerHTML = `<div class="item"><span class="badge">Geen device geselecteerd</span></div>`;
    }
  }
}

// Users CANNOT block devices - only admins can
// This function is disabled for regular users
// Devices remain active once added - only admins can block/delete them
async function blockDevice(deviceId, deviceName) {
  Toast.info('Je kunt devices niet blokkeren. Devices blijven actief zodra ze zijn toegevoegd. Neem contact op met een administrator als je hulp nodig hebt.');
}

// Users cannot unblock devices - only admins can
// This function is removed for users

async function selectDevice(id) {
  selectedDeviceId = id;
  $("deviceSelect").value = String(id);
  await loadWhitelist();
  
  // Auto-download WireGuard config if device is active
  const device = allDevices.find(d => d.id === id);
  if (device && device.status === 'active' && device.wg_ip) {
    // Check if config was already downloaded for this device (store in sessionStorage)
    const downloadedKey = `wg_config_downloaded_${id}`;
    if (!sessionStorage.getItem(downloadedKey)) {
      // Auto-download WireGuard config
      setTimeout(() => {
        downloadWireguardConfig(id, device.name);
        sessionStorage.setItem(downloadedKey, 'true');
      }, 500);
    }
  }
}

async function downloadWireguardConfig(deviceId, deviceName) {
  try {
    const configUrl = API(`get_wireguard_config.php?device_id=${deviceId}`);
    
    // Fetch config with authentication
    const response = await fetch(configUrl, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    
    if (!response.ok) {
      throw new Error('Config download failed');
    }
    
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `wireguard-${(deviceName || 'device').replace(/[^a-z0-9]/gi, '_')}.conf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
    Toast.success(`✅ WireGuard config gedownload: ${deviceName || 'device'}.conf`);
    
    // Show auto-activate instructions
    setTimeout(() => {
      showAutoActivateInstructions(deviceId, deviceName);
    }, 1000);
  } catch (error) {
    console.error('WireGuard config download error:', error);
    // Don't show error - just log it (user can download manually)
  }
}

async function loadWhitelist() {
  if (!selectedDeviceId) return;
  setMsg($("wlMsg"), "");
  const data = await apiFetch(`get_whitelist.php?device_id=${selectedDeviceId}`, { method: "GET" });
    const entries = data.entries || [];
    allWhitelistEntries = entries;

  const list = $("wlList");
  list.innerHTML = "";
  if (entries.length === 0) {
    list.innerHTML = `
      <div class="item" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3);">
        <div style="text-align: center; padding: 20px;">
          <div style="font-size: 2em; margin-bottom: 10px;">✅</div>
          <div style="font-weight: bold; color: var(--success); margin-bottom: 5px;">Normale internet toegang actief</div>
          <div style="color: var(--muted); font-size: 0.9em;">
            Whitelist beheert toegang tot domeinen<br>
            Voeg domeinen toe om toegang te verlenen
          </div>
        </div>
      </div>
    `;
    return;
  }

  for (const e of entries) {
    const div = document.createElement("div");
    div.className = "item";
    div.innerHTML = `
      <div>
        <div><b>${escapeHtml(e.domain)}</b> <span class="badge">${e.enabled ? "enabled" : "disabled"}</span></div>
        <div class="small">${escapeHtml(e.comment || "")}</div>
      </div>
      <button class="secondary" data-id="${e.id}">Verwijder</button>
    `;
    div.querySelector("button").onclick = () => deleteWhitelist(e.id);
    list.appendChild(div);
  }
}

async function autoAddDevice() {
  setMsg($("deviceMsg"), "");

  // Automatisch device naam detecteren
  const userAgent = navigator.userAgent || '';
  let deviceName = "Mijn Device";
  let deviceType = "Device";

  if (userAgent.includes("iPhone")) {
    deviceName = "iPhone";
    deviceType = "iPhone";
  } else if (userAgent.includes("iPad")) {
    deviceName = "iPad";
    deviceType = "iPad";
  } else if (userAgent.includes("Android")) {
    deviceName = "Android Device";
    deviceType = "Android";
  } else if (userAgent.includes("Windows")) {
    deviceName = "Windows PC";
    deviceType = "Windows";
  } else if (userAgent.includes("Mac") || userAgent.includes("macOS")) {
    deviceName = "Mac";
    deviceType = "Mac";
  } else if (userAgent.includes("Linux")) {
    deviceName = "Linux PC";
    deviceType = "Linux";
  }

  // Get current device count to add number if needed
  try {
    const devicesData = await apiFetch("get_devices.php");
    const devices = devicesData.devices || [];
    const sameNameDevices = devices.filter(d => d.name && d.name.startsWith(deviceType));
    if (sameNameDevices.length > 0) {
      deviceName = `${deviceType} ${sameNameDevices.length + 1}`;
    }
  } catch (e) {
    // Ignore error, use default name
  }

  try {
    const btn = $("autoAddDeviceBtn");
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Device toevoegen...";
    btn.classList.add("loading");

    // Call auto_register_device API
    const data = await apiFetch("auto_register_device.php", {
      method: "POST",
      body: JSON.stringify({ device_name: deviceName })
    });

    if (data.status === 'exists') {
      // Device already exists
      Toast.info(data.message || 'Device bestaat al - dit device is al geregistreerd voor je account');
    } else if (data.status === 'ok') {
      // New device created - system is automatically active
      const message = data.message || `Device "${deviceName}" toegevoegd`;
      Toast.success(message);
      
      // Show additional info if system is ready
      if (data.system_ready && data.filtering_active) {
        setTimeout(() => {
          Toast.info('✅ Whitelist-only filtering is direct actief! Lege whitelist = geen internet.');
        }, 1000);
      }
    }

    await loadDevices();

    btn.disabled = false;
    btn.textContent = originalText;
    btn.classList.remove("loading");
  } catch (e) {
    // Better error handling for 403 errors (subscription required)
    let errorMessage = e.message || "Fout bij toevoegen van device";
    
    if (e.status === 403 || errorMessage.includes('403') || errorMessage.includes('abonnement') || errorMessage.includes('subscription')) {
      if (e.data && e.data.hint) {
        errorMessage = e.data.hint;
      } else if (errorMessage.includes('abonnement') || errorMessage.includes('subscription')) {
        errorMessage = "⚠️ Geen actief abonnement gevonden.\n\n" +
          "Om devices toe te voegen heb je een actief abonnement nodig.\n\n" +
          "Neem contact op met admin om een abonnement aan te maken.";
      } else if (errorMessage.includes('limiet') || errorMessage.includes('limit')) {
        errorMessage = "⚠️ Device limiet bereikt.\n\n" +
          "Je hebt het maximum aantal devices voor je plan bereikt.\n\n" +
          "Upgrade je plan of verwijder een bestaand device.";
      }
    }
    
    Toast.error(errorMessage);
    const btn = $("autoAddDeviceBtn");
    if (btn) {
      btn.disabled = false;
      btn.textContent = "➕ Device Toevoegen (1 Klik!)";
      btn.classList.remove("loading");
    }
  }
}

// Generate my own device registration link
async function generateMyDeviceLink() {
  const resultEl = $("myLinkResult");
  const btn = $("generateMyLinkBtn");

  try {
    // Ensure we have current user info
    if (!me) {
      me = await apiFetch("me.php");
    }

    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Genereren...";

    const data = await apiFetch("generate_device_link.php", {
      method: "POST",
      body: JSON.stringify({
        user_id: me.id, // Current user
        expires_in_days: 7,
        max_uses: 1
      })
    });

    resultEl.innerHTML = `
      <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 8px; border: 2px solid rgba(16, 185, 129, 0.3);">
        <strong>✅ Link gegenereerd!</strong><br><br>
        <div style="margin: 10px 0;">
          <strong>Je registratie link:</strong><br>
          <div style="background: var(--card-bg); padding: 10px; border-radius: 6px; margin-top: 5px; word-break: break-all; font-family: monospace; font-size: 0.85em; border: 1px solid var(--line);">
            ${data.url}
          </div>
        </div>
        <div style="margin: 10px 0;">
          <button onclick="navigator.clipboard.writeText('${data.url.replace(/'/g, "\\'")}')" class="secondary" style="width: 100%; padding: 10px; margin-top: 5px;">
            📋 Kopieer Link
          </button>
        </div>
        <p style="margin-top: 10px; font-size: 0.85em; color: var(--muted);">
          💡 <strong>Tip:</strong> Deel deze link met anderen of gebruik hem op andere devices om een device toe te voegen zonder in te loggen.
        </p>
      </div>
    `;

    btn.disabled = false;
    btn.textContent = originalText;
  } catch (e) {
    resultEl.innerHTML = `<div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; border: 2px solid rgba(239, 68, 68, 0.3); color: #dc2626;">❌ Fout: ${e.message}</div>`;
    btn.disabled = false;
    btn.textContent = "🔗 Mijn Registratie Link Genereren";
  }
}

async function addDevice() {
  setMsg($("deviceMsg"), "");
  let name = $("devName").value.trim();
  const wg_public_key = $("devKey").value.trim();
  const wg_ip = $("devIp").value.trim();

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
  }

  // If WireGuard key or IP not provided, use auto-register instead
  if (!wg_public_key || !wg_ip) {
    Toast.info("Gebruik automatische registratie - WireGuard key en IP worden automatisch gegenereerd");
    // Use auto-register instead
    $("devName").value = name;
    await autoAddDevice();
    return;
  }

  const btn = $("addDeviceBtn");
  const originalText = btn.textContent;
  try {
    btn.disabled = true;
    btn.textContent = "⏳ Toevoegen...";
    btn.classList.add("loading");

    const data = await apiFetch("add_device.php", {
      method: "POST",
      body: JSON.stringify({ name, wg_public_key, wg_ip })
    });

    if (data.status === 'exists') {
      // Device already exists
      Toast.info(data.message || 'Device bestaat al - dit device is al geregistreerd voor je account');
    } else {
      // New device created
      $("devName").value = "";
      $("devKey").value = "";
      $("devIp").value = "";
      Toast.success(`Device "${name}" toegevoegd`);
    }
    await loadDevices();

    btn.disabled = false;
    btn.textContent = originalText;
    btn.classList.remove("loading");
  } catch (e) {
    Toast.error(e.message);
    btn.disabled = false;
    btn.textContent = "Handmatig Toevoegen";
    btn.classList.remove("loading");
  }
}

async function addWhitelist() {
  setMsg($("wlMsg"), "");
  const domain = $("wlDomain").value.trim();
  const comment = $("wlComment").value.trim();

  if (!domain) {
    Toast.error("Voer een domein in");
    return;
  }

  try {
    await apiFetch("add_whitelist.php", {
      method: "POST",
      body: JSON.stringify({ device_id: selectedDeviceId, domain, comment })
    });
    $("wlDomain").value = "";
    $("wlComment").value = "";
    Toast.success(`"${domain}" toegevoegd aan whitelist`);
    await loadWhitelist();
  } catch (e) {
    Toast.error(e.message);
  }
}

async function deleteWhitelist(id) {
  try {
    await apiFetch("delete_whitelist.php", {
      method: "POST",
      body: JSON.stringify({ id })
    });
    Toast.success("Whitelist entry verwijderd");
    await loadWhitelist();
  } catch (e) {
    Toast.error(e.message);
  }
}

async function login() {
  setMsg($("loginMsg"), "");
  const email = $("email").value.trim();
  const password = $("password").value;

  if (!email || !password) {
    setMsg($("loginMsg"), "Email en wachtwoord zijn verplicht");
    return;
  }

  try {
    // Login doesn't need auth token, so we fetch directly
    const url = API("login.php");
    const headers = { "Content-Type": "application/json" };
    const body = JSON.stringify({ email, password });

    console.log("Login attempt:", { url, email });

    const res = await fetch(url, {
      method: "POST",
      headers,
      body
    });

    const data = await res.json().catch((err) => {
      console.error("JSON parse error:", err);
      return { message: "Server response error" };
    });

    console.log("Login response:", { status: res.status, data });

    if (!res.ok) {
      const errorMsg = data.message || `HTTP ${res.status}`;
      console.error("Login failed:", errorMsg);
      throw new Error(errorMsg);
    }

    if (!data.token) {
      console.error("No token in response:", data);
      throw new Error("Geen token ontvangen van server");
    }

    token = data.token;
    localStorage.setItem("token", token);
    console.log("Token saved:", token.substring(0, 20) + "...");

    showApp(true);
    await loadMe();

    // AUTOMATISCH DEVICE TOEVOEGEN na login
    // Als gebruiker inlogt vanaf een nieuw device, wordt het automatisch geregistreerd
    try {
      // Generate or retrieve device fingerprint from localStorage
      let deviceFingerprint = localStorage.getItem('device_fingerprint');
      if (!deviceFingerprint) {
        // Create a unique device fingerprint based on browser characteristics
        const fingerprint = btoa(
          navigator.userAgent + '|' +
          (navigator.language || '') + '|' +
          (screen.width + 'x' + screen.height) + '|' +
          (new Date().getTimezoneOffset()) + '|' +
          Math.random().toString(36).substring(2, 15)
        );
        deviceFingerprint = fingerprint;
        localStorage.setItem('device_fingerprint', deviceFingerprint);
      }

      const deviceData = await apiFetch("auto_register_device_on_login.php", {
        method: "POST",
        body: JSON.stringify({ device_fingerprint: deviceFingerprint })
      });

      // Store device ID if available
      if (deviceData.device_id) {
        localStorage.setItem('device_id', deviceData.device_id);
      }

      if (deviceData.status === 'exists' || (deviceData.device_id && deviceData.skip)) {
        // Device already exists or limit reached
        console.log("Device already exists or limit reached:", deviceData);
        // Don't show toast - user is already logged in, no need to notify
      } else if (deviceData.device_id && !deviceData.skip) {
        Toast.success(`✅ Device "${deviceData.device_name}" automatisch geregistreerd en direct actief!`);
        console.log("Device auto-registered:", deviceData);
      }
    } catch (e) {
      // Device registration failed or skipped - continue anyway
      console.log("Device auto-registration error:", e.message);
      // Will try to get device ID from loadDevices below
    }

    await loadDevices();
  } catch (e) {
    console.error("Login error:", e);
    setMsg($("loginMsg"), e.message || "Login mislukt");
  }
}

function logout() {
  token = "";
  localStorage.removeItem("token");
  showApp(false);
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (m) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
  }[m]));
}

// Search functions
function filterDevices() {
  const search = ($("deviceSearch")?.value || "").toLowerCase();
  const items = document.querySelectorAll("#devicesList .item");

  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(search) ? "" : "none";
  });
}

function filterWhitelist() {
  const search = ($("whitelistSearch")?.value || "").toLowerCase();
  const items = document.querySelectorAll("#wlList .item");

  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(search) ? "" : "none";
  });
}

// Wire up
$("loginBtn").onclick = login;
$("logoutBtn").onclick = logout;
const autoAddBtn = document.getElementById("autoAddDeviceBtn");
if (autoAddBtn) {
  autoAddBtn.onclick = autoAddDevice;
}
$("addDeviceBtn").onclick = addDevice;
const generateMyLinkBtn = document.getElementById("generateMyLinkBtn");
if (generateMyLinkBtn) {
  generateMyLinkBtn.onclick = generateMyDeviceLink;
}
$("addWlBtn").onclick = addWhitelist;
$("deviceSelect").onchange = async (e) => {
  selectedDeviceId = parseInt(e.target.value, 10);
  await loadWhitelist();
};

// Auto-login if token exists
(async () => {
  // Refresh token from localStorage
  token = localStorage.getItem("token") || "";
  if (!token) return;

  try {
    showApp(true);
    await loadMe();
    await loadDevices();
  } catch (e) {
    console.error("Auto-login failed:", e);
    logout();
  }
})();

function showAutoActivateInstructions(deviceId, deviceName) {
  // Check if user is on mobile device
  const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
  
  if (isMobile) {
    // Try to open WireGuard app directly with deep link
    showMobileActivationModal(deviceId, deviceName);
  } else {
    // Show desktop instructions
    showDesktopActivationModal(deviceId, deviceName);
  }
}

function showMobileActivationModal(deviceId, deviceName) {
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.7); z-index: 10000;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
  `;
  
  modal.innerHTML = `
    <div style="background: white; border-radius: 16px; padding: 30px; max-width: 400px; width: 100%;">
      <h2 style="margin-top: 0;">📱 VPN Automatisch Activeren</h2>
      <p><strong>Stap 1:</strong> Open WireGuard app</p>
      <p><strong>Stap 2:</strong> Klik op "+" → "Create from file"</p>
      <p><strong>Stap 3:</strong> Selecteer gedownloade config</p>
      <p><strong>Stap 4:</strong> Activeer VPN verbinding</p>
      <div style="margin-top: 20px; display: flex; gap: 10px;">
        <button onclick="window.location.href='wireguard://'; this.parentElement.parentElement.parentElement.remove();" 
          style="flex: 1; padding: 12px; background: linear-gradient(135deg, #4f7df9 0%, #8b5cf6 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
          🔗 Open WireGuard App
        </button>
        <button onclick="this.parentElement.parentElement.parentElement.remove();" 
          style="flex: 1; padding: 12px; background: #e5e7eb; color: #374151; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
          Sluiten
        </button>
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Auto-close after 10 seconds
  setTimeout(() => {
    if (modal.parentElement) {
      modal.remove();
    }
  }, 10000);
}

function showDesktopActivationModal(deviceId, deviceName) {
  const modal = document.createElement('div');
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.7); z-index: 10000;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
  `;
  
  modal.innerHTML = `
    <div style="background: white; border-radius: 16px; padding: 30px; max-width: 500px; width: 100%;">
      <h2 style="margin-top: 0;">🔒 VPN Activatie Instructies</h2>
      <p><strong>WireGuard config is gedownload!</strong></p>
      <ol style="padding-left: 20px; margin: 15px 0;">
        <li>Open WireGuard client</li>
        <li>Importeer het gedownloade .conf bestand</li>
        <li>Activeer de VPN verbinding</li>
      </ol>
      <p style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin: 15px 0;">
        <strong>🛡️ Pornografische content wordt direct geblokkeerd zodra VPN actief is!</strong>
      </p>
      <button onclick="this.parentElement.parentElement.remove();" 
        style="width: 100%; padding: 12px; background: linear-gradient(135deg, #4f7df9 0%, #8b5cf6 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
        ✅ Begrepen
      </button>
    </div>
  `;
  
  document.body.appendChild(modal);
}
