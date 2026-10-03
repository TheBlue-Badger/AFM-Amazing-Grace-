<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=events');
}

$id = (int)($_POST['id'] ?? 0);
$title = trim(mb_substr((string)($_POST['title'] ?? ''), 0, 160));
$description = trim(mb_substr((string)($_POST['description'] ?? ''), 0, 1000));
$eventDate = trim((string)($_POST['event_date'] ?? ''));
$eventTime = trim(mb_substr((string)($_POST['event_time'] ?? ''), 0, 40));
$location = trim(mb_substr((string)($_POST['location'] ?? ''), 0, 160));
$isActive = !empty($_POST['is_active']) ? 1 : 0;

if ($title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
    $_SESSION['flash'] = 'Please provide at least a title and a valid date.';
    redirect('/admin/dashboard.php?tab=events');
}

$pdo = db();
$posterImage = '';

if ($id > 0) {
    $existing = $pdo->prepare('SELECT poster_image FROM events WHERE id = ?');
    $existing->execute([$id]);
    $row = $existing->fetch();
    $posterImage = $row['poster_image'] ?? '';
}

if (!empty($_FILES['poster_image']['name'])) {
    $result = handle_upload($_FILES['poster_image'], 'events');
    if (!$result['ok'] && $result['error']) {
        $_SESSION['flash'] = $result['error'];
        redirect('/admin/dashboard.php?tab=events');
    }
    if ($result['ok']) {
        if ($posterImage) delete_upload($posterImage, 'events');
        $posterImage = $result['filename'];
    }
}

if (!empty($_POST['remove_poster'])) {
    if ($posterImage) delete_upload($posterImage, 'events');
    $posterImage = '';
}

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE events SET title=?, description=?, poster_image=?, event_date=?, event_time=?, location=?, is_active=? WHERE id=?');
    $stmt->execute([$title, $description, $posterImage, $eventDate, $eventTime, $location, $isActive, $id]);
    $_SESSION['flash'] = 'Event updated.';
} else {
    $stmt = $pdo->prepare('INSERT INTO events (title, description, poster_image, event_date, event_time, location, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $description, $posterImage, $eventDate, $eventTime, $location, $isActive, time()]);
    $_SESSION['flash'] = 'Event added.';
}

redirect('/admin/dashboard.php?tab=events');
