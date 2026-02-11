<?php
/**
 * Comprehensive Website Check
 * Tests all components of the system
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Check - Volledige Systeem Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5em;
        }
        .check-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }
        .check-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .check-item {
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .check-item .status {
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9em;
        }
        .status.ok { background: #10b981; color: white; }
        .status.error { background: #ef4444; color: white; }
        .status.warning { background: #f59e0b; color: white; }
        .status.info { background: #6b7280; color: white; opacity: 0.8; }
        .check-item .name {
            flex: 1;
            font-weight: 500;
        }
        .check-item .details {
            font-size: 0.85em;
            color: #666;
            margin-top: 4px;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: center;
        }
        .summary h2 {
            margin-bottom: 15px;
            font-size: 2em;
        }
        .summary .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .summary .stat {
            padding: 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            margin: 5px;
            min-width: 150px;
        }
        .summary .stat .number {
            font-size: 2.5em;
            font-weight: bold;
        }
        .summary .stat .label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.85em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Volledige Website Check</h1>
        
        <?php
        $checks = [];
        $total = 0;
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        function add_check($section, $name, $status, $details = '') {
            global $checks, $total, $passed, $failed, $warnings;
            $total++;
            if ($status === 'ok') $passed++;
            elseif ($status === 'error') $failed++;
            elseif ($status === 'warning') $warnings++;
            // 'info' status doesn't count as warning or error
            
            if (!isset($checks[$section])) {
                $checks[$section] = [];
            }
            $checks[$section][] = [
                'name' => $name,
                'status' => $status,
                'details' => $details
            ];
        }
        
        // ============================================
        // 1. PHP CONFIGURATION
        // ============================================
        $php_version = PHP_VERSION;
        $php_ok = version_compare($php_version, '7.4.0', '>=');
        add_check('PHP', 'PHP Versie', $php_ok ? 'ok' : 'warning', "Versie: $php_version (min 7.4.0 vereist)");
        
        $extensions = ['mysqli', 'json', 'mbstring', 'curl'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            add_check('PHP', "PHP Extension: $ext", $loaded ? 'ok' : 'error', $loaded ? 'Geladen' : 'Niet geladen');
        }
        
        // ============================================
        // 2. CONFIGURATION FILES
        // ============================================
        $config_files = [
            'config.php',
            'config_security.php',
            'config_security_advanced.php',
            'config_validation.php',
            'config_logging.php',
            'config_production.php',
            'config_porn_block.php'
        ];
        
        foreach ($config_files as $file) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $required = in_array($file, ['config.php', 'config_porn_block.php']);
            add_check('Configuratie', $file, 
                $exists ? 'ok' : ($required ? 'error' : 'warning'),
                $exists ? 'Bestaat' : ($required ? 'VERPLICHT - Bestand ontbreekt' : 'Optioneel - Bestand ontbreekt')
            );
        }
        
        // ============================================
        // 3. DATABASE CONNECTION
        // ============================================
        try {
            if (file_exists(__DIR__ . '/config.php')) {
                require __DIR__ . '/config.php';
                
                if (isset($conn) && $conn instanceof mysqli) {
                    if ($conn->connect_error) {
                        add_check('Database', 'Database Connectie', 'error', "Error: " . $conn->connect_error);
                    } else {
                        $ping = @$conn->ping();
                        if ($ping) {
                            add_check('Database', 'Database Connectie', 'ok', "Verbonden met: " . ($conn->host_info ?? 'unknown'));
                            
                            // Check tables
                            $tables = ['users', 'devices', 'whitelist', 'subscriptions'];
                            foreach ($tables as $table) {
                                $result = @$conn->query("SHOW TABLES LIKE '$table'");
                                $exists = $result && $result->num_rows > 0;
                                add_check('Database', "Tabel: $table", $exists ? 'ok' : 'error', $exists ? 'Bestaat' : 'Ontbreekt');
                            }
                            
                            // Check admin user
                            $result = @$conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
                            if ($result) {
                                $row = $result->fetch_assoc();
                                $admin_count = (int)$row['count'];
                                // Check if there are any users at all
                                $user_result = @$conn->query("SELECT COUNT(*) as count FROM users");
                                $user_count = 0;
                                if ($user_result) {
                                    $user_row = $user_result->fetch_assoc();
                                    $user_count = (int)$user_row['count'];
                                }
                                
                                if ($admin_count > 0) {
                                    add_check('Database', 'Admin Gebruiker', 'ok', "$admin_count admin gebruiker(s)");
                                } elseif ($user_count > 0) {
                                    add_check('Database', 'Admin Gebruiker', 'warning', 
                                        'Geen admin gebruiker gevonden - Maak er een met: php create_admin_user.php <email> <wachtwoord>');
                                } else {
                                    add_check('Database', 'Admin Gebruiker', 'info', 
                                        'Geen gebruikers gevonden - Registreer eerste gebruiker via subscribe.html');
                                }
                            }
                        } else {
                            add_check('Database', 'Database Ping', 'error', 'Database ping mislukt');
                        }
                    }
                } else {
                    add_check('Database', 'Database Connectie', 'error', 'Database connectie niet beschikbaar');
                }
            } else {
                add_check('Database', 'Database Connectie', 'error', 'config.php niet gevonden');
            }
        } catch (Throwable $e) {
            add_check('Database', 'Database Connectie', 'error', "Exception: " . $e->getMessage());
        }
        
        // ============================================
        // 4. API ENDPOINTS
        // ============================================
        $api_files = [
            'api/login.php',
            'api/admin_check.php',
            'api/admin_stats.php',
            'api/admin_stats_enhanced.php',
            'api/get_whitelist.php',
            'api/add_whitelist.php',
            'api/get_wireguard_config.php',
            'api/register.php',
            'api/auto_register_device.php'
        ];
        
        foreach ($api_files as $file) {
            $exists = file_exists(__DIR__ . '/' . $file);
            add_check('API Endpoints', basename($file), $exists ? 'ok' : 'error', $exists ? 'Bestaat' : 'Bestand ontbreekt');
        }
        
        // ============================================
        // 5. FRONTEND FILES
        // ============================================
        $frontend_files = [
            'admin/index.html',
            'admin/admin.js',
            'public/index.html',
            'app.js',
            'subscribe.html',
            'superadmin_login.html'
        ];
        
        foreach ($frontend_files as $file) {
            $exists = file_exists(__DIR__ . '/' . $file);
            add_check('Frontend', basename($file), $exists ? 'ok' : 'error', $exists ? 'Bestaat' : 'Bestand ontbreekt');
        }
        
        // ============================================
        // 6. DNS SERVER
        // ============================================
        $dns_file = 'dns_whitelist_server.py';
        $dns_exists = file_exists(__DIR__ . '/' . $dns_file);
        add_check('DNS Server', 'DNS Server Script', $dns_exists ? 'ok' : 'warning', $dns_exists ? 'Bestaat' : 'Optioneel - Voor VPN filtering');
        
        // Check if DNS server is running (if on Linux/Mac)
        if (PHP_OS_FAMILY !== 'Windows') {
            $dns_running = false;
            $output = [];
            exec("ps aux | grep 'dns_whitelist_server.py' | grep -v grep 2>/dev/null", $output);
            if (!empty($output)) {
                $dns_running = true;
            }
            // DNS server is optioneel voor lokale development, maar verplicht voor productie
            // Don't show as warning if we're in development mode
            $is_dev = !$is_production;
            add_check('DNS Server', 'DNS Server Status', $dns_running ? 'ok' : ($is_dev ? 'info' : 'warning'), 
                $dns_running ? 'Draait' : ($is_dev ? 'Niet actief (optioneel voor lokale test)' : 'Niet actief - Start voor VPN filtering'));
        }
        
        // ============================================
        // 7. SECURITY CHECKS
        // ============================================
        $security_checks = [];
        
        // Check if porn blocking config exists
        if (file_exists(__DIR__ . '/config_porn_block.php')) {
            require_once __DIR__ . '/config_porn_block.php';
            if (function_exists('is_pornographic_domain')) {
                add_check('Security', 'Porn Blocking Function', 'ok', 'is_pornographic_domain() beschikbaar');
                
                // Test porn detection
                $test_domains = ['pornhub.com', 'google.com'];
                foreach ($test_domains as $domain) {
                    $is_porn = is_pornographic_domain($domain);
                    $expected = ($domain === 'pornhub.com');
                    $correct = ($is_porn === $expected);
                    add_check('Security', "Porn Detection: $domain", $correct ? 'ok' : 'error', 
                        $correct ? "Correct: " . ($is_porn ? 'Geblokkeerd' : 'Toegestaan') : "Fout: Verwachting " . ($expected ? 'geblokkeerd' : 'toegestaan'));
                }
            } else {
                add_check('Security', 'Porn Blocking Function', 'error', 'is_pornographic_domain() functie niet gevonden');
            }
        }
        
        // ============================================
        // 8. FILE PERMISSIONS
        // ============================================
        $writable_dirs = ['logs'];
        foreach ($writable_dirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            if (!file_exists($path)) {
                // Try to create it
                @mkdir($path, 0755, true);
            }
            
            if (file_exists($path)) {
                // Test actual write access
                $test_file = $path . '/.write_test_' . time();
                $writable = false;
                if (@file_put_contents($test_file, 'test') !== false) {
                    @unlink($test_file);
                    $writable = true;
                }
                
                if ($writable) {
                    add_check('Bestandsrechten', "Directory: $dir", 'ok', 'Schrijfbaar');
                } else {
                    // Try to fix permissions
                    $perms = fileperms($path);
                    $owner_readable = ($perms & 0400) ? true : false;
                    $owner_writable = ($perms & 0200) ? true : false;
                    
                    if (!$owner_writable) {
                        add_check('Bestandsrechten', "Directory: $dir", 'warning', 
                            'Niet schrijfbaar - Voer uit: chmod 755 logs of ./fix_logs_permissions.sh');
                    } else {
                        add_check('Bestandsrechten', "Directory: $dir", 'warning', 
                            'Schrijftest mislukt - Check permissies');
                    }
                }
            } else {
                add_check('Bestandsrechten', "Directory: $dir", 'warning', 
                    'Directory bestaat niet en kon niet worden aangemaakt');
            }
        }
        
        // ============================================
        // DISPLAY RESULTS
        // ============================================
        foreach ($checks as $section => $items) {
            echo "<div class='check-section'>";
            echo "<h2>" . htmlspecialchars($section) . "</h2>";
            foreach ($items as $item) {
                echo "<div class='check-item'>";
                echo "<div>";
                echo "<div class='name'>" . htmlspecialchars($item['name']) . "</div>";
                if ($item['details']) {
                    echo "<div class='details'>" . htmlspecialchars($item['details']) . "</div>";
                }
                echo "</div>";
                echo "<span class='status " . htmlspecialchars($item['status']) . "'>" . strtoupper($item['status']) . "</span>";
                echo "</div>";
            }
            echo "</div>";
        }
        
        // ============================================
        // SUMMARY
        // ============================================
        $percentage = $total > 0 ? round(($passed / $total) * 100) : 0;
        $overall_status = 'ok';
        if ($failed > 0) $overall_status = 'error';
        elseif ($warnings > 0) $overall_status = 'warning';
        
        echo "<div class='summary'>";
        echo "<h2>📊 Samenvatting</h2>";
        echo "<div class='stats'>";
        echo "<div class='stat'><div class='number'>$total</div><div class='label'>Totaal Checks</div></div>";
        echo "<div class='stat'><div class='number'>$passed</div><div class='label'>✅ Geslaagd</div></div>";
        echo "<div class='stat'><div class='number'>$warnings</div><div class='label'>⚠️ Waarschuwingen</div></div>";
        echo "<div class='stat'><div class='number'>$failed</div><div class='label'>❌ Gefaald</div></div>";
        echo "<div class='stat'><div class='number'>$percentage%</div><div class='label'>Succes Percentage</div></div>";
        echo "</div>";
        echo "<p style='margin-top: 20px; font-size: 1.2em;'>";
        if ($overall_status === 'ok') {
            echo "✅ <strong>Website Status: OPERATIONEEL</strong>";
        } elseif ($overall_status === 'warning') {
            echo "⚠️ <strong>Website Status: WERKT MET WAARSCHUWINGEN</strong>";
        } else {
            echo "❌ <strong>Website Status: PROBLEMEN GEVONDEN</strong>";
        }
        echo "</p>";
        echo "</div>";
        
        // ============================================
        // RECOMMENDATIONS
        // ============================================
        if ($failed > 0 || $warnings > 0) {
            echo "<div class='check-section'>";
            echo "<h2>💡 Aanbevelingen</h2>";
            
            if ($failed > 0) {
                echo "<div class='check-item'>";
                echo "<div class='name'>❌ Kritieke Problemen</div>";
                echo "<div class='details'>Er zijn " . $failed . " gefaalde checks. Los deze eerst op voordat je de website gebruikt.</div>";
                echo "</div>";
            }
            
            if ($warnings > 0) {
                echo "<div class='check-item'>";
                echo "<div class='name'>⚠️ Waarschuwingen</div>";
                echo "<div class='details'>Er zijn " . $warnings . " waarschuwingen. Deze zijn niet kritiek maar kunnen de functionaliteit beïnvloeden.</div>";
                echo "</div>";
            }
            
            // Specific recommendations
            $has_db_error = false;
            foreach ($checks as $section => $items) {
                if ($section === 'Database') {
                    foreach ($items as $item) {
                        if ($item['status'] === 'error' && strpos($item['name'], 'Connectie') !== false) {
                            $has_db_error = true;
                            break 2;
                        }
                    }
                }
            }
            
            if ($has_db_error) {
                echo "<div class='check-item'>";
                echo "<div class='name'>🔧 Database Connectie Probleem</div>";
                echo "<div class='details'>";
                echo "1. Check database credentials in config.php of config_production.php<br>";
                echo "2. Zorg dat MySQL/MariaDB draait<br>";
                echo "3. Test connectie met: <code>php api/test_login_debug.php</code>";
                echo "</div>";
                echo "</div>";
            }
            
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
