<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    redirect('/admin/dashboard.php');
}

$error = '';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $ip = client_ip();
        if (too_many_login_attempts($pdo, $ip)) {
            $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
        } else {
            $login = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? OR email = ?');
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                clear_login_attempts($pdo, $ip);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_email'] = $user['email'];
                $pdo->prepare('UPDATE admin_users SET last_login = ? WHERE id = ?')->execute([time(), $user['id']]);
                redirect('/admin/dashboard.php');
            } else {
                record_login_attempt($pdo, $ip);
                $error = 'Incorrect username/email or password.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(SITE_NAME) ?>, Admin Login</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div class="brand">
      <img src="/images/logo.jpg" alt="<?= h(SITE_NAME) ?> crest" style="width:44px;height:44px;border-radius:50%;">
      <div class="brand-text">
        <strong><?= h(SITE_SHORT_NAME) ?></strong>
        <span>STAFF AREA</span>
      </div>
    </div>
    <h2>Admin login</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/login.php">
      <?= csrf_field() ?>
      <div class="field"><label>Username or email</label><input type="text" name="username" autocomplete="username" required autofocus></div>
      <div class="field"><label>Password</label><input type="password" name="password" autocomplete="current-password" required></div>
      <button type="submit" class="btn btn-dark btn-block">Log in</button>
    </form>
    <p class="form-note" style="margin-top:18px;text-align:center;">
      First time here? Check <code>/data/admin_credentials.txt</code> on the server for your initial login, then change your password immediately.
    </p>
    <p class="form-note" style="text-align:center;"><a href="/index.php">&larr; Back to the site</a></p>
  </div>
</div>
</body>
</html>
