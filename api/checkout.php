<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

require_post();
require_csrf();

$summary = cart_summary();
if (empty($summary['items'])) {
    json_out(['error' => 'Your cart is empty.'], 400);
}

$name = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 120));
$email = trim(mb_substr((string)($_POST['email'] ?? ''), 0, 160));
$paymentMethod = ($_POST['payment_method'] ?? '') === 'online' ? 'online' : 'in_person';

if ($name === '') {
    json_out(['error' => 'Please enter your name.'], 400);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['error' => 'Please enter a valid email address.'], 400);
}

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO orders (name, email, items_json, total, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$name, $email, json_encode($summary['items']), $summary['total'], $paymentMethod, time()]);
$orderId = $pdo->lastInsertId();

// This starter template does not process live payments. We clear the cart
// and hand back a reference plus payment instructions matching what the
// visitor chose (pay online via EFT, or pay in person after a service).
unset($_SESSION['cart']);

json_out([
    'success' => true,
    'order_id' => $orderId,
    'reference' => 'AGE-' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT),
    'total' => $summary['total'],
    'payment_method' => $paymentMethod,
    'bank' => [
        'bank' => BANK_NAME,
        'holder' => BANK_ACCOUNT_HOLDER,
        'account' => BANK_ACCOUNT_NUMBER,
        'branch' => BANK_BRANCH_CODE,
        'type' => BANK_ACCOUNT_TYPE,
    ],
]);
