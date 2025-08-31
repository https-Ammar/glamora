<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

require('../config/db.php');

$order_id = null;
$order = [];
$order_items = [];
$subtotal = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = (int) $_GET['id'];

    $stmt = $conn->prepare("
        SELECT o.*, u.email, u.name as user_name 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");

    if ($stmt === false) {
        die('Error preparing order query: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $order_id);
    if (!$stmt->execute()) {
        die('Error executing order query: ' . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
    }
    $stmt->close();

    if (!empty($order)) {
        $items_stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");

        if ($items_stmt === false) {
            die('Error preparing order items query: ' . htmlspecialchars($conn->error));
        }

        $items_stmt->bind_param("i", $order_id);
        if (!$items_stmt->execute()) {
            die('Error executing order items query: ' . htmlspecialchars($items_stmt->error));
        }

        $items_result = $items_stmt->get_result();
        $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
        $items_stmt->close();

        foreach ($order_items as $item) {
            $subtotal += $item['total_price'];
        }
    }

    $coupon_data = null;
    if (!empty($order['coupon_code'])) {
        $coupon_stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ?");
        if ($coupon_stmt === false) {
            die('Error preparing coupon query: ' . htmlspecialchars($conn->error));
        }

        $coupon_stmt->bind_param("s", $order['coupon_code']);
        if (!$coupon_stmt->execute()) {
            die('Error executing coupon query: ' . htmlspecialchars($coupon_stmt->error));
        }

        $coupon_result = $coupon_stmt->get_result();
        $coupon_data = $coupon_result->fetch_assoc();
        $coupon_stmt->close();
    }
}

if (empty($order)) {
    header('Location: ../auth/profile.php');
    exit();
}

require './vendor/autoload.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // تغيير إلى سيرفر Gmail
    $mail->SMTPAuth = true;
    $mail->Username = 'ammar132004@gmail.com'; // إيميل Gmail الخاص بك
    $mail->Password = 'mflgywgxhhmdkqib'; // كلمة مرور التطبيق
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Enable verbose debug output (يمكنك تعطيله بعد التأكد من عمل الإيميل)
    // $mail->SMTPDebug = 2;

    // Recipients
    $mail->setFrom('ammar132004@gmail.com', 'Your Store Name'); // يجب أن يكون نفس إيميل Gmail
    $mail->addAddress($order['email'], $order['user_name']);
    $mail->addReplyTo('ammar132004@gmail.com', 'Information'); // إيميل للرد عليه

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'تأكيد طلبك #' . $order_id;

    // إنشاء محتوى الإيميل
    ob_start();
    ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">

        <head>
            <meta charset="UTF-8">
            <title>تأكيد الطلب #<?php echo $order_id; ?></title>

        </head>

        <body>
            <div class="container">
                <div class="header">
                    <h1>شكراً لطلبك!</h1>
                    <p>رقم الطلب: #<?php echo $order_id; ?></p>
                </div>

                <div class="order-details">
                    <h2>تفاصيل الطلب</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo $item['qty']; ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3>ملخص الطلب</h3>
                    <p>المجموع الفرعي: $<?php echo number_format($subtotal, 2); ?></p>
                    <?php if (!empty($order['coupon_code'])): ?>
                            <p>الخصم (<?php echo htmlspecialchars($order['coupon_code']); ?>):
                                -$<?php echo number_format($order['discount_value'] ?? 0, 2); ?></p>
                    <?php endif; ?>
                    <p>الشحن: $0.00</p>
                    <p><strong>المجموع الكلي: $<?php echo number_format($order['finaltotalprice'] ?? 0, 2); ?></strong></p>
                </div>

                <div class="footer">
                    <p>شكراً لتسوقك معنا!</p>
                    <p>لأي استفسارات، لا تتردد في التواصل معنا على ammar132004@gmail.com</p>
                </div>
            </div>
        </body>

        </html>
        <?php
        $email_content = ob_get_clean();

        $mail->Body = $email_content;
        $mail->AltBody = "شكراً لطلبك! رقم الطلب: #$order_id\n\nيمكنك مراجعة تفاصيل طلبك في حسابك على موقعنا.";

        $mail->send();
    // يمكنك إضافة رسالة نجاح هنا إذا أردت
    // $_SESSION['email_sent'] = true;

} catch (Exception $e) {
    error_log("فشل إرسال الإيميل: {$mail->ErrorInfo}");
    // يمكنك إضافة رسالة خطأ هنا إذا أردت
    // $_SESSION['email_error'] = "فشل إرسال إيميل التأكيد: {$mail->ErrorInfo}";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - #<?php echo htmlspecialchars($order_id); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

</head>

<body>
    <div class="container py-5">
        <div class="order-card bg-white">
            <div class="header-section text-center">
                <h1 class="fw-bold mb-3">Thank You For Your Order!</h1>
                <p class="mb-2">Your order has been received and will be processed shortly</p>
                <p class="mb-0">Order Number: <strong>#<?php echo htmlspecialchars($order_id); ?></strong></p>
            </div>

            <div class="p-4">
                <div class="alert success-alert text-center mb-4">
                    <h2 class="h4 fw-bold mb-2">Order Confirmed Successfully</h2>
                    <p class="mb-0">Shipping details and tracking information will be sent to your email</p>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="info-card p-3 h-100">
                            <h3 class="h5 fw-bold mb-3">Customer Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></p>
                            <p><strong>Email:</strong>
                                <?php echo htmlspecialchars($order['email'] ?? $order['customer_email'] ?? 'N/A'); ?>
                            </p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phoneone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-card p-3 h-100">
                            <h3 class="h5 fw-bold mb-3">Shipping Information</h3>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></p>
                            <p><strong>City:</strong> <?php echo htmlspecialchars($order['city'] ?? 'N/A'); ?></p>
                            <p><strong>Status:</strong>
                                <?php
                                $status_map = [
                                    'pending' => 'Pending',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                    'refunded' => 'Refunded'
                                ];

                                echo $status_map[$order['orderstate'] ?? ''] ?? 'N/A';
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-card p-3 h-100">
                            <h3 class="h5 fw-bold mb-3">Order Information</h3>
                            <p><strong>Order Date:</strong>
                                <?php
                                if (!empty($order['created_at'])) {
                                    echo date('M d, Y H:i', strtotime($order['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </p>
                            <p><strong>Payment Method:</strong> Cash on Delivery</p>
                            <p><strong>Items:</strong> <?php echo count($order_items); ?></p>
                        </div>
                    </div>
                </div>

                <h2 class="h4 fw-bold mb-3">Order Details</h2>
                <div class="table-responsive mb-4">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($order_items)): ?>
                                    <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($item['product_image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>"
                                                                alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                                class="product-img">
                                                    <?php else: ?>
                                                            <img src="images/no-image.jpg" alt="No image" class="product-img">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['qty']; ?></td>
                                                <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No items found in this order</td>
                                    </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-card p-4 mb-4">
                    <h3 class="h5 fw-bold mb-3">Order Summary</h3>
                    <div class="summary-item d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <?php if (!empty($order['coupon_code'])): ?>
                            <div class="summary-item d-flex justify-content-between">
                                <span>Discount (<?php echo htmlspecialchars($order['coupon_code']); ?>):</span>
                                <span>-$<?php echo number_format($order['discount_value'] ?? 0, 2); ?></span>
                            </div>
                    <?php endif; ?>

                    <div class="summary-item d-flex justify-content-between">
                        <span>Shipping:</span>
                        <span>$0.00</span>
                    </div>

                    <div class="summary-item d-flex justify-content-between">
                        <span>Grand Total:</span>
                        <span>$<?php echo number_format($order['finaltotalprice'] ?? 0, 2); ?></span>
                    </div>
                </div>

                <div class="no-print text-center py-3">
                    <a href="./profile.php" class="btn btn-primary me-2">View All Orders</a>
                    <button onclick="window.print()" class="btn btn-outline-primary">Print Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>



<!--  -->
