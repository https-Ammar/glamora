<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

require('./db.php');
header('Content-Type: application/json');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
    . "://" . $_SERVER['HTTP_HOST'] . "/glamora/";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
$size_id = isset($_POST['size_id']) ? $_POST['size_id'] : null;
$color_id = isset($_POST['color_id']) ? $_POST['color_id'] : null;
$size_name = isset($_POST['size_name']) ? $_POST['size_name'] : 'Not specified';
$color_name = isset($_POST['color_name']) ? $_POST['color_name'] : 'Not specified';
$color_hex = isset($_POST['color_hex']) ? $_POST['color_hex'] : '';
$color_image = isset($_POST['color_image']) ? $_POST['color_image'] : '';

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$product_stmt = $conn->prepare("
    SELECT p.*, 
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if (!empty($color_image)) {
    $image_url = str_starts_with($color_image, 'http')
        ? $color_image
        : $base_url . 'dashboard/' . ltrim($color_image, './');
} elseif (!empty($product['image'])) {
    $image_url = str_starts_with($product['image'], 'http')
        ? $product['image']
        : $base_url . 'dashboard/' . ltrim($product['image'], './');
} else {
    $image_url = $base_url . 'assets/images/default.jpg';
}

$on_sale = $product['on_sale'] && $product['sale_price'] > 0;
$price = $on_sale ? $product['sale_price'] : $product['price'];

$cart_item = [
    'id' => $product_id,
    'name' => $product['name'],
    'price' => $price,
    'original_price' => $product['price'],
    'sale_price' => $on_sale ? $product['sale_price'] : null,
    'quantity' => $quantity,
    'image' => $image_url,
    'size_id' => $size_id,
    'size_name' => $size_name,
    'size_code' => $size_id,
    'color_id' => $color_id,
    'color_name' => $color_name,
    'color_hex' => $color_hex,
    'color_image' => $image_url,
    'product_url' => $base_url . 'product.php?id=' . $product_id,
    'category_name' => $product['category_name'] ?? '',
    'unique_id' => uniqid() // Add a unique identifier for each cart item
];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Always add as new item (don't check for existing combinations)
$_SESSION['cart'][] = $cart_item;

$total_count = count($_SESSION['cart']); // Count of distinct items
$total_quantity = array_reduce($_SESSION['cart'], function ($carry, $item) {
    return $carry + $item['quantity'];
}, 0);

$subtotal = array_reduce($_SESSION['cart'], function ($carry, $item) {
    return $carry + ($item['price'] * $item['quantity']);
}, 0);

$response = [
    'success' => true,
    'message' => 'Product added to cart successfully',
    'cart_count' => $total_quantity, // Or use $total_count if you want to count distinct items
    'subtotal' => number_format($subtotal, 2),
    'cart_items' => $_SESSION['cart'],
    'is_empty' => $total_count === 0
];

if ($total_count === 0) {
    $response['empty_message'] = 'Your shopping cart is currently empty. Start shopping now!';
}

echo json_encode($response);
?>