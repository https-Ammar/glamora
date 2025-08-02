<?php
require('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // تحقق من وجود العنصر في السلة
    $stmt = $conn->prepare("SELECT qty FROM cart WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $fetch = $result->fetch_assoc();
            $newQty = $fetch['qty'] + 1;

            // تحديث الكمية
            $updateStmt = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("ii", $newQty, $id);
                $updateStmt->execute();

                if ($updateStmt->affected_rows > 0) {
                    echo "✅ Quantity updated successfully.";
                } else {
                    echo "⚠️ Quantity not changed.";
                }
                $updateStmt->close();
            } else {
                echo "❌ Error in update statement.";
            }
        } else {
            echo "❌ Cart item not found.";
        }
        $stmt->close();
    } else {
        echo "❌ Error in select statement.";
    }
} else {
    echo "❌ Invalid request.";
}
?>