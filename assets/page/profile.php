<?php
session_start();
require('./db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['userId'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// جلب الطلبات
$stmtOrders = $conn->prepare("
    SELECT 
        orders.*, 
        coupons.code AS coupon_code, 
        orders.discount_type, 
        orders.discount_value 
    FROM 
        orders 
    LEFT JOIN 
        coupons ON orders.coupon_id = coupons.id 
    WHERE 
        orders.user_id = ? 
    ORDER BY 
        orders.created_at DESC
");
$stmtOrders->bind_param("i", $userId);
$stmtOrders->execute();
$orders = $stmtOrders->get_result();
$stmtOrders->close();
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>صفحة البروفايل</title>
    <style>
        body {
            font-family: Arial;
            direction: rtl;
            text-align: right;
            padding: 20px;
        }

        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .order-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }

        button.cancel {
            background: red;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
        }

        .order-html {
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
            margin-top: 10px;
        }

        form.logout-form {
            display: inline;
        }

        button.logout-btn {
            background: #555;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <h2>مرحبًا <?= htmlspecialchars($user['name'] ?? 'زائر') ?></h2>

    <?php if (!empty($user['profile_image'])): ?>
        <img src="<?= htmlspecialchars($user['profile_image']) ?>" class="profile-img">
    <?php else: ?>
        <p>لا توجد صورة</p>
    <?php endif; ?>

    <p><strong>البريد:</strong> <?= htmlspecialchars($user['email'] ?? 'غير متوفر') ?></p>
    <p><strong>الهاتف:</strong> <?= htmlspecialchars($user['phone'] ?? 'غير متوفر') ?></p>
    <p><strong>العنوان:</strong> <?= htmlspecialchars($user['address'] ?? 'غير متوفر') ?></p>
    <p><strong>المدينة:</strong> <?= htmlspecialchars($user['city'] ?? 'غير متوفر') ?></p>
    <p><strong>الدولة:</strong> <?= htmlspecialchars($user['country'] ?? 'غير متوفر') ?></p>

    <p>
        <a href="upload_image.php">تغيير الصورة</a> |
    <form method="POST" class="logout-form">
        <input type="hidden" name="logout" value="1">
        <button type="submit" class="logout-btn">تسجيل الخروج</button>
    </form>
    </p>

    <hr>
    <h3>سجل طلباتك</h3>

    <?php if ($orders->num_rows > 0): ?>
        <?php while ($order = $orders->fetch_assoc()): ?>
            <div class="order-box">
                <strong>رقم الطلب:</strong> <?= $order['id'] ?><br>
                <strong>الحالة:</strong>
                <?php
                switch ($order['orderstate']) {
                    case 'inprogress':
                        echo 'قيد المعالجة';
                        break;
                    case 'accepted':
                        echo 'تم القبول';
                        break;
                    case 'rejected':
                        echo 'تم الرفض';
                        break;
                    default:
                        echo htmlspecialchars($order['orderstate']);
                        break;
                }
                ?><br>

                <?php
                $finalPrice = (float) $order['finaltotalprice'];
                $discountValue = (float) $order['discount_value'];
                $discountType = $order['discount_type'];
                $priceBeforeDiscount = $finalPrice;

                if (!empty($order['coupon_code']) && $discountValue > 0) {
                    if ($discountType === 'percentage') {
                        $priceBeforeDiscount = $finalPrice / (1 - ($discountValue / 100));
                    } else {
                        $priceBeforeDiscount = $finalPrice + $discountValue;
                    }
                }
                ?>

                <?php if (!empty($order['coupon_code'])): ?>
                    <strong>السعر قبل الخصم:</strong> <?= number_format($priceBeforeDiscount, 2) ?> جنيه<br>
                    <strong>كود الخصم:</strong> <?= htmlspecialchars($order['coupon_code']) ?><br>
                    <strong>قيمة الخصم:</strong>
                    <?= ($discountType === 'percentage')
                        ? htmlspecialchars($discountValue) . '%'
                        : number_format($discountValue, 2) . ' جنيه'; ?><br>
                <?php endif; ?>

                <strong>السعر النهائي بعد الخصم:</strong> <?= number_format($finalPrice, 2) ?> جنيه<br>
                <strong>عدد المنتجات:</strong> <?= htmlspecialchars($order['numberofproducts']) ?><br>
                <strong>تاريخ الطلب:</strong> <?= htmlspecialchars($order['created_at']) ?><br>

                <div class="order-html">
                    <?= $order['html_tag'] ?? 'لا يوجد تفاصيل للطلب' ?>
                </div>

                <?php if ($order['orderstate'] === 'inprogress'): ?>
                    <form method="POST" action="cancel_order.php" onsubmit="return confirm('هل أنت متأكد من إلغاء الطلب؟');">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" class="cancel">إلغاء الطلب</button>
                    </form>
                <?php else: ?>
                    <p style="color:gray;">لا يمكن إلغاء هذا الطلب</p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>لا توجد طلبات بعد.</p>
    <?php endif; ?>

</body>

</html>