<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

require_post();
require_csrf();

$name = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 80));
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 1000));

if ($name === '' || $message === '') {
    json_out(['error' => 'Please enter your name and a message.'], 400);
}

$pdo = db();
$now = time();

// Reuse an existing thread for this session if one is already open, so
// refreshing the page doesn't fragment the conversation.
if (!empty($_SESSION['chat_thread_id'])) {
    $threadId = (int)$_SESSION['chat_thread_id'];
    $check = $pdo->prepare('SELECT id FROM chat_threads WHERE id = ?');
    $check->execute([$threadId]);
    if (!$check->fetch()) {
        $threadId = null;
    }
} else {
    $threadId = null;
}

if (!$threadId) {
    $stmt = $pdo->prepare('INSERT INTO chat_threads (visitor_name, created_at, updated_at) VALUES (?, ?, ?)');
    $stmt->execute([$name, $now, $now]);
    $threadId = (int)$pdo->lastInsertId();
    $_SESSION['chat_thread_id'] = $threadId;
} else {
    $pdo->prepare('UPDATE chat_threads SET visitor_name = ?, updated_at = ? WHERE id = ?')->execute([$name, $now, $threadId]);
}

$pdo->prepare('INSERT INTO chat_messages (thread_id, sender, message, created_at) VALUES (?, ?, ?, ?)')
    ->execute([$threadId, 'visitor', $message, $now]);

$stmt = $pdo->prepare('SELECT sender, message, created_at FROM chat_messages WHERE thread_id = ? ORDER BY id ASC');
$stmt->execute([$threadId]);
json_out(['messages' => $stmt->fetchAll()]);
