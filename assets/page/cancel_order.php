<?php
session_start();
require('./db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_SESSION['userId'])) {
    $orderId = intval($_POST['order_id']);
    $userId = $_SESSION['userId'];

    // نتحقق أن الطلب يخص نفس المستخدم ومعلق
    $check = $conn->query("SELECT * FROM orders WHERE id = $orderId AND user_id = $userId AND orderstate = 'inprogress'");

    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE orders SET orderstate = 'cancelled' WHERE id = $orderId");
        header("Location: profile.php");
        exit();
    } else {
        echo "لا يمكن إلغاء الطلب أو الطلب غير موجود.";
    }
}
?>