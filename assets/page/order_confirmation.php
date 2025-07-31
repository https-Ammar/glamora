<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

require('./db.php');

// التحقق من وجود معرف الطلب
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ./profile.php');
    exit();
}

$order_id = (int) $_GET['id'];

// جلب بيانات الطلب من قاعدة البيانات
$stmt = $conn->prepare("
  SELECT o.*, u.email, u.name as user_name 
  FROM orders o
  LEFT JOIN users u ON o.user_id = u.id
  WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ./profile.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// جلب عناصر الطلب
$items_stmt = $conn->prepare("
  SELECT oi.*, p.name as product_name, p.image as product_image 
  FROM order_items oi
  JOIN products p ON oi.product_id = p.id
  WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// جلب بيانات القسيمة إذا وجدت
$coupon_data = null;
if (!empty($order['coupon_code'])) {
    $coupon_stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ?");
    $coupon_stmt->bind_param("s", $order['coupon_code']);
    $coupon_stmt->execute();
    $coupon_result = $coupon_stmt->get_result();
    $coupon_data = $coupon_result->fetch_assoc();
    $coupon_stmt->close();
}

// حساب الإجمالي الفرعي
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['total_price'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الطلب - <?php echo $order_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .header h1 {
            color: #4CAF50;
            margin-bottom: 10px;
        }

        .order-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .info-box {
            flex: 1;
            min-width: 250px;
            margin: 10px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .info-box h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .order-items th,
        .order-items td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        .order-items th {
            background-color: #f2f2f2;
            font-weight: 500;
        }

        .order-items img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .order-summary {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
        }

        .success-message {
            text-align: center;
            padding: 20px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #388E3C;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                background-color: white;
            }

            .container {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>شكراً لطلبك!</h1>
            <p>تم استلام طلبك بنجاح وسيتم تجهيزه في أقرب وقت ممكن</p>
            <p>رقم الطلب: <strong>#<?php echo $order_id; ?></strong></p>
        </div>

        <div class="success-message">
            <h2>تم تأكيد الطلب بنجاح</h2>
            <p>سيتم إرسال تفاصيل الشحن والتتبع إلى بريدك الإلكتروني</p>
        </div>

        <div class="order-info">
            <div class="info-box">
                <h3>معلومات العميل</h3>
                <p><strong>الاسم:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                <p><strong>البريد الإلكتروني:</strong>
                    <?php echo htmlspecialchars($order['email'] ?? $order['customer_email'] ?? 'غير متوفر'); ?></p>
                <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($order['phoneone']); ?></p>
            </div>

            <div class="info-box">
                <h3>معلومات الشحن</h3>
                <p><strong>العنوان:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                <p><strong>المدينة:</strong> <?php echo htmlspecialchars($order['city']); ?></p>
                <p><strong>حالة الطلب:</strong>
                    <?php
                    $status_map = [
                        'pending' => 'قيد الانتظار',
                        'processing' => 'قيد المعالجة',
                        'shipped' => 'تم الشحن',
                        'delivered' => 'تم التسليم',
                        'cancelled' => 'ملغي',
                        'refunded' => 'تم الاسترجاع'
                    ];
                    echo $status_map[$order['orderstate']] ?? $order['orderstate'];
                    ?>
                </p>
            </div>

            <div class="info-box">
                <h3>معلومات الطلب</h3>
                <p><strong>تاريخ الطلب:</strong> <?php echo date('Y/m/d H:i', strtotime($order['created_at'])); ?></p>
                <p><strong>طريقة الدفع:</strong> الدفع عند الاستلام</p>
                <p><strong>عدد المنتجات:</strong> <?php echo count($order_items); ?></p>
            </div>
        </div>

        <h2>تفاصيل الطلب</h2>
        <table class="order-items">
            <thead>
                <tr>
                    <th>الصورة</th>
                    <th>المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                    <th>المجموع</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td>
                            <?php if (!empty($item['product_image'])): ?>
                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <?php else: ?>
                                <img src="images/no-image.jpg" alt="لا توجد صورة">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo number_format($item['price'], 2); ?> ر.س</td>
                        <td><?php echo $item['qty']; ?></td>
                        <td><?php echo number_format($item['total_price'], 2); ?> ر.س</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="order-summary">
            <h3>ملخص الطلب</h3>
            <div class="summary-row">
                <span>الإجمالي الفرعي:</span>
                <span><?php echo number_format($subtotal, 2); ?> ر.س</span>
            </div>

            <?php if (!empty($order['coupon_code'])): ?>
                <div class="summary-row">
                    <span>كود الخصم (<?php echo htmlspecialchars($order['coupon_code']); ?>):</span>
                    <span>-<?php echo number_format($order['discount_value'], 2); ?> ر.س</span>
                </div>
            <?php endif; ?>

            <div class="summary-row">
                <span>تكلفة الشحن:</span>
                <span>0.00 ر.س</span>
            </div>

            <div class="summary-row">
                <span>الإجمالي النهائي:</span>
                <span><?php echo number_format($order['finaltotalprice'], 2); ?> ر.س</span>
            </div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <a href="./profile.php" class="btn">عرض جميع الطلبات</a>
            <button onclick="window.print()" class="btn">طباعة الفاتورة</button>
        </div>
    </div>
</body>

</html>