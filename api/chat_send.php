<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

require_post();
require_csrf();

if (empty($_SESSION['chat_thread_id'])) {
    json_out(['error' => 'Please start a conversation first.'], 400);
}
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 1000));
if ($message === '') {
    json_out(['error' => 'Message cannot be empty.'], 400);
}

$pdo = db();
$threadId = (int)$_SESSION['chat_thread_id'];
$check = $pdo->prepare('SELECT id FROM chat_threads WHERE id = ?');
$check->execute([$threadId]);
if (!$check->fetch()) {
    json_out(['error' => 'This conversation no longer exists.'], 404);
}

$now = time();
$pdo->prepare('INSERT INTO chat_messages (thread_id, sender, message, created_at) VALUES (?, ?, ?, ?)')
    ->execute([$threadId, 'visitor', $message, $now]);
$pdo->prepare('UPDATE chat_threads SET updated_at = ? WHERE id = ?')->execute([$now, $threadId]);

$stmt = $pdo->prepare('SELECT sender, message, created_at FROM chat_messages WHERE thread_id = ? ORDER BY id ASC');
$stmt->execute([$threadId]);
json_out(['messages' => $stmt->fetchAll()]);
