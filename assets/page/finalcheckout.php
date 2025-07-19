<?php
require('./db.php');
session_start();

$finalproducttotal = 0.0;
$couponDiscount = 0.0;
$couponIdUsed = null;
$couponMessage = '';
$getalltage = '';
$i = 0;

$userid = $_SESSION['userId'] ?? ($_COOKIE['userid'] ?? null);

if (!$userid) {
    header('Location: ./index.php');
    exit();
}

// التحقق من عدد المنتجات في السلة
$stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    $i = $row['product_count'];
}
$stmt->close();

if ($i > 0) {
    $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $getallcartproducts = $stmt->get_result();

    while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
        $cartproduct = $getcartproducts['prouductid'];

        $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $productStmt->bind_param("i", $cartproduct);
        $productStmt->execute();
        $selectproduct = $productStmt->get_result();

        if ($fetchproduct = $selectproduct->fetch_assoc()) {
            $getfirstbyfirst = $fetchproduct['total_final_price'] * $getcartproducts['qty'];
            $finalproducttotal += $getfirstbyfirst;

            $tage = '<tr>
                <td scope="row" class="py-4">
                    <div class="cart-info d-flex flex-wrap align-items-center">
                        <img src="../' . htmlspecialchars($fetchproduct['img']) . '" class="col-lg-3 viwe_img">
                        <div class="col-lg-9"></div>
                    </div>
                </td>
                <td class="py-4"><p>' . htmlspecialchars($fetchproduct["name"]) . '</p></td>
                <td class="py-4"><p>count ( ' . htmlspecialchars($getcartproducts["qty"]) . ' )</p></td>
                <td class="py-4"><p>' . htmlspecialchars($getfirstbyfirst) . '</p></td>
                <td class="py-4"></td>
            </tr>';

            $getalltage .= $tage;
        }

        $productStmt->close();
    }
    $stmt->close();

    // التحقق من الكوبون وتطبيق الخصم
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['coupon_code'])) {
        $couponCode = trim($_POST['coupon_code']);

        $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->bind_param("s", $couponCode);
        $stmt->execute();
        $couponResult = $stmt->get_result();

        if ($couponResult && $couponResult->num_rows > 0) {
            $coupon = $couponResult->fetch_assoc();
            $couponId = $coupon['id'];

            $usageStmt = $conn->prepare("SELECT COUNT(*) as usage_count FROM orders WHERE coupon_id = ?");
            $usageStmt->bind_param("i", $couponId);
            $usageStmt->execute();
            $usageCount = $usageStmt->get_result()->fetch_assoc()['usage_count'];
            $usageStmt->close();

            if ($usageCount < $coupon['max_uses']) {
                if ($coupon['discount_type'] === 'percentage') {
                    $couponDiscount = $finalproducttotal * ($coupon['discount_value'] / 100);
                } else {
                    $couponDiscount = $coupon['discount_value'];
                }

                $couponDiscount = min($couponDiscount, $finalproducttotal);
                $finalproducttotal -= $couponDiscount;
                $couponIdUsed = $couponId;

                $couponMessage = "✅ تم تطبيق الكوبون بنجاح. الخصم: " . number_format($couponDiscount, 2);
            } else {
                $couponMessage = "❌ تم تجاوز الحد الأقصى لاستخدام هذا الكوبون.";
            }
        } else {
            $couponMessage = "❌ كود الكوبون غير صالح أو منتهي.";
        }

        $stmt->close();
    }

    // تنفيذ الطلب
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['cleintname'])) {
        $name = mysqli_real_escape_string($conn, $_POST['cleintname'] ?? '');
        $phoneone = mysqli_real_escape_string($conn, $_POST['phoneone'] ?? '');
        $phonetwo = mysqli_real_escape_string($conn, $_POST['phonetwo'] ?? '');
        $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
        $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');

        $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->bind_param("i", $userid);
        $checkUser->execute();
        $checkResult = $checkUser->get_result();

        if ($checkResult->num_rows === 0) {
            $createUser = $conn->prepare("INSERT INTO users(id, name, email, password) VALUES (?, '', '', '')");
            $createUser->bind_param("i", $userid);
            $createUser->execute();
            $createUser->close();
        }
        $checkUser->close();

        // ✅ هنا تم تصحيح عدد أنواع البيانات إلى 11 نوعًا فقط:
        $orderstate = 'inprogress';
        $stmt = $conn->prepare("INSERT INTO orders (user_id, name, phoneone, phonetwo, city, address, htmltage, orderstate, data, numberofproducts, finaltotalprice, coupon_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)");
        $stmt->bind_param("isssssssidi", $userid, $name, $phoneone, $phonetwo, $city, $address, $getalltage, $orderstate, $i, $finalproducttotal, $couponIdUsed);
        $stmt->execute();
        $stmt->close();

        // تفريغ السلة
        $deleteCart = $conn->prepare("DELETE FROM cart WHERE userid = ?");
        $deleteCart->bind_param("s", $userid);
        $deleteCart->execute();
        $deleteCart->close();

        header('Location: ./index.php');
        exit();
    }

} else {
    header('Location: ./index.php');
    exit();
}

// عرض رسالة الكوبون (إن وُجد)
if (!empty($couponMessage)) {
    echo '<div style="margin:10px; padding:10px; background:#f2f2f2; border:1px solid #ccc;">' . $couponMessage . '</div>';
}
?>