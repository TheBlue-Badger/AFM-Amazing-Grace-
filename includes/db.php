<?php
if (!defined('APP_BOOT')) { http_response_code(403); exit('No direct access'); }
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection, creating tables and seed data on first run.
 *
 * The database driver is chosen automatically by the DB_DRIVER constant set in
 * config.php:
 *   • 'sqlite' — when DB_NAME is still the placeholder (local development)
 *   • 'mysql'  — when you've filled in real MySQL credentials (production)
 *
 * Safe to call on every request: table creation uses IF NOT EXISTS, and
 * seeding only runs when admin_users is empty.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Database connection failed: ' . $e->getMessage());
            exit('This site is temporarily unavailable. Please check back shortly.');
        }
    } else {
        // SQLite — create the data directory if it doesn't exist yet
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        try {
            $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('SQLite connection failed: ' . $e->getMessage());
            exit('This site is temporarily unavailable. Please check back shortly.');
        }
    }

    migrate($pdo);

    $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($adminCount === 0) {
        seed($pdo);
    }

    return $pdo;
}

/**
 * Returns true when running on MySQL/MariaDB, false for SQLite.
 */
function is_mysql(): bool {
    return DB_DRIVER === 'mysql';
}

/**
 * Adds a column to an existing table if it isn't there yet.
 * Works on both MySQL (information_schema) and SQLite (PRAGMA table_info).
 */
function add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void {
    if (is_mysql()) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
        );
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    } else {
        // SQLite: use PRAGMA table_info
        $cols = $pdo->query("PRAGMA table_info({$table})")->fetchAll();
        $exists = false;
        foreach ($cols as $col) {
            if (strcasecmp($col['name'], $column) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            // SQLite ALTER TABLE ADD COLUMN doesn't support UNIQUE, PRIMARY KEY,
            // or positional AFTER — strip them all. The constraint only matters
            // for upgrades of very old databases; new installs already have the
            // column in the CREATE TABLE statement.
            $def = preg_replace('/\s+AFTER\s+\S+/i', '', $definition);
            $def = preg_replace('/\bUNIQUE\b/i', '', $def);
            $def = preg_replace('/\bPRIMARY\s+KEY\b/i', '', $def);
            $def = trim(preg_replace('/\s{2,}/', ' ', $def));
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$def}");
        }
    }
}

function migrate(PDO $pdo): void {
    if (is_mysql()) {
        // ---- MySQL / MariaDB tables ----
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) UNIQUE NOT NULL,
            email VARCHAR(160) NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_default_password TINYINT(1) NOT NULL DEFAULT 0,
            created_at INT UNSIGNED NOT NULL,
            last_login INT UNSIGNED NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        add_column_if_missing($pdo, 'admin_users', 'email', "VARCHAR(160) NULL UNIQUE AFTER username");

        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            description VARCHAR(500) NOT NULL DEFAULT '',
            tag VARCHAR(40) NOT NULL DEFAULT '',
            type ENUM('fixed','amount') NOT NULL DEFAULT 'fixed',
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            image VARCHAR(255) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_threads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            visitor_name VARCHAR(80) NOT NULL DEFAULT 'Visitor',
            created_at INT UNSIGNED NOT NULL,
            updated_at INT UNSIGNED NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            thread_id INT UNSIGNED NOT NULL,
            sender ENUM('visitor','admin') NOT NULL,
            message TEXT NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
            INDEX idx_chat_messages_thread (thread_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL,
            reason VARCHAR(120) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL,
            items_json TEXT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            payment_method ENUM('online','in_person') NOT NULL DEFAULT 'in_person',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at INT UNSIGNED NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            attempted_at INT UNSIGNED NOT NULL,
            INDEX idx_login_attempts_ip_time (ip, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            description VARCHAR(1000) NOT NULL DEFAULT '',
            poster_image VARCHAR(255) NOT NULL DEFAULT '',
            event_date DATE NOT NULL,
            event_time VARCHAR(40) NOT NULL DEFAULT '',
            location VARCHAR(160) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at INT UNSIGNED NOT NULL,
            INDEX idx_events_date (event_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_photos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            caption VARCHAR(160) NOT NULL DEFAULT '',
            service_date DATE NOT NULL,
            uploaded_at INT UNSIGNED NOT NULL,
            INDEX idx_gallery_date (service_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS livestreams (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            stream_url VARCHAR(400) NOT NULL DEFAULT '',
            is_live TINYINT(1) NOT NULL DEFAULT 0,
            scheduled_at INT UNSIGNED NULL,
            created_at INT UNSIGNED NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS stream_comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            livestream_id INT UNSIGNED NOT NULL,
            name VARCHAR(60) NOT NULL,
            message VARCHAR(300) NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            INDEX idx_stream_comments_stream (livestream_id, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            path VARCHAR(255) NOT NULL,
            load_ms INT UNSIGNED NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            INDEX idx_page_views_created (created_at),
            INDEX idx_page_views_path (path)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    } else {
        // ---- SQLite tables ----
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE,
            password_hash TEXT NOT NULL,
            is_default_password INTEGER NOT NULL DEFAULT 0,
            created_at INTEGER NOT NULL,
            last_login INTEGER
        )");
        add_column_if_missing($pdo, 'admin_users', 'email', "TEXT UNIQUE");

        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            tag TEXT NOT NULL DEFAULT '',
            type TEXT NOT NULL DEFAULT 'fixed',
            price REAL NOT NULL DEFAULT 0,
            image TEXT NOT NULL DEFAULT '',
            is_active INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_threads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            visitor_name TEXT NOT NULL DEFAULT 'Visitor',
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            thread_id INTEGER NOT NULL REFERENCES chat_threads(id) ON DELETE CASCADE,
            sender TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            reason TEXT NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            created_at INTEGER NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            items_json TEXT NOT NULL,
            total REAL NOT NULL,
            payment_method TEXT NOT NULL DEFAULT 'in_person',
            status TEXT NOT NULL DEFAULT 'pending',
            created_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempted_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            poster_image TEXT NOT NULL DEFAULT '',
            event_date TEXT NOT NULL,
            event_time TEXT NOT NULL DEFAULT '',
            location TEXT NOT NULL DEFAULT '',
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_photos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            caption TEXT NOT NULL DEFAULT '',
            service_date TEXT NOT NULL,
            uploaded_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS livestreams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            stream_url TEXT NOT NULL DEFAULT '',
            is_live INTEGER NOT NULL DEFAULT 0,
            scheduled_at INTEGER,
            created_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS stream_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            livestream_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at INTEGER NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            path TEXT NOT NULL,
            load_ms INTEGER NOT NULL,
            created_at INTEGER NOT NULL
        )");
    }
}

function seed(PDO $pdo): void {
    // ---- Default admin account with a random, one-time password ----
    // We never ship a fixed default password in the code, since a guessable
    // shared default is one of the most common ways template sites get hacked.
    // This first account uses the developer email from config.php, so logging
    // in with it immediately unlocks the Developer tab.
    $password = bin2hex(random_bytes(6)); // 12-character random hex password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash, is_default_password, created_at) VALUES (?, ?, ?, 1, ?)');
    $stmt->execute(['admin', DEVELOPER_EMAIL, $hash, time()]);

    $credDir = __DIR__ . '/../data';
    if (!is_dir($credDir)) {
        mkdir($credDir, 0770, true);
    }
    $credFile = $credDir . '/admin_credentials.txt';
    file_put_contents(
        $credFile,
        "AFM Amazing Grace Center, initial admin login\n" .
        "================================================\n" .
        "Username: admin\n" .
        "Email: " . DEVELOPER_EMAIL . "\n" .
        "Password: {$password}\n\n" .
        "You can log in with either the username or the email above, both work.\n" .
        "This account's email matches DEVELOPER_EMAIL in includes/config.php, so it\n" .
        "will automatically see the extra Developer tab (site performance stats,\n" .
        "system diagnostics). Add other staff as regular admins from the Admins tab,\n" .
        "they won't see the Developer tab unless their email also matches.\n\n" .
        "Log in at /admin/login.php then change this password immediately\n" .
        "from the Account tab. This file is protected from web access by\n" .
        ".htaccess, but you should delete it from the server once you've\n" .
        "logged in and changed the password.\n"
    );
    @chmod($credFile, 0600);

    // ---- Seed an empty livestream row so the admin form has something to edit ----
    $pdo->prepare('INSERT INTO livestreams (title, stream_url, is_live, created_at) VALUES (?, ?, 0, ?)')
        ->execute(['Sunday Service Livestream', '', time()]);

    // ---- Seed starter products ----
    $products = [
        ['Tithe', 'Bring your tithe to the storehouse, wherever you are.', 'Giving', 'amount', 0, 1],
        ['Building Fund Offering', 'Help us grow a permanent home for the Eersteriver family.', 'Giving', 'amount', 0, 2],
        ['General Offering', 'A free-will offering toward the week-to-week work of the ministry.', 'Giving', 'amount', 0, 3],
        ['Amazing Grace T-Shirt', 'Church branded T-shirt, unisex sizing S to XXL.', 'Merch', 'fixed', 250, 4],
        ['Church Anniversary Banquet', 'One seat at our anniversary celebration dinner.', 'Event', 'fixed', 150, 5],
        ['Devotional Booklet (Quarterly)', 'Our quarterly devotional and Sunday bulletin bundle.', 'Resource', 'fixed', 40, 6],
        ['Youth Camp Registration', 'Secures one young person a place at the next youth camp.', 'Event', 'fixed', 350, 7],
    ];
    $stmt = $pdo->prepare('INSERT INTO products (name, description, tag, type, price, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($products as $p) {
        $stmt->execute($p);
    }
}
