<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Device Registreren - Porno-vrij</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .card {
      max-width: 500px;
      width: 100%;
    }
    .spinner {
      border: 4px solid rgba(16, 185, 129, 0.1);
      border-top: 4px solid #10b981;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 20px auto;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <section class="card">
      <h2>📱 Device Registreren</h2>
      <div id="status">
        <div class="spinner"></div>
        <p style="text-align: center; margin-top: 20px;">Device wordt geregistreerd...</p>
      </div>
      <div id="result" style="display: none;"></div>
    </section>
  </div>

  <script>
    // Get token from URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (!token) {
      document.getElementById('status').innerHTML = `
        <div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; border: 2px solid rgba(239, 68, 68, 0.3); color: #dc2626;">
          <strong>❌ Fout:</strong> Geen token gevonden in de URL.<br>
          Controleer of je de volledige link hebt gebruikt.
        </div>
      `;
    } else {
      // Register device via API
      registerDevice(token);
    }

    async function registerDevice(token) {
      try {
        // Get base URL
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
        const apiUrl = baseUrl + '/api/add_device_via_link.php';

        const response = await fetch(apiUrl + '?token=' + encodeURIComponent(token), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            token: token
          })
        });

        const data = await response.json();

        document.getElementById('status').style.display = 'none';
        const resultEl = document.getElementById('result');
        resultEl.style.display = 'block';

        if (response.ok && data.status === 'created') {
          resultEl.innerHTML = `
            <div style="background: rgba(16, 185, 129, 0.1); padding: 20px; border-radius: 8px; border: 2px solid rgba(16, 185, 129, 0.3);">
              <h3 style="color: #10b981; margin-top: 0;">✅ Device Succesvol Geregistreerd!</h3>
              <p><strong>Device naam:</strong> ${data.device_name || 'Onbekend'}</p>
              <p><strong>Device ID:</strong> ${data.device_id || 'N/A'}</p>
              ${data.wg_public_key ? `<p><strong>WireGuard Key:</strong> <code style="font-size: 0.85em; word-break: break-all;">${data.wg_public_key}</code></p>` : ''}
              ${data.wg_ip ? `<p><strong>IP Adres:</strong> <code>${data.wg_ip}</code></p>` : ''}
              <p style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(16, 185, 129, 0.3);">
                <strong>🎉 Gefeliciteerd!</strong><br>
                Je device is nu geregistreerd en beschermd tegen pornografische content.
              </p>
            </div>
          `;
        } else if (data.status === 'exists') {
          resultEl.innerHTML = `
            <div style="background: rgba(59, 130, 246, 0.1); padding: 20px; border-radius: 8px; border: 2px solid rgba(59, 130, 246, 0.3);">
              <h3 style="color: #3b82f6; margin-top: 0;">ℹ️ Device Bestaat Al</h3>
              <p>${data.message || 'Dit device is al geregistreerd.'}</p>
              ${data.device_name ? `<p><strong>Device naam:</strong> ${data.device_name}</p>` : ''}
            </div>
          `;
        } else {
          throw new Error(data.message || 'Onbekende fout');
        }
      } catch (error) {
        document.getElementById('status').style.display = 'none';
        const resultEl = document.getElementById('result');
        resultEl.style.display = 'block';
        resultEl.innerHTML = `
          <div style="background: rgba(239, 68, 68, 0.1); padding: 20px; border-radius: 8px; border: 2px solid rgba(239, 68, 68, 0.3); color: #dc2626;">
            <h3 style="color: #dc2626; margin-top: 0;">❌ Registratie Mislukt</h3>
            <p><strong>Fout:</strong> ${error.message || 'Onbekende fout opgetreden'}</p>
            <p style="margin-top: 15px; font-size: 0.9em;">
              Mogelijke oorzaken:<br>
              • De link is verlopen<br>
              • De link is al gebruikt (max aantal keer bereikt)<br>
              • De link is ongeldig<br>
              • Er is een serverfout opgetreden
            </p>
          </div>
        `;
      }
    }
  </script>
</body>
</html>
