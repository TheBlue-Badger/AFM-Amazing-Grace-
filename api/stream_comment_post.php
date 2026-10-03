<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

require_post();
require_csrf();

$pdo = db();
$stream = $pdo->query('SELECT id, is_live FROM livestreams ORDER BY id DESC LIMIT 1')->fetch();
if (!$stream || !$stream['is_live']) {
    json_out(['error' => 'The stream is not live right now.'], 400);
}

// Simple per-session rate limit so one visitor can't flood the wall.
$now = time();
if (!empty($_SESSION['last_stream_comment']) && ($now - $_SESSION['last_stream_comment']) < 2) {
    json_out(['error' => 'You are posting too fast, please slow down.'], 429);
}

$name = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 40));
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 200));

if ($name === '' || $message === '') {
    json_out(['error' => 'Please enter your name and a message.'], 400);
}

// Keep a consistent display name for this visitor for the rest of the stream.
$_SESSION['stream_comment_name'] = $name;
$_SESSION['last_stream_comment'] = $now;

$stmt = $pdo->prepare('INSERT INTO stream_comments (livestream_id, name, message, created_at) VALUES (?, ?, ?, ?)');
$stmt->execute([$stream['id'], $name, $message, $now]);

json_out(['success' => true, 'id' => $pdo->lastInsertId()]);
