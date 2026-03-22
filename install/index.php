<?php
/**
 * NovaStar51 Express Install
 *
 * Fast one-page installation for developers and testing labs
 * All fields visible, optional test buttons, one-shot install
 */

// ============================================
// LOAD CONFIGURATION
// ============================================
$config = require 'config_mapping.php';
require 'system_detect.php';

// ============================================
// SECURITY: Block ALL requests if already installed
// ============================================
if (file_exists(__DIR__ . '/' . $config['paths']['lock'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Already installed']);
        exit;
    }
    // GET → show "already installed" page
    $lock_raw = trim(file_get_contents(__DIR__ . '/' . $config['paths']['lock']));
    $install_date = '';
    if (preg_match('/installed on (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lock_raw, $m)) {
        $install_date = $m[1];
    }
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>NovaStar51 - Già Installato</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    </head>
    <body class="bg-light py-5">
      <div class="container" style="max-width: 680px;">
        <header class="text-center mb-4">
          <h1 class="fw-bold">NovaStar51</h1>
          <div class="btn-group btn-group-sm mt-2" role="group">
            <button type="button" class="btn btn-outline-primary lang-btn" onclick="setLang('it')">Italiano</button>
            <button type="button" class="btn btn-primary lang-btn active" onclick="setLang('en')">English</button>
          </div>
        </header>

        <div class="card shadow">
          <div class="card-header bg-danger text-white text-center py-4">
            <h2 class="h4 mb-0" data-it="NovaStar51 Già Installato" data-en="NovaStar51 Already Installed">NovaStar51 Già Installato</h2>
          </div>
          <div class="card-body p-4">
            <div class="alert alert-danger">
              <strong data-it="Installazione bloccata." data-en="Installation is locked.">Installazione bloccata.</strong><br>
              <span data-it="Il sistema è già configurato." data-en="The system is already configured.">Il sistema è già configurato.</span>
              <?php if ($install_date): ?>
              <br><small class="text-muted" data-it="Installato il: <?= $install_date ?>" data-en="Installed on: <?= $install_date ?>">Installato il: <?= $install_date ?></small>
              <?php endif; ?>
            </div>

            <div class="mb-4">
              <strong data-it="Elimina /install/ per sicurezza:" data-en="Delete /install/ for security:">Elimina /install/ per sicurezza:</strong>
              <ul class="mb-3 mt-2">
                <li><strong>Nova Administrator &gt; System Configuration &gt; Install Cleanup</strong></li>
                <li><span data-it="Manualmente:" data-en="Manually:">Manualmente:</span> <span data-it="elimina la cartella" data-en="delete the folder">elimina la cartella</span> <code>/install/</code> <span data-it="via FTP/terminale" data-en="via FTP/terminal">via FTP/terminale</span></li>
              </ul>

              <strong data-it="Per reinstallare:" data-en="To reinstall:">Per reinstallare:</strong>
              <ul class="mb-3 mt-2">
                <li><span data-it="Elimina manualmente il file" data-en="Manually delete the file">Elimina manualmente il file</span> <code>.installed</code> <span data-it="dalla root del progetto" data-en="from the project root">dalla root del progetto</span></li>
                <li><span data-it="Ripristina la cartella" data-en="Restore the folder">Ripristina la cartella</span> <code>/install/</code></li>
              </ul>

              <div class="alert alert-danger mb-0">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong data-it="ATTENZIONE:" data-en="WARNING:">ATTENZIONE:</strong> <span data-it="Reinstallare eliminerà tutti i dati esistenti!" data-en="Reinstalling will delete all existing data!">Reinstallare eliminerà tutti i dati esistenti!</span>
              </div>
            </div>

            <a href="<?= $config['paths']['nova_login'] ?>" class="btn btn-primary btn-lg w-100 py-3" data-it="Vai a Nova Login" data-en="Go to Nova Login">
              Vai a Nova Login
            </a>
          </div>
        </div>

        <footer class="text-center text-muted py-4">
          NovaStar51 Express Install
        </footer>
      </div>
      <script>
        let currentLang = 'en';
        function setLang(lang) {
          currentLang = lang;
          document.querySelectorAll('.lang-btn').forEach(btn => {
            const isActive = (lang === 'it' && btn.textContent === 'Italiano') || (lang === 'en' && btn.textContent === 'English');
            btn.classList.toggle('active', isActive);
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-outline-primary', !isActive);
          });
          document.querySelectorAll('[data-' + lang + ']').forEach(el => {
            el.textContent = el.getAttribute('data-' + lang);
          });
        }
        setLang('en');
      </script>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Generate temporary password
 *
 * @param int $length Password length
 * @return string Generated password
 */
function generate_temp_password($length = 12) {
    global $config;
    $charset = $config['password']['temp_charset'];
    $password = '';
    $charset_length = strlen($charset);

    for ($i = 0; $i < $length; $i++) {
        $password .= $charset[random_int(0, $charset_length - 1)];
    }

    return $password;
}

// ============================================
// AJAX HANDLER: Test Database Connection
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'test_db') {
    header('Content-Type: application/json');

    $response = [
        'success' => false,
        'message' => '',
        'data' => []
    ];

    // Get posted data
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';

    // Validate inputs
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $response['message_it'] = 'Compila tutti i campi database';
        $response['message_en'] = 'Please fill in all required database fields';
        echo json_encode($response);
        exit;
    }

    // Quick test: MySQL port reachable?
    $mysql_port = $config['db']['port'];
    $fp = @fsockopen($db_host, $mysql_port, $errno, $errstr, 5);

    if (!$fp) {
        $response['error_field'] = 'host';
        $response['message_it'] = "Impossibile raggiungere MySQL su '$db_host' (porta $mysql_port)";
        $response['message_en'] = "Cannot reach MySQL server at '$db_host' (port $mysql_port)";
        echo json_encode($response);
        exit;
    }
    fclose($fp);

    // Try MySQL connection
    try {
        $conn = mysqli_connect($db_host, $db_user, $db_pass);

        if (!$conn) {
            $response['error_field'] = 'credentials';
            $response['message_it'] = "Accesso negato per '$db_user'";
            $response['message_en'] = "Access denied for user '$db_user'";
            echo json_encode($response);
            exit;
        }

    } catch (mysqli_sql_exception $e) {
        $error_msg = $e->getMessage();

        if (strpos($error_msg, 'Access denied') !== false) {
            $response['error_field'] = 'credentials';
            $response['message_it'] = "Username o password errati per '$db_user'";
            $response['message_en'] = "Wrong username or password for user '$db_user'";
        } else {
            $response['error_field'] = 'general';
            $response['message_it'] = 'Connessione MySQL fallita: ' . $error_msg;
            $response['message_en'] = 'MySQL connection failed: ' . $error_msg;
        }

        echo json_encode($response);
        exit;
    }

    // Try to select database
    try {
        if (!mysqli_select_db($conn, $db_name)) {
            mysqli_close($conn);
            $response['error_field'] = 'db_name';
            $response['message_it'] = "Database '$db_name' non trovato. Crealo prima via phpMyAdmin";
            $response['message_en'] = "Database '$db_name' not found. Create it via phpMyAdmin first";
            echo json_encode($response);
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        mysqli_close($conn);
        $response['error_field'] = 'db_name';
        $response['message_it'] = "Impossibile accedere al database '$db_name'";
        $response['message_en'] = "Cannot access database '$db_name'";
        echo json_encode($response);
        exit;
    }

    // Check MySQL version
    $mysql_version = mysqli_get_server_info($conn);
    $response['data']['mysql_version'] = $mysql_version;

    if (version_compare($mysql_version, $config['db']['min_version'], '<')) {
        mysqli_close($conn);
        $response['message_it'] = "Richiesto MySQL {$config['db']['min_version']}+ (attuale: $mysql_version)";
        $response['message_en'] = "MySQL {$config['db']['min_version']}+ required (current: $mysql_version)";
        echo json_encode($response);
        exit;
    }

    // Check if Star51 already installed
    try {
        $check_ns = mysqli_query($conn, "SHOW TABLES LIKE 'ns_admins'");

        if ($check_ns && mysqli_num_rows($check_ns) > 0) {
            mysqli_close($conn);
            $response['message_it'] = "Database '$db_name' ha già NovaStar51 installato! Usa un database diverso";
            $response['message_en'] = "Database '$db_name' already has NovaStar51 installed! Use a different database";
            $response['data']['star51_exists'] = true;
            echo json_encode($response);
            exit;
        }

        // Check if database is shared
        $all_tables = mysqli_query($conn, "SHOW TABLES");
        $table_count = mysqli_num_rows($all_tables);
    } catch (mysqli_sql_exception $e) {
        mysqli_close($conn);
        $response['message_it'] = "Query database fallita: " . $e->getMessage();
        $response['message_en'] = "Database query failed: " . $e->getMessage();
        echo json_encode($response);
        exit;
    }

    $response['data']['table_count'] = $table_count;

    if ($table_count > 0) {
        // Shared database warning
        $table_names = [];
        while ($row = mysqli_fetch_array($all_tables)) {
            $table_names[] = $row[0];
            if (count($table_names) >= 10) break;
        }
        $response['data']['table_names'] = $table_names;
        $response['data']['has_tables'] = true;
        $response['message_it'] = "⚠️ Il database contiene $table_count tabelle esistenti. NovaStar51 aggiungerà " . count($config['db']['required_tables']) . " nuove tabelle (ns_*)";
        $response['message_en'] = "⚠️ Database contains $table_count existing tables. NovaStar51 will add " . count($config['db']['required_tables']) . " new tables (ns_*)";
        $response['success'] = true;
        $response['warning'] = true;
    } else {
        // Perfect: empty database
        $response['success'] = true;
        $response['message_it'] = "✓ Connessione riuscita! MySQL $mysql_version | Database vuoto e pronto";
        $response['message_en'] = "✓ Connection successful! MySQL $mysql_version | Database empty and ready";
    }

    mysqli_close($conn);
    echo json_encode($response);
    exit;
}

// ============================================
// AJAX HANDLER: Test SMTP Connection
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'test_smtp') {
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];

    $gmail_email = trim($_POST['gmail_email'] ?? '');
    $gmail_app_password = str_replace(['-', ' '], '', trim($_POST['gmail_app_password'] ?? ''));

    // Validate Gmail email
    if (!filter_var($gmail_email, FILTER_VALIDATE_EMAIL)) {
        $response['message_it'] = 'Formato email non valido';
        $response['message_en'] = 'Invalid email address format';
        echo json_encode($response);
        exit;
    }

    if (!str_ends_with(strtolower($gmail_email), '@gmail.com')) {
        $response['message_it'] = 'Deve essere un indirizzo @gmail.com';
        $response['message_en'] = 'Must be a @gmail.com address';
        echo json_encode($response);
        exit;
    }

    // Validate App Password
    if (strlen($gmail_app_password) !== 16) {
        $response['message_it'] = 'Gmail App Password deve essere 16 caratteri';
        $response['message_en'] = 'Gmail App Password must be exactly 16 characters';
        echo json_encode($response);
        exit;
    }

    // Test SMTP port connectivity
    $smtp_host = $config['email']['host'];
    $smtp_port = $config['email']['port'];

    $smtp = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 5);

    if (!$smtp) {
        $response['message_it'] = "Impossibile connettersi a $smtp_host:$smtp_port";
        $response['message_en'] = "Cannot connect to $smtp_host:$smtp_port";
    } else {
        fclose($smtp);
        $response['success'] = true;
        $response['message_it'] = "✓ Connessione SMTP OK! Porta $smtp_port raggiungibile";
        $response['message_en'] = "✓ SMTP connection OK! Port $smtp_port reachable";
    }

    echo json_encode($response);
    exit;
}

// ============================================
// POST HANDLER: Install NovaStar51
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {

    $install_errors = [];
    $install_log_it = [];  // Log in italiano
    $install_log_en = [];  // Log in inglese

    // Helper: Add bilingual log message
    function logMsg($it, $en) {
        global $install_log_it, $install_log_en;
        $install_log_it[] = $it;
        $install_log_en[] = $en;
    }

    // Get form data
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';

    $admin_first = trim($_POST['admin_first'] ?? '');
    $admin_last = trim($_POST['admin_last'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';

    $gmail_email = trim($_POST['gmail_email'] ?? '');
    $gmail_app_password = trim($_POST['gmail_app_password'] ?? '');

    $site_url = rtrim(trim($_POST['site_url'] ?? ''), '/');

    // Basic validation
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $install_errors[] = 'Tutti i campi database sono obbligatori / All database fields are required';
    }
    if (empty($admin_first) || empty($admin_last) || empty($admin_email) || empty($admin_username) || empty($admin_password)) {
        $install_errors[] = 'Tutti i campi Super Admin sono obbligatori / All Super Admin fields are required';
    }
    if (strlen($admin_password) < $config['password']['min_length']) {
        $install_errors[] = "Password deve essere almeno {$config['password']['min_length']} caratteri / Password must be at least {$config['password']['min_length']} characters";
    }
    if ($admin_password !== $admin_password_confirm) {
        $install_errors[] = 'Le password non corrispondono / Passwords do not match';
    }
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $install_errors[] = 'Formato email non valido / Invalid email format';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $admin_username)) {
        $install_errors[] = 'Username può contenere solo lettere, numeri e _ / Username can only contain letters, numbers and _';
    }

    if (empty($install_errors)) {

        // Connect to database
        try {
            $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

            if (!$conn) {
                $install_errors[] = 'Impossibile connettersi al database / Cannot connect to database';
            }

        } catch (mysqli_sql_exception $e) {
            $install_errors[] = 'Connessione database fallita / Database connection failed: ' . $e->getMessage();
        }

        if (empty($install_errors)) {

            // STEP 1: Import SQL schema
            logMsg('Lettura schema SQL...', 'Reading SQL schema...');

            $sql_file = $config['paths']['sql'];

            if (!file_exists($sql_file)) {
                $install_errors[] = "File SQL non trovato / SQL file not found: $sql_file";
            } else {
                $sql_content = file_get_contents($sql_file);

                // Remove comments and split queries
                $sql_content = preg_replace('/^--.*$/m', '', $sql_content);
                $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

                $queries = array_filter(array_map('trim', explode(';', $sql_content)));

                logMsg('Trovate ' . count($queries) . ' SQL statements', 'Found ' . count($queries) . ' SQL statements');

                // Execute queries
                $query_count = 0;
                foreach ($queries as $query) {
                    if (!empty($query)) {
                        try {
                            mysqli_query($conn, $query);
                            $query_count++;
                        } catch (mysqli_sql_exception $e) {
                            if (stripos($query, 'CREATE TABLE') !== false || stripos($query, 'ALTER TABLE') !== false) {
                                $install_errors[] = 'SQL Error: ' . $e->getMessage();
                                break;
                            }
                        }
                    }
                }

                logMsg("Eseguite $query_count SQL statements", "Executed $query_count SQL statements");
            }

            // STEP 2: Insert Super Admin
            if (empty($install_errors)) {
                logMsg('Creazione account Super Admin...', 'Creating Super Admin account...');

                // PASSWORD LOGIC: Gmail configured? Use user password : Generate temp password
                $force_password_change = 0;
                $final_password = $admin_password;
                $temp_password_generated = null;

                if (empty($gmail_email) || empty($gmail_app_password)) {
                    // Gmail not configured → generate temp password
                    $temp_password_generated = generate_temp_password($config['password']['temp_length']);
                    $final_password = $temp_password_generated;
                    $force_password_change = 1;
                    logMsg('⚠️ Email disabilitata → Password temporanea generata', '⚠️ Email disabled → Temporary password generated');
                }

                $password_hash = password_hash($final_password, PASSWORD_DEFAULT);

                $sql_admin = "INSERT INTO ns_admins
                    (first_name, last_name, username, password, email, level, is_active, created_by, force_password_change)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                try {
                    $stmt = mysqli_prepare($conn, $sql_admin);
                    mysqli_stmt_bind_param($stmt, 'sssssiiii',
                        $admin_first,
                        $admin_last,
                        $admin_username,
                        $password_hash,
                        $admin_email,
                        $config['admin']['level'],
                        $config['admin']['active'],
                        $config['admin']['created_by'],
                        $force_password_change
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    logMsg("Super Admin creato: $admin_username", "Super Admin created: $admin_username");

                } catch (mysqli_sql_exception $e) {
                    $install_errors[] = 'Impossibile creare Super Admin / Cannot create Super Admin: ' . $e->getMessage();
                }
            }

            // STEP 3: Create nova_config_values.php (Unified Configuration - nova/conf/)
            if (empty($install_errors)) {
                logMsg('Creazione file configurazione sistema...', 'Creating system config file...');

                // Read existing defaults from config_values file (Single Source of Truth)
                $config_values_path = __DIR__ . '/' . $config['paths']['config_values'];
                if (file_exists($config_values_path)) {
                    include $config_values_path;
                    // $nova_settings is now available from the included file
                } else {
                    $install_errors[] = "File configurazione non trovato / Config file not found: {$config['paths']['config_values']}";
                }
            }

            // STEP 3b: Populate config with user data
            if (empty($install_errors) && isset($nova_settings)) {
                // Populate Database Local (Box A)
                $nova_settings['db_host_local'] = $db_host;
                $nova_settings['db_user_local'] = $db_user;
                $nova_settings['db_pass_local'] = $db_pass;
                $nova_settings['db_name_local'] = $db_name;

                // Populate Site URL if provided (Box D - SEO)
                if (!empty($site_url)) {
                    $nova_settings['site_url'] = $site_url;
                    logMsg('URL sito configurato: ' . $site_url, 'Site URL configured: ' . $site_url);
                }

                // Populate SMTP if provided (Box C)
                if (!empty($gmail_email) && !empty($gmail_app_password)) {
                    $nova_settings['smtp_user'] = $gmail_email;
                    $nova_settings['smtp_pass'] = str_replace(['-', ' '], '', $gmail_app_password);
                    logMsg('SMTP Gmail configurato', 'Gmail SMTP configured');
                }

                // Generate PHP file with var_export
                $config_content = "<?php
/**
 * Star51 Solo — System Configuration
 * Generated by Express Install on " . date('Y-m-d H:i:s') . "
 * Edit this file manually to change settings
 */

\$nova_settings = " . var_export($nova_settings, true) . ";
?>";

                if (!file_put_contents(__DIR__ . '/' . $config['paths']['config_values'], $config_content)) {
                    $install_errors[] = "Impossibile scrivere / Cannot write {$config['paths']['config_values']}";
                } else {
                    logMsg('Database configurato per Ambiente Locale', 'Database configured for Local Environment');
                }
            }

            // STEP 4: Create .installed lock file
            if (empty($install_errors)) {
                logMsg('Creazione file lock...', 'Creating installation lock file...');

                $lock_content = "NovaStar51 installed on " . date('Y-m-d H:i:s') . "\n";

                if (!file_put_contents(__DIR__ . '/' . $config['paths']['lock'], $lock_content)) {
                    $install_errors[] = "Impossibile creare / Cannot create {$config['paths']['lock']}";
                } else {
                    logMsg('Installazione completata - /install/ può essere eliminata', 'Installation locked - /install/ can be deleted');
                }
            }

            // STEP 5: Set writable permissions (chmod 777) on upload/config directories
            if (empty($install_errors)) {
                logMsg('Impostazione permessi cartelle (777)...', 'Setting directory permissions (777)...');

                $chmod_results = set_writable_permissions($config['paths']['writable']);
                $chmod_success = 0;
                $chmod_total = count($chmod_results);

                foreach ($chmod_results as $dir => $result) {
                    if ($result['success']) {
                        $chmod_success++;
                        logMsg("✓ $dir: {$result['message']}", "✓ $dir: {$result['message']}");
                    } else {
                        // Log warning but don't fail installation
                        logMsg("⚠ $dir: {$result['message']}", "⚠ $dir: {$result['message']}");
                    }
                }

                logMsg("Permessi: $chmod_success/$chmod_total cartelle configurate", "Permissions: $chmod_success/$chmod_total directories configured");
            }

            // STEP 6: Copy _htaccess_logs to nova/logs/.htaccess
            if (empty($install_errors)) {
                $htaccess_source = __DIR__ . '/_htaccess_logs';
                $htaccess_dest = __DIR__ . '/../nova/logs/.htaccess';

                if (file_exists($htaccess_source)) {
                    if (copy($htaccess_source, $htaccess_dest)) {
                        logMsg('✓ Protezione logs attivata (.htaccess)', '✓ Logs protection enabled (.htaccess)');
                    } else {
                        logMsg('⚠ Impossibile attivare protezione logs', '⚠ Could not enable logs protection');
                    }
                }
            }

            // STEP 7: Copy _htaccess_root to .htaccess (project root)
            if (empty($install_errors)) {
                $htaccess_root_source = __DIR__ . '/' . $config['paths']['htaccess_root'];
                $htaccess_root_dest = __DIR__ . '/' . $config['paths']['htaccess_dest'];

                if (file_exists($htaccess_root_source)) {
                    if (copy($htaccess_root_source, $htaccess_root_dest)) {
                        logMsg('✓ .htaccess creato (compressione, cache, sicurezza)', '✓ .htaccess created (compression, cache, security)');
                    } else {
                        logMsg('⚠ Impossibile creare .htaccess nella root', '⚠ Could not create .htaccess in root');
                    }
                }
            }

            // STEP 8: Generate robots.txt (only if site_url provided)
            if (empty($install_errors) && !empty($site_url)) {
                $robots_lines = ['User-agent: *', 'Allow: /'];

                foreach ($config['seo']['robots_disallow'] as $dir) {
                    $robots_lines[] = 'Disallow: ' . $dir;
                }

                $robots_lines[] = '';
                $robots_lines[] = 'Sitemap: ' . $site_url . '/sitemap.xml';

                $robots_content = implode("\n", $robots_lines) . "\n";

                if (file_put_contents(__DIR__ . '/' . $config['paths']['robots_txt'], $robots_content)) {
                    logMsg('✓ robots.txt generato', '✓ robots.txt generated');
                } else {
                    logMsg('⚠ Impossibile creare robots.txt', '⚠ Could not create robots.txt');
                }
            }

            // STEP 9: Generate sitemap.xml (only if site_url provided)
            if (empty($install_errors) && !empty($site_url)) {
                $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

                $today = date('Y-m-d');

                foreach ($config['seo']['sitemap_pages'] as $page) {
                    $loc = $site_url . '/' . $page['page'];
                    // index.php → site root URL
                    if ($page['page'] === 'index.php') {
                        $loc = $site_url . '/';
                    }
                    $sitemap .= '  <url>' . "\n";
                    $sitemap .= '    <loc>' . htmlspecialchars($loc) . '</loc>' . "\n";
                    $sitemap .= '    <lastmod>' . $today . '</lastmod>' . "\n";
                    $sitemap .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
                    $sitemap .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
                    $sitemap .= '  </url>' . "\n";
                }

                $sitemap .= '</urlset>' . "\n";

                if (file_put_contents(__DIR__ . '/' . $config['paths']['sitemap_xml'], $sitemap)) {
                    logMsg('✓ sitemap.xml generato (' . count($config['seo']['sitemap_pages']) . ' pagine)', '✓ sitemap.xml generated (' . count($config['seo']['sitemap_pages']) . ' pages)');
                } else {
                    logMsg('⚠ Impossibile creare sitemap.xml', '⚠ Could not create sitemap.xml');
                }
            }

            mysqli_close($conn);
        }
    }

    // Show results
    if (empty($install_errors)) {
        // SUCCESS!
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>NovaStar51 - Installazione Completata</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
        </head>
        <body class="bg-light py-5">
            <div class="container" style="max-width: 680px;">
                <header class="text-center mb-4">
                    <h1 class="fw-bold">NovaStar51</h1>
                    <p class="text-muted mb-2"><small>v2.5.0</small></p>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary lang-btn" onclick="setLang('it')">Italiano</button>
                        <button type="button" class="btn btn-primary lang-btn active" onclick="setLang('en')">English</button>
                    </div>
                </header>

                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center py-4">
                        <h2 class="h4 mb-0" data-it="Installazione Completata!" data-en="Installation Complete!">Installazione Completata!</h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-success border-success border-3">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong data-it="NovaStar51 è pronto!" data-en="NovaStar51 is ready!">NovaStar51 è pronto!</strong><br>
                            <span data-it="Il CMS è stato installato con successo." data-en="Your CMS has been installed successfully.">Il CMS è stato installato con successo.</span>
                        </div>

                        <h5 class="mb-2" data-it="Log Installazione:" data-en="Installation Log:">Log Installazione:</h5>
                        <div class="bg-light p-3 rounded font-monospace small mb-4 border border-2 border-secondary">
                            <!-- Italian Log -->
                            <div class="log-it">
                                <?php foreach ($install_log_it as $log): ?>
                                    &bull; <?= htmlspecialchars($log) ?><br>
                                <?php endforeach; ?>
                            </div>
                            <!-- English Log -->
                            <div class="log-en" style="display: none;">
                                <?php foreach ($install_log_en as $log): ?>
                                    &bull; <?= htmlspecialchars($log) ?><br>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if ($temp_password_generated): ?>
                            <div class="alert alert-warning border-warning border-3 p-4 mb-4">
                                <h5 class="text-warning fw-bold" data-it="PASSWORD TEMPORANEA" data-en="TEMPORARY PASSWORD">PASSWORD TEMPORANEA</h5>
                                <div class="bg-white p-3 rounded text-center my-3">
                                    <code class="fs-4 fw-bold letter-spacing-2"><?= htmlspecialchars($temp_password_generated) ?></code>
                                </div>
                                <p class="mb-0">
                                    <strong data-it="ANNOTALA!" data-en="WRITE IT DOWN!">ANNOTALA!</strong><br>
                                    <span data-it="Devi cambiare questa password al primo login." data-en="You MUST change this password on first login.">Devi cambiare questa password al primo login.</span><br>
                                    <span data-it="Email disabilitata, questa è l'unica via di accesso." data-en="Email is disabled, this is your only way to access Nova.">Email disabilitata, questa è l'unica via di accesso.</span>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info border-info border-3 mb-4">
                                <strong data-it="Credenziali di Login" data-en="Login Credentials">Credenziali di Login</strong><br>
                                <strong>Username:</strong> <?= htmlspecialchars($admin_username) ?><br>
                                <strong>Password:</strong> <span data-it="(quella inserita)" data-en="(the one you entered)">(quella inserita)</span><br>
                                <strong>Email:</strong> <?= htmlspecialchars($admin_email) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Nota di Sicurezza (SOPRA il pulsante) -->
                        <div class="alert alert-warning border-warning border-3 mb-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                            <strong data-it="Nota di Sicurezza:" data-en="Security Note:">Nota di Sicurezza:</strong><br>
                            <span data-it="Per sicurezza, ELIMINA la cartella" data-en="For security, DELETE the folder">Per sicurezza, <strong>ELIMINA la cartella</strong></span> <code>/install/</code>
                            <ul class="mb-0 mt-2">
                                <li><strong>Nova Administrator &gt; System Configuration &gt; Install Cleanup</strong></li>
                                <li><span data-it="Manualmente:" data-en="Manually:">Manualmente:</span> <span data-it="via FTP o terminale" data-en="via FTP or terminal">via FTP o terminale</span></li>
                            </ul>
                        </div>

                        <!-- Pulsante Login -->
                        <a href="<?= htmlspecialchars($config['paths']['nova_login']) ?>" class="btn btn-primary btn-lg w-100 py-3 shadow-sm" data-it="Vai alla login di Nova" data-en="Go to Nova Login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Vai alla login di Nova
                        </a>
                    </div>
                </div>

                <footer class="text-center text-muted py-4">
                    NovaStar51 Express Install
                </footer>
            </div>
            <script>
                let currentLang = 'en';
                function setLang(lang) {
                    currentLang = lang;
                    document.querySelectorAll('.lang-btn').forEach(btn => {
                        const isActive = (lang === 'it' && btn.textContent === 'Italiano') || (lang === 'en' && btn.textContent === 'English');
                        btn.classList.toggle('active', isActive);
                        btn.classList.toggle('btn-primary', isActive);
                        btn.classList.toggle('btn-outline-primary', !isActive);
                    });
                    document.querySelectorAll('[data-' + lang + ']').forEach(el => {
                        el.textContent = el.getAttribute('data-' + lang);
                    });

                    // Toggle installation log language
                    const logIt = document.querySelector('.log-it');
                    const logEn = document.querySelector('.log-en');
                    if (logIt && logEn) {
                        logIt.style.display = (lang === 'it') ? 'block' : 'none';
                        logEn.style.display = (lang === 'en') ? 'block' : 'none';
                    }
                }
                setLang('en');
            </script>
        </body>
        </html>
        <?php
        exit;

    } else {
        // ERRORS - will be shown in form below
        $form_data = $_POST;
        $form_errors = $install_errors;
    }
}

// ============================================
// DETECT SYSTEM
// ============================================
$system_info = detect_system();

// Prepare form defaults
$form_data = $form_data ?? [];
$form_errors = $form_errors ?? [];

$db_host = $form_data['db_host'] ?? 'localhost';
$db_name = $form_data['db_name'] ?? suggest_db_name();
$db_user = $form_data['db_user'] ?? 'root';
$db_pass = $form_data['db_pass'] ?? '';

$admin_first = $form_data['admin_first'] ?? '';
$admin_last = $form_data['admin_last'] ?? '';
$admin_email = $form_data['admin_email'] ?? '';
$admin_username = $form_data['admin_username'] ?? 'admin';
$admin_password = '';  // Never pre-fill password

$gmail_email = $form_data['gmail_email'] ?? '';
$gmail_app_password = '';  // Never pre-fill password

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaStar51 - Express Install</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container" style="max-width: 680px;">
        <header class="text-center mb-4">
            <h1 class="fw-bold">NovaStar51</h1>
            <p class="text-muted mb-0">
                <small>v2.5.0</small>
                <span class="mx-2">·</span>
                <span data-it="Setup Rapido" data-en="Quick Setup">Setup Rapido</span>
                <span class="mx-2">·</span>
                <a href="install_guide.html" target="_blank" class="text-muted text-decoration-none"><i class="bi bi-book me-1"></i><span data-it="Guida" data-en="Guide">Guida</span></a>
            </p>
        </header>

        <div class="card shadow mb-4 position-relative">
            <!-- Language Toggle (floating on card border) -->
            <div class="position-absolute" style="top: -1px; right: 16px; transform: translateY(-50%);">
                <div class="btn-group btn-group-sm shadow-sm" role="group">
                    <button type="button" class="btn btn-outline-primary lang-btn bg-white" onclick="setLang('it')">IT</button>
                    <button type="button" class="btn btn-primary lang-btn active" onclick="setLang('en')">EN</button>
                </div>
            </div>
            <!-- Requirements Check -->
            <div class="card-header bg-light">
                <small class="text-uppercase fw-semibold text-muted" data-it="Requisiti di Sistema" data-en="System Requirements">Requisiti di Sistema</small>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                            <span class="badge rounded-pill <?= $system_info['php']['ok'] ? 'bg-success' : 'bg-danger' ?>"><?= $system_info['php']['ok'] ? '✓' : '✗' ?></span>
                            <small>PHP <?= $system_info['php']['version'] ?> <span class="text-muted">(req. <?= $config['requirements']['php_min'] ?>+)</span></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                            <span class="badge rounded-pill <?= $system_info['mysql_available'] ? 'bg-success' : 'bg-danger' ?>"><?= $system_info['mysql_available'] ? '✓' : '✗' ?></span>
                            <small>MySQL Extension <span class="text-muted">(req. <?= $config['db']['min_version'] ?>+)</span></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                            <span class="badge rounded-pill <?= $system_info['extensions']['all_ok'] ? 'bg-success' : 'bg-danger' ?>"><?= $system_info['extensions']['all_ok'] ? '✓' : '✗' ?></span>
                            <small><span data-it="Estensioni" data-en="Extensions">Estensioni</span> <span class="text-muted">(<?= $system_info['extensions']['ok_count'] ?>/<?= $system_info['extensions']['total'] ?>)</span></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                            <span class="badge rounded-pill <?= $system_info['writable']['all_ok'] ? 'bg-success' : 'bg-danger' ?>"><?= $system_info['writable']['all_ok'] ? '✓' : '✗' ?></span>
                            <small><span data-it="Directory scrivibili" data-en="Writable dirs">Directory scrivibili</span> <span class="text-muted">(<?= $system_info['writable']['ok_count'] ?>/<?= $system_info['writable']['total'] ?>)</span></small>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$system_info['php']['ok'] || !$system_info['mysql_available']): ?>
                <!-- Critical errors -->
                <div class="card-body border-top">
                    <div class="alert alert-danger mb-0">
                        <h5 class="alert-heading" data-it="Impossibile Continuare" data-en="Cannot Continue">Impossibile Continuare</h5>
                        <p class="mb-0" data-it="Risolvi gli errori sopra prima di installare NovaStar51." data-en="Please fix the critical errors above before installing NovaStar51.">Risolvi gli errori sopra prima di installare NovaStar51.</p>
                    </div>
                </div>
            <?php else: ?>

                <?php if (!empty($form_errors)): ?>
                    <!-- Installation Errors -->
                    <div class="card-body border-top pb-0">
                        <div class="alert alert-danger">
                            <h6 class="alert-heading" data-it="Installazione Fallita" data-en="Installation Failed">Installazione Fallita</h6>
                            <ul class="mb-0">
                                <?php foreach ($form_errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="setup-form" method="POST" action="">

                    <!-- Database Section -->
                    <div class="card-body border-top border-primary border-3 py-4">
                        <h5 class="d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-database text-primary"></i>
                            <span data-it="Configurazione Database" data-en="Database Configuration">Configurazione Database</span>
                        </h5>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Host Database <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace border-warning border-2" id="db_host" name="db_host" required>
                                <div class="form-text" data-it="Es: localhost o 127.0.0.1" data-en="E.g.: localhost or 127.0.0.1">Es: localhost o 127.0.0.1</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><span data-it="Nome Database" data-en="Database Name">Nome Database</span> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace border-warning border-2" id="db_name" name="db_name" required>
                                <div class="form-text"><strong data-it="Deve essere creato prima via phpMyAdmin" data-en="Must be created first via phpMyAdmin">Deve essere creato prima via phpMyAdmin</strong><br>
                                <small class="text-muted">Collation: <code>utf8mb4_unicode_ci</code></small></div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace border-warning border-2" id="db_user" name="db_user" required>
                                <div class="form-text" data-it="Es: root" data-en="E.g.: root">Es: root</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control font-monospace border-warning border-2" id="db_pass" name="db_pass">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="db_pass"><i class="bi bi-eye-slash"></i></button>
                                </div>
                                <div class="form-text" data-it="Spesso vuota in locale, consigliata in produzione" data-en="Often empty locally, recommended in production">Spesso vuota in locale, consigliata in produzione</div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-warning" id="btn-test-db">
                            <i class="bi bi-plug-fill"></i>
                            <span data-it="Testa Connessione" data-en="Test Connection">Testa Connessione</span>
                        </button>
                        <div class="alert mt-3 mb-0 small" id="db-result" style="display: none;"></div>
                    </div>

                    <!-- Admin Account Section -->
                    <div class="card-body border-top border-success border-3 py-4">
                        <h5 class="d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-person-badge text-success"></i>
                            <span data-it="Account Super Admin" data-en="Super Admin Account">Account Super Admin</span>
                        </h5>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label"><span data-it="Nome" data-en="First Name">Nome</span> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-warning border-2" id="admin_first" name="admin_first" required>
                                <div class="form-text" data-it="Es: Mario" data-en="E.g.: Mario">Es: Mario</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><span data-it="Cognome" data-en="Last Name">Cognome</span> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-warning border-2" id="admin_last" name="admin_last" required>
                                <div class="form-text" data-it="Es: Rossi" data-en="E.g.: Rossi">Es: Rossi</div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control border-warning border-2" id="admin_email" name="admin_email" required>
                                <div class="form-text" data-it="Email per login e recupero password" data-en="Email for login and password recovery">Email per login e recupero password</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace border-warning border-2" id="admin_username" name="admin_username" required>
                                <div class="form-text" data-it="Es: mario_rossi (lettere, numeri, _)" data-en="E.g.: mario_rossi (letters, numbers, _)">Es: mario_rossi (lettere, numeri, _)</div>
                            </div>
                        </div>

                        <div class="row g-4 mb-0">
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control font-monospace border-warning border-2" id="admin_password" name="admin_password" minlength="<?= $config['password']['min_length'] ?>" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="admin_password"><i class="bi bi-eye-slash"></i></button>
                                </div>
                                <div class="form-text" data-it="Minimo <?= $config['password']['min_length'] ?> caratteri" data-en="Minimum <?= $config['password']['min_length'] ?> characters">Minimo <?= $config['password']['min_length'] ?> caratteri</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><span data-it="Conferma Password" data-en="Confirm Password">Conferma Password</span> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control font-monospace border-warning border-2" id="admin_password_confirm" name="admin_password_confirm" minlength="<?= $config['password']['min_length'] ?>" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="admin_password_confirm"><i class="bi bi-eye-slash"></i></button>
                                </div>
                                <div class="form-text" data-it="Ripeti la password" data-en="Repeat the password">Ripeti la password</div>
                            </div>
                        </div>
                    </div>

                    <!-- SMTP Section -->
                    <div class="card-body border-top border-info border-3 py-4">
                        <h5 class="d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-envelope text-info"></i>
                            <span data-it="Configurazione Gmail SMTP" data-en="Gmail SMTP Configuration">Configurazione Gmail SMTP</span>
                            <span class="badge bg-secondary" data-it="Facoltativo" data-en="Optional">Facoltativo</span>
                        </h5>

                        <div id="smtpSection">
                            <div class="alert alert-warning small mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong data-it="Senza Gmail configurato:" data-en="Without Gmail configured:">Senza Gmail configurato:</strong><br>
                                <span data-it="- Niente recupero password via email" data-en="- No password recovery via email">- Niente recupero password via email</span><br>
                                <span data-it="- Se dimentichi la password, devi intervenire sul database" data-en="- If you forget password, you must fix it in database">- Se dimentichi la password, devi intervenire sul database</span>
                            </div>

                            <div id="gmail-fields">
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label" data-it="Indirizzo Gmail" data-en="Gmail Address">Indirizzo Gmail</label>
                                        <input type="email" class="form-control border-warning border-2" id="gmail_email" name="gmail_email">
                                        <div class="form-text" data-it="Es: tuonome@gmail.com" data-en="E.g.: yourname@gmail.com">Es: tuonome@gmail.com</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gmail App Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control font-monospace border-warning border-2" id="gmail_app_password" name="gmail_app_password">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="gmail_app_password"><i class="bi bi-eye-slash"></i></button>
                                        </div>
                                        <div class="form-text">
                                            <span data-it="16 car." data-en="16 char.">16 car.</span> &middot;
                                            <a href="https://support.google.com/accounts/answer/185833" target="_blank" data-it="Come generarla" data-en="How to generate">Come generarla</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                                            <span class="badge rounded-pill border text-muted bg-transparent" id="smtp-check-host">✓</span>
                                            <small><strong>SMTP Host:</strong> smtp.gmail.com</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                                            <span class="badge rounded-pill border text-muted bg-transparent" id="smtp-check-port">✓</span>
                                            <small><strong>SMTP Port:</strong> 587 (TLS)</small>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-info text-white" id="btn-test-smtp">
                                    <i class="bi bi-send-fill"></i>
                                    <span data-it="Testa Connessione SMTP" data-en="Test SMTP Connection">Testa Connessione SMTP</span>
                                </button>
                                <div class="alert alert-warning mt-3 mb-0 small" id="smtp-error" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Site URL Section (SEO) -->
                    <div class="card-body border-top border-warning border-3 py-4">
                        <h5 class="d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-globe2 text-warning"></i>
                            <span data-it="URL del Sito (SEO)" data-en="Site URL (SEO)">URL del Sito (SEO)</span>
                            <span class="badge bg-secondary" data-it="Facoltativo" data-en="Optional">Facoltativo</span>
                        </h5>

                        <div class="alert alert-info small mb-4">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <span
                              data-it="Se compilato, verranno generati automaticamente: .htaccess (performance e sicurezza), robots.txt e sitemap.xml"
                              data-en="If provided, the following files will be auto-generated: .htaccess (performance and security), robots.txt and sitemap.xml"
                            >Se compilato, verranno generati automaticamente: .htaccess (performance e sicurezza), robots.txt e sitemap.xml</span>
                        </div>

                        <div class="row g-4 mb-0">
                            <div class="col-md-8">
                                <label class="form-label">URL <span class="text-muted">(https://)</span></label>
                                <input
                                  type="url"
                                  class="form-control font-monospace border-warning border-2"
                                  id="site_url"
                                  name="site_url"
                                  placeholder="https://www.tuosito.com"
                                >
                                <div class="form-text" data-it="URL pubblico completo, senza slash finale. Es: https://www.tuosito.com o https://tuosito.com/star51" data-en="Full public URL, no trailing slash. E.g.: https://www.example.com or https://example.com/star51">URL pubblico completo, senza slash finale. Es: https://www.tuosito.com o https://tuosito.com/star51</div>
                            </div>
                        </div>
                    </div>

                    <!-- Install Button -->
                    <div class="card-body border-top text-center py-4">
                        <button type="submit" name="install" class="btn btn-primary btn-lg w-100 py-3">
                            <i class="bi bi-rocket-takeoff-fill"></i>
                            <span data-it="Installa NovaStar51 Ora!" data-en="Install NovaStar51 Now!">Installa NovaStar51 Ora!</span>
                        </button>
                    </div>

                </form>

            <?php endif; ?>
        </div>

        <footer class="text-center text-muted">
            NovaStar51 Express Install
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Language Toggle
        let currentLang = 'en';

        function setLang(lang) {
            currentLang = lang;
            document.querySelectorAll('.lang-btn').forEach(btn => {
                const isActive = (lang === 'it' && btn.textContent === 'IT') || (lang === 'en' && btn.textContent === 'EN');
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('btn-primary', isActive);
                btn.classList.toggle('btn-outline-primary', !isActive);
                btn.classList.toggle('bg-white', !isActive);
            });
            document.querySelectorAll('[data-' + lang + ']').forEach(el => {
                el.textContent = el.getAttribute('data-' + lang);
            });
        }
        setLang('en');

        // Helper: Get localized message from response
        function getLocalizedMessage(data) {
            if (data.message_it && data.message_en) {
                return currentLang === 'it' ? data.message_it : data.message_en;
            }
            return data.message || 'Unknown error';
        }

        // Test Database Connection
        const btnTestDb = document.getElementById('btn-test-db');
        if (btnTestDb) {
            btnTestDb.addEventListener('click', function() {
                const btn = this;
                const resultEl = document.getElementById('db-result');

                const dbHost = document.getElementById('db_host').value;
                const dbName = document.getElementById('db_name').value;
                const dbUser = document.getElementById('db_user').value;
                const dbPass = document.getElementById('db_pass').value;

                if (!dbHost || !dbName || !dbUser) {
                    resultEl.textContent = currentLang === 'it' ? 'Compila i campi database' : 'Fill in database fields';
                    resultEl.className = 'alert alert-warning mt-3 mb-0 small';
                    resultEl.style.display = 'block';
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + (currentLang === 'it' ? 'Test in corso...' : 'Testing...');

                fetch('index.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=test_db&db_host=' + encodeURIComponent(dbHost) +
                          '&db_name=' + encodeURIComponent(dbName) +
                          '&db_user=' + encodeURIComponent(dbUser) +
                          '&db_pass=' + encodeURIComponent(dbPass)
                })
                .then(response => response.json())
                .then(data => {
                    resultEl.textContent = getLocalizedMessage(data);
                    if (data.success) {
                        resultEl.className = data.warning ? 'alert alert-warning mt-3 mb-0 small' : 'alert alert-success mt-3 mb-0 small';
                    } else {
                        resultEl.className = 'alert alert-danger mt-3 mb-0 small';
                    }
                    resultEl.style.display = 'block';

                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-plug-fill"></i> ' + (currentLang === 'it' ? 'Testa Connessione' : 'Test Connection');
                })
                .catch(error => {
                    resultEl.textContent = 'Error: ' + error.message;
                    resultEl.className = 'alert alert-danger mt-3 mb-0 small';
                    resultEl.style.display = 'block';

                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-plug-fill"></i> ' + (currentLang === 'it' ? 'Testa Connessione' : 'Test Connection');
                });
            });
        }

        // Test SMTP Connection
        const btnTestSmtp = document.getElementById('btn-test-smtp');
        if (btnTestSmtp) {
            const checkHost = document.getElementById('smtp-check-host');
            const checkPort = document.getElementById('smtp-check-port');
            const smtpError = document.getElementById('smtp-error');

            function setSmtpBadges(status) {
                const cls = status === 'success' ? 'badge rounded-pill bg-success text-white' : 'badge rounded-pill border text-muted bg-transparent';
                checkHost.className = cls;
                checkPort.className = cls;
            }

            const smtpBtnLabel = {
                it: 'Testa Connessione SMTP',
                en: 'Test SMTP Connection'
            };
            const smtpBtnLoading = {
                it: 'Test in corso...',
                en: 'Testing...'
            };

            btnTestSmtp.addEventListener('click', function() {
                const btn = this;

                const gmailEmail = document.getElementById('gmail_email').value;
                const gmailAppPass = document.getElementById('gmail_app_password').value;

                if (!gmailEmail || !gmailAppPass) {
                    smtpError.textContent = currentLang === 'it' ? 'Compila i campi Gmail' : 'Fill in Gmail fields';
                    smtpError.style.display = 'block';
                    setSmtpBadges('error');
                    return;
                }

                smtpError.style.display = 'none';
                setSmtpBadges('secondary');
                btn.disabled = true;
                btn.querySelector('i').className = 'bi bi-hourglass-split';
                btn.querySelector('span').textContent = smtpBtnLoading[currentLang];

                fetch('index.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=test_smtp&gmail_email=' + encodeURIComponent(gmailEmail) +
                          '&gmail_app_password=' + encodeURIComponent(gmailAppPass)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setSmtpBadges('success');
                        smtpError.style.display = 'none';
                    } else {
                        setSmtpBadges('error');
                        smtpError.textContent = getLocalizedMessage(data);
                        smtpError.style.display = 'block';
                    }

                    btn.disabled = false;
                    btn.querySelector('i').className = 'bi bi-send-fill';
                    btn.querySelector('span').textContent = smtpBtnLabel[currentLang];
                })
                .catch(error => {
                    setSmtpBadges('error');
                    smtpError.textContent = 'Error: ' + error.message;
                    smtpError.style.display = 'block';

                    btn.disabled = false;
                    btn.querySelector('i').className = 'bi bi-send-fill';
                    btn.querySelector('span').textContent = smtpBtnLabel[currentLang];
                });
            });
        }

        // Form Validation
        const setupForm = document.getElementById('setup-form');
        if (setupForm) {
            setupForm.addEventListener('submit', function(e) {
                const pass = document.getElementById('admin_password').value;
                const passConfirm = document.getElementById('admin_password_confirm').value;

                if (pass !== passConfirm) {
                    e.preventDefault();
                    alert(currentLang === 'it' ? 'Le password non corrispondono!' : 'Passwords do not match!');
                    return false;
                }

                const confirmMsg = currentLang === 'it'
                    ? 'Pronto per installare NovaStar51?\n\nVerranno create le tabelle database e configurato il sistema.'
                    : 'Ready to install NovaStar51?\n\nThis will create database tables and configure the system.';

                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // Input border color: yellow (empty) -> green (filled)
        document.querySelectorAll('.border-warning').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('border-warning');
                    this.classList.add('border-success');
                } else {
                    this.classList.remove('border-success');
                    this.classList.add('border-warning');
                }
            });
        });

        // Password Show/Hide Toggle
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            });
        });
    </script>
</body>
</html>
