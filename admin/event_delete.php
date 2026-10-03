<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=events');
}

$id = (int)($_POST['id'] ?? 0);
$pdo = db();
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT poster_image FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['poster_image']) {
        delete_upload($row['poster_image'], 'events');
    }
    $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = 'Event removed.';
}
redirect('/admin/dashboard.php?tab=events');
