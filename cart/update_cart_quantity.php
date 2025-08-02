<?php
require('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['id']) || !isset($_POST['action'])) {
            throw new Exception("Missing parameters.");
        }

        $cartId = intval($_POST['id']);
        $action = $_POST['action'];

        // جلب الكمية الحالية
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE id = ?");
        $stmt->bind_param("i", $cartId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Item not found in cart.");
        }

        $currentQty = $result->fetch_assoc()['quantity'];
        $newQty = $currentQty;

        if ($action === 'increase') {
            $newQty = $currentQty + 1;
        } elseif ($action === 'decrease') {
            $newQty = $currentQty - 1;
            if ($newQty < 1) {
                $newQty = 1;
            }
        }

        // تحديث الكمية
        $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newQty, $cartId);
        $updateStmt->execute();

        if ($updateStmt->affected_rows === 0) {
            throw new Exception("Failed to update quantity.");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Quantity updated successfully',
            'new_quantity' => $newQty
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