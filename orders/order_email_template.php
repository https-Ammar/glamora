<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <title>تأكيد الطلب #<?php echo $order_id; ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #4e73df;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }

        .order-details {
            margin: 20px 0;
            border: 1px solid #e3e6f0;
            padding: 15px;
            border-radius: 8px;
        }

        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #777;
            text-align: center;
            padding: 10px;
            border-top: 1px solid #e3e6f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e3e6f0;
        }

        th {
            background-color: #f8f9fc;
            font-weight: bold;
        }

        .total-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fc;
            border-radius: 8px;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>شكراً لطلبك!</h1>
            <p>تم تأكيد طلبك بنجاح وسيتم تجهيزه قريباً</p>
        </div>

        <div class="order-info">
            <h2 style="color: #4e73df; border-bottom: 2px solid #f8f9fc; padding-bottom: 10px;">معلومات الطلب</h2>
            <p><strong>رقم الطلب:</strong> #<?php echo $order_id; ?></p>
            <p><strong>التاريخ:</strong> <?php echo date('Y/m/d H:i', strtotime($order['created_at'])); ?></p>
            <p><strong>حالة الطلب:</strong>
                <?php
                $status_map = [
                    'pending' => 'قيد الانتظار',
                    'processing' => 'قيد التجهيز',
                    'shipped' => 'تم الشحن',
                    'delivered' => 'تم التسليم',
                    'cancelled' => 'ملغي',
                    'refunded' => 'تم الاسترجاع'
                ];
                echo $status_map[$order['orderstate'] ?? 'pending'];
                ?>
            </p>
            <p><strong>طريقة الدفع:</strong> الدفع عند الاستلام</p>
        </div>

        <div class="order-details">
            <h2 style="color: #4e73df; border-bottom: 2px solid #f8f9fc; padding-bottom: 10px;">تفاصيل الطلب</h2>
            <table>
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
                                    <img src="<?php echo $item['product_image']; ?>" alt="<?php echo $item['product_name']; ?>"
                                        class="product-img">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/60" alt="No image" class="product-img">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['product_name']; ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['qty']; ?></td>
                            <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <h3 style="color: #4e73df; margin-top: 0;">ملخص الطلب</h3>
                <p><strong>المجموع الفرعي:</strong> $<?php echo number_format($subtotal, 2); ?></p>
                <?php if (!empty($order['coupon_code'])): ?>
                    <p><strong>الخصم (<?php echo $order['coupon_code']; ?>):</strong>
                        -$<?php echo number_format($order['discount_value'] ?? 0, 2); ?></p>
                <?php endif; ?>
                <p><strong>الشحن:</strong> $0.00</p>
                <p style="font-weight: bold; font-size: 1.1em; color: #2e59d9;">
                    <strong>المجموع النهائي:</strong> $<?php echo number_format($order['finaltotalprice'] ?? 0, 2); ?>
                </p>
            </div>
        </div>

        <div class="customer-info">
            <h2 style="color: #4e73df; border-bottom: 2px solid #f8f9fc; padding-bottom: 10px;">معلومات العميل</h2>
            <p><strong>الاسم:</strong> <?php echo $order['user_name']; ?></p>
            <p><strong>البريد الإلكتروني:</strong> <?php echo $order['email']; ?></p>
            <p><strong>الهاتف:</strong> <?php echo $order['phoneone']; ?></p>
            <p><strong>عنوان التسليم:</strong> <?php echo $order['address']; ?>, <?php echo $order['city']; ?></p>
        </div>

        <div class="footer">
            <p>شكراً لتسوقك معنا!</p>
            <p>لأي استفسارات، لا تتردد في التواصل معنا عبر info@example.com</p>
            <p>© <?php echo date('Y'); ?> متجرنا. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>

</html>