<?php
session_start();
require('./db.php');

// تفعيل عرض أخطاء MySQLi (مفيد للتطوير)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// التأكد من تسجيل الدخول
if (!isset($_SESSION['userId'])) {
    header('Location: ./login.php');
    exit();
}

// جلب بيانات المستخدم
$userid = $_SESSION['userId'];
$select = $conn->prepare("SELECT * FROM usersadmin WHERE id = ?");
$select->bind_param("i", $userid);
$select->execute();
$fetchname = $select->get_result()->fetch_assoc();
$select->close();

// حذف منتج
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM cart WHERE productid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header('Location: index.php');
        exit();
    }
}

// تحديث حالة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET orderstate = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO order_status_logs (order_id, status) VALUES (?, ?)");
    $stmt->bind_param("is", $orderId, $status);
    $stmt->execute();
    $stmt->close();

    header('Location: index.php');
    exit();
}

// حذف تصنيف وكل ما يتعلق به
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['add_coupon'])) {
    $categoryId = intval($_POST['id']);

    $stmt1 = $conn->prepare("DELETE FROM products WHERE category_id = ?");
    $stmt1->bind_param("i", $categoryId);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("DELETE FROM ads WHERE categoryid = ?");
    $stmt2->bind_param("i", $categoryId);
    $stmt2->execute();
    $stmt2->close();

    $stmt3 = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt3->bind_param("i", $categoryId);
    $stmt3->execute();
    $stmt3->close();

    header('Location: index.php');
    exit();
}

// إضافة إعلان
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category'], $_POST['linkaddress'], $_FILES['photo'])) {
    $filepath = 'uploads/';
    if (!file_exists($filepath)) {
        mkdir($filepath, 0777, true);
    }

    foreach ($_FILES['photo']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $ext = pathinfo($_FILES['photo']['name'][$key], PATHINFO_EXTENSION);
            $uniqueName = uniqid('', true) . '.' . $ext;
            $photo_path = $filepath . $uniqueName;

            if (move_uploaded_file($tmp_name, $photo_path)) {
                $category_id = intval($_POST['category'][$key]);
                $linkaddress = $_POST['linkaddress'][$key];

                $stmt = $conn->prepare("INSERT INTO ads (categoryid, photo, linkaddress) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $category_id, $photo_path, $linkaddress);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header('Location: index.php');
    exit();
}

// إضافة تصنيف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_FILES['image']) && !isset($_POST['add_coupon']) && !isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;
    $image = $_FILES['image'];

    if (!empty($name) && $image && $image['error'] === 0) {
        $targetDir = 'uploads/categories/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('', true) . '.' . $ext;
        $targetPath = $targetDir . $imageName;

        if (move_uploaded_file($image['tmp_name'], $targetPath)) {
            if ($parent_id) {
                $stmt = $conn->prepare("INSERT INTO categories (name, image, parent_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $name, $targetPath, $parent_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $targetPath);
            }
            $stmt->execute();
            $stmt->close();

            header('Location: index.php');
            exit();
        } else {
            echo "❌ فشل رفع الصورة.";
        }
    } else {
        echo "❌ تأكد من إدخال الاسم الصحيح واختيار صورة صالحة.";
    }
}

// إضافة كوبون خصم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['coupon_code']);
    $discountType = $_POST['discount_type'];
    $discountValue = floatval($_POST['discount_value']);
    $maxUses = intval($_POST['max_uses']);
    $expiresAt = $_POST['expires_at'];

    if (!empty($code) && in_array($discountType, ['percentage', 'fixed']) && $discountValue > 0 && $maxUses > 0 && !empty($expiresAt)) {
        $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, max_uses, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $code, $discountType, $discountValue, $maxUses, $expiresAt);
        $stmt->execute();
        $stmt->close();
        header("Location: index.php?success=coupon");
        exit();
    } else {
        echo "<p style='color:red;'>يرجى ملء جميع الحقول بشكل صحيح.</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $description = trim($_POST['description']);
    $tags = trim($_POST['tags']);
    $price = floatval($_POST['price']);
    $discountPercent = isset($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : 0;

    // ✅ حساب سعر الخصم إن وُجد
    $salePrice = ($discountPercent > 0 && $discountPercent <= 100)
        ? $price - ($price * ($discountPercent / 100))
        : null;

    $quantity = intval($_POST['quantity']);
    $stockStatus = in_array($_POST['stock_status'], ['in_stock', 'pre_order', 'out_of_stock']) ? $_POST['stock_status'] : 'in_stock';
    $isNew = isset($_POST['is_new']) ? 1 : 0;
    $onSale = isset($_POST['on_sale']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $barcode = !empty($_POST['barcode']) ? $_POST['barcode'] : uniqid('PRD-');
    $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $categoryId = intval($_POST['category_id']);

    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = 'uploads/products/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('', true) . '.' . $ext;
        $targetPath = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = $targetPath;
        }
    }

    // ✅ حفظ البيانات بما في ذلك الخصم والسعر بعد الخصم
    $stmt = $conn->prepare("INSERT INTO products (
        name, brand, description, tags, price, sale_price, discount_percent,
        quantity, stock_status, is_new, on_sale, is_featured, barcode,
        expiry_date, category_id, image
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssddddsiiissss",
        $name,
        $brand,
        $description,
        $tags,
        $price,
        $salePrice,
        $discountPercent,
        $quantity,
        $stockStatus,
        $isNew,
        $onSale,
        $isFeatured,
        $barcode,
        $expiryDate,
        $categoryId,
        $imagePath
    );
    $stmt->execute();
    $stmt->close();

    header("Location: index.php?success=product");
    exit();
}

?>






<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
        }

        .nav-link {
            color: #fff;
        }

        .nav-link:hover {
            background-color: #495057;
        }

        .nav-link.active {
            background-color: #007bff;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }

        .stats-card {
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
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
                            <a class="nav-link active" href="#" onclick="showSection('dashboard')">
                                <i class="fas fa-tachometer-alt"></i> الرئيسية
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('orders')">
                                <i class="fas fa-shopping-cart"></i> الطلبات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('products')">
                                <i class="fas fa-box"></i> المنتجات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('add-product')">
                                <i class="fas fa-plus"></i> إضافة منتج
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('categories')">
                                <i class="fas fa-tags"></i> التصنيفات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('ads')">
                                <i class="fas fa-bullhorn"></i> الإعلانات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="showSection('coupons')">
                                <i class="fas fa-ticket-alt"></i> كوبونات الخصم
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
                    <h1 class="h2">لوحة التحكم</h1>
                </div>

                <!-- Dashboard Section -->
                <div id="dashboard" class="content-section active">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">إجمالي الطلبات</h5>
                                    <?php
                                    $totalOrders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
                                    ?>
                                    <h2 class="card-text"><?= $totalOrders ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">المنتجات</h5>
                                    <?php
                                    $totalProducts = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
                                    ?>
                                    <h2 class="card-text"><?= $totalProducts ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">إجمالي المبيعات</h5>
                                    <?php
                                    $totalSales = $conn->query("SELECT SUM(finaltotalprice) as total FROM orders WHERE orderstate = 'done'")->fetch_assoc()['total'];
                                    ?>
                                    <h2 class="card-text"><?= number_format($totalSales ?? 0, 2) ?> ج.م</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark stats-card">
                                <div class="card-body">
                                    <h5 class="card-title">العملاء</h5>
                                    <?php
                                    $totalCustomers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
                                    ?>
                                    <h2 class="card-text"><?= $totalCustomers ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">أحدث الطلبات</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>رقم الطلب</th>
                                                    <th>اسم العميل</th>
                                                    <th>التاريخ</th>
                                                    <th>الحالة</th>
                                                    <th>الهاتف</th>
                                                    <th>الإجمالي</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
                                                while ($order = $orders->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td><?= $order['id'] ?></td>
                                                        <td><?= htmlspecialchars($order['name']) ?></td>
                                                        <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                                                        <td>
                                                            <span class="badge status-badge bg-<?=
                                                                $order['orderstate'] == 'done' ? 'success' :
                                                                ($order['orderstate'] == 'inprogress' ? 'warning' :
                                                                    ($order['orderstate'] == 'accepted' ? 'primary' : 'danger'))
                                                                ?>">
                                                                <?= $order['orderstate'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($order['phoneone']) ?></td>
                                                        <td><?= number_format($order['finaltotalprice'], 2) ?> ج.م</td>
                                                        <td>
                                                            <a href="order_details.php?id=<?= $order['id'] ?>"
                                                                class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section -->
                <div id="orders" class="content-section">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">إدارة الطلبات</h5>
                            <div class="mb-3">
                                <input type="text" id="orderSearch" class="form-control" placeholder="ابحث عن طلب...">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>رقم الطلب</th>
                                            <th>اسم العميل</th>
                                            <th>الهاتف</th>
                                            <th>العنوان</th>
                                            <th>المدينة</th>
                                            <th>عدد المنتجات</th>
                                            <th>إجمالي السعر</th>
                                            <th>الحالة</th>
                                            <th>التاريخ</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ordersTableBody">
                                        <?php
                                        $orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
                                        while ($order = $orders->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><?= $order['id'] ?></td>
                                                <td><?= htmlspecialchars($order['name']) ?></td>
                                                <td><?= htmlspecialchars($order['phoneone']) ?></td>
                                                <td><?= htmlspecialchars($order['address']) ?></td>
                                                <td><?= htmlspecialchars($order['city']) ?></td>
                                                <td><?= $order['numberofproducts'] ?></td>
                                                <td><?= number_format($order['finaltotalprice'], 2) ?> ج.م</td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <select name="status" class="form-select form-select-sm"
                                                            onchange="this.form.submit()">
                                                            <option value="inprogress" <?= $order['orderstate'] == 'inprogress' ? 'selected' : '' ?>>قيد المعالجة</option>
                                                            <option value="accepted" <?= $order['orderstate'] == 'accepted' ? 'selected' : '' ?>>تم القبول</option>
                                                            <option value="done" <?= $order['orderstate'] == 'done' ? 'selected' : '' ?>>مكتمل</option>
                                                            <option value="rejected" <?= $order['orderstate'] == 'rejected' ? 'selected' : '' ?>>تم الرفض</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <a href="order_details.php?id=<?= $order['id'] ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div id="products" class="content-section">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">إدارة المنتجات</h5>

                            <div class="mb-3">
                                <input type="text" id="productSearch" class="form-control"
                                    placeholder="البحث عن منتج...">
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الصورة</th>
                                            <th>اسم المنتج</th>
                                            <th>السعر</th>
                                            <th>سعر الخصم</th>
                                            <th>الكمية</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productTableBody">
                                        <?php
                                        $products = $conn->query("SELECT * FROM products");
                                        while ($product = $products->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><?= $product['id'] ?></td>
                                                <td>
                                                    <img src="<?= htmlspecialchars($product['image']) ?>"
                                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                                        style="width: 50px; height: 50px; object-fit: cover;">
                                                </td>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= number_format($product['price'], 2) ?> ج.م</td>
                                                <td>
                                                    <?= ($product['sale_price'] !== null && $product['sale_price'] !== '') ? number_format($product['sale_price'], 2) . ' ج.م' : '-' ?>
                                                </td>
                                                <td><?= (int) $product['quantity'] ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?=
                                                            $product['stock_status'] === 'in_stock' ? 'success' :
                                                            ($product['stock_status'] === 'pre_order' ? 'warning' : 'danger') ?>">
                                                        <?=
                                                            $product['stock_status'] === 'in_stock' ? 'متوفر' :
                                                            ($product['stock_status'] === 'pre_order' ? 'طلب مسبق' : 'غير متوفر') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="edit_product.php?id=<?= $product['id'] ?>"
                                                        class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?id=<?= $product['id'] ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JavaScript للبحث -->
                <script>
                    document.getElementById('productSearch').addEventListener('input', function () {
                        const filter = this.value.toLowerCase();
                        const rows = document.querySelectorAll('#productTableBody tr');

                        rows.forEach(row => {
                            const productName = row.children[2].textContent.toLowerCase();
                            row.style.display = productName.includes(filter) ? '' : 'none';
                        });
                    });
                </script>

                <!-- Add Product Section -->
                <div id="add-product" class="content-section">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">إضافة منتج جديد</h5>
                            <form method="POST" enctype="multipart/form-data" action="">
                                <input type="hidden" name="add_product" value="1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">اسم المنتج*</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="brand" class="form-label">العلامة التجارية</label>
                                            <input type="text" class="form-control" id="brand" name="brand">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">السعر*</label>
                                            <input type="number" step="0.01" class="form-control" id="price"
                                                name="price" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="discount_percent" class="form-label">نسبة الخصم %</label>
                                            <input type="number" class="form-control" id="discount_percent"
                                                name="discount_percent" min="0" max="100">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">الكمية*</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity"
                                                required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stock_status" class="form-label">حالة المخزون*</label>
                                            <select class="form-select" id="stock_status" name="stock_status" required>
                                                <option value="in_stock">متوفر</option>
                                                <option value="out_of_stock">غير متوفر</option>
                                                <option value="pre_order">طلب مسبق</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- التصنيفات الفرعية فقط -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label">التصنيف*</label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value="">اختر التصنيف</option>
                                                <?php
                                                $categories = $conn->query("SELECT c1.id, c1.name AS child_name, c2.name AS parent_name 
                                                            FROM categories c1
                                                            LEFT JOIN categories c2 ON c1.parent_id = c2.id
                                                            WHERE c1.parent_id IS NOT NULL");
                                                while ($category = $categories->fetch_assoc()):
                                                    ?>
                                                    <option value="<?= $category['id'] ?>">
                                                        <?= htmlspecialchars($category['parent_name'] . ' > ' . $category['child_name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="expiry_date" class="form-label">تاريخ الانتهاء</label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">خيارات</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_new"
                                                    name="is_new">
                                                <label class="form-check-label" for="is_new">منتج جديد</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="on_sale"
                                                    name="on_sale">
                                                <label class="form-check-label" for="on_sale">عرض خاص</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured"
                                                    name="is_featured">
                                                <label class="form-check-label" for="is_featured">منتج مميز</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="description" class="form-label">الوصف*</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                required></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="tags" class="form-label">الكلمات الدلالية (افصلها
                                                بفواصل)</label>
                                            <input type="text" class="form-control" id="tags" name="tags">
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="image" class="form-label">صورة المنتج*</label>
                                            <input type="file" class="form-control" id="image" name="image"
                                                accept="image/*" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">إضافة المنتج</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Categories Section -->
                <div id="categories" class="content-section">
                    <div class="row">
                        <!-- Form لإضافة تصنيف جديد -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">إضافة تصنيف جديد</h5>
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">اسم التصنيف*</label>
                                            <input type="text" class="form-control" name="name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="image" class="form-label">صورة التصنيف*</label>
                                            <input type="file" class="form-control" name="image" accept="image/*"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="parent_id" class="form-label">التصنيف الأب (اختياري)</label>
                                            <select class="form-select" name="parent_id">
                                                <option value="">بدون تصنيف أب</option>
                                                <?php
                                                $parents = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                                                while ($parent = $parents->fetch_assoc()):
                                                    ?>
                                                    <option value="<?= $parent['id'] ?>">
                                                        <?= htmlspecialchars($parent['name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">إضافة التصنيف</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- جدول عرض التصنيفات -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">التصنيفات الحالية</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>الاسم</th>
                                                    <th>الصورة</th>
                                                    <th>الأب</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $categories = $conn->query("
                                    SELECT c1.*, c2.name AS parent_name 
                                    FROM categories c1 
                                    LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                                    ORDER BY c1.id DESC
                                ");
                                                while ($category = $categories->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td><?= $category['id'] ?></td>
                                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                                        <td>
                                                            <img src="<?= htmlspecialchars($category['image']) ?>"
                                                                alt="<?= htmlspecialchars($category['name']) ?>"
                                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                        </td>
                                                        <td>
                                                            <?= $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '<span class="text-muted">رئيسي</span>' ?>
                                                        </td>
                                                        <td>
                                                            <form action="" method="POST" class="d-inline">
                                                                <input type="hidden" name="id"
                                                                    value="<?= $category['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('هل أنت متأكد من حذف هذا التصنيف؟ سيتم حذف جميع المنتجات المرتبطة به!')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ads Section -->
                <div id="ads" class="content-section">
                    <div class="row">
                        <!-- إضافة إعلان جديد -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">إضافة إعلانات جديدة</h5>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div id="ads-container">
                                            <div class="row ad-row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">التصنيف*</label>
                                                        <select name="category[]" class="form-select" required>
                                                            <option value="">اختر التصنيف</option>
                                                            <?php
                                                            $categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                                                            while ($category = $categories->fetch_assoc()):
                                                                ?>
                                                                <option value="<?= $category['id'] ?>">
                                                                    <?= htmlspecialchars($category['name']) ?>
                                                                </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">الصورة*</label>
                                                        <input type="file" name="photo[]" class="form-control"
                                                            accept="image/*" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label class="form-label">رابط الإعلان*</label>
                                                        <input type="url" name="linkaddress[]" class="form-control"
                                                            placeholder="https://example.com" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="mb-3">
                                                        <label class="form-label d-block">&nbsp;</label>
                                                        <button type="button" class="btn btn-danger form-control"
                                                            onclick="removeAdRow(this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 d-flex justify-content-between">
                                            <button type="button" class="btn btn-secondary" onclick="addAdRow()">+ إعلان
                                                آخر</button>
                                            <button type="submit" class="btn btn-primary">حفظ الإعلانات</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- عرض الإعلانات الحالية -->
                        <div class="col-md-12 mt-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">الإعلانات الحالية</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>التصنيف</th>
                                                    <th>الصورة</th>
                                                    <th>الرابط</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $ads = $conn->query("
                                    SELECT ads.id, ads.photo, ads.linkaddress, categories.name AS category_name 
                                    FROM ads 
                                    JOIN categories ON ads.categoryid = categories.id 
                                    ORDER BY ads.id DESC
                                ");
                                                while ($ad = $ads->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td><?= $ad['id'] ?></td>
                                                        <td><?= htmlspecialchars($ad['category_name']) ?></td>
                                                        <td>
                                                            <img src="<?= htmlspecialchars($ad['photo']) ?>"
                                                                alt="صورة الإعلان"
                                                                style="width: 100px; height: 60px; object-fit: cover;">
                                                        </td>
                                                        <td>
                                                            <a href="<?= htmlspecialchars($ad['linkaddress']) ?>"
                                                                target="_blank">
                                                                <?= mb_strlen($ad['linkaddress']) > 30 ? mb_substr($ad['linkaddress'], 0, 30) . '...' : $ad['linkaddress'] ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <a href="delete_ad.php?id=<?= $ad['id'] ?>"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('هل أنت متأكد من حذف هذا الإعلان؟')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                <?php if ($ads->num_rows === 0): ?>
                                                    <tr>
                                                        <td colspan="5">لا توجد إعلانات حالياً.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JavaScript لإضافة وحذف صفوف الإعلانات ديناميكياً -->
                <script>
                    function addAdRow() {
                        const adRow = document.querySelector('.ad-row');
                        const clone = adRow.cloneNode(true);

                        // Reset fields
                        clone.querySelectorAll('input, select').forEach(input => {
                            if (input.type === 'file' || input.type === 'url') {
                                input.value = '';
                            } else {
                                input.selectedIndex = 0;
                            }
                        });

                        document.getElementById('ads-container').appendChild(clone);
                    }

                    function removeAdRow(button) {
                        const rows = document.querySelectorAll('.ad-row');
                        if (rows.length > 1) {
                            button.closest('.ad-row').remove();
                        } else {
                            alert("لا يمكن حذف الصف الأخير.");
                        }
                    }
                </script>

                <!-- Coupons Section -->
                <div id="coupons" class="content-section">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">إضافة كوبون خصم</h5>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="coupon_code" class="form-label">كود الكوبون*</label>
                                            <input type="text" class="form-control" name="coupon_code" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="discount_type" class="form-label">نوع الخصم*</label>
                                            <select name="discount_type" class="form-select" required>
                                                <option value="percentage">نسبة مئوية</option>
                                                <option value="fixed">قيمة ثابتة</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="discount_value" class="form-label">قيمة الخصم*</label>
                                            <input type="number" step="0.01" class="form-control" name="discount_value"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="max_uses" class="form-label">عدد مرات الاستخدام*</label>
                                            <input type="number" class="form-control" name="max_uses" min="1" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="expires_at" class="form-label">تاريخ الانتهاء*</label>
                                            <input type="datetime-local" class="form-control" name="expires_at"
                                                required>
                                        </div>
                                        <button type="submit" name="add_coupon" class="btn btn-primary">إضافة
                                            الكوبون</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">الكوبونات الحالية</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>الكود</th>
                                                    <th>النوع</th>
                                                    <th>القيمة</th>
                                                    <th>المستخدم</th>
                                                    <th>الحد الأقصى</th>
                                                    <th>تاريخ الانتهاء</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $coupons = $conn->query("
                                                    SELECT c.*, COUNT(o.id) as used_count 
                                                    FROM coupons c 
                                                    LEFT JOIN orders o ON o.coupon_id = c.id 
                                                    GROUP BY c.id 
                                                    ORDER BY c.id DESC
                                                ");
                                                if ($coupons->num_rows > 0):
                                                    while ($coupon = $coupons->fetch_assoc()):
                                                        ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($coupon['code']) ?></td>
                                                            <td><?= $coupon['discount_type'] == 'percentage' ? 'نسبة' : 'قيمة' ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($coupon['discount_value']) ?><?= $coupon['discount_type'] == 'percentage' ? '%' : ' ج.م' ?>
                                                            </td>
                                                            <td><?= $coupon['used_count'] ?></td>
                                                            <td><?= htmlspecialchars($coupon['max_uses']) ?></td>
                                                            <td><?= date('Y-m-d', strtotime($coupon['expires_at'])) ?></td>
                                                            <td>
                                                                <a href="delete_coupon.php?id=<?= $coupon['id'] ?>"
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('هل أنت متأكد من حذف هذا الكوبون؟')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    endwhile;
                                                else:
                                                    ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">لا توجد كوبونات حالياً</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide sections
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');

            // Update active nav link
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Search functionality for products
        document.getElementById('productSearch').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productTableBody tr');
            rows.forEach(row => {
                const name = row.cells[2].textContent.toLowerCase();
                row.style.display = name.includes(filter) ? '' : 'none';
            });
        });

        // Search functionality for orders
        document.getElementById('orderSearch').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTableBody tr');
            rows.forEach(row => {
                const orderId = row.cells[0].textContent.toLowerCase();
                const customerName = row.cells[1].textContent.toLowerCase();
                const phone = row.cells[2].textContent.toLowerCase();

                if (orderId.includes(filter) || customerName.includes(filter) || phone.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add advertisement row
        function addAdRow() {
            const container = document.getElementById('ads-container');
            const newRow = document.createElement('div');
            newRow.className = 'row ad-row';
            newRow.innerHTML = `
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">التصنيف*</label>
                        <select name="category[]" class="form-select" required>
                            <option value="">اختر التصنيف</option>
                            <?php
                            $categories = $conn->query("SELECT * FROM categories");
                            while ($category = $categories->fetch_assoc()):
                                ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">الصورة*</label>
                        <input type="file" name="photo[]" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">رابط الإعلان*</label>
                        <input type="url" name="linkaddress[]" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger form-control" onclick="removeAdRow(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        // Remove advertisement row
        function removeAdRow(button) {
            button.closest('.ad-row').remove();
        }
    </script>
</body>

</html>