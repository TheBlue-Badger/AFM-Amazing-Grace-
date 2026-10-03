<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['chat_thread_id'])) {
    json_out(['messages' => []]);
}
$pdo = db();
$threadId = (int)$_SESSION['chat_thread_id'];
$stmt = $pdo->prepare('SELECT sender, message, created_at FROM chat_messages WHERE thread_id = ? ORDER BY id ASC');
$stmt->execute([$threadId]);
json_out(['messages' => $stmt->fetchAll()]);
