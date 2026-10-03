<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=account');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch();

$current = (string)($_POST['current_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

if (!$user || !password_verify($current, $user['password_hash'])) {
    $_SESSION['flash'] = 'Current password is incorrect.';
} elseif (strlen($new) < 12) {
    $_SESSION['flash'] = 'New password must be at least 12 characters.';
} elseif ($new !== $confirm) {
    $_SESSION['flash'] = 'New password and confirmation do not match.';
} else {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE admin_users SET password_hash = ?, is_default_password = 0 WHERE id = ?')
        ->execute([$hash, $user['id']]);
    $_SESSION['flash'] = 'Password updated successfully.';
}

redirect('/admin/dashboard.php?tab=account');
