<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$threadId = (int)($_GET['thread'] ?? 0);
if ($threadId <= 0) {
    json_out(['messages' => []]);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT sender, message, created_at FROM chat_messages WHERE thread_id = ? ORDER BY id ASC');
$stmt->execute([$threadId]);
json_out(['messages' => $stmt->fetchAll()]);
