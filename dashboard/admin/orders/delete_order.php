<?php
header('Content-Type: application/json');

// الاتصال بقاعدة البيانات
require('./config/db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$orderId = intval($_GET['id']);

// تنفيذ الحذف
$stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting order']);
}

$stmt->close();
$conn->close();
?>