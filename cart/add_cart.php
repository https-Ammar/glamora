<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

require('../config/db.php');
header('Content-Type: application/json');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/glamora/";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$size_id = filter_input(INPUT_POST, 'size_id', FILTER_VALIDATE_INT);
$color_id = filter_input(INPUT_POST, 'color_id', FILTER_VALIDATE_INT);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$stmt = $conn->prepare("
    SELECT p.*, 
           ps.id AS size_id, ps.size_value AS size_name,
           pc.id AS color_id, pc.color_name, pc.color_image
    FROM products p
    LEFT JOIN product_sizes ps ON p.id = ps.product_id AND (ps.id = ? OR ? IS NULL)
    LEFT JOIN product_colors pc ON p.id = pc.product_id AND (pc.id = ? OR ? IS NULL)
    WHERE p.id = ?
");
$stmt->bind_param("iiiii", $size_id, $size_id, $color_id, $color_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$image_url = '';
if (!empty($product['color_image'])) {
    $image_url = str_starts_with($product['color_image'], 'http') ? $product['color_image'] : $base_url . 'dashboard/' . ltrim($product['color_image'], '/');
} elseif (!empty($product['image'])) {
    $image_url = str_starts_with($product['image'], 'http') ? $product['image'] : $base_url . 'dashboard/' . ltrim($product['image'], '/');
} else {
    $image_url = $base_url . 'assets/images/default.jpg';
}

$cart_item = [
    'id' => $product_id,
    'name' => $product['name'],
    'price' => ($product['on_sale'] && $product['sale_price']) ? $product['sale_price'] : $product['price'],
    'original_price' => $product['price'],
    'quantity' => $quantity,
    'image' => $image_url,
    'size_id' => $size_id ?: null,
    'size_name' => $product['size_name'] ?? 'Not specified',
    'color_id' => $color_id ?: null,
    'color_name' => $product['color_name'] ?? 'Not specified',
    'color_image' => $image_url
];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$found_index = null;
foreach ($_SESSION['cart'] as $index => $item) {
    if ($item['id'] == $product_id && $item['size_id'] == $size_id && $item['color_id'] == $color_id) {
        $found_index = $index;
        break;
    }
}

if ($found_index !== null) {
    $_SESSION['cart'][$found_index]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][] = $cart_item;
}

$total_count = array_reduce($_SESSION['cart'], function ($carry, $item) {
    return $carry + $item['quantity'];
}, 0);

$subtotal = array_reduce($_SESSION['cart'], function ($carry, $item) {
    return $carry + ($item['price'] * $item['quantity']);
}, 0);

$response = [
    'success' => true,
    'message' => 'Product added to cart successfully',
    'cart_count' => $total_count,
    'subtotal' => number_format($subtotal, 2),
    'cart' => $_SESSION['cart'],
    'is_empty' => $total_count === 0
];

if ($total_count === 0) {
    $response['empty_message'] = 'Your shopping cart is currently empty. Start shopping now!';
}

echo json_encode($response);
?>