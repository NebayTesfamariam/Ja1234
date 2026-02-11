<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Nieuw Wachtwoord Instellen - Porno-vrij Platform</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="wrap">
    <header>
      <h1>🔐 Nieuw Wachtwoord Instellen</h1>
      <p class="sub">Stel een nieuw wachtwoord in voor je account</p>
    </header>

    <section class="card">
      <h2>🔑 Nieuw Wachtwoord</h2>
      <p class="hint" style="background: rgba(79, 125, 249, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(79, 125, 249, 0.3); margin-bottom: 20px;">
        <strong>💡 Veiligheid:</strong><br>
        • Minimaal 6 tekens<br>
        • Gebruik een sterk wachtwoord<br>
        • Dit wachtwoord vervangt je oude wachtwoord
      </p>
      
      <div class="row">
        <input id="password" type="password" placeholder="Nieuw wachtwoord (min. 6 tekens)" />
      </div>
      <div class="row">
        <input id="passwordConfirm" type="password" placeholder="Bevestig wachtwoord" />
        <button id="resetBtn" class="primary">Wachtwoord Resetten</button>
      </div>
      
      <div id="message" class="msg"></div>
      
      <p class="hint" style="margin-top: 20px; text-align: center;">
        <a href="public/index.html" style="color: var(--primary); text-decoration: none;">← Terug naar inloggen</a>
      </p>
    </section>
  </div>

  <script src="js/toast.js"></script>
  <script>
    const $ = (id) => document.getElementById(id);
    
    // Get token from URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (!token) {
      document.body.innerHTML = `
        <div class="wrap">
          <section class="card">
            <h2>❌ Ongeldige Link</h2>
            <p class="hint" style="color: var(--danger);">
              Deze reset link is ongeldig of ontbreekt.<br>
              <a href="reset_password_request.html" style="color: var(--primary);">Vraag een nieuwe reset link aan</a>
            </p>
          </section>
        </div>
      `;
    }
    
    function setMsg(el, msg, isError = false) {
      if (!el) return;
      el.innerHTML = msg;
      el.className = "msg " + (isError ? "error" : "success");
      el.style.display = msg ? "block" : "none";
    }
    
    async function resetPassword() {
      const password = $("password").value.trim();
      const passwordConfirm = $("passwordConfirm").value.trim();
      
      if (!password) {
        setMsg($("message"), "Vul een wachtwoord in", true);
        return;
      }
      
      if (password.length < 6) {
        setMsg($("message"), "Wachtwoord moet minimaal 6 tekens lang zijn", true);
        return;
      }
      
      if (password !== passwordConfirm) {
        setMsg($("message"), "Wachtwoorden komen niet overeen", true);
        return;
      }
      
      $("resetBtn").disabled = true;
      $("resetBtn").textContent = "Resetten...";
      setMsg($("message"), "");
      
      try {
        const response = await fetch("api/password_reset.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ token, password })
        });
        
        const data = await response.json();
        
        if (response.ok) {
          setMsg($("message"), data.message || "Wachtwoord succesvol gereset! Je wordt doorgestuurd naar de login pagina...");
          Toast.success("Wachtwoord gereset!");
          
          // Redirect to login after 3 seconds
          setTimeout(() => {
            window.location.href = "public/index.html";
          }, 3000);
        } else {
          setMsg($("message"), data.message || "Er is een fout opgetreden", true);
          Toast.error(data.message || "Fout bij resetten");
        }
      } catch (e) {
        setMsg($("message"), "Er is een fout opgetreden: " + e.message, true);
        Toast.error("Fout: " + e.message);
      } finally {
        $("resetBtn").disabled = false;
        $("resetBtn").textContent = "Wachtwoord Resetten";
      }
    }
    
    $("resetBtn").onclick = resetPassword;
    
    // Allow Enter key to submit
    [$("password"), $("passwordConfirm")].forEach(input => {
      input.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          resetPassword();
        }
      });
    });
  </script>
</body>
</html>
