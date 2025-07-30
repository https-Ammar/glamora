<?php
session_start();
require('./db.php');

// معالجة الموافقة أو الرفض على التعليق
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($comment_id && in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        try {
            $stmt = $conn->prepare("UPDATE product_comments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $comment_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Comment has been " . $new_status . " successfully";
            } else {
                $_SESSION['error'] = "Error updating comment status";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// عرض التعليقات المعلقة فقط
try {
    $stmt = $conn->prepare("
        SELECT pc.*, p.name AS product_name, p.id AS product_id
        FROM product_comments pc
        JOIN products p ON pc.product_id = p.id
        WHERE pc.status = 'pending'
        ORDER BY pc.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $comments = [];
    $_SESSION['error'] = "Error fetching comments: " . $e->getMessage();
}

// إنشاء رمز CSRF إذا لم يكن موجودًا
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراجعة التعليقات المعلقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .comment-text {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .rating-stars {
            color: #ffc107;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0"><i class="bi bi-chat-square-text me-2"></i>التعليقات المعلقة للمراجعة</h2>
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

                <?php if (empty($comments)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="bi bi-check-circle-fill fs-1 d-block mb-3"></i>
                        لا توجد تعليقات معلقة حاليًا للمراجعة
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>المنتج</th>
                                    <th>الاسم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>التقييم</th>
                                    <th>التعليق</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td>
                                            <a href="product.php?id=<?= (int) $comment['product_id'] ?>" target="_blank">
                                                <?= htmlspecialchars($comment['product_name']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($comment['name']) ?></td>
                                        <td>
                                            <a href="mailto:<?= htmlspecialchars($comment['email']) ?>">
                                                <?= htmlspecialchars($comment['email']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (is_numeric($comment['rating'])): ?>
                                                <div class="rating-stars">
                                                    <?php
                                                    $rating = (int) $comment['rating'];
                                                    for ($i = 1; $i <= 5; $i++):
                                                        ?>
                                                        <i class="bi bi-star<?= $i <= $rating ? '-fill' : '' ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="text-muted ms-1">(<?= $rating ?>/5)</span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="comment-text">
                                            <?php
                                            $rawComment = trim((string) $comment['comment']);
                                            echo (!empty($rawComment) && $rawComment !== '0')
                                                ? nl2br(htmlspecialchars($rawComment))
                                                : '<span class="text-muted fst-italic">لا يوجد تعليق</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?= date('Y/m/d H:i', strtotime($comment['created_at'])) ?>
                                        </td>
                                        <td>
                                            <form method="post" class="d-flex gap-2">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                                <button type="submit" name="action" value="approve"
                                                    class="btn btn-success btn-sm">
                                                    <i class="bi bi-check-circle"></i> قبول
                                                </button>
                                                <button type="submit" name="action" value="reject"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="bi bi-x-circle"></i> رفض
                                                </button>
                                            </form>
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
                    <small>إجمالي التعليقات المعلقة: <?= count($comments) ?></small>
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i> العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>