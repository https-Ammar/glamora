<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once('../config/db.php');
$imagePath = './dashboard/';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// نفس كود تتبع الزيارات كما في الصفحة الرئيسية
if ($conn) {
    $check_stmt = $conn->prepare("SELECT id FROM site_visits WHERE ip_address = ? AND DATE(visit_time) = CURDATE()");
    if ($check_stmt) {
        $check_stmt->bind_param("s", $ip);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows === 0) {
            $country = "Unknown";
            $context = stream_context_create(['http' => ['timeout' => 2]]);
            $api_url = "http://ip-api.com/json/" . urlencode($ip);
            $api_response = @file_get_contents($api_url, false, $context);

            if ($api_response !== false) {
                $data = json_decode($api_response, true);
                if (!empty($data['country'])) {
                    $country = (string) $data['country'];
                }
            }
            $insert_stmt = $conn->prepare("INSERT INTO site_visits (ip_address, country) VALUES (?, ?)");
            if ($insert_stmt) {
                $insert_stmt->bind_param("ss", $ip, $country);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
        }
        $check_stmt->close();
    }
} else {
    die("Database connection failed");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>GLAMORA - Discounted Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="./assets/style/main.css" />
    <style>
        .discounted-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .discounted-header {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            margin-bottom: 30px;
        }

        .discounted-header h1 {
            font-size: 2.5rem;
            color: #333;
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="_Tiket">
        <p>GLAMORA</p>
    </div>
    <section id="lod_file">


        <div class="discounted-header">
            <h1>Discounted Products</h1>
            <p>Special offers and great deals</p>
        </div>

        <main class="container__">
            <?php
            // استعلام لجلب جميع المنتجات التي لها خصم
            $discountQuery = "SELECT * FROM products WHERE discount_percent > 0 OR sale_price < price";
            $stmt = $conn->prepare($discountQuery);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo '<div class="discounted-products-grid">';

                while ($product = $result->fetch_assoc()) {
                    $productId = (int) $product['id'];
                    $productName = htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $productImage = htmlspecialchars($imagePath . ltrim($product['image'] ?? 'default.jpg', './'), ENT_QUOTES, 'UTF-8');
                    $price = (float) ($product['price'] ?? 0);
                    $salePrice = isset($product['sale_price']) ? (float) $product['sale_price'] : null;
                    $discountPercent = (int) ($product['discount_percent'] ?? 0);
                    $finalPrice = $salePrice ?? ($price - ($price * $discountPercent / 100));

                    // حساب نسبة الخصم الفعلية
                    $actualDiscount = $salePrice ? round((($price - $salePrice) / $price) * 100) : $discountPercent;
                    ?>
                    <div class="item">
                        <a href="../pages/view.php?id=<?php echo $productId; ?>" title="<?php echo $productName; ?>">
                            <figure class="bg_img" style="background-image: url('<?php echo $productImage; ?>');">
                                <span class="discount-badge">-<?php echo $actualDiscount; ?>%</span>
                                <?php if (!empty($product['is_featured'])): ?>
                                    <span class="badge bg-success text">Featured</span>
                                <?php endif; ?>
                                <?php if (!empty($product['is_new'])): ?>
                                    <span class="badge bg-success text">New</span>
                                <?php endif; ?>
                            </figure>
                        </a>

                        <div class="product-info">
                            <span class="text-muted small"><?php echo htmlspecialchars($product['brand'] ?? ''); ?></span><br>
                            <span class="snize-title" style="max-height: 2.8em; -webkit-line-clamp: 2;">
                                <?php echo $productName; ?>
                            </span>
                        </div>

                        <div class="price-section">
                            <?php if ($actualDiscount > 0): ?>
                                <span class="original-price" style="text-decoration: line-through; color: #999;">
                                    <?php echo number_format($price, 2); ?> EGP
                                </span>
                            <?php endif; ?>
                            <span class="final-price" style="color: #dc3545; font-weight: bold;">
                                <?php echo number_format($finalPrice, 2); ?> EGP
                            </span>
                        </div>

                        <div class="flex_pric playSound" onclick="addcart(<?php echo $productId; ?>)">
                            <button class="d-flex align-items-center nav-link click">Add to Cart</button>
                        </div>

                        <div class="ptn_" style="display: none;">
                            <div class="input-group product-qty">
                                <span class="input-group-btn">
                                    <button type="button" class="quantity-left-minus btn btn-danger btn-number"
                                        data-type="minus">
                                        <svg width="16" height="16" fill="currentColor" class="bi bi-dash">
                                            <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" />
                                        </svg>
                                    </button>
                                </span>
                                <input type="text" name="quantity"
                                    class="form-control input-number quantity<?php echo $productId; ?>" value="1">
                                <span class="input-group-btn">
                                    <button type="button" class="quantity-right-plus btn btn-success btn-number"
                                        data-type="plus">
                                        <svg width="16" height="16" fill="currentColor" class="bi bi-plus">
                                            <path
                                                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
                                        </svg>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php
                }

                echo '</div>';
            } else {
                echo '<div class="text-center py-5">
                <h3>No discounted products available at the moment</h3>
                <p>Check back later for great deals!</p>
              </div>';
            }

            $stmt->close();
            ?>
        </main>


    </section>

    <script>
        // نفس الدوال الموجودة في الصفحة الرئيسية
        function loadCart() {
            $.ajax({
                type: "GET",
                url: "../cart/show_cart.php",
                success: function (response) {
                    $('#offcanvasCart').html(response);
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                }
            });
        }

        function addcart(productid) {
            const quantity = $('.quantity' + productid).val() || 1;
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                type: "POST",
                url: "../cart/add_cart.php",
                data: {
                    product_id: productid,
                    quantity: quantity,
                    csrf_token: csrfToken
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            loadCart();
                        } else {
                            console.error(data.message);
                        }
                    } catch (e) {
                        console.error("Error parsing response:", e);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                }
            });
        }

        window.addEventListener("DOMContentLoaded", function () {
            const lod_file = document.getElementById('lod_file');
            const loading = document.getElementById('loading');
            if (lod_file && loading) {
                lod_file.style.display = 'block';
                loading.style.display = 'none';
            }

            loadCart();
        });
    </script>

    <audio id="audio" src="./assets/page/like.mp3"></audio>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
</body>

</html>