<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

require_post();
require_csrf();

$lineId = (string)($_POST['line_id'] ?? '');
if (isset($_SESSION['cart'][$lineId])) {
    unset($_SESSION['cart'][$lineId]);
}
json_out(['success' => true, 'cart' => cart_summary()]);
