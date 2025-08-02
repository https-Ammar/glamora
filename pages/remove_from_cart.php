<?php
require('db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['id'])) {
            throw new Exception("Missing cart item ID.");
        }

        $cartId = intval($_POST['id']);

        // حذف العنصر من السلة
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->bind_param("i", $cartId);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Item not found in cart.");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>