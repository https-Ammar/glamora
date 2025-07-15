<?php
session_start();
require('./db.php');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['userId'];

// جلب بيانات المستخدم باستخدام Prepared Statement
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// جلب الطلبات الخاصة بالمستخدم
$stmtOrders = $conn->prepare("SELECT * FROM orders WHERE userid = ? ORDER BY created_at DESC");
$stmtOrders->bind_param("i", $userId);
$stmtOrders->execute();
$orders = $stmtOrders->get_result();
?>

<h2>مرحبًا <?= htmlspecialchars($user['name']) ?></h2>

<?php if (!empty($user['profile_image'])): ?>
    <img src="<?= htmlspecialchars($user['profile_image']) ?>" width="100" height="100" style="border-radius:50%">
<?php else: ?>
    <p>لا توجد صورة</p>
<?php endif; ?>

<p><strong>البريد:</strong> <?= htmlspecialchars($user['email']) ?></p>
<p><strong>الهاتف:</strong> <?= htmlspecialchars($user['phone']) ?></p>
<p><strong>العنوان:</strong> <?= htmlspecialchars($user['address']) ?></p>
<p><strong>المدينة:</strong> <?= htmlspecialchars($user['city']) ?></p>
<p><strong>الدولة:</strong> <?= htmlspecialchars($user['country']) ?></p>

<a href="upload_image.php">تغيير الصورة</a> |
<a href="logout.php">تسجيل الخروج</a>

<hr>
<h3>طلباتك</h3>

<?php while ($order = $orders->fetch_assoc()): ?>
    <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 15px;">
        <strong>رقم الطلب:</strong> <?= $order['id'] ?><br>
        <strong>الحالة:</strong>
        <?php
        if ($order['orderstate'] == 'inprogress') {
            echo 'قيد المعالجة';
        } elseif ($order['orderstate'] == 'accepted') {
            echo 'تم القبول';
        } elseif ($order['orderstate'] == 'rejected') {
            echo 'تم الرفض';
        } else {
            echo htmlspecialchars($order['orderstate']);
        }
        ?><br>

        <strong>السعر النهائي:</strong> <?= htmlspecialchars($order['finaltotalprice']) ?> جنيه<br>
        <strong>عدد المنتجات:</strong> <?= htmlspecialchars($order['numberofproducts']) ?><br>
        <strong>تاريخ الطلب:</strong> <?= htmlspecialchars($order['created_at']) ?><br>

        <?php
        // جلب صورة منتج واحد من الطلب
        $orderId = $order['id'];
        $stmtProduct = $conn->prepare("
            SELECT p.img 
            FROM cart c 
            JOIN products p ON c.prouductid = p.id 
            WHERE c.userid = ? AND c.orderid = ? 
            LIMIT 1
        ");
        $stmtProduct->bind_param("ii", $userId, $orderId);
        $stmtProduct->execute();
        $productResult = $stmtProduct->get_result();

        if ($product = $productResult->fetch_assoc()):
            ?>
            <img src="<?= htmlspecialchars($product['img']) ?>" width="120">
        <?php else: ?>
            <p>لا توجد صورة متاحة</p>
        <?php endif;
        $stmtProduct->close();
        ?>

        <!-- زر إلغاء الطلب -->
        <?php if ($order['orderstate'] == 'inprogress'): ?>
            <form method="POST" action="cancel_order.php" onsubmit="return confirm('هل أنت متأكد من إلغاء الطلب؟');">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <button type="submit" style="background:red;color:white;padding:5px 10px;">إلغاء الطلب</button>
            </form>
        <?php else: ?>
            <p style="color:gray;">لا يمكن إلغاء هذا الطلب</p>
        <?php endif; ?>
    </div>
<?php endwhile; ?>