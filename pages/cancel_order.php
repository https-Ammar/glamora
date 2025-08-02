<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);
require('./db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header('Location: orders.php');
    exit();
}

if (!isset($_POST['order_id'])) {
    $_SESSION['error'] = "Order ID not found";
    header('Location: orders.php');
    exit();
}

$userId = $_SESSION['user_id'];
$orderId = (int) $_POST['order_id'];

$stmt = $conn->prepare("SELECT id, orderstate FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = "Order not found or you don't have permission to cancel it";
    header('Location: orders.php');
    exit();
}

if ($order['orderstate'] !== 'inprogress') {
    $_SESSION['error'] = "Cannot cancel order in its current state";
    header('Location: orders.php');
    exit();
}

$stmt = $conn->prepare("UPDATE orders SET orderstate = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $orderId);

if ($stmt->execute()) {
    $_SESSION['success'] = "Order cancelled successfully";
} else {
    $_SESSION['error'] = "An error occurred while trying to cancel the order";
}
$stmt->close();

header('Location: orders.php');
exit();
?>