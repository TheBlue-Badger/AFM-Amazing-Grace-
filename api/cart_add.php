<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

require_post();
require_csrf();

$pdo = db();
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

if (!$productId) {
    json_out(['error' => 'Invalid product.'], 400);
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    json_out(['error' => 'That item is no longer available.'], 404);
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($product['type'] === 'amount') {
    if ($amount === false || $amount === null || $amount <= 0) {
        json_out(['error' => 'Please enter a valid amount.'], 400);
    }
    $amount = round(min($amount, 1000000), 2); // sanity ceiling
    $lineId = 'l' . bin2hex(random_bytes(4));
    $_SESSION['cart'][$lineId] = [
        'product_id' => (int)$product['id'],
        'name' => $product['name'],
        'price' => $amount,
        'qty' => 1,
        'type' => 'amount',
    ];
} else {
    $found = false;
    foreach ($_SESSION['cart'] as $lineId => &$line) {
        if ((int)$line['product_id'] === (int)$product['id'] && $line['type'] === 'fixed') {
            $line['qty'] = min((int)$line['qty'] + 1, 99);
            $found = true;
            break;
        }
    }
    unset($line);
    if (!$found) {
        $newLineId = 'l' . bin2hex(random_bytes(4));
        $_SESSION['cart'][$newLineId] = [
            'product_id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'qty' => 1,
            'type' => 'fixed',
        ];
    }
}

json_out(['success' => true, 'cart' => cart_summary()]);
