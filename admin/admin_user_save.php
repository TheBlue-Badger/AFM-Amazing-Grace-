<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = 'Your session expired. Please try again.';
    redirect('/admin/dashboard.php?tab=admins');
}

$username = trim(mb_substr((string)($_POST['username'] ?? ''), 0, 60));
$email = trim(mb_substr((string)($_POST['email'] ?? ''), 0, 160));
$password = (string)($_POST['password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

$errors = [];
if ($username === '') $errors[] = 'Please enter a username.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (strlen($password) < 12) $errors[] = 'Password must be at least 12 characters.';
if ($password !== $confirm) $errors[] = 'Password and confirmation do not match.';

if ($errors) {
    $_SESSION['flash'] = implode(' ', $errors);
    redirect('/admin/dashboard.php?tab=admins');
}

$pdo = db();
try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash, is_default_password, created_at) VALUES (?, ?, ?, 0, ?)');
    $stmt->execute([$username, $email, $hash, time()]);
    $isDev = strcasecmp($email, DEVELOPER_EMAIL) === 0;
    $_SESSION['flash'] = 'Admin account created.' . ($isDev ? ' This email matches DEVELOPER_EMAIL, so it will see the Developer tab.' : '');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['flash'] = 'That username or email is already in use.';
    } else {
        $_SESSION['flash'] = 'Could not create that admin account.';
    }
}

redirect('/admin/dashboard.php?tab=admins');
