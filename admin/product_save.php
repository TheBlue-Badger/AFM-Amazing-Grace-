<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=products');
}

$id = (int)($_POST['id'] ?? 0);
$name = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 160));
$description = trim(mb_substr((string)($_POST['description'] ?? ''), 0, 400));
$tag = trim(mb_substr((string)($_POST['tag'] ?? ''), 0, 40));
$type = ($_POST['type'] ?? 'fixed') === 'amount' ? 'amount' : 'fixed';
$price = max(0, (float)($_POST['price'] ?? 0));
$isActive = !empty($_POST['is_active']) ? 1 : 0;

if ($name === '') {
    $_SESSION['flash'] = 'Product name is required.';
    redirect('/admin/dashboard.php?tab=products');
}

$pdo = db();
$image = '';
if ($id > 0) {
    $existing = $pdo->prepare('SELECT image FROM products WHERE id = ?');
    $existing->execute([$id]);
    $row = $existing->fetch();
    $image = $row['image'] ?? '';
}

if (!empty($_FILES['image']['name'])) {
    $result = handle_upload($_FILES['image'], 'products');
    if (!$result['ok'] && $result['error']) {
        $_SESSION['flash'] = $result['error'];
        redirect('/admin/dashboard.php?tab=products');
    }
    if ($result['ok']) {
        if ($image) delete_upload($image, 'products');
        $image = $result['filename'];
    }
}
if (!empty($_POST['remove_image'])) {
    if ($image) delete_upload($image, 'products');
    $image = '';
}

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE products SET name=?, description=?, tag=?, type=?, price=?, image=?, is_active=? WHERE id=?');
    $stmt->execute([$name, $description, $tag, $type, $price, $image, $isActive, $id]);
    $_SESSION['flash'] = 'Product updated.';
} else {
    $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM products')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO products (name, description, tag, type, price, image, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $description, $tag, $type, $price, $image, $isActive, $maxOrder + 1]);
    $_SESSION['flash'] = 'Product added.';
}

redirect('/admin/dashboard.php?tab=products');
