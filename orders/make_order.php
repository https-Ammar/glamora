<?php
session_start();
require('./db.php');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['userId'];
$today = date('Y-m-d');

// احسب مجموع السعر وعدد المنتجات
$totalPrice = 0;
$numberOfProducts = 0;
$cartItems = [];

$stmt = $conn->prepare("SELECT c.*, p.total_final_price FROM cart c JOIN products p ON c.prouductid = p.id WHERE c.userid = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $qty = $row['qty'];
    $price = $row['total_final_price'];
    $totalPrice += $qty * $price;
    $numberOfProducts += $qty;
    $cartItems[] = $row;
}
$stmt->close();

// لو مفيش منتجات
if (count($cartItems) === 0) {
    echo "السلة فارغة";
    exit();
}

// إدخال الطلب في orders
$stmtOrder = $conn->prepare("INSERT INTO orders (user_id, name, phoneone, address, city, orderstate, data, numberofproducts, finaltotalprice) VALUES (?, '', '', '', '', 'inprogress', ?, ?, ?)");
$stmtOrder->bind_param("isid", $userId, $today, $numberOfProducts, $totalPrice);
$stmtOrder->execute();
$orderId = $stmtOrder->insert_id;
$stmtOrder->close();

// إدخال تفاصيل المنتجات
foreach ($cartItems as $item) {
    $productId = $item['prouductid'];
    $qty = $item['qty'];
    $price = $item['total_final_price'];
    $total = $qty * $price;

    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmtItem->bind_param("iiidd", $orderId, $productId, $qty, $price, $total);
    $stmtItem->execute();
    $stmtItem->close();
}

// حذف المنتجات من السلة
$conn->query("DELETE FROM cart WHERE userid = $userId");

// التوجيه للبروفايل
header("Location: profile.php");
exit();
?>