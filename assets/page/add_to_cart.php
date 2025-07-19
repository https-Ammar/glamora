<?php
require('db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productid'], $_POST['qty'])) {
    try {
        $productid = intval($_POST['productid']);
        $qty = intval($_POST['qty']);

        if ($productid <= 0 || $qty <= 0) {
            throw new Exception("بيانات المنتج غير صالحة.");
        }

        // إنشاء معرف المستخدم إذا لم يكن موجودًا
        if (!isset($_COOKIE['userid']) || !is_numeric($_COOKIE['userid'])) {
            $userid = time() + rand(1000, 9999);
            setcookie('userid', $userid, time() + (86400 * 30), "/");
        } else {
            $userid = intval($_COOKIE['userid']);
        }

        // بدء المعاملة
        $conn->begin_transaction();

        // التحقق من وجود المنتج والمخزون
        $checkStock = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $checkStock->bind_param("i", $productid);
        $checkStock->execute();
        $stockResult = $checkStock->get_result();

        if ($stockResult->num_rows === 0) {
            throw new Exception("المنتج غير موجود.");
        }

        $product = $stockResult->fetch_assoc();
        if ($product['quantity'] < $qty) {
            throw new Exception("الكمية المطلوبة غير متوفرة في المخزون.");
        }

        // التحقق مما إذا كان المنتج موجود بالفعل في السلة
        $checkCart = $conn->prepare("SELECT id, qty FROM cart WHERE userid = ? AND productid = ?");
        $checkCart->bind_param("ii", $userid, $productid);
        $checkCart->execute();
        $cartResult = $checkCart->get_result();

        if ($cartResult->num_rows > 0) {
            $cart = $cartResult->fetch_assoc();
            $newQty = $cart['qty'] + $qty;
            $updateCart = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ?");
            $updateCart->bind_param("ii", $newQty, $cart['id']);
            $updateCart->execute();
            $updateCart->close();
            $response = "تم تحديث الكمية في السلة.";
        } else {
            $insertCart = $conn->prepare("INSERT INTO cart(userid, productid, qty) VALUES (?, ?, ?)");
            $insertCart->bind_param("iii", $userid, $productid, $qty);
            $insertCart->execute();
            $insertCart->close();
            $response = "تمت إضافة المنتج إلى السلة.";
        }

        // تنفيذ المعاملة
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $response]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        if (isset($checkStock))
            $checkStock->close();
        if (isset($checkCart))
            $checkCart->close();
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
}
?>