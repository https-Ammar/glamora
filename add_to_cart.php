<?php
require('db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من البيانات الأساسية
        if (!isset($_POST['productid'])) {
            throw new Exception("معرف المنتج مطلوب.");
        }

        $productid = intval($_POST['productid']);
        $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
        $color = isset($_POST['color']) ? trim($_POST['color']) : null;
        $size = isset($_POST['size']) ? trim($_POST['size']) : null;

        if ($productid <= 0 || $qty <= 0) {
            throw new Exception("بيانات المنتج غير صالحة.");
        }

        // إدارة معرف المستخدم
        if (!isset($_COOKIE['cart_userid']) || !is_numeric($_COOKIE['cart_userid'])) {
            $userid = time() . rand(1000, 9999);
            setcookie('cart_userid', $userid, time() + (86400 * 30), "/");
        } else {
            $userid = $_COOKIE['cart_userid'];
        }

        // بدء المعاملة
        $conn->begin_transaction();

        // جلب معلومات المنتج مع الألوان والأحجام
        $productQuery = $conn->prepare("
            SELECT 
                p.id, 
                p.name, 
                p.price, 
                p.sale_price,
                p.on_sale,
                p.quantity AS stock_quantity,
                p.colors,
                p.sizes,
                p.image
            FROM products p 
            WHERE p.id = ?
        ");
        $productQuery->bind_param("i", $productid);
        $productQuery->execute();
        $productResult = $productQuery->get_result();

        if ($productResult->num_rows === 0) {
            throw new Exception("المنتج غير موجود.");
        }

        $product = $productResult->fetch_assoc();
        $productQuery->close();

        // حساب السعر النهائي (سعر الخصم إذا كان في عرض)
        $basePrice = $product['on_sale'] && $product['sale_price'] ? $product['sale_price'] : $product['price'];
        $finalPrice = $basePrice;

        // التحقق من المخزون
        if ($product['stock_quantity'] < $qty) {
            throw new Exception("الكمية المطلوبة غير متوفرة في المخزون.");
        }

        // التحقق من صحة اللون إذا تم اختياره
        $colorHex = null;
        if ($color !== null) {
            $colors = json_decode($product['colors'], true) ?: [];
            $validColor = false;

            foreach ($colors as $c) {
                if ($c['name'] === $color) {
                    $validColor = true;
                    $colorHex = $c['hex'];
                    break;
                }
            }

            if (!$validColor) {
                throw new Exception("اللون المحدد غير متوفر لهذا المنتج.");
            }
        }

        // التحقق من صحة الحجم إذا تم اختياره
        if ($size !== null) {
            $sizes = json_decode($product['sizes'], true) ?: [];
            $validSize = false;

            foreach ($sizes as $s) {
                if ($s['name'] === $size) {
                    $validSize = true;
                    $finalPrice = $s['price'];
                    break;
                }
            }

            if (!$validSize) {
                throw new Exception("الحجم المحدد غير متوفر لهذا المنتج.");
            }
        }

        // التحقق مما إذا كان المنتج موجود بالفعل في السلة بنفس المواصفات
        $checkCart = $conn->prepare("
            SELECT id, quantity 
            FROM cart 
            WHERE 
                userid = ? AND 
                productid = ? AND 
                color " . ($color === null ? "IS NULL" : "= ?") . " AND 
                size " . ($size === null ? "IS NULL" : "= ?")
        );

        if ($color === null && $size === null) {
            $checkCart->bind_param("ii", $userid, $productid);
        } elseif ($color !== null && $size === null) {
            $checkCart->bind_param("iis", $userid, $productid, $color);
        } elseif ($color === null && $size !== null) {
            $checkCart->bind_param("iis", $userid, $productid, $size);
        } else {
            $checkCart->bind_param("iiss", $userid, $productid, $color, $size);
        }

        $checkCart->execute();
        $cartResult = $checkCart->get_result();

        if ($cartResult->num_rows > 0) {
            // تحديث الكمية إذا كان المنتج موجوداً
            $cart = $cartResult->fetch_assoc();
            $newQty = $cart['quantity'] + $qty;

            $updateCart = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $updateCart->bind_param("ii", $newQty, $cart['id']);
            $updateCart->execute();
            $updateCart->close();

            $response = "تم تحديث الكمية في السلة.";
        } else {
            // إضافة منتج جديد للسلة
            $insertCart = $conn->prepare("
                INSERT INTO cart(
                    userid, 
                    productid, 
                    quantity, 
                    price,
                    color, 
                    color_hex,
                    size, 
                    product_name,
                    product_image,
                    added_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insertCart->bind_param(
                "iiidsssss",
                $userid,
                $productid,
                $qty,
                $finalPrice,
                $color,
                $colorHex,
                $size,
                $product['name'],
                $product['image']
            );
            $insertCart->execute();
            $insertCart->close();

            $response = "تمت إضافة المنتج إلى السلة.";
        }

        $checkCart->close();

        // تنفيذ المعاملة
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => $response,
            'cart_count' => getCartCount($conn, $userid),
            'product' => [
                'id' => $productid,
                'name' => $product['name'],
                'price' => $finalPrice,
                'quantity' => $qty,
                'color' => $color,
                'color_hex' => $colorHex,
                'size' => $size,
                'image' => $product['image']
            ]
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'طلب غير صالح'
    ]);
}

// دالة مساعدة للحصول على عدد العناصر في السلة
function getCartCount($conn, $userid)
{
    $countQuery = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE userid = ?");
    $countQuery->bind_param("s", $userid);
    $countQuery->execute();
    $result = $countQuery->get_result()->fetch_assoc();
    $countQuery->close();
    return $result['total'] ?? 0;
}
?>