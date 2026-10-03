<?php
// Page-load timing starts here, the very first line of the very first file
// every page includes, so footer.php can measure true end-to-end time.
define('APP_START_TIME', microtime(true));

/**
 * Block this file from ever being requested directly over the web, even on
 * servers where .htaccess is ignored (e.g. misconfigured Apache or Nginx
 * without an equivalent rule). This only allows the file to run when it has
 * been include()'d/require()'d by another script.
 */
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(403);
    exit('No direct access');
}

/**
 * Site configuration.
 * Edit the constants below for your deployment. Keep this file OUTSIDE
 * the public web root in production if your host allows it, or make sure
 * the .htaccess in this folder is active so it can never be requested directly.
 */

if (!defined('APP_BOOT')) {
    define('APP_BOOT', true);
}

define('SITE_NAME', 'AFM Amazing Grace Center');
define('SITE_SHORT_NAME', 'Amazing Grace Eersteriver');
define('SITE_TAGLINE', 'Eersteriver Branch, AFM of South Africa (ECC)');
define('SITE_CITY', 'Eersteriver, Cape Town');
define('SITE_ADDRESS', 'Forest Heights High School, Eersteriver, Cape Town, 7100');
define('SITE_EMAIL', 'afmeersteriver@gmail.com');
define('SITE_PHONE', '+27 64 286 8638');
define('SITE_PHONE_DISPLAY', '064 286 8638');
define('SITE_FACEBOOK', 'https://www.facebook.com/profile.php?id=100072885317896');
define('SITE_TIKTOK', 'https://tiktok.com/@afm.amazing.grace');
define('SITE_YOUTUBE', 'https://youtube.com/@AFMAmazingGraceCenter');
define('SITE_WHATSAPP_JOIN', 'https://whatsform.com/da0_ir');
define('PASTOR_NAME', 'Rev G. K. Muwunganirwa');

// ---- Developer access ----
// Whoever logs into the admin panel with this email is automatically
// treated as the developer/maintainer: they see an extra "Developer" tab
// with site performance stats and system diagnostics that other admins
// don't see. This is a plain email match, not a separate login system, so
// you log in through the normal admin login with an account using this
// email address.
define('DEVELOPER_EMAIL', 'mandundutaicy1@gmail.com');

// ---- Banking details shown at checkout for EFT / online bank transfer ----
// Replace these with your real details before launch.
define('BANK_NAME', '[Your Bank Name]');
define('BANK_ACCOUNT_HOLDER', 'AFM Amazing Grace Center Eersteriver');
define('BANK_ACCOUNT_NUMBER', '[0000000000]');
define('BANK_BRANCH_CODE', '[000000]');
define('BANK_ACCOUNT_TYPE', '[Cheque / Savings]');

// ---- Database configuration ----
// The site supports two database backends:
//   • SQLite  — zero config, perfect for local development, works on any host
//   • MySQL   — for production / cPanel / shared hosting
//
// HOW IT WORKS: if DB_NAME is still the placeholder 'your_database_name',
// the site uses the local SQLite file automatically. Once you fill in your
// real MySQL credentials (from cPanel → MySQL Databases), it switches to MySQL.
//
// MySQL / MariaDB settings (fill these in when deploying to your live host):
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'church_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// SQLite path (used automatically when the MySQL details above are unchanged):
define('DB_SQLITE_PATH', __DIR__ . '/../data/church.sqlite');

// Auto-detect which driver to use:
define('DB_DRIVER', DB_NAME === 'your_database_name' ? 'sqlite' : 'mysql');

define('CURRENCY_PREFIX', 'R');

// ---- File uploads (product images, event posters, gallery photos) ----
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL_BASE', '/uploads');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB

// ---- Session hardening (must run before session_start()) ----
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_only_cookies', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_name('agec_session');
    session_start();
}

// ---- Baseline security headers ----
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
