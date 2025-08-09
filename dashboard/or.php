<?php
session_start();
require('./db.php');

// جلب بيانات الطلب
if (!isset($_GET['id'])) {
    header('Location: or.php');
    exit();
}

$orderId = intval($_GET['id']);
$orderQuery = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$orderQuery->bind_param("i", $orderId);
$orderQuery->execute();
$order = $orderQuery->get_result()->fetch_assoc();
$orderQuery->close();

if (!$order) {
    header('Location: or.php');
    exit();
}

// جلب منتجات الطلب
$productsQuery = $conn->prepare("
    SELECT op.*, p.name, p.price, p.image 
    FROM order_products op 
    JOIN products p ON op.product_id = p.id 
    WHERE op.order_id = ?
");
$productsQuery->bind_param("i", $orderId);
$productsQuery->execute();
$products = $productsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$productsQuery->close();

// جلب سجل حالة الطلب
$statusLogQuery = $conn->prepare("
    SELECT * FROM order_status_logs 
    WHERE order_id = ? 
    ORDER BY created_at DESC
");
$statusLogQuery->bind_param("i", $orderId);
$statusLogQuery->execute();
$statusLogs = $statusLogQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$statusLogQuery->close();

// تحديث حالة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];

    $updateStmt = $conn->prepare("UPDATE orders SET orderstate = ? WHERE id = ?");
    $updateStmt->bind_param("si", $status, $orderId);
    $updateStmt->execute();
    $updateStmt->close();

    $logStmt = $conn->prepare("INSERT INTO order_status_logs (order_id, status) VALUES (?, ?)");
    $logStmt->bind_param("is", $orderId, $status);
    $logStmt->execute();
    $logStmt->close();

    header("Location: order_details.php?id=$orderId");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الطلب #<?= $order['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
        }

        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
        }

        .nav-link {
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .nav-link:hover {
            background-color: #495057;
        }

        .nav-link.active {
            background-color: #007bff;
        }

        .order-header {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .order-products {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .order-product {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .order-product:last-child {
            border-bottom: none;
        }

        .order-product img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-left: 15px;
            border-radius: 5px;
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 0.4em 0.8em;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-4">
                        <h5>مرحباً <?php echo htmlspecialchars($fetchname['name']); ?></h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> الرئيسية
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="or.php">
                                <i class="fas fa-shopping-cart"></i> الطلبات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box"></i> المنتجات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fas fa-tags"></i> التصنيفات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h2">تفاصيل الطلب #<?= $order['id'] ?></h1>
                        <a href="or.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> رجوع
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <!-- معلومات الطلب -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">معلومات الطلب</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>اسم العميل:</strong> <?= htmlspecialchars($order['name']) ?></p>
                                        <p><strong>الهاتف:</strong> <?= htmlspecialchars($order['phoneone']) ?></p>
                                        <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($order['email']) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>العنوان:</strong> <?= htmlspecialchars($order['address']) ?></p>
                                        <p><strong>المدينة:</strong> <?= htmlspecialchars($order['city']) ?></p>
                                        <p><strong>تاريخ الطلب:</strong>
                                            <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></p>
                                    </div>
                                </div>

                                <form method="POST" class="mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <label for="status" class="form-label"><strong>تغيير حالة
                                                    الطلب:</strong></label>
                                            <select name="status" id="status" class="form-select">
                                                <option value="inprogress" <?= $order['orderstate'] == 'inprogress' ? 'selected' : '' ?>>قيد المعالجة</option>
                                                <option value="accepted" <?= $order['orderstate'] == 'accepted' ? 'selected' : '' ?>>تم القبول</option>
                                                <option value="done" <?= $order['orderstate'] == 'done' ? 'selected' : '' ?>>مكتمل</option>
                                                <option value="rejected" <?= $order['orderstate'] == 'rejected' ? 'selected' : '' ?>>تم الرفض</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="submit" class="btn btn-primary mt-3">
                                                <i class="fas fa-save"></i> حفظ التغييرات
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- منتجات الطلب -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">منتجات الطلب</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($products as $product): ?>
                                    <div class="order-product">
                                        <img src="<?= htmlspecialchars($product['image']) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>">
                                        <div class="flex-grow-1">
                                            <h6><?= htmlspecialchars($product['name']) ?></h6>
                                            <p class="mb-1">الكمية: <?= $product['quantity'] ?></p>
                                            <p class="mb-1">السعر: <?= number_format($product['price'], 2) ?> ج.م</p>
                                            <p class="mb-0">المجموع:
                                                <?= number_format($product['price'] * $product['quantity'], 2) ?> ج.م</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="mt-4 pt-3 border-top">
                                    <div class="row">
                                        <div class="col-md-6 offset-md-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th>المجموع الفرعي:</th>
                                                    <td><?= number_format($order['totalprice'], 2) ?> ج.م</td>
                                                </tr>
                                                <tr>
                                                    <th>تكلفة الشحن:</th>
                                                    <td><?= number_format($order['shippingprice'], 2) ?> ج.م</td>
                                                </tr>
                                                <tr>
                                                    <th>الخصم:</th>
                                                    <td><?= number_format($order['discountprice'], 2) ?> ج.م</td>
                                                </tr>
                                                <tr class="table-active">
                                                    <th>الإجمالي النهائي:</th>
                                                    <td><strong><?= number_format($order['finaltotalprice'], 2) ?>
                                                            ج.م</strong></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- حالة الطلب -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">حالة الطلب</h5>
                            </div>
                            <div class="card-body text-center">
                                <span class="badge status-badge bg-<?=
                                    $order['orderstate'] == 'done' ? 'success' :
                                    ($order['orderstate'] == 'inprogress' ? 'warning' :
                                        ($order['orderstate'] == 'accepted' ? 'primary' : 'danger'))
                                    ?>">
                                    <?= $order['orderstate'] ?>
                                </span>

                                <div class="mt-3">
                                    <a href="print_order.php?id=<?= $order['id'] ?>" class="btn btn-outline-secondary"
                                        target="_blank">
                                        <i class="fas fa-print"></i> طباعة الفاتورة
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- سجل حالة الطلب -->
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">سجل حالة الطلب</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($statusLogs)): ?>
                                    <div class="timeline">
                                        <?php foreach ($statusLogs as $log): ?>
                                            <div class="timeline-item">
                                                <h6 class="mb-1">
                                                    <?=
                                                        $log['status'] == 'done' ? 'مكتمل' :
                                                        ($log['status'] == 'inprogress' ? 'قيد المعالجة' :
                                                            ($log['status'] == 'accepted' ? 'تم القبول' : 'تم الرفض'))
                                                        ?>
                                                </h6>
                                                <p class="text-muted small mb-0">
                                                    <?= date('Y-m-d H:i', strtotime($log['created_at'])) ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">لا يوجد سجل لتغيرات الحالة</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>