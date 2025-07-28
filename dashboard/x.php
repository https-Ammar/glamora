<?php
session_start();
require('./db.php');

// الموافقة أو الرفض على التعليق
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($comment_id && in_array($action, ['approve', 'reject'])) {
        $new_status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE product_comments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $comment_id);
        $stmt->execute();
    }
}

// عرض التعليقات المعلقة فقط
$stmt = $conn->prepare("
    SELECT pc.*, p.name AS product_name
    FROM product_comments pc
    JOIN products p ON pc.product_id = p.id
    WHERE pc.status = 'pending'
    ORDER BY pc.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Pending Comments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-5">
    <h2 class="mb-4">Pending Comments for Review</h2>
    <?php if (empty($comments)): ?>
        <div class="alert alert-info">No pending comments.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?= htmlspecialchars($comment['product_name']) ?></td>
                            <td><?= htmlspecialchars($comment['name']) ?></td>
                            <td><?= htmlspecialchars($comment['email']) ?></td>
                            <td>
                                <?= is_numeric($comment['rating']) ? (int) $comment['rating'] . '/5' : 'N/A' ?>
                            </td>
                            <td>
                                <?php
                                $rawComment = trim((string) $comment['comment']);
                                echo (!empty($rawComment) && $rawComment !== '0')
                                    ? nl2br(htmlspecialchars($rawComment))
                                    : '<em class="text-muted">No comment</em>';
                                ?>
                            </td>
                            <td>
                                <form method="post" class="d-flex gap-1">
                                    <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                    <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                    <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</body>

</html>