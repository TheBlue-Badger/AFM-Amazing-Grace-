<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=gallery');
}

$caption = trim(mb_substr((string)($_POST['caption'] ?? ''), 0, 160));
$serviceDate = trim((string)($_POST['service_date'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
    $serviceDate = date('Y-m-d');
}

if (empty($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
    $_SESSION['flash'] = 'Please choose at least one photo to upload.';
    redirect('/admin/dashboard.php?tab=gallery');
}

$pdo = db();
$count = 0;
$errors = [];
$fileCount = count($_FILES['photos']['name']);

for ($i = 0; $i < $fileCount; $i++) {
    if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    $single = [
        'name' => $_FILES['photos']['name'][$i],
        'type' => $_FILES['photos']['type'][$i],
        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
        'error' => $_FILES['photos']['error'][$i],
        'size' => $_FILES['photos']['size'][$i],
    ];
    $result = handle_upload($single, 'gallery');
    if ($result['ok']) {
        $pdo->prepare('INSERT INTO gallery_photos (filename, caption, service_date, uploaded_at) VALUES (?, ?, ?, ?)')
            ->execute([$result['filename'], $caption, $serviceDate, time()]);
        $count++;
    } elseif ($result['error']) {
        $errors[] = $result['error'];
    }
}

if ($count > 0) {
    $_SESSION['flash'] = $count . ' photo' . ($count === 1 ? '' : 's') . ' uploaded to the gallery' . ($errors ? ', but some files were skipped (' . implode('; ', array_unique($errors)) . ').' : '.');
} else {
    $_SESSION['flash'] = 'No photos were uploaded. ' . implode('; ', array_unique($errors));
}

redirect('/admin/dashboard.php?tab=gallery');
