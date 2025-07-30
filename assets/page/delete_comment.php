<?php
session_start();
require_once 'config.php';

// التحقق من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// التحقق من وجود البيانات المطلوبة
if (!isset($_POST['comment_id'], $_POST['product_id'], $_POST['csrf_token'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing required data']));
}

// التحقق من صحة CSRF Token
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid security token']));
}

// تنظيف المدخلات
$comment_id = (int) $_POST['comment_id'];
$product_id = (int) $_POST['product_id'];
$user_id = (int) $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'] ?? false;

// بدء المعاملة
$conn->begin_transaction();

try {
    // جلب بيانات التعليق مع التحقق من الصلاحيات
    $stmt = $conn->prepare("SELECT user_id, product_id FROM comments WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Comment not found");
    }

    $comment = $result->fetch_assoc();

    // التحقق من الصلاحيات
    if ($comment['user_id'] != $user_id && !$is_admin) {
        throw new Exception("You don't have permission to delete this comment");
    }

    // 1. حذف الإعجابات المرتبطة
    $stmt = $conn->prepare("DELETE FROM likes WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();

    // 2. حذف الردود المرتبطة
    $stmt = $conn->prepare("DELETE FROM replies WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();

    // 3. حذف التعليق نفسه
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();

    // تأكيد العملية
    $conn->commit();

    // إرجاع النتيجة
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'comment_id' => $comment_id,
        'message' => 'Comment deleted successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>