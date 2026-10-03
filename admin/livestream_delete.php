<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=livestream');
}

$id = (int)($_POST['id'] ?? 0);
$pdo = db();
if ($id > 0) {
    $pdo->prepare('DELETE FROM livestreams WHERE id = ?')->execute([$id]);
    // Always leave at least one row so the admin form has something to edit.
    $remaining = (int)$pdo->query('SELECT COUNT(*) FROM livestreams')->fetchColumn();
    if ($remaining === 0) {
        $pdo->prepare('INSERT INTO livestreams (title, stream_url, is_live, created_at) VALUES (?, ?, 0, ?)')
            ->execute(['Sunday Service Livestream', '', time()]);
    }
    $_SESSION['flash'] = 'Livestream entry removed.';
}
redirect('/admin/dashboard.php?tab=livestream');
