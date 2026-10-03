<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=products');
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['image']) {
        delete_upload($row['image'], 'products');
    }
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = 'Product removed.';
}
redirect('/admin/dashboard.php?tab=products');
