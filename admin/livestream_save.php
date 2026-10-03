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
$title = trim(mb_substr((string)($_POST['title'] ?? ''), 0, 160)) ?: 'Sunday Service Livestream';
$streamUrl = trim(mb_substr((string)($_POST['stream_url'] ?? ''), 0, 400));
$isLive = !empty($_POST['is_live']) ? 1 : 0;
$scheduledAtRaw = trim((string)($_POST['scheduled_at'] ?? ''));
$scheduledAt = $scheduledAtRaw !== '' ? strtotime($scheduledAtRaw) : null;

$pdo = db();
if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE livestreams SET title=?, stream_url=?, is_live=?, scheduled_at=? WHERE id=?');
    $stmt->execute([$title, $streamUrl, $isLive, $scheduledAt ?: null, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO livestreams (title, stream_url, is_live, scheduled_at, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$title, $streamUrl, $isLive, $scheduledAt ?: null, time()]);
}
$_SESSION['flash'] = $isLive ? 'You are now live on the website.' : 'Livestream settings saved.';
redirect('/admin/dashboard.php?tab=livestream');
