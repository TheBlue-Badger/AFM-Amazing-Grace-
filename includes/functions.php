<?php
if (!defined('APP_BOOT')) { http_response_code(403); exit('No direct access'); }

/** Escape for safe HTML output. Use this around every piece of dynamic data. */
function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** ---- CSRF protection ---- */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}
function verify_csrf(): bool {
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($_SESSION['csrf_token']) && is_string($sent) && hash_equals($_SESSION['csrf_token'], $sent);
}
function require_csrf(): void {
    if (!verify_csrf()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Your session expired. Please refresh the page and try again.']);
        exit;
    }
}

/** ---- Formatting ---- */
function money($n): string {
    $n = (float)$n;
    $decimals = (fmod($n, 1.0) === 0.0) ? 0 : 2;
    return CURRENCY_PREFIX . number_format($n, $decimals);
}

/** ---- Admin auth guards ---- */
function is_admin(): bool {
    return !empty($_SESSION['admin_id']);
}
function require_admin(): void {
    if (!is_admin()) {
        header('Location: /admin/login.php');
        exit;
    }
}
/** True if the currently logged-in admin's email matches DEVELOPER_EMAIL. */
function is_developer(): bool {
    return is_admin()
        && !empty($_SESSION['admin_email'])
        && strcasecmp($_SESSION['admin_email'], DEVELOPER_EMAIL) === 0;
}
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

/** ---- Lightweight self-hosted performance tracking ---- */
function track_page_view(): void {
    try {
        $loadMs = (int)round((microtime(true) - APP_START_TIME) * 1000);
        $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        db()->prepare('INSERT INTO page_views (path, load_ms, created_at) VALUES (?, ?, ?)')
            ->execute([mb_substr($path, 0, 255), max(0, $loadMs), time()]);
    } catch (Throwable $e) {
        // Tracking must never break the page it's tracking.
    }
}

/** ---- Login rate limiting (per IP) ---- */
function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function too_many_login_attempts(PDO $pdo, string $ip, int $limit = 6, int $windowSeconds = 900): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?');
    $stmt->execute([$ip, time() - $windowSeconds]);
    return (int)$stmt->fetchColumn() >= $limit;
}
function record_login_attempt(PDO $pdo, string $ip): void {
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)');
    $stmt->execute([$ip, time()]);
    // Housekeeping: drop attempts older than a day so the table doesn't grow forever.
    $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([time() - 86400]);
}
function clear_login_attempts(PDO $pdo, string $ip): void {
    $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
}

/** ---- JSON responses for API endpoints ---- */
function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_out(['error' => 'Method not allowed'], 405);
    }
}

/** ---- File uploads (product images, event posters, gallery photos) ---- */
const ALLOWED_UPLOAD_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Validate and move an uploaded file into UPLOAD_DIR/$subdir, using a random
 * filename (never the original name, to avoid path traversal / overwrite
 * tricks). Returns the stored filename on success, or null if no file was
 * uploaded, or a string error message on failure.
 *
 * @return array{ok:bool, filename?:string, error?:string}
 */
function handle_upload(array $file, string $subdir, int $maxSize = MAX_UPLOAD_SIZE): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => null]; // nothing uploaded, not an error
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'error' => 'That file is too large (max ' . round($maxSize / 1024 / 1024, 1) . 'MB).'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(ALLOWED_UPLOAD_MIME[$mime])) {
        return ['ok' => false, 'error' => 'Please upload a JPEG, PNG, or WebP image.'];
    }

    $ext = ALLOWED_UPLOAD_MIME[$mime];
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $targetDir = UPLOAD_DIR . '/' . trim($subdir, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0770, true);
    }
    $target = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded file.'];
    }
    @chmod($target, 0640);

    return ['ok' => true, 'filename' => $filename];
}

/** Delete a previously uploaded file. Safe against path traversal. */
function delete_upload(string $filename, string $subdir): void {
    $filename = basename($filename); // strip any directory components
    if ($filename === '' || $filename === '.' || $filename === '..') {
        return;
    }
    $path = UPLOAD_DIR . '/' . trim($subdir, '/') . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

/** Public URL for a stored upload, or '' if no filename given. */
function upload_url(string $filename, string $subdir): string {
    if ($filename === '') return '';
    return UPLOAD_URL_BASE . '/' . trim($subdir, '/') . '/' . rawurlencode($filename);
}

/** ---- Cart (stored server-side in the PHP session) ---- */
function cart_summary(): array {
    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    $total = 0.0;
    foreach ($cart as $lineId => $line) {
        $lineTotal = (float)$line['price'] * (int)$line['qty'];
        $total += $lineTotal;
        $items[] = [
            'line_id' => $lineId,
            'name' => $line['name'],
            'price' => (float)$line['price'],
            'qty' => (int)$line['qty'],
            'type' => $line['type'],
            'line_total' => $lineTotal,
        ];
    }
    return ['items' => $items, 'total' => $total, 'count' => array_sum(array_column($cart, 'qty'))];
}
