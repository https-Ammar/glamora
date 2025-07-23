<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false,
    'use_strict_mode' => true
]);

require('db.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$size_id = $_POST['size_id'] ?? null;
$size_name = $_POST['size_name'] ?? null;
$color_id = $_POST['color_id'] ?? null;
$color_name = $_POST['color_name'] ?? null;
$color_image = $_POST['color_image'] ?? null;

if (!$product_id || !$quantity) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

if (!isset($_COOKIE['userid'])) {
    echo json_encode(['success' => false, 'message' => 'User not identified']);
    exit;
}

$userid = $_COOKIE['userid'];

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Product not found");
    }

    $product = $result->fetch_assoc();
    $stmt->close();

    if ($product['quantity'] < $quantity) {
        throw new Exception("Requested quantity not available in stock");
    }

    $check = $conn->prepare("SELECT id, qty FROM cart WHERE userid = ? AND productid = ? AND size = ? AND color = ?");
    $check->bind_param("siss", $userid, $product_id, $size_name, $color_name);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        $new_qty = $existing['qty'] + $quantity;
        $update = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ?");
        $update->bind_param("ii", $new_qty, $existing['id']);
        $update->execute();
        $update->close();
        $message = "Product quantity updated in cart";
    } else {
        $insert = $conn->prepare("INSERT INTO cart (userid, productid, qty, size, color) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("siiss", $userid, $product_id, $quantity, $size_name, $color_name);
        $insert->execute();
        $insert->close();
        $message = "Product added to cart";
    }

    $countStmt = $conn->prepare("SELECT SUM(qty) as total_qty FROM cart WHERE userid = ?");
    $countStmt->bind_param("s", $userid);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $cart_count = $countResult['total_qty'] ?? 0;
    $countStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cart_count
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>