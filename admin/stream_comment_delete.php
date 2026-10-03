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

$pdo = db();
if (!empty($_POST['clear_all'])) {
    $stream = $pdo->query('SELECT id FROM livestreams ORDER BY id DESC LIMIT 1')->fetch();
    if ($stream) {
        $pdo->prepare('DELETE FROM stream_comments WHERE livestream_id = ?')->execute([$stream['id']]);
    }
    $_SESSION['flash'] = 'Live comments cleared.';
} else {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM stream_comments WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = 'Comment removed.';
    }
}
redirect('/admin/dashboard.php?tab=livestream');
