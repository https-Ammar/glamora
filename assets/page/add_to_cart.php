<?php
require('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productid'], $_POST['qty'])) {
    $productid = intval($_POST['productid']);
    $qty = intval($_POST['qty']);
    $userid = isset($_COOKIE['userid']) ? intval($_COOKIE['userid']) : 0;

    if ($userid > 0 && $productid > 0 && $qty > 0) {
        $stmt = $conn->prepare("INSERT INTO cart (userid, prouductid, qty) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $userid, $productid, $qty);

        if ($stmt->execute()) {
            echo "تمت إضافة المنتج للسلة بنجاح.";
        } else {
            echo "حدث خطأ أثناء الإضافة: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "بيانات غير صالحة.";
    }
}
?>