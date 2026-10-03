<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    if ($isAjax) json_out(['error' => 'Session expired.'], 403);
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=messages');
}

$threadId = (int)($_POST['thread_id'] ?? 0);
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 1000));

$pdo = db();
$check = $pdo->prepare('SELECT id FROM chat_threads WHERE id = ?');
$check->execute([$threadId]);
if (!$check->fetch() || $message === '') {
    if ($isAjax) json_out(['error' => 'Could not send that message.'], 400);
    redirect('/admin/dashboard.php?tab=messages');
}

$now = time();
$pdo->prepare('INSERT INTO chat_messages (thread_id, sender, message, created_at) VALUES (?, ?, ?, ?)')
    ->execute([$threadId, 'admin', $message, $now]);
$pdo->prepare('UPDATE chat_threads SET updated_at = ? WHERE id = ?')->execute([$now, $threadId]);

if ($isAjax) {
    $stmt = $pdo->prepare('SELECT sender, message, created_at FROM chat_messages WHERE thread_id = ? ORDER BY id ASC');
    $stmt->execute([$threadId]);
    json_out(['messages' => $stmt->fetchAll()]);
}

redirect('/admin/dashboard.php?tab=messages&thread=' . $threadId);
