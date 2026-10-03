<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

$pdo = db();
$stream = $pdo->query('SELECT id, is_live FROM livestreams ORDER BY id DESC LIMIT 1')->fetch();
if (!$stream) {
    json_out(['is_live' => false, 'comments' => [], 'last_id' => 0]);
}

$since = (int)($_GET['since'] ?? 0);
$stmt = $pdo->prepare('SELECT id, name, message, created_at FROM stream_comments WHERE livestream_id = ? AND id > ? ORDER BY id ASC LIMIT 200');
$stmt->execute([$stream['id'], $since]);
$comments = $stmt->fetchAll();

$lastId = $comments ? (int)end($comments)['id'] : $since;

json_out([
    'is_live' => (bool)$stream['is_live'],
    'comments' => $comments,
    'last_id' => $lastId,
]);
