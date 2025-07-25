<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

require('./db.php');

$key = $_POST['key'] ?? null;
$quantity = (int) ($_POST['quantity'] ?? 0);

if (!isset($_SESSION['cart'][$key])) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit;
}

if ($quantity <= 0) {
    unset($_SESSION['cart'][$key]);
} else {
    $_SESSION['cart'][$key]['quantity'] = $quantity;
}

$cart_count = count($_SESSION['cart'] ?? []);

echo json_encode([
    'success' => true,
    'cart_count' => $cart_count
]);
?>