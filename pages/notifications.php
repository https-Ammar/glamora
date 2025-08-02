<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

require('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /glamora/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $conn = new mysqli("localhost", "username", "password", "shop");

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['mark_all_read'])) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = "تم تحديد جميع الإشعارات كمقروءة";
            header("Location: notifications.php");
            exit();
        }

        if (isset($_POST['delete_read'])) {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = "تم حذف الإشعارات المقروءة";
            header("Location: notifications.php");
            exit();
        }
    }

    if (isset($_GET['delete'])) {
        $notification_id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "تم حذف الإشعار بنجاح";
        header("Location: notifications.php");
        exit();
    }

    if (isset($_GET['mark_as_read'])) {
        $notification_id = intval($_GET['mark_as_read']);
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
        header("Location: notifications.php");
        exit();
    }

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    $query = "SELECT n.*, 
              o.id as order_id, o.orderstate as order_status, o.created_at as order_date,
              p.id as product_id, p.name as product_name, p.slug as product_slug, p.image as product_image,
              c.name as coupon_name, c.code as coupon_code, c.expires_at as coupon_expiry
              FROM notifications n
              LEFT JOIN orders o ON n.related_id = o.id AND n.type = 'order'
              LEFT JOIN products p ON n.related_id = p.id AND n.type = 'product'
              LEFT JOIN coupons c ON n.related_id = c.id AND n.type = 'promotion'
              WHERE n.user_id = ?
              ORDER BY n.created_at DESC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_notifications = $result->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = $result->fetch_row()[0];
    $stmt->close();

    $total_pages = ceil($total_notifications / $per_page);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error_message'] = "حدث خطأ في النظام. يرجى المحاولة لاحقاً.";
    header("Location: notifications.php");
    exit();
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

function getOrderStatusText($status)
{
    $statuses = [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد المعالجة',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
        'refunded' => 'تم الاسترجاع'
    ];
    return $statuses[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات - <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'حسابي'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }

        .notification-card {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .notification-card.unread {
            border-left-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .notification-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <?php include('../../includes/header.php'); ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="bi bi-bell-fill text-primary"></i> الإشعارات
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-primary"><?php echo $unread_count; ?> جديد</span>
                        <?php endif; ?>
                    </h2>
                    <div class="d-flex">
                        <form method="post" class="me-2">
                            <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-check-all"></i> تحديد الكل كمقروء
                            </button>
                        </form>
                        <form method="post">
                            <button type="submit" name="delete_read" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> حذف المقروء
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="bi bi-bell-slash-fill fs-1 d-block mb-3"></i>
                        <h4>لا توجد إشعارات لعرضها</h4>
                        <p class="mb-0">عندما تتلقى إشعارات جديدة، ستظهر هنا</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <div
                                class="list-group-item notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex flex-grow-1">
                                        <?php if ($notification['product_image']): ?>
                                            <img src="/glamora/uploads/products/<?php echo htmlspecialchars($notification['product_image']); ?>"
                                                class="notification-image me-3" alt="صورة المنتج">
                                        <?php endif; ?>

                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <p class="mb-1 fw-bold">
                                                    <?php echo htmlspecialchars($notification['message']); ?>
                                                </p>
                                                <div class="notification-actions">
                                                    <?php if (!$notification['is_read']): ?>
                                                        <a href="notifications.php?mark_as_read=<?php echo $notification['id']; ?>"
                                                            class="btn btn-sm btn-outline-success me-1" title="تحديد كمقروء">
                                                            <i class="bi bi-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="notifications.php?delete=<?php echo $notification['id']; ?>"
                                                        class="btn btn-sm btn-outline-danger" title="حذف"
                                                        onclick="return confirm('هل أنت متأكد من حذف هذا الإشعار؟')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </div>

                                            <?php if ($notification['order_id']): ?>
                                                <div class="mt-2">
                                                    <span class="status-badge status-<?php echo $notification['order_status']; ?>">
                                                        <?php echo getOrderStatusText($notification['order_status']); ?>
                                                    </span>
                                                    <a href="/glamora/order_confirmation.php?id=<?php echo $notification['order_id']; ?>"
                                                        class="text-primary ms-2">
                                                        <i class="bi bi-receipt"></i> عرض الطلب
                                                        #<?php echo $notification['order_id']; ?>
                                                    </a>
                                                </div>
                                            <?php elseif ($notification['product_id']): ?>
                                                <a href="/glamora/products/view.php?slug=<?php echo $notification['product_slug']; ?>"
                                                    class="text-primary d-block mt-1">
                                                    <i class="bi bi-box-seam"></i>
                                                    <?php echo htmlspecialchars($notification['product_name']); ?>
                                                </a>
                                            <?php elseif ($notification['coupon_code']): ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-success">
                                                        كود الخصم: <?php echo htmlspecialchars($notification['coupon_code']); ?>
                                                    </span>
                                                    <?php if ($notification['coupon_expiry']): ?>
                                                        <span class="text-muted ms-2">
                                                            ينتهي في:
                                                            <?php echo date('Y-m-d', strtotime($notification['coupon_expiry'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <small class="notification-time d-block mt-2">
                                                <i class="bi bi-clock"></i>
                                                <?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="notifications.php?page=<?php echo $page - 1; ?>">
                                            <i class="bi bi-chevron-left"></i> السابق
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="notifications.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="notifications.php?page=<?php echo $page + 1; ?>">
                                            التالي <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(function () {
            location.reload();
        }, 60000);

        document.querySelectorAll('.delete-notification').forEach(link => {
            link.addEventListener('click', function (e) {
                if (!confirm('هل أنت متأكد من حذف هذا الإشعار؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>