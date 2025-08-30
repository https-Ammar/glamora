<?php
session_start();
require('../config/db.php');

// معالجة الموافقة أو الرفض أو الحذف للتعليق
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('رمز CSRF غير صالح');
    }

    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($comment_id && in_array($action, ['approve', 'reject', 'delete'])) {
        try {
            if ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM product_comments WHERE id = ?");
                $stmt->bind_param("i", $comment_id);
                $message = "تم حذف التعليق بنجاح";
            } else {
                $new_status = ($action === 'approve') ? 'approved' : 'rejected';
                $stmt = $conn->prepare("UPDATE product_comments SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $comment_id);
                $message = "تم " . ($action === 'approve' ? "قبول" : "رفض") . " التعليق بنجاح";
            }

            if ($stmt->execute()) {
                $_SESSION['message'] = $message;
            } else {
                $_SESSION['error'] = "حدث خطأ أثناء معالجة التعليق";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// عرض جميع التعليقات مع إمكانية التصفية
try {
    $status_filter = $_GET['status'] ?? 'all';
    $search_query = $_GET['search'] ?? '';

    $sql = "SELECT pc.*, p.name AS product_name, p.id AS product_id 
            FROM product_comments pc
            JOIN products p ON pc.product_id = p.id";

    $where = [];
    $params = [];
    $types = '';

    if ($status_filter !== 'all') {
        $where[] = "pc.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if (!empty($search_query)) {
        $where[] = "(pc.name LIKE ? OR pc.comment LIKE ? OR p.name LIKE ?)";
        $search_param = "%$search_query%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= 'sss';
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY pc.created_at DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $comments = [];
    $_SESSION['error'] = "خطأ في جلب التعليقات: " . $e->getMessage();
}

// إنشاء رمز CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التعليقات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .comment-text {
            white-space: pre-wrap;
            word-wrap: break-word;
            max-width: 300px;
        }

        .rating-stars {
            color: #ffc107;
        }

        .status-badge {
            font-size: 0.8rem;
        }

        .approved {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .rejected {
            background-color: #f8d7da;
            color: #842029;
        }

        .pending {
            background-color: #fff3cd;
            color: #664d03;
        }

        .filter-active {
            font-weight: bold;
            border-bottom: 2px solid #0d6efd;
        }

        .search-box {
            max-width: 300px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0"><i class="bi bi-chat-square-text me-2"></i>إدارة التعليقات</h2>
                <span class="badge bg-light text-dark fs-6">إجمالي التعليقات: <?= count($comments) ?></span>
            </div>

            <div class="card-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?= ($status_filter === 'all') ? 'filter-active' : '' ?>"
                                    href="?status=all&search=<?= urlencode($search_query) ?>">الكل</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($status_filter === 'pending') ? 'filter-active' : '' ?>"
                                    href="?status=pending&search=<?= urlencode($search_query) ?>">معلقة</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($status_filter === 'approved') ? 'filter-active' : '' ?>"
                                    href="?status=approved&search=<?= urlencode($search_query) ?>">مقبولة</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($status_filter === 'rejected') ? 'filter-active' : '' ?>"
                                    href="?status=rejected&search=<?= urlencode($search_query) ?>">مرفوضة</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <form method="get" class="d-flex">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                            <input type="text" name="search" class="form-control me-2 search-box"
                                placeholder="بحث في التعليقات..." value="<?= htmlspecialchars($search_query) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (empty($comments)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="bi bi-info-circle-fill fs-1 d-block mb-3"></i>
                        لا توجد تعليقات لعرضها
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="100">الحالة</th>
                                    <th>المنتج</th>
                                    <th>الاسم</th>
                                    <th>التقييم</th>
                                    <th>التعليق</th>
                                    <th width="150">التاريخ</th>
                                    <th width="200">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $status_class = match ($comment['status']) {
                                                'approved' => 'approved',
                                                'rejected' => 'rejected',
                                                default => 'pending'
                                            };
                                            $status_text = match ($comment['status']) {
                                                'approved' => 'مقبول',
                                                'rejected' => 'مرفوض',
                                                default => 'معلق'
                                            };
                                            ?>
                                            <span class="badge rounded-pill <?= $status_class ?> status-badge">
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="product.php?id=<?= (int) $comment['product_id'] ?>" target="_blank">
                                                <?= htmlspecialchars($comment['product_name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($comment['name']) ?>
                                            <?php if (!empty($comment['email'])): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <a href="mailto:<?= htmlspecialchars($comment['email']) ?>">
                                                        <?= htmlspecialchars($comment['email']) ?>
                                                    </a>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (is_numeric($comment['rating'])): ?>
                                                <div class="rating-stars">
                                                    <?= str_repeat('<i class="bi bi-star-fill"></i>', (int) $comment['rating']) ?>
                                                    <?= str_repeat('<i class="bi bi-star"></i>', 5 - (int) $comment['rating']) ?>
                                                    <span class="text-muted ms-1">(<?= $comment['rating'] ?>/5)</span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="comment-text">
                                            <?php
                                            $rawComment = trim((string) ($comment['comment'] ?? ''));
                                            if (empty($rawComment)) {
                                                echo '<span class="text-muted fst-italic">لا يوجد تعليق نصي</span>';
                                            } else {
                                                echo nl2br(htmlspecialchars($rawComment));
                                            }
                                            ?>
                                            <?php
                                            $rawComment = trim((string) ($comment['comment'] ?? ''));
                                            if (empty($rawComment)) {
                                                echo '<span class="text-muted fst-italic">لا يوجد تعليق نصي</span>';
                                            } else {
                                                echo nl2br(htmlspecialchars($rawComment));
                                            }
                                            ?>

                                        </td>
                                        <td>
                                            <?= date('Y/m/d H:i', strtotime($comment['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">

                                                    <?php if ($comment['status'] === 'pending'): ?>
                                                        <button type="submit" name="action" value="approve"
                                                            class="btn btn-success btn-sm" title="قبول التعليق">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                        <button type="submit" name="action" value="reject"
                                                            class="btn btn-danger btn-sm" title="رفض التعليق">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <button type="submit" name="action" value="delete"
                                                        class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('هل أنت متأكد من حذف هذا التعليق؟')"
                                                        title="حذف التعليق">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-footer text-muted">
                <div class="d-flex justify-content-between align-items-center">
                    <small>
                        عرض <?= count($comments) ?> من <?= count($comments) ?> تعليق
                    </small>
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i> العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تأكيد الحذف مع رسالة مخصصة
        document.querySelectorAll('button[value="delete"]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                if (!confirm('سيتم حذف التعليق نهائياً. هل أنت متأكد؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>