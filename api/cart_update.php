<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

require_post();
require_csrf();

$lineId = (string)($_POST['line_id'] ?? '');
$delta = filter_input(INPUT_POST, 'delta', FILTER_VALIDATE_INT);

if (!isset($_SESSION['cart'][$lineId]) || $delta === null) {
    json_out(['error' => 'That item is not in your cart.'], 404);
}

$_SESSION['cart'][$lineId]['qty'] = (int)$_SESSION['cart'][$lineId]['qty'] + $delta;
if ($_SESSION['cart'][$lineId]['qty'] <= 0) {
    unset($_SESSION['cart'][$lineId]);
} else {
    $_SESSION['cart'][$lineId]['qty'] = min($_SESSION['cart'][$lineId]['qty'], 99);
}

json_out(['success' => true, 'cart' => cart_summary()]);
