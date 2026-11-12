<?php
/**
 * NovaStar51 Express Install
 *
 * Progressive one-page setup with verified steps and editable summaries.
 * Real installation POST with server validation and a no-JavaScript fallback.
 *
 * Created: 11 November 2025 - Session 29
 * Updated: 24 November 2025 - Session 35 (New Design)
 * Updated: 17 January 2026 - Session 57 (nova/conf refactoring, chmod 777 auto)
 * Updated: 31 October 2026 - Session 103 (redesign V4 interface, progressive setup and aligned bilingual guide)
 * Philosophy: "Copy-Paste and Go" 🍷
 */

// ============================================
// DEBUG MODE - Show all errors
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ============================================
// LOAD CONFIGURATION
// ============================================
$config = require 'config_mapping.php';
require_once dirname(__DIR__) . '/nova/inc/inc_nova_version.php';
require 'system_detect.php';

// The language belongs to this request, never to a previous application session.
$installer_lang = in_array($_POST['installer_lang'] ?? '', ['en', 'it'], true) ? $_POST['installer_lang'] : 'en';
$install_success = false;
header('Cache-Control: no-store');

// The installation lock must be evaluated before any AJAX or install handler.
$installation_locked = file_exists($config['paths']['lock']);

if ($installation_locked && isset($_POST['action'])) {
    http_response_code(423);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message_it' => 'Installazione bloccata.',
        'message_en' => 'Installation is locked.'
    ]);
    exit;
}

if ($installation_locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(423);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Generate a secure administration password
 *
 * @return string Generated password
 */
function generate_secure_password() {
    global $config;
    $length = $config['password']['generated_length'];
    $charset = $config['password']['generated_charset'];
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
if (!$installation_locked && isset($_POST['action']) && $_POST['action'] === 'test_db') {
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

    // Match installation transport: localhost may use a socket instead of TCP.
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
    $response['data']['mysql_version_ok'] = version_compare($mysql_version, $config['db']['min_version'], '>=');

    if (!$response['data']['mysql_version_ok']) {
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
if (!$installation_locked && isset($_POST['action']) && $_POST['action'] === 'test_smtp') {
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
if (!$installation_locked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {

    $install_errors = [];
    $install_log_it = [];  // Log in italiano
    $install_log_en = [];  // Log in inglese
    $install_log_states = [];

    // Helper: Add bilingual log message
    function logMsg($it, $en, $state = 'info') {
        global $install_log_it, $install_log_en, $install_log_states;
        $install_log_it[] = $it;
        $install_log_en[] = $en;
        $install_log_states[] = $state;
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
    $password_mode = $_POST['password_mode'] ?? 'generated';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';

    $smtp_enabled = ($_POST['smtp_enabled'] ?? '') === '1';
    $gmail_email = $smtp_enabled ? trim($_POST['gmail_email'] ?? '') : '';
    $gmail_app_password = $smtp_enabled ? preg_replace('/[\\s-]+/', '', trim($_POST['gmail_app_password'] ?? '')) : '';

    // Site URL (manual override or auto-detect)
    $site_url = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $site_url_final = $site_url;

    if ($site_url_final === '') {
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $script_name = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $project_path = preg_replace('#/install(?:/index\.php)?$#', '', $script_name);
        $project_path = rtrim((string)$project_path, '/');

        $site_url_final = $scheme . '://' . $host . $project_path;
    }

    // Basic validation
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $install_errors[] = 'Tutti i campi database sono obbligatori / All database fields are required';
    }
    if (empty($admin_first) || empty($admin_last) || empty($admin_email) || empty($admin_username)) {
        $install_errors[] = 'Tutti i campi Super Admin sono obbligatori / All Super Admin fields are required';
    }

    if ($admin_email !== '' && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $install_errors[] = 'Indirizzo email non valido / Invalid email address';
    }
    if ($admin_username !== '' && !preg_match('/^[A-Za-z0-9_]+$/D', $admin_username)) {
        $install_errors[] = 'Username: usa lettere, numeri e trattino basso / Username: use letters, numbers and underscores';
    }
    if ($smtp_enabled && (!filter_var($gmail_email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\\.com$/iD', $gmail_email) || !preg_match('/^[A-Za-z0-9]{16}$/D', $gmail_app_password))) {
        $install_errors[] = 'Controlla indirizzo Gmail e password per app di 16 caratteri / Check the Gmail address and 16-character app password';
    }
    if ($site_url !== '' && (!filter_var($site_url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($site_url, PHP_URL_SCHEME)), ['http', 'https'], true))) {
        $install_errors[] = 'URL del sito non valido: usa http:// o https:// / Invalid site URL: use http:// or https://';
    }

    if (!in_array($password_mode, ['generated', 'custom'], true)) {
        $install_errors[] = 'Modalità password non valida / Invalid password mode';
    }

    if ($password_mode === 'custom') {
        if ($admin_password === '' || $admin_password_confirm === '') {
            $install_errors[] = 'Password e conferma sono obbligatorie / Password and confirmation are required';
        } elseif ($admin_password !== $admin_password_confirm) {
            $install_errors[] = 'Le password non corrispondono / Passwords do not match';
        } elseif (strlen($admin_password) < $config['password']['min_length']) {
            $install_errors[] = "Password deve essere almeno {$config['password']['min_length']} caratteri / Password must be at least {$config['password']['min_length']} characters";
        }
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

                // Password mode is independent from SMTP configuration.
                $force_password_change = 0;
                $generated_password = null;

                if ($password_mode === 'generated') {
                    $generated_password = generate_secure_password();
                    $final_password = $generated_password;
                    logMsg('Password sicura generata', 'Secure password generated');
                } else {
                    $final_password = $admin_password;
                    logMsg('Password personale configurata', 'Custom password configured');
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

                // Populate Site URL (manual override or auto-detected)
                $nova_settings['site_url'] = $site_url_final;
                if (!empty($site_url)) {
                    logMsg('URL sito configurato: ' . $site_url_final, 'Site URL configured: ' . $site_url_final);
                } else {
                    logMsg('URL sito rilevato automaticamente: ' . $site_url_final, 'Site URL auto-detected: ' . $site_url_final);
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
 * Nova System Configuration
 * Auto-generated by Express Install
 * Created: " . date('Y-m-d H:i:s') . "
 *
 * This file contains all system settings (dimensions, database, email)
 * Edit installation settings in nova/conf/nova_config_values.php
 */

\$nova_settings = " . var_export($nova_settings, true) . ";
?>";

                if (!file_put_contents($config['paths']['config_values'], $config_content)) {
                    $install_errors[] = "Impossibile scrivere / Cannot write {$config['paths']['config_values']}";
                } else {
                    logMsg('Database configurato per Ambiente Locale', 'Database configured for Local Environment');
                }
            }

            // STEP 4: Create .installed lock file
            if (empty($install_errors)) {
                logMsg('Creazione file lock...', 'Creating installation lock file...');

                $lock_content = "NovaStar51 installed on " . date('Y-m-d H:i:s') . "\n";
                $lock_content .= "Super Admin: $admin_username\n";
                $lock_content .= "Database: $db_name\n";

                if (!file_put_contents($config['paths']['lock'], $lock_content)) {
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
                        logMsg("✓ $dir: {$result['message']}", "✓ $dir: {$result['message']}", 'passed');
                    } else {
                        // Log warning but don't fail installation
                        logMsg("⚠ $dir: {$result['message']}", "⚠ $dir: {$result['message']}", 'warning');
                    }
                }

                logMsg("Permessi: $chmod_success/$chmod_total cartelle configurate", "Permissions: $chmod_success/$chmod_total directories configured");
            }

            // STEP 6: Protect Nova configuration and logs from direct HTTP access
            if (empty($install_errors)) {
                $nova_protections = [
                    'configurazione Nova' => [
                        'source' => $config['paths']['htaccess_conf'],
                        'destination' => $config['paths']['htaccess_conf_dest'],
                        'label_en' => 'Nova configuration',
                    ],
                    'log Nova' => [
                        'source' => $config['paths']['htaccess_logs'],
                        'destination' => $config['paths']['htaccess_logs_dest'],
                        'label_en' => 'Nova logs',
                    ],
                ];

                foreach ($nova_protections as $label_it => $protection) {
                    $htaccess_source = __DIR__ . '/' . $protection['source'];
                    $htaccess_dest = __DIR__ . '/' . $protection['destination'];

                    if (!file_exists($htaccess_source)) {
                        logMsg("⚠ Template protezione $label_it non trovato", "⚠ {$protection['label_en']} protection template not found", 'warning');
                        continue;
                    }

                    if (copy($htaccess_source, $htaccess_dest)) {
                        logMsg("✓ Protezione $label_it attivata (.htaccess)", "✓ {$protection['label_en']} protection enabled (.htaccess)", 'passed');
                    } else {
                        logMsg("⚠ Impossibile attivare protezione $label_it", "⚠ Could not enable {$protection['label_en']} protection", 'warning');
                    }
                }
            }

            // STEP 7: Copy _htaccess_root to .htaccess (project root)
            if (empty($install_errors)) {
                $htaccess_root_source = __DIR__ . '/' . $config['paths']['htaccess_root'];
                $htaccess_root_dest = __DIR__ . '/' . $config['paths']['htaccess_dest'];

                if (file_exists($htaccess_root_source)) {
                    if (copy($htaccess_root_source, $htaccess_root_dest)) {
                        logMsg('✓ .htaccess creato (compressione, cache, sicurezza)', '✓ .htaccess created (compression, cache, security)', 'passed');
                    } else {
                        logMsg('⚠ Impossibile creare .htaccess nella root', '⚠ Could not create .htaccess in root', 'warning');
                    }
                }
            }

            // STEP 8: Generate robots.txt
            if (empty($install_errors) && !empty($site_url_final)) {
                $site_url_path = parse_url($site_url_final, PHP_URL_PATH) ?? '';
                $site_url_path = rtrim($site_url_path, '/');
                $robots_lines = ['User-agent: *', 'Allow: /'];

                foreach ($config['seo']['robots_disallow'] as $dir) {
                    $robots_lines[] = 'Disallow: ' . $site_url_path . $dir;
                }

                $robots_lines[] = '';
                $robots_lines[] = 'Sitemap: ' . $site_url_final . '/sitemap.xml';

                $robots_content = implode("\n", $robots_lines) . "\n";

                if (file_put_contents(__DIR__ . '/' . $config['paths']['robots_txt'], $robots_content)) {
                    logMsg('✓ robots.txt generato', '✓ robots.txt generated', 'passed');
                } else {
                    logMsg('⚠ Impossibile creare robots.txt', '⚠ Could not create robots.txt', 'warning');
                }
            }

            // STEP 9: Generate sitemap.xml
            if (empty($install_errors) && !empty($site_url_final)) {
                $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

                $today = date('Y-m-d');

                foreach ($config['seo']['sitemap_pages'] as $page) {
                    $loc = $site_url_final . '/' . $page['page'];
                    // index.php → site root URL
                    if ($page['page'] === 'index.php') {
                        $loc = $site_url_final . '/';
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
                    logMsg('✓ sitemap.xml generato (' . count($config['seo']['sitemap_pages']) . ' pagine)', '✓ sitemap.xml generated (' . count($config['seo']['sitemap_pages']) . ' pages)', 'passed');
                } else {
                    logMsg('⚠ Impossibile creare sitemap.xml', '⚠ Could not create sitemap.xml', 'warning');
                }
            }

            mysqli_close($conn);
        }
    }


    $install_success = empty($install_errors);
    if (!$install_success) {
      $form_data = $_POST;
      unset($form_data['db_pass'], $form_data['admin_password'], $form_data['admin_password_confirm'], $form_data['gmail_app_password']);
      $form_errors = $install_errors;
    }
}

// Render all installer states through the same interface.
$system = detect_system();
$critical_requirements_ok = $system['php']['ok'] && $system['mysql_available'];
$requirements_have_issues = !$system['php']['ok'] || !$system['extensions']['all_ok'] || !$system['writable']['all_ok'];
$requirements_have_warnings = false;
$requirement_colors = ['passed' => 'text-success', 'warning' => 'text-warning-emphasis', 'error' => 'text-danger', 'pending' => 'text-body-secondary'];
$requirement_icons = ['passed' => 'bi-check-circle-fill', 'warning' => 'bi-exclamation-triangle-fill', 'error' => 'bi-x-circle-fill', 'pending' => 'bi-clock'];
$mysql_initial_state = $system['mysql_available'] ? 'pending' : 'error';
$requirement_rows = [[
  'kind' => 'php', 'name' => 'php',
  'label' => 'PHP ' . $system['php']['version'] . ' (' . $system['php']['required'] . '+)',
  'state' => $system['php']['ok'] ? 'passed' : 'error',
  'en' => $system['php']['ok'] ? 'Compatible version' : 'Minimum PHP version not met',
  'it' => $system['php']['ok'] ? 'Versione compatibile' : 'Versione minima di PHP non soddisfatta',
]];
foreach ($system['extensions']['list'] as $extension => $available) {
  $state = $available ? 'passed' : ($extension === 'mysqli' ? 'error' : 'warning');
  if ($state === 'warning') $requirements_have_warnings = true;
  $requirement_rows[] = [
    'kind' => 'extension', 'name' => $extension, 'label' => $extension, 'state' => $state,
    'en' => $available ? 'Available' : ($extension === 'mysqli' ? 'Required extension missing' : 'Extension unavailable'),
    'it' => $available ? 'Disponibile' : ($extension === 'mysqli' ? 'Estensione richiesta assente' : 'Estensione non disponibile'),
  ];
}
foreach ($system['writable']['list'] as $path => $folder) {
  $writable = !empty($folder['writable']);
  if (!$writable) $requirements_have_warnings = true;
  $requirement_rows[] = [
    'kind' => 'folder', 'name' => $path, 'label' => $path, 'state' => $writable ? 'passed' : 'warning',
    'en' => $writable ? 'Writable' : (!empty($folder['exists']) ? 'Not writable; permissions will be attempted during installation' : 'Missing folder; creation will be attempted during installation'),
    'it' => $writable ? 'Scrivibile' : (!empty($folder['exists']) ? 'Non scrivibile: tentativo di impostare i permessi durante il setup' : 'Cartella assente: tentativo di creazione durante il setup'),
  ];
}
$form_data = $form_data ?? [];
$form_errors = $form_errors ?? [];
$form_value = static fn($name) => htmlspecialchars((string)($form_data[$name] ?? ''), ENT_QUOTES, 'UTF-8');
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$selected_password_mode = ($form_data['password_mode'] ?? '') === 'custom' ? 'custom' : 'generated';
?>
<!doctype html>
<html lang="<?= $installer_lang ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Star51 - Installation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@<?= $escape($config['ui']['bootstrap_version']) ?>/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@<?= $escape($config['ui']['bootstrap_icons_version']) ?>/font/bootstrap-icons.min.css">
    <style>
      /* Installer-local styles: no dependency on the installed application. */
      :root {
        --nova-brand-orange: #ff5b19;
        --nova-mint: #aecacd;
        --nova-mint-dark: #578a8f;
        --nova-charcoal: #161616;
        --nova-white: #ffffff;
        --nova-gray-border: #dee2e6;
        --nova-gray-muted: #6c757d;
        --bs-body-color: var(--nova-charcoal);
        --bs-body-bg: var(--nova-white);
        --bs-border-radius: 6px;
        --bs-link-color: var(--nova-brand-orange);
        --bs-link-hover-color: var(--nova-charcoal);
        --bs-link-color-rgb: 255, 91, 25;
        --bs-link-hover-color-rgb: 22, 22, 22;
      }
      [hidden] { display: none !important; }
      body { min-height: 100vh; padding-inline: 20px; letter-spacing: 0; background: rgb(174 202 205 / 30%); }
      a { color: var(--nova-brand-orange); text-decoration: none; transition: color .2s ease; }
      button, a { touch-action: manipulation; }
      :is(button, a, input):focus-visible { outline: 3px solid var(--nova-charcoal); outline-offset: 3px; }
      .wrap { width: 100%; max-width: 720px; margin-inline: auto; padding-inline: 32px; }
      .setup-panel { width: 100%; margin: 16px auto 32px; background: var(--nova-white); border: 1px solid var(--nova-gray-border); border-radius: 8px; box-shadow: 0 6px 20px rgb(22 22 22 / 7%); }
      .installer-shell { max-width: 720px; }
      .topbar { margin-inline: 32px; border-bottom: 1px solid var(--nova-gray-border); }
      .topbar .wrap { min-height: 80px; padding-inline: 0; }
      .brand strong { display: block; font-size: 20px; }
      .brand small { font-size: 12px; color: var(--nova-gray-muted); }
      .langs .btn {
        --bs-btn-color: var(--nova-mint-dark);
        --bs-btn-bg: var(--nova-white);
        --bs-btn-border-color: var(--nova-mint);
        --bs-btn-hover-color: var(--nova-charcoal);
        --bs-btn-hover-bg: rgb(174 202 205 / 20%);
        --bs-btn-hover-border-color: var(--nova-mint-dark);
        --bs-btn-active-color: var(--nova-charcoal);
        --bs-btn-active-bg: var(--nova-mint);
        --bs-btn-active-border-color: var(--nova-mint-dark);
        width: 44px;
        height: 44px;
        padding-inline: 0;
        border-radius: 0;
      }
      .langs .btn:first-child { border-radius: 6px 0 0 6px; }
      .langs .btn:last-child { border-radius: 0 6px 6px 0; }
      .langs [aria-pressed="true"] { background: var(--nova-mint-dark); border-color: var(--nova-mint-dark); color: var(--nova-white); font-weight: 600; }
      .langs .btn:focus-visible { position: relative; z-index: 1; outline: 3px solid var(--nova-mint-dark); outline-offset: 3px; }
      .page-title { display: flex; align-items: start; justify-content: space-between; gap: 24px; padding: 48px 0 40px; }
      h1 { font-size: 24px; font-weight: 600; line-height: 1.3; margin: 0 0 12px; }
      h2 { font-size: 18px; font-weight: 600; line-height: 1.4; margin: 0 0 12px; }
      p { margin: 0; }
      .muted, .section-copy > p, .hint, .optional { color: var(--nova-gray-muted); }
      .title-copy, .section-copy > p, .hint, .smtp-off { font-size: 14px; }
      .guide-link { white-space: nowrap; font-size: 14px; }
      .guide-link i { margin-right: 6px; }
      .requirements-panel { border-block: 1px solid var(--nova-gray-border); padding: 32px 0; }
      .section-heading > .bi { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 25px; width: 25px; height: 25px; font-size: 18px; line-height: 1; }
      .check-status { display: block; margin: 12px 0 0 !important; font-size: 13px; color: var(--nova-gray-muted); }
      .check-status.is-passed { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: var(--bs-success-text-emphasis); }
      .form-section { padding: 40px 0; border-bottom: 1px solid var(--nova-gray-border); }
      .form-section[data-state="active"] { padding-block: 40px; background: var(--nova-white); }
      .section-copy { position: relative; }
      .section-heading i { color: var(--nova-gray-muted); }
      .section-controls { width: 100%; min-width: 0; padding: 0; border: 0; margin: 32px 0 0; }
      .fields, .password-options { grid-template-columns: minmax(0, 1fr); gap: 32px; }
      .password-options { gap: 12px; }
      .field { min-width: 0; }
      label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
      .hint { margin-bottom: 8px; }
      .form-control { min-height: 46px; border-color: #adb5bd; }
      .form-control:focus, .form-check-input:focus { border-color: var(--nova-mint-dark); box-shadow: 0 0 0 3px rgb(174 202 205 / 40%); }
      .form-check-input:checked { background-color: var(--nova-mint-dark); border-color: var(--nova-mint-dark); }
      .password-input { position: relative; }
      .password-input .form-control { padding-right: 48px; }
      .eye { position: absolute; top: 1px; right: 1px; min-width: 44px; min-height: 44px; color: var(--nova-charcoal); }
      .btn { min-height: 44px; letter-spacing: 0; transition: background-color .12s ease, border-color .12s ease, box-shadow .12s ease; }
      .btn-primary {
        --bs-btn-color: var(--nova-brand-orange);
        --bs-btn-bg: transparent;
        --bs-btn-border-color: var(--nova-brand-orange);
        --bs-btn-hover-color: var(--nova-white);
        --bs-btn-hover-bg: var(--nova-brand-orange);
        --bs-btn-hover-border-color: var(--nova-brand-orange);
        --bs-btn-active-color: var(--nova-white);
        --bs-btn-active-bg: var(--nova-brand-orange);
        --bs-btn-active-border-color: var(--nova-brand-orange);
        --bs-btn-disabled-color: var(--nova-brand-orange);
        --bs-btn-disabled-bg: transparent;
        --bs-btn-disabled-border-color: var(--nova-brand-orange);
      }
      .btn i { margin-right: 6px; }
      .eye i { margin: 0; }
      .step-action { margin-top: 32px; }
      .step-continue.btn { position: absolute; top: 0; right: 0; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; padding: 0; border-radius: 50%; }
      .step-continue i { margin: 0; font-size: 20px; line-height: 1; }
      .step-continue:focus-visible { outline: 2px solid var(--nova-brand-orange); outline-offset: 3px; }
      .section-copy:has(> .step-continue:not([hidden])) { padding-right: 60px; }
      .option { display: flex; align-items: center; gap: 12px; padding: 12px 16px; min-height: 48px; margin: 0; border: 1px solid var(--nova-gray-border); border-radius: 6px; font-weight: 400; cursor: pointer; }
      .option:has(input:checked) { background: rgb(174 202 205 / 20%); }
      .option .form-check-input, .switch-label .form-check-input { flex-shrink: 0; float: none; margin: 0; }
      .optional { font-size: 14px; font-weight: 400; color: var(--nova-gray-muted); }
      .switch-row.form-switch { padding-left: 0; }
      .switch-label { display: flex; align-items: center; gap: 12px; min-height: 44px; font-size: 16px; cursor: pointer; }
      .switch-label .form-check-input { width: 40px; height: 22px; cursor: pointer; }
      .smtp-content, .smtp-off { margin-top: 16px; }
      .section-state { display: flex; align-items: center; gap: 7px; margin-top: 10px; font-size: 14px; font-weight: 400; }
      .draft-notice { display: flex; align-items: baseline; gap: 8px; width: fit-content; max-width: 100%; margin: 12px 0 0; padding: 8px 12px; border: 1px solid #aecacd; border-left: 3px solid #578a8f; border-radius: 4px; background: #edf4f5; color: #294c50; font-size: 14px; font-weight: 600; overflow-wrap: anywhere; }
      .draft-notice[hidden] { display: none; }
      .draft-notice i { flex-shrink: 0; }
      .form-section[data-state="complete"] .section-state { gap: 8px; font-size: 14px; font-weight: 600; color: var(--bs-success-text-emphasis); }
      .section-state .bi-check-circle-fill, .check-status .bi-check-circle-fill { flex-shrink: 0; font-size: 20px; line-height: 1; }
      .form-section[data-state="complete"] .section-heading { padding-right: 104px; }
      .step-edit { position: absolute; right: 0; top: 0; font-size: 14px; font-weight: 600; }
      .step-edit {
        --bs-btn-color: var(--nova-mint-dark);
        --bs-btn-bg: transparent;
        --bs-btn-border-color: var(--nova-mint);
        --bs-btn-hover-color: var(--nova-white);
        --bs-btn-hover-bg: var(--nova-mint-dark);
        --bs-btn-hover-border-color: var(--nova-mint-dark);
        --bs-btn-active-color: var(--nova-white);
        --bs-btn-active-bg: var(--nova-mint-dark);
        --bs-btn-active-border-color: var(--nova-mint-dark);
      }
      .step-edit:focus { background: var(--nova-mint-dark); color: var(--nova-white); border-color: var(--nova-mint-dark); box-shadow: none; }
      .step-edit:focus-visible { outline-color: var(--nova-mint-dark); }
      .btn:active { box-shadow: inset 0 2px 3px rgb(22 22 22 / 18%); }
      @media (prefers-reduced-motion: reduce) { .btn { transition: none; } }
      .step-recap { display: grid; grid-template-columns: 88px minmax(0, 1fr); gap: 8px 16px; margin: 24px 0 0; font-size: 14px; }
      .step-recap:empty { display: none; }
      .step-recap dt { font-weight: 400; color: var(--nova-gray-muted); }
      .step-recap dd { margin: 0; min-width: 0; overflow-wrap: anywhere; font-weight: 600; }
      .notice, .verification-feedback { padding: 16px; border: 0; border-radius: 6px; background: rgb(174 202 205 / 15%); font-size: 14px; }
      #generated-note { border-left: 0; border-radius: 6px; }
      .verification-feedback { display: flex; gap: 10px; align-items: start; margin-top: 16px; }
      .verification-feedback::before { content: "\f431"; font-family: bootstrap-icons; }
      .verification-feedback[data-kind="error"] { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); border: 0; border-radius: 6px; }
      .verification-feedback[data-kind="error"]::before { content: "\f33a"; }
      .form-control[aria-invalid="true"], .form-control[aria-invalid="true"]:focus { border-color: var(--bs-danger); }
      .form-control[aria-invalid="true"]:focus { box-shadow: 0 0 0 3px rgba(var(--bs-danger-rgb), .2); outline-color: var(--bs-danger); }
      .install-footer { display: grid; gap: 16px; padding-block: 32px; }
      .install-footer p { font-size: 13px; color: var(--nova-gray-muted); }
      .bottom { gap: 12px; padding-block: 24px; border-top: 1px solid var(--nova-gray-border); font-size: 12px; color: var(--nova-gray-muted); }
      .review-list { margin-bottom: 20px; }
      .review-list div { display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 14px; padding-block: 12px; border-bottom: 1px solid var(--nova-gray-border); font-size: 14px; }
      .review-list dt { font-weight: 400; color: var(--nova-gray-muted); }
      .review-list dd { margin: 0; overflow-wrap: anywhere; }
      .result-screen { padding-block: 32px; }
      .result-screen .review-list { margin-top: 24px; }
      @media (max-width: 480px) {
        body { padding-inline: 12px; }
        .wrap { padding-inline: 16px; }
        .topbar { margin-inline: 16px; }
        .brand strong { font-size: 17px; }
        .page-title { flex-wrap: wrap; gap: 12px; padding-top: 24px; }
        h1 { font-size: 24px; }
        .form-section, .form-section[data-state="active"] { padding-block: 32px; }
        .step-edit { position: static; margin-top: 12px; }
        .form-section[data-state="complete"] .section-heading { padding-right: 0; }
        .step-recap, .review-list div { grid-template-columns: minmax(0, 1fr); gap: 3px; }
        .step-recap dd { margin-bottom: 8px; }
        .bottom { flex-direction: column; gap: 12px; }
      }

      .installation-log { font-size: 14px; line-height: 1.6; overflow-wrap: anywhere; }
      .credentials { margin-block: 24px; padding: 20px; background: rgb(174 202 205 / 15%); border-radius: 6px; overflow-wrap: anywhere; }
      .credentials code { display: block; margin-top: 12px; font-size: 20px; color: var(--nova-charcoal); }
      .waiting-layer { position: fixed; inset: 0; z-index: 1100; display: grid; place-items: center; padding: 24px; background: rgb(22 22 22 / 45%); }
      .waiting-content { width: min(440px, 100%); padding: 32px; background: white; border-radius: 8px; text-align: center; }
      .waiting-content p { margin-top: 16px; }
      .server-errors { margin-bottom: 24px; }
      .server-errors ul { margin: 0; }
      .check-details { margin-bottom: 20px; font-size: 14px; overflow-wrap: anywhere; }
      .check-details > summary { min-height: 44px; list-style: none; }
      .check-details > summary::-webkit-details-marker { display: none; }
      .check-details[open] > summary .bi-chevron-down { transform: rotate(180deg); }
      summary { cursor: pointer; }
      .check-details ul { margin-top: 12px; }
      .verification-feedback[data-kind="warning"] { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    </style>
  </head>
  <body>
    <div class="installer-shell setup-panel">
      <header class="topbar">
        <div class="wrap d-flex align-items-center justify-content-between">
          <div class="brand"><div><strong>Star51</strong><small data-en="Version <?= $escape(STAR51_VERSION) ?>" data-it="Versione <?= $escape(STAR51_VERSION) ?>">Version <?= $escape(STAR51_VERSION) ?></small></div></div>
          <nav class="langs d-flex" aria-label="Language">
            <button
              class="btn"
              type="button"
              data-lang="en"
              aria-pressed="<?= $installer_lang === 'en' ? 'true' : 'false' ?>"
              title="English"
              aria-label="English"
            >EN</button>
            <button
              class="btn"
              type="button"
              data-lang="it"
              aria-pressed="<?= $installer_lang === 'it' ? 'true' : 'false' ?>"
              title="Italiano"
              aria-label="Italiano"
            >IT</button>
          </nav>
        </div>
      </header>
      <main class="wrap">
        <div class="page-title<?= $install_success ? ' py-4' : '' ?>">
          <div>
            <?php if ($install_success): ?>
              <h1 data-en="Installation complete" data-it="Installazione completata">Installation complete</h1>
            <?php elseif ($installation_locked): ?>
              <h1 data-en="Installation is locked." data-it="Installazione bloccata.">Installation is locked.</h1>
              <p
                class="title-copy muted"
                data-en="Star51 is already installed."
                data-it="Star51 e gia installato."
              >Star51 is already installed.</p>
            <?php else: ?>
              <h1 data-en="Install Star51" data-it="Installa Star51">Install Star51</h1>
              <p
                class="title-copy muted"
                data-en="Connect your database and create your administrator account."
                data-it="Collega il database e crea il tuo account amministratore."
              >Connect your database and create your administrator account.</p>
            <?php endif; ?>
          </div>
          <a
            class="guide-link"
            href="install_guide.html"
            target="_blank"
            rel="noopener"
          ><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span data-en="Installation Guide" data-it="Guida di installazione">Installation Guide</span></a>
        </div>
        <?php if ($install_success || $installation_locked): ?>
          <section class="result-screen<?= $install_success ? ' pt-0' : '' ?>">
            <?php if ($install_success): ?>
              <p class="check-status is-passed"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span data-en="Installation completed successfully" data-it="Installazione completata con successo">Installation completed successfully</span></p>
              <div class="credentials">
                <dl class="review-list">
                  <div><dt>Username</dt><dd><?= $escape($admin_username) ?></dd></div>
                  <div><dt>Email</dt><dd><?= $escape($admin_email) ?></dd></div>
                </dl>
                <?php if ($generated_password !== null): ?>
                  <strong data-en="Secure password generated" data-it="Password sicura generata">Secure password generated</strong>
                  <code><?= $escape($generated_password) ?></code>
                  <div
                    class="alert alert-warning mt-3 mb-0"
                    id="password-save-warning"
                    role="alert"
                  >
                    <strong class="d-block mb-1"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><span data-en="Save your password" data-it="Conserva la password">Save your password</span></strong>
                    <span
                      data-en="Save it before continuing to the administration area. It will not be shown again."
                      data-it="Conservala prima di proseguire nell'amministrazione. Non verra mostrata nuovamente."
                    >Save it before continuing to the administration area. It will not be shown again.</span>
                  </div>
                <?php else: ?>
                  <p data-en="Use the password you chose during installation." data-it="Usa la password che hai scelto durante l'installazione.">Use the password you chose during installation.</p>
                <?php endif; ?>
              </div>
              <details class="check-details mb-4" id="installation-details">
                <summary class="d-flex align-items-center justify-content-between gap-2 fs-6 fw-semibold py-2 px-3 rounded bg-body-tertiary">
                  <span data-en="Installation log" data-it="Registro installazione">Installation log</span>
                  <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </summary>
                <div class="pt-3">
                  <ul class="installation-log mb-0">
                    <?php foreach ($install_log_en as $index => $entry): ?>
                      <?php $log_state = $install_log_states[$index] ?? 'info'; ?>
                      <li
                        class="<?= $requirement_colors[$log_state] ?? '' ?>"
                        data-log-state="<?= $escape($log_state) ?>"
                        data-en="<?= $escape($entry) ?>"
                        data-it="<?= $escape($install_log_it[$index]) ?>"
                      ><?= $escape($entry) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </details>
            <?php endif; ?>
            <div
              class="alert alert-warning mb-4"
              id="installation-removal-warning"
              role="alert"
            >
              <strong class="d-block mb-2"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><span data-en="Remove installation files" data-it="Rimuovi i file di installazione">Remove installation files</span></strong>
              <p class="mb-2">
                <span data-en="From Nova:" data-it="Da Nova:">From Nova:</span>
                <span data-en="Nova Admin &gt; System Configuration &gt; Installation File Removal" data-it="Nova Admin &gt; Configurazione di Sistema &gt; Rimozione file di installazione">Nova Admin &gt; System Configuration &gt; Installation File Removal</span>.
              </p>
              <p>
                <span data-en="Alternatively, delete the entire" data-it="In alternativa, elimina l'intera cartella">Alternatively, delete the entire</span>
                <code>/install/</code>
                <span data-en="folder using FTP or your server file manager." data-it="tramite FTP o il file manager del server.">folder using FTP or your server file manager.</span>
              </p>
            </div>
            <a
              class="btn btn-primary w-100 fw-semibold"
              href="<?= $escape($config['paths']['nova_login']) ?>"
            ><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span data-en="Administration area" data-it="Area di amministrazione">Administration area</span></a>
          </section>
        <?php else: ?>
          <?php if ($form_errors): ?>
            <div
              class="verification-feedback server-errors"
              data-kind="error"
              role="alert"
              tabindex="-1"
              id="server-errors"
            >
              <ul>
                <?php foreach ($form_errors as $error): ?>
                  <li><?= $escape($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <section class="requirements-panel" aria-labelledby="requirements-heading">
            <h2
              id="requirements-heading"
              data-en="1. System requirements"
              data-it="1. Requisiti di sistema"
            >1. System requirements</h2>
            <p
              class="hint mt-3 mb-4"
              id="minimum-requirements"
              data-en="Requires PHP <?= $escape($config['requirements']['php_min']) ?> or later, MySQL <?= $escape($config['db']['min_version']) ?> or later, the PHP extensions listed in the details, and writable working folders."
              data-it="Richiede PHP <?= $escape($config['requirements']['php_min']) ?> o superiore, MySQL <?= $escape($config['db']['min_version']) ?> o superiore, le estensioni PHP indicate nei dettagli e le cartelle di lavoro scrivibili."
            >Requires PHP <?= $escape($config['requirements']['php_min']) ?> or later, MySQL <?= $escape($config['db']['min_version']) ?> or later, the PHP extensions listed in the details, and writable working folders.</p>
            <details
              class="check-details"
              id="requirements-details"
              <?= $requirements_have_issues ? 'open' : '' ?>
            >
              <summary class="d-flex align-items-center justify-content-between gap-2 fw-semibold py-2 px-3 rounded bg-body-tertiary">
                <span data-en="Check details" data-it="Dettagli dei controlli">Check details</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
              </summary>
              <ul class="list-unstyled mb-0">
                <?php foreach ($requirement_rows as $requirement): ?>
                  <li
                    class="d-flex align-items-start gap-2 py-2 <?= $requirement_colors[$requirement['state']] ?>"
                    data-check-kind="<?= $escape($requirement['kind']) ?>"
                    data-check-name="<?= $escape($requirement['name']) ?>"
                    data-check-state="<?= $requirement['state'] ?>"
                  >
                    <i class="bi <?= $requirement_icons[$requirement['state']] ?>" aria-hidden="true"></i>
                    <span>
                      <strong class="fw-semibold"><?= $escape($requirement['label']) ?></strong>:
                      <span data-en="<?= $escape($requirement['en']) ?>" data-it="<?= $escape($requirement['it']) ?>"><?= $escape($requirement['en']) ?></span>
                    </span>
                  </li>
                <?php endforeach; ?>
                <li
                  class="d-flex align-items-start gap-2 py-2 <?= $requirement_colors[$mysql_initial_state] ?>"
                  id="mysql-check-detail"
                  data-check-state="<?= $mysql_initial_state ?>"
                  data-driver-available="<?= $system['mysql_available'] ? 'true' : 'false' ?>"
                  data-required-version="<?= $escape($config['db']['min_version']) ?>"
                >
                  <i class="bi <?= $requirement_icons[$mysql_initial_state] ?>" aria-hidden="true"></i>
                  <span>
                    <strong class="fw-semibold">MySQL</strong>:
                    <span
                      id="mysql-detail-status"
                      data-en="<?= $system['mysql_available'] ? 'Version to verify at step 2' : 'Required mysqli extension missing' ?>"
                      data-it="<?= $system['mysql_available'] ? 'Versione da verificare al punto 2' : 'Estensione mysqli richiesta assente' ?>"
                    ><?= $system['mysql_available'] ? 'Version to verify at step 2' : 'Required mysqli extension missing' ?></span>
                  </span>
                </li>
              </ul>
            </details>
            <p class="check-status <?= $critical_requirements_ok ? 'is-passed' : 'text-danger' ?>" role="status">
              <i class="bi <?= $critical_requirements_ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle' ?>" aria-hidden="true"></i>
              <span data-en="<?= $critical_requirements_ok ? 'Preliminary checks passed' : 'Fix the failed requirements before continuing' ?>" data-it="<?= $critical_requirements_ok ? 'Controlli preliminari superati' : 'Correggi i requisiti non superati prima di continuare' ?>"><?= $critical_requirements_ok ? 'Preliminary checks passed' : 'Fix the failed requirements before continuing' ?></span>
            </p>
            <?php if ($requirements_have_warnings): ?>
              <p
                class="alert alert-warning mt-3"
                data-en="Review the failed checks above. The installer will try to create missing upload folders and set writable permissions; optional extensions may still need enabling on your server."
                data-it="Controlla le verifiche non superate. L'installer tentera di creare le cartelle upload mancanti e impostare i permessi; potresti dover abilitare altre estensioni sul server."
              >Review the failed checks above. The installer will try to create missing upload folders and set writable permissions; optional extensions may still need enabling on your server.</p>
            <?php endif; ?>
          </section>
          <form
            id="setup-form"
            method="post"
            autocomplete="off"
            data-requirements-ok="<?= $critical_requirements_ok ? 'true' : 'false' ?>"
            data-password-length="<?= (int)$config['password']['generated_length'] ?>"
          >
            <input
              type="hidden"
              name="install"
              value="1"
            >
            <input
              type="hidden"
              name="installer_lang"
              value="<?= $installer_lang ?>"
            >
            <noscript><p
              class="notice mb-4"
              data-en="JavaScript is disabled. Complete the fields and submit the form; the server will validate your settings."
              data-it="JavaScript non e attivo. Compila i campi e invia il modulo: il server verifichera le impostazioni."
            >JavaScript is disabled. Complete the fields and submit the form; the server will validate your settings.</p></noscript>
            <section class="form-section" aria-labelledby="database-heading">
              <div class="section-copy">
                <h2 class="section-heading d-flex align-items-center gap-2" id="database-heading"><i class="bi bi-database" aria-hidden="true"></i><span data-en="Database" data-it="Database">Database</span></h2>
                <p data-en="Connect Star51 to your database." data-it="Collega Star51 al tuo database.">Connect Star51 to your database.</p>
                <button
                  class="btn btn-primary step-continue"
                  type="button"
                  id="requirements-continue"
                  title="Continue to database"
                  <?= $critical_requirements_ok ? '' : 'disabled' ?>
                  hidden
                ><i class="bi bi-arrow-down" aria-hidden="true"></i><span class="visually-hidden" data-en="Continue to database" data-it="Continua al database">Continue to database</span></button>
              </div>
              <div class="fields d-grid">
                <div class="field">
                  <label for="db_host"><span data-en="Database host" data-it="Host del database">Database host</span> <span class="required">*</span></label
                  ><input
                    class="form-control"
                    id="db_host"
                    name="db_host"
                    value="<?= $form_value('db_host') ?>"
                    required
                    spellcheck="false"
                    aria-describedby="host-hint"
                  />
                  <p
                    class="hint"
                    id="host-hint"
                    data-en="Usually localhost or 127.0.0.1."
                    data-it="Di solito localhost o 127.0.0.1."
                  >Usually localhost or 127.0.0.1.</p>
                </div>
                <div class="field">
                  <label for="db_name"><span data-en="Database name" data-it="Nome del database">Database name</span> <span class="required">*</span></label
                  ><input
                    class="form-control"
                    id="db_name"
                    name="db_name"
                    value="<?= $form_value('db_name') ?>"
                    required
                    spellcheck="false"
                    aria-describedby="database-hint"
                  />
                  <p
                    class="hint"
                    id="database-hint"
                  >
                    <span data-en="Create it first, for example in phpMyAdmin." data-it="Crealo prima, ad esempio con phpMyAdmin.">Create it first, for example in phpMyAdmin.</span>
                    <span class="d-block">Collation: <code>utf8mb4_unicode_ci</code></span>
                  </p>
                </div>
                <div class="field">
                  <label for="db_user"><span data-en="Database username" data-it="Utente del database">Database username</span> <span class="required">*</span></label
                  ><input
                    class="form-control"
                    id="db_user"
                    name="db_user"
                    value="<?= $form_value('db_user') ?>"
                    required
                    spellcheck="false"
                  />
                </div>
                <div class="field">
                  <label
                    for="db_pass"
                    data-en="Database password"
                    data-it="Password del database"
                  >Database password</label>
                  <div class="password-input">
                    <input
                      class="form-control"
                      id="db_pass"
                      name="db_pass"
                      type="password"
                      autocomplete="new-password"
                    /><button
                      class="eye btn btn-light"
                      type="button"
                      data-toggle="db_pass"
                      aria-label="Show password"
                      title="Show password"
                    ><i class="bi bi-eye" aria-hidden="true"></i></button>
                  </div>
                  <p
                    class="hint"
                    data-en="May be empty on a local development server."
                    data-it="Puo essere vuota su un server di sviluppo locale."
                  >May be empty on a local development server.</p>
                </div>
                <div class="full action-row">
                  <button
                    type="button"
                    class="btn btn-primary w-100 fw-semibold"
                    id="test-db"
                    hidden
                  ><i class="bi bi-plug" aria-hidden="true"></i><span data-en="Test connection" data-it="Verifica connessione">Test connection</span></button><span
                    class="check-status"
                    id="db-status"
                    role="status"
                    hidden
                    data-en="Not tested"
                    data-it="Non verificata"
                  >Not tested</span>
                </div>
              </div>
            </section>
            <section class="form-section" aria-labelledby="account-heading">
              <div class="section-copy">
                <h2 class="section-heading d-flex align-items-center gap-2" id="account-heading"><i class="bi bi-person-gear" aria-hidden="true"></i><span data-en="Super Admin" data-it="Super Admin">Super Admin</span></h2>
                <p data-en="Create your administrator account." data-it="Crea il tuo account amministratore.">Create your administrator account.</p>
              </div>
              <div class="fields d-grid">
                <div class="field">
                  <label for="admin_first"><span data-en="First name" data-it="Nome">First name</span> <span class="required">*</span></label
                  ><input
                    class="form-control"
                    id="admin_first"
                    name="admin_first"
                    value="<?= $form_value('admin_first') ?>"
                    required
                    autocomplete="given-name"
                  />
                </div>
                <div class="field">
                  <label for="admin_last"><span data-en="Last name" data-it="Cognome">Last name</span> <span class="required">*</span></label
                  ><input
                    class="form-control"
                    id="admin_last"
                    name="admin_last"
                    value="<?= $form_value('admin_last') ?>"
                    required
                    autocomplete="family-name"
                  />
                </div>
                <div class="field">
                  <label for="admin_email">Email <span class="required">*</span></label
                  ><input
                    class="form-control"
                    id="admin_email"
                    name="admin_email"
                    value="<?= $form_value('admin_email') ?>"
                    type="email"
                    required
                    autocomplete="email"
                    spellcheck="false"
                  />
                  <p
                    class="hint"
                    data-en="Used for account communications and password recovery."
                    data-it="Per comunicazioni dell'account e recupero password."
                  >Used for account communications and password recovery.</p>
                </div>
                <div class="field">
                  <label for="admin_username">Username <span class="required">*</span></label
                  ><input
                    class="form-control"
                    id="admin_username"
                    name="admin_username"
                    value="<?= $form_value('admin_username') ?>"
                    required
                    autocomplete="username"
                    pattern="[A-Za-z0-9_]+"
                    aria-describedby="username-hint"
                  />
                  <p
                    class="hint"
                    id="username-hint"
                    data-en="Letters, numbers and underscores."
                    data-it="Lettere, numeri e trattino basso."
                  >Letters, numbers and underscores.</p>
                </div>
                <fieldset class="full border-0 p-0 m-0">
                  <legend
                    class="fs-6 fw-semibold mb-2"
                    data-en="Administrator password"
                    data-it="Password amministratore"
                  >Administrator password</legend>
                  <div class="password-options d-grid">
                    <label class="option"><input
                      class="form-check-input"
                      type="radio"
                      name="password_mode"
                      value="generated"
                      <?= $selected_password_mode === 'generated' ? 'checked' : '' ?>
                    /><span data-en="Generate a password" data-it="Genera una password">Generate a password</span></label
                    ><label class="option"><input
                      class="form-check-input"
                      type="radio"
                      name="password_mode"
                      value="custom"
                      <?= $selected_password_mode === 'custom' ? 'checked' : '' ?>
                    /><span data-en="Use my password" data-it="Usa la mia password">Use my password</span></label>
                  </div>
                </fieldset>
                <div class="notice full" id="generated-note">
                  <i class="bi bi-shield-check" aria-hidden="true"></i><span data-en="A secure <?= (int)$config['password']['generated_length'] ?>-character password will be generated during installation and shown at the end." data-it="Una password sicura di <?= (int)$config['password']['generated_length'] ?> caratteri verra generata durante l'installazione e mostrata alla fine.">A secure <?= (int)$config['password']['generated_length'] ?>-character password will be generated during installation and shown at the end.</span>
                </div>
                <div class="field personal-password">
                  <label
                    for="admin_password"
                    data-en="Password"
                    data-it="Password"
                  >Password</label>
                  <div class="password-input">
                    <input
                      class="form-control"
                      id="admin_password"
                      name="admin_password"
                      type="password"
                      minlength="<?= (int)$config['password']['min_length'] ?>"
                      autocomplete="new-password"
                    /><button
                      class="eye btn btn-light"
                      type="button"
                      data-toggle="admin_password"
                      aria-label="Show password"
                      title="Show password"
                    ><i class="bi bi-eye" aria-hidden="true"></i></button>
                  </div>
                  <p
                    class="hint"
                    data-en="At least <?= (int)$config['password']['min_length'] ?> characters."
                    data-it="Almeno <?= (int)$config['password']['min_length'] ?> caratteri."
                  >At least <?= (int)$config['password']['min_length'] ?> characters.</p>
                </div>
                <div class="field personal-password"><label
                  for="admin_password_confirm"
                  data-en="Confirm password"
                  data-it="Conferma password"
                >Confirm password</label>
                  <div class="password-input">
                    <input
                      class="form-control"
                      id="admin_password_confirm"
                      name="admin_password_confirm"
                      type="password"
                      minlength="<?= (int)$config['password']['min_length'] ?>"
                      autocomplete="new-password"
                    /><button
                      class="eye btn btn-light"
                      type="button"
                      data-toggle="admin_password_confirm"
                      aria-label="Show password"
                      title="Show password"
                    ><i class="bi bi-eye" aria-hidden="true"></i></button>
                  </div>
                </div>
              </div>
            </section>
            <section class="form-section" aria-labelledby="email-heading">
              <div class="section-copy">
                <h2 class="section-heading d-flex align-items-center gap-2" id="email-heading"><i class="bi bi-envelope" aria-hidden="true"></i><span data-en="Email" data-it="Email">Email</span><span
                  class="optional"
                  data-en="Optional"
                  data-it="Facoltativo"
                >Optional</span></h2>
                <p data-en="Configure outgoing password recovery and contact emails." data-it="Configura l'invio delle email di recupero password e contatto.">Configure outgoing password recovery and contact emails.</p>
              </div>
              <div>
                <div class="switch-row form-switch">
                  <label class="switch-label"><input
                    class="form-check-input"
                    id="smtp-toggle"
                    name="smtp_enabled"
                    value="1"
                    type="checkbox"
                    <?= !empty($form_data['smtp_enabled']) ? 'checked' : '' ?>
                    role="switch"
                    aria-controls="smtp-content"
                  /><span data-en="Set up Gmail SMTP" data-it="Configura Gmail SMTP">Set up Gmail SMTP</span></label>
                </div>
                <div
                  class="smtp-off alert alert-warning"
                  id="smtp-off"
                >
                  <strong class="d-block mb-2" data-en="Without Gmail SMTP" data-it="Senza Gmail SMTP">Without Gmail SMTP</strong>
                  <ul class="mb-0 ps-3">
                    <li data-en="Password recovery depends on your server's mail delivery." data-it="Il recupero password dipende dal servizio email del server.">Password recovery depends on your server's mail delivery.</li>
                    <li data-en="Contact confirmation emails depend on your server's mail delivery." data-it="Le conferme di contatto dipendono dal servizio email del server.">Contact confirmation emails depend on your server's mail delivery.</li>
                  </ul>
                </div>
                <div class="smtp-content fields d-grid" id="smtp-content">
                  <div class="field"><label
                    for="gmail_email"
                    data-en="Gmail address"
                    data-it="Indirizzo Gmail"
                  >Gmail address</label><input
                    class="form-control"
                    id="gmail_email"
                    name="gmail_email"
                    value="<?= $form_value('gmail_email') ?>"
                    type="email"
                    autocomplete="off"
                  /></div>
                  <div class="field">
                    <label
                      for="gmail_app_password"
                      data-en="Gmail app password"
                      data-it="Password per app Gmail"
                    >Gmail app password</label>
                    <div class="password-input">
                      <input
                        class="form-control"
                        id="gmail_app_password"
                        name="gmail_app_password"
                        type="password"
                        autocomplete="new-password"
                      /><button
                        class="eye btn btn-light"
                        type="button"
                        data-toggle="gmail_app_password"
                        aria-label="Show password"
                        title="Show password"
                      ><i class="bi bi-eye" aria-hidden="true"></i></button>
                    </div>
                    <p class="hint"><span data-en="16 characters. " data-it="16 caratteri. ">16 characters. </span><a
                      href="https://support.google.com/accounts/answer/185833"
                      target="_blank"
                      rel="noopener"
                    ><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i><span data-en="Create an app password" data-it="Crea una password per app">Create an app password</span></a></p>
                  </div>

                </div>
              </div>
            </section>
            <section class="form-section" aria-labelledby="site-heading">
              <div class="section-copy">
                <h2 class="section-heading d-flex align-items-center gap-2" id="site-heading"><i class="bi bi-globe2" aria-hidden="true"></i><span data-en="Site address" data-it="Indirizzo del sito">Site address</span></h2>
                <p data-en="Enter your site's URL to generate sitemap.xml and robots.txt." data-it="Indica l'URL del sito per generare sitemap.xml e robots.txt.">Enter your site's URL to generate sitemap.xml and robots.txt.</p>
              </div>
              <div class="fields d-grid">
                <div class="full">
                  <label for="site_url"><span data-en="Public URL" data-it="URL pubblico">Public URL</span> <span
                    class="optional"
                    data-en="Optional"
                    data-it="Facoltativo"
                  >Optional</span></label
                  ><input
                    class="form-control"
                    id="site_url"
                    name="site_url"
                    value="<?= $form_value('site_url') ?>"
                    type="url"
                    spellcheck="false"
                  />
                  <p
                    class="hint"
                    data-en="Leave empty to detect it automatically. Include the port and subfolder, if present."
                    data-it="Lascia vuoto per rilevarlo automaticamente. Includi porta e sottocartella, se presenti."
                  >Leave empty to detect it automatically. Include the port and subfolder, if present.</p>
                </div>
              </div>
            </section>
            <div class="install-footer">
              <p><span class="required">*</span> <span data-en="Required fields. Review your settings before installation." data-it="Campi obbligatori. Verifica le impostazioni prima dell'installazione.">Required fields. Review your settings before installation.</span></p>
              <p data-en="Installation will create the Star51 tables and save your configuration." data-it="L'installazione creera le tabelle Star51 e salvera la configurazione.">Installation will create the Star51 tables and save your configuration.</p>
              <button
                type="submit"
                class="btn btn-primary w-100 fw-semibold"
                id="install-submit"
              ><i class="bi bi-play me-1" aria-hidden="true"></i><span data-en="Install Star51" data-it="Installa Star51">Install Star51</span></button>
            </div>
          </form>

        <?php endif; ?>
        <footer class="bottom d-flex align-items-start justify-content-between">
          <span>Star51 <?= $escape(STAR51_VERSION) ?></span>
          <a
            href="install_guide.html"
            target="_blank"
            rel="noopener"
          ><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i><span data-en="Installation Guide" data-it="Guida di installazione">Installation Guide</span></a>
        </footer>
      </main>
    </div>


    <div
      class="waiting-layer"
      id="install-waiting"
      role="status"
      aria-live="polite"
      hidden
    >
      <div class="waiting-content">
        <div class="spinner-border" aria-hidden="true"></div>
        <p data-en="Installation in progress. Keep this page open and wait for the result." data-it="Installazione in corso. Mantieni aperta questa pagina e attendi il risultato.">Installation in progress. Keep this page open and wait for the result.</p>
      </div>
    </div>

    <script>
      (() => {
        function installerDraft(form, storage, key, restore = true) {
          // Only these non-secret fields may be stored, regardless of input type.
          const names = ['db_host', 'db_name', 'db_user', 'admin_first', 'admin_last',
            'admin_email', 'admin_username', 'gmail_email', 'site_url', 'installer_lang'];
          const restoredFields = [];
          if (!form) {
            try { if (storage) storage.removeItem(key); } catch (_) {}
            return;
          }
          if (restore) {
            try {
              const draft = JSON.parse(storage.getItem(key));
              if (draft && draft.version === 1 && draft.fields) {
                names.forEach(name => {
                  const value = draft.fields[name];
                  if (typeof value === 'string' && value.length <= 2048) {
                    form.elements[name].value = value;
                    if (value.trim() !== '') restoredFields.push(name);
                  }
                });
                if (['generate', 'custom'].includes(draft.fields.password_mode)) {
                  form.elements.password_mode.value = draft.fields.password_mode;
                  if (draft.fields.password_mode === 'custom') restoredFields.push('password_mode');
                }
                if (typeof draft.fields.smtp_enabled === 'boolean') {
                  form.elements.smtp_enabled.checked = draft.fields.smtp_enabled;
                  if (draft.fields.smtp_enabled) restoredFields.push('smtp_enabled');
                }
              }
            } catch (_) {}
          }
          return {
            restoredFields,
            save() {
              try {
                const fields = {};
                names.forEach(name => { fields[name] = form.elements[name].value; });
                fields.password_mode = form.elements.password_mode.value;
                fields.smtp_enabled = form.elements.smtp_enabled.checked;
                storage.setItem(key, JSON.stringify({ version: 1, fields }));
              } catch (_) {}
            },
          };
        }
        // End installer draft helper.
        let language = document.documentElement.lang === 'it' ? 'it' : 'en';
        const form = document.querySelector('#setup-form');
        let draftStorage = null;
        try { draftStorage = window.sessionStorage; } catch (_) {}
        const draft = installerDraft(form, draftStorage, 'star51:installer-draft:v1:' + window.location.pathname,
          <?= ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? 'false' : 'true' ?>);
        if (form && ['en', 'it'].includes(form.elements.installer_lang.value)) {
          language = form.elements.installer_lang.value;
        }
        const text = (en, it) => language === 'en' ? en : it;
        const translated = (element, en, it) => {
          element.dataset.en = en;
          element.dataset.it = it;
          element.textContent = text(en, it);
        };
        let refreshSteps = () => {};
        function updateEye(button) {
          const visible = document.getElementById(button.dataset.toggle).type === 'text';
          const label = visible ? text('Hide password', 'Nascondi password') : text('Show password', 'Mostra password');
          button.setAttribute('aria-label', label);
          button.title = label;
          button.querySelector('i').className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
        function setLanguage(lang) {
          language = lang;
          document.documentElement.lang = lang;
          document.querySelectorAll('[data-en]').forEach(element => {
            element.textContent = element.dataset[lang];
          });
          document.querySelectorAll('[data-lang]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.lang === lang));
          });
          document.querySelector('.langs').setAttribute('aria-label', text('Language', 'Lingua'));
          document.querySelectorAll('[data-toggle]').forEach(updateEye);
          if (form) form.elements.installer_lang.value = lang;
          refreshSteps();
        }
        document.querySelectorAll('[data-lang]').forEach(button => {
          button.addEventListener('click', () => setLanguage(button.dataset.lang));
        });
        document.querySelectorAll('[data-toggle]').forEach(button => {
          button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.toggle);
            input.type = input.type === 'password' ? 'text' : 'password';
            updateEye(button);
          });
        });
        setLanguage(language);
        if (!form) return;
        form.addEventListener('input', () => draft.save());
        form.addEventListener('change', () => draft.save());
        document.querySelectorAll('[data-lang]').forEach(button => {
          button.addEventListener('click', () => draft.save());
        });

        // JavaScript enhances an ordinary POST form; the server remains authoritative.
        form.noValidate = true;
        const value = id => document.getElementById(id).value;
        const smtpEnabled = () => document.querySelector('#smtp-toggle').checked;
        const customPassword = () => form.querySelector('[name="password_mode"]:checked').value === 'custom';
        const generatedLength = Number(form.dataset.passwordLength);
        const sections = Array.from(form.querySelectorAll('.form-section'));
        const completed = [false, false, false];
        const revisions = [0, 0, 0, 0];
        const pending = [false, false, false];
        const remoteMessages = [null, null, null];
        const controls = [];
        const states = [];
        const recaps = [];
        const edits = [];
        const draftNotices = [];
        const draftSectionFields = [
          ['db_host', 'db_name', 'db_user'],
          ['admin_first', 'admin_last', 'admin_email', 'admin_username', 'password_mode'],
          ['gmail_email', 'smtp_enabled'],
          ['site_url'],
        ];
        let requirementsDone = false;
        let installing = false;
        const requirementsAction = document.querySelector('#requirements-continue');
        requirementsAction.hidden = false;
        const footer = form.querySelector('.install-footer');

        sections.forEach((section, index) => {
          const body = section.lastElementChild;
          const fieldset = document.createElement('fieldset');
          fieldset.className = 'section-controls';
          fieldset.setAttribute('aria-labelledby', section.querySelector('h2').id);
          body.before(fieldset);
          fieldset.append(body);
          controls.push(fieldset);
          const copy = section.querySelector('.section-copy');
          const number = document.createElement('span');
          number.textContent = (index + 2) + '. ';
          section.querySelector('h2').prepend(number);
          const state = document.createElement('p');
          state.className = 'section-state';
          state.setAttribute('role', 'status');
          copy.append(state);
          states.push(state);
          const draftNotice = document.createElement('p');
          draftNotice.className = 'draft-notice';
          draftNotice.hidden = true;
          copy.append(draftNotice);
          draftNotices.push(draftNotice);
          const recap = document.createElement('dl');
          recap.className = 'step-recap';
          copy.append(recap);
          recaps.push(recap);
          const edit = document.createElement('button');
          edit.type = 'button';
          edit.className = 'step-edit btn';
          edit.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i><span></span>';
          copy.append(edit);
          edits.push(edit);
          edit.addEventListener('click', () => {
            invalidate(index);
            delete fieldset.dataset.attempted;
            clearFeedback(index);
            renderSteps();
            fieldset.querySelector('input:not(:disabled), button').focus();
          });
          const changed = () => {
            invalidate(index);
            syncOptions();
            if (fieldset.dataset.attempted) validateSection(index, false);
            else clearFeedback(index);
            renderSteps();
          };
          fieldset.addEventListener('input', changed);
          fieldset.addEventListener('change', changed);
        });
        function invalidate(index) {
          for (let i = index; i < revisions.length; i++) revisions[i]++;
          if (index < 3) completed.fill(false, index);
          for (let i = index; i < 3; i++) remoteMessages[i] = null;
        }
        function syncOptions() {
          const custom = customPassword();
          document.querySelector('#generated-note').hidden = custom;
          document.querySelectorAll('.personal-password').forEach(field => {
            field.hidden = !custom;
            const input = field.querySelector('input');
            input.disabled = !custom;
            input.required = custom;
          });
          document.querySelector('#smtp-content').hidden = !smtpEnabled();
          document.querySelector('#smtp-off').hidden = smtpEnabled();
          document.querySelectorAll('#smtp-content input').forEach(input => {
            input.disabled = !smtpEnabled();
            input.required = smtpEnabled();
          });
          const confirm = document.getElementById('admin_password_confirm');
          confirm.setCustomValidity(custom && confirm.value !== value('admin_password')
            ? text('Passwords do not match.', 'Le password non corrispondono.') : '');
          const email = document.getElementById('gmail_email');
          email.setCustomValidity(smtpEnabled() && email.value && !/^[^@\s]+@gmail\.com$/i.test(email.value.trim())
            ? text('Use a Gmail address.', 'Usa un indirizzo Gmail.') : '');
          const password = document.getElementById('gmail_app_password');
          password.setCustomValidity(smtpEnabled() && password.value && !/^[A-Za-z0-9]{16}$/.test(password.value.replace(/[\s-]/g, ''))
            ? text('Enter the 16-character Gmail app password.', 'Inserisci la password per app Gmail di 16 caratteri.') : '');
          const url = document.getElementById('site_url');
          url.setCustomValidity(url.value && !/^https?:\/\/[^\s]+$/i.test(url.value)
            ? text('Use an http:// or https:// URL.', 'Usa un URL http:// o https://.') : '');
        }
        function feedbackFor(index) {
          let feedback = controls[index].querySelector('.verification-feedback');
          if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'verification-feedback';
            feedback.id = 'step-feedback-' + index;
            feedback.setAttribute('role', 'alert');
            controls[index].append(feedback);
          }
          return feedback;
        }
        function clearFeedback(index) {
          const feedback = feedbackFor(index);
          feedback.hidden = true;
          delete feedback.dataset.inputId;
          controls[index].querySelectorAll('input').forEach(input => {
            input.removeAttribute('aria-invalid');
            const described = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && id !== feedback.id);
            if (described.length) input.setAttribute('aria-describedby', described.join(' '));
            else input.removeAttribute('aria-describedby');
          });
        }
        function showFeedback(index, message, inputId = '', kind = 'error') {
          const feedback = feedbackFor(index);
          feedback.hidden = false;
          feedback.dataset.kind = kind;
          feedback.textContent = message;
          if (inputId) {
            feedback.dataset.inputId = inputId;
            const input = document.getElementById(inputId);
            input.setAttribute('aria-invalid', 'true');
            const ids = new Set((input.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
            ids.add(feedback.id);
            input.setAttribute('aria-describedby', Array.from(ids).join(' '));
          }
        }
        function fieldError(input) {
          const label = input.closest('.field, .full')?.querySelector('label')?.textContent.trim().replace(/\s*\*$/, '') || text('Field', 'Campo');
          let message = text('Check the value entered.', 'Controlla il valore inserito.');
          if (input.validity.valueMissing || (input.required && !input.value.trim())) message = text('Complete this field.', 'Completa questo campo.');
          else if (input.validity.customError) message = input.validationMessage;
          else if (input.validity.typeMismatch) message = input.type === 'email'
            ? text('Enter a valid email address.', 'Inserisci un indirizzo email valido.')
            : text('Enter a complete URL.', 'Inserisci un URL completo.');
          else if (input.validity.patternMismatch) message = text('Use letters, numbers and underscores only.', 'Usa solo lettere, numeri e trattino basso.');
          else if (input.minLength > 0 && input.value.length < input.minLength) message = text('Use at least ' + input.minLength + ' characters.', 'Usa almeno ' + input.minLength + ' caratteri.');
          return text('Check ', 'Controlla ') + label + ': ' + message;
        }
        function validateSection(index, focus = true) {
          controls[index].dataset.attempted = 'true';
          syncOptions();
          clearFeedback(index);
          const invalid = Array.from(controls[index].querySelectorAll('input')).filter(input =>
            !input.disabled && (!input.checkValidity() || (input.required && !input.value.trim()) || (input.minLength > 0 && input.value && input.value.length < input.minLength))
          );
          invalid.forEach(input => input.setAttribute('aria-invalid', 'true'));
          if (!invalid.length) return true;
          showFeedback(index, fieldError(invalid[0]), invalid[0].id);
          if (focus) invalid[0].focus();
          return false;
        }
        function addAction(index, en, it, handler) {
          const row = document.createElement('div');
          row.className = 'step-action';
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'btn btn-primary w-100 fw-semibold';
          translated(button, en, it);
          button.addEventListener('click', handler);
          row.append(button);
          controls[index].append(row);
          return button;
        }
        const databaseAction = document.querySelector('#test-db');
        databaseAction.hidden = false;
        document.querySelector('#db-status').remove();
        translated(databaseAction.querySelector('span'), 'Verify and continue', 'Verifica e continua');
        const accountAction = addAction(1, 'Confirm administrator', 'Conferma amministratore', () => {
          if (!validateSection(1)) return;
          completed[1] = true;
          renderSteps();
          focusStep(2);
        });
        const emailAction = addAction(2, 'Continue without SMTP', 'Continua senza SMTP', async () => {
          if (!validateSection(2)) return;
          if (smtpEnabled()) await checkRemote(2, 'test_smtp', ['gmail_email', 'gmail_app_password']);
          else {
            completed[2] = true;
            renderSteps();
            focusStep(3);
          }
        });
        databaseAction.addEventListener('click', () => checkRemote(0, 'test_db', ['db_host', 'db_name', 'db_user', 'db_pass']));
        async function checkRemote(index, action, fields) {
          if (pending[index] || !validateSection(index)) return;
          const revision = revisions[index];
          pending[index] = true;
          remoteMessages[index] = null;
          const controller = new AbortController();
          const timeout = setTimeout(() => controller.abort(), 20000);
          renderSteps();
          showFeedback(index, text('Checking connection...', 'Verifica della connessione...'), '', 'info');
          try {
            const body = new URLSearchParams({ action });
            fields.forEach(name => body.set(name, value(name)));
            const response = await fetch(window.location.pathname, { method: 'POST', body, signal: controller.signal, credentials: 'same-origin' });
            const result = await response.json();
            if (revisions[index] !== revision) return;
            remoteMessages[index] = result;
            if (!response.ok || !result.success) {
              const inputId = index === 0 ? ({host:'db_host', credentials:'db_user', db_name:'db_name'}[result.error_field] || '') : '';
              showFeedback(index, result['message_' + language] || text('Connection failed.', 'Connessione fallita.'), inputId);
            } else {
              completed[index] = true;
              clearFeedback(index);
              renderSteps();
              focusStep(index + 1);
            }
          } catch (error) {
            if (revisions[index] === revision) showFeedback(index, text('Cannot complete the check. Verify the connection and try again.', 'Impossibile completare la verifica. Controlla la connessione e riprova.'));
          } finally {
            clearTimeout(timeout);
            pending[index] = false;
            renderSteps();
          }
        }
        function focusStep(index) {
          const heading = sections[index].querySelector('h2');
          heading.tabIndex = -1;
          heading.focus();
        }
        function mysqlRequirementState(result, driverAvailable) {
          if (!driverAvailable) return { state: 'error', version: null };
          const data = result?.data;
          if (typeof data?.mysql_version !== 'string' || !data.mysql_version.trim() || typeof data?.mysql_version_ok !== 'boolean') {
            return { state: 'pending', version: null };
          }
          return { state: data.mysql_version_ok ? 'passed' : 'error', version: data.mysql_version };
        }
        function renderMysqlRequirement() {
          const detail = document.querySelector('#mysql-check-detail');
          const driverAvailable = detail.dataset.driverAvailable === 'true';
          const result = mysqlRequirementState(remoteMessages[0], driverAvailable);
          const colors = { pending: 'text-body-secondary', passed: 'text-success', error: 'text-danger' };
          const icons = { pending: 'bi-clock', passed: 'bi-check-circle-fill', error: 'bi-x-circle-fill' };
          detail.dataset.checkState = result.state;
          detail.className = 'd-flex align-items-start gap-2 py-2 ' + colors[result.state];
          detail.querySelector('i').className = 'bi ' + icons[result.state];
          const required = detail.dataset.requiredVersion + '+';
          translated(document.querySelector('#mysql-detail-status'),
            !driverAvailable ? 'Required mysqli extension missing' : result.state === 'pending' ? 'Version to verify at step 2' : result.state === 'passed' ? result.version + ' - compatible version' : result.version + ' - required: ' + required,
            !driverAvailable ? 'Estensione mysqli richiesta assente' : result.state === 'pending' ? 'Versione da verificare al punto 2' : result.state === 'passed' ? result.version + ' - versione compatibile' : result.version + ' - richiesto: ' + required);
          if (result.state === 'error') document.querySelector('#requirements-details').open = true;
        }
        function passwordMask(id) {
          return value(id).length ? '\u2022'.repeat(8) : text('Not set', 'Non impostata');
        }
        function stepSummary(index) {
          if (index === 0) return [
            ['Database', value('db_name')], ['Host', value('db_host')],
            ['Username', value('db_user')], ['Password', passwordMask('db_pass')],
            [text('Check', 'Verifica'), remoteMessages[0]?.[text('message_en', 'message_it')] || text('Connection verified', 'Connessione verificata')]
          ];
          if (index === 1) return [
            [text('Name', 'Nome'), value('admin_first') + ' ' + value('admin_last')],
            ['Username', value('admin_username')], ['Email', value('admin_email')],
            ['Password', customPassword() ? passwordMask('admin_password') + text(' (personal)', ' (personale)')
              : text('Automatic, ' + generatedLength + ' characters. Will be generated during installation and shown at the end.', 'Automatica, ' + generatedLength + " caratteri. Verra generata durante l'installazione e mostrata alla fine.")]
          ];
          if (!smtpEnabled()) return [
            ['SMTP', text('Not configured', 'Non configurato')],
            [text('Access', 'Accesso'), text('Username and password', 'Username e password')]
          ];
          return [
            ['Gmail', value('gmail_email')], ['Password', passwordMask('gmail_app_password')],
            [text('Check', 'Verifica'), text('Valid data format; SMTP service reachable', 'Formato dei dati corretto; servizio SMTP raggiungibile')],
            [text('Access', 'Accesso'), text('Username and password', 'Username e password')]
          ];
        }
        function renderSteps() {
          syncOptions();
          renderMysqlRequirement();
          sections.forEach((section, index) => {
            const enabled = requirementsDone && completed.slice(0, index).every(Boolean);
            const done = index < 3 && completed[index];
            controls[index].disabled = !enabled;
            controls[index].hidden = !enabled || done;
            section.dataset.state = !enabled ? 'locked' : done ? 'complete' : 'active';
            states[index].hidden = !enabled;
            translated(states[index], done ? 'Confirmed' : 'Ready to complete', done ? 'Confermato' : 'Da completare');
            if (done) states[index].insertAdjacentHTML('afterbegin', '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>');
            const recovered = draftSectionFields[index].some(name => draft.restoredFields.includes(name));
            draftNotices[index].hidden = done || !recovered;
            translated(draftNotices[index], 'Data restored \u00b7 Verify again', 'Dati recuperati \u00b7 Da verificare');
            draftNotices[index].insertAdjacentHTML('afterbegin', '<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>');
            edits[index].hidden = !done || !enabled;
            translated(edits[index].querySelector('span'), 'Edit', 'Modifica');
            edits[index].setAttribute('aria-label', text('Edit ', 'Modifica ') + section.querySelector('h2').textContent.trim());
            recaps[index].replaceChildren();
            if (done && enabled) {
              stepSummary(index).forEach(([label, content]) => {
                const dt = document.createElement('dt'), dd = document.createElement('dd');
                dt.textContent = label;
                dd.textContent = content;
                recaps[index].append(dt, dd);
              });
            }
          });
          databaseAction.disabled = pending[0];
          emailAction.disabled = pending[2];
          translated(emailAction, smtpEnabled() ? 'Verify and continue' : 'Continue without SMTP', smtpEnabled() ? 'Verifica e continua' : 'Continua senza SMTP');
          const ready = requirementsDone && completed.every(Boolean) && !pending.some(Boolean);
          footer.hidden = !ready;
          footer.querySelector('button').disabled = !ready || installing;
          requirementsAction.hidden = requirementsDone;
          requirementsAction.title = text('Continue to database', 'Continua al database');
        }
        requirementsAction.addEventListener('click', () => {
          if (form.dataset.requirementsOk !== 'true') return;
          requirementsDone = true;
          renderSteps();
          focusStep(0);
        });
        function validateAll() {
          if (!requirementsDone || !completed.every(Boolean) || pending.some(Boolean)) return false;
          for (let index = 0; index < controls.length; index++) {
            if (!validateSection(index)) {
              if (index < 3) completed.fill(false, index);
              renderSteps();
              return false;
            }
          }
          return true;
        }
        form.addEventListener('submit', event => {
          event.preventDefault();
          beginInstallation();
        });
        function beginInstallation() {
          if (installing || !validateAll()) return;
          installing = true;
          form.elements.installer_lang.value = language;
          form.inert = true;
          form.querySelectorAll('button').forEach(button => button.disabled = true);
          document.querySelector('#install-waiting').hidden = false;
          HTMLFormElement.prototype.submit.call(form);
        }
        window.addEventListener('pageshow', () => {
          installing = false;
          form.inert = false;
          form.querySelectorAll('button').forEach(button => button.disabled = false);
          document.querySelector('#install-waiting').hidden = true;
          renderSteps();
        });
        refreshSteps = () => {
          renderSteps();
          controls.forEach((fieldset, index) => {
            if (fieldset.dataset.attempted && !completed[index]) validateSection(index, false);
            const remote = remoteMessages[index];
            if (remote && !remote.success) showFeedback(index, remote['message_' + language] || text('Connection failed.', 'Connessione fallita.'));
          });
        };
        document.querySelectorAll('.field').forEach(field => {
          const hint = field.querySelector(':scope > .hint');
          const input = field.querySelector(':scope > input, :scope > .password-input');
          if (hint && input) input.before(hint);
        });
        setLanguage(language);
        if (document.querySelector('#server-errors')) document.querySelector('#server-errors').focus();
      })();
    </script>
  </body>
</html>
