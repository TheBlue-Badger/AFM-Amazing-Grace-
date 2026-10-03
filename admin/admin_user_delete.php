<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=admins');
}

$id = (int)($_POST['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch();

if (!$target) {
    $_SESSION['flash'] = 'That admin account no longer exists.';
} elseif ($id === (int)$_SESSION['admin_id']) {
    $_SESSION['flash'] = "You can't remove your own account while logged in as it.";
} elseif ($target['email'] && strcasecmp($target['email'], DEVELOPER_EMAIL) === 0) {
    $_SESSION['flash'] = 'This account is protected because it matches the developer email in config.php.';
} else {
    $total = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($total <= 1) {
        $_SESSION['flash'] = 'At least one admin account has to remain.';
    } else {
        $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = 'Admin account removed.';
    }
}

redirect('/admin/dashboard.php?tab=admins');
