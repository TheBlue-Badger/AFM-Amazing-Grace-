<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=gallery');
}

$id = (int)($_POST['id'] ?? 0);
$pdo = db();
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT filename FROM gallery_photos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        delete_upload($row['filename'], 'gallery');
        $pdo->prepare('DELETE FROM gallery_photos WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = 'Photo deleted.';
    }
}
redirect('/admin/dashboard.php?tab=gallery');
