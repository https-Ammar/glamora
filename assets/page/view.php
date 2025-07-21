<?php
require('./db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: ./index.php');
  exit();
}

$id = (int) $_GET['id'];

// جلب بيانات المنتج مع تفاصيل التصنيفات
$stmt = $conn->prepare("
  SELECT 
    p.*, 
    c1.name AS category_name, 
    c2.name AS parent_category_name 
  FROM products p 
  LEFT JOIN categories c1 ON p.category_id = c1.id 
  LEFT JOIN categories c2 ON c1.parent_id = c2.id 
  WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
  header('Location: ./index.php');
  exit();
}

// دوال مساعدة
function safe($value)
{
  return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatPrice($price)
{
  return number_format((float) $price, 2, '.', '');
}

// البيانات الأساسية
$name = safe($product['name']);
$description = safe($product['description']);
$brand = safe($product['brand']);
$tags = safe($product['tags']);
$barcode = safe($product['barcode']);
$category_id = (int) $product['category_id'];
$category_name = safe($product['category_name']);
$parent_category = safe($product['parent_category_name']);

// الأسعار
$price = (float) $product['price'];
$sale_price = isset($product['sale_price']) ? (float) $product['sale_price'] : null;
$on_sale = $product['on_sale'] && $sale_price;
$final_price = $on_sale ? $sale_price : $price;
$discount = $on_sale ? round((($price - $sale_price) / $price) * 100) : 0;

// الصورة الرئيسية
$image_path = !empty($product['image'])
  ? (str_starts_with($product['image'], 'http')
    ? $product['image']
    : 'http://localhost:8888/glamora/dashboard/uploads/products/' . ltrim($product['image'], './'))
  : 'http://localhost:8888/glamora/assets/images/default.jpg';

// صور الجاليري
$gallery = [];
if (!empty($product['gallery'])) {
  $gallery = json_decode($product['gallery'], true);
  foreach ($gallery as &$img) {
    if (!str_starts_with($img, 'http')) {
      $img = str_starts_with($img, 'uploads/products/')
        ? 'http://localhost:8888/glamora/dashboard/' . ltrim($img, './')
        : 'http://localhost:8888/glamora/dashboard/uploads/products/' . ltrim($img, './');
    }
  }
}

// المقاسات والألوان
$sizes = !empty($product['sizes']) ? json_decode($product['sizes'], true) : [];
$colors = !empty($product['colors']) ? json_decode($product['colors'], true) : [];

// حالة المنتج والمخزون
$stock_status = safe($product['stock_status']);
$is_new = $product['is_new'] ? 'Yes' : 'No';
$is_featured = $product['is_featured'] ? 'Yes' : 'No';
$quantity = (int) $product['quantity'];
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $name ?> | GLAMORA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../style/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
  <style>
    .product-gallery {
      display: flex;
      gap: 10px;
      margin-top: 15px;
      flex-wrap: wrap;
    }

    .product-gallery img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      cursor: pointer;
      border: 1px solid #ddd;
    }

    .product-gallery img:hover {
      border-color: #333;
    }

    .size-option,
    .color-option {
      display: inline-block;
      margin: 5px;
      padding: 5px 10px;
      border: 1px solid #ddd;
      cursor: pointer;
    }

    .size-option.selected,
    .color-option.selected {
      border-color: #000;
      background: #f0f0f0;
    }

    .color-option {
      width: 30px;
      height: 30px;
      border-radius: 50%;
    }
  </style>
</head>

<body>

  <?php require('./header.php'); ?>

  <main>
    <section class="viwe_product">
      <div class="img_pro">
        <div class="img_viwe">
          <img id="mainImage" class="img_co" src="<?= $image_path ?>" alt="<?= $name ?>">
        </div>
        <?php if (!empty($gallery)): ?>
          <div class="product-gallery">
            <?php foreach ($gallery as $img): ?>
              <img src="<?= $img ?>" alt="<?= $name ?>" onclick="document.getElementById('mainImage').src = this.src">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="pric_viwe">
        <p class="label-c">Brand: <?= $brand ?></p>
        <h1 class="heading-c"><?= $name ?></h1>
        <p class="label-c"><?= $description ?></p>

        <div class="price-wrapper">
          <?php if ($on_sale): ?>
            <p class="text-muted"><s>EGP <?= formatPrice($price) ?></s></p>
            <p class="text-success"><?= $discount ?>% off</p>
          <?php endif; ?>
          <p class="final-price">EGP <?= formatPrice($final_price) ?></p>
        </div>

        <!-- Sizes -->
        <?php if (!empty($sizes)): ?>
          <div class="product-options">
            <h4>Sizes</h4>
            <div class="size-options">
              <?php foreach ($sizes as $size): ?>
                <div class="size-option" data-price="<?= formatPrice($size['price']) ?>">
                  <?= safe($size['name']) ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Colors -->
        <?php if (!empty($colors)): ?>
          <div class="product-options">
            <h4>Colors</h4>
            <div class="color-options">
              <?php foreach ($colors as $color): ?>
                <div class="color-option" style="background-color: <?= safe($color['hex']) ?>"
                  title="<?= safe($color['name']) ?>">
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="buy-section">
          <div class="product-qty">
            <button class="btn btn-danger btn-number" data-type="minus">-</button>
            <input type="text" id="quantity" name="quantity" value="1" min="1" max="<?= $quantity ?>" readonly>
            <button class="btn btn-success btn-number" data-type="plus">+</button>
          </div>
          <div class="flex_pric playSound" onclick='addToCart(<?= $product["id"] ?>)'>
            <button class="nav-link">Add To Cart</button>
            <div class="block_P">
              <span class="price"><?= formatPrice($final_price) ?></span>
              <span>EGP</span>
            </div>
          </div>
        </div>

        <div class="product-details">
          <p><strong>Category:</strong> <?= $parent_category ?> > <?= $category_name ?></p>
          <p><strong>Brand:</strong> <?= $brand ?></p>
          <p><strong>Tags:</strong> <?= $tags ?></p>
          <p><strong>Barcode:</strong> <?= $barcode ?></p>
          <p><strong>Stock Status:</strong> <?= $stock_status ?> (<?= $quantity ?> available)</p>
          <p><strong>New Product:</strong> <?= $is_new ?></p>
          <p><strong>On Sale:</strong> <?= $on_sale ? 'Yes' : 'No' ?></p>
          <p><strong>Featured:</strong> <?= $is_featured ?></p>
          <?php if (!empty($product['expiry_date'])): ?>
            <p><strong>Expiry Date:</strong> <?= safe($product['expiry_date']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Related Products -->
    <section class="py-5">
      <div class="_con">
        <div class="row">
          <div class="col-md-12">
            <h3 class="title">Related Products</h3>
          </div>
        </div>
        <div class="owl-carousel js-home-products">
          <?php
          // Get related products from same category
          $stmtSimilar = $conn->prepare("
            SELECT p.* 
            FROM products p
            WHERE p.category_id = ? AND p.id != ?
            ORDER BY RAND()
            LIMIT 10
          ");
          $stmtSimilar->bind_param("ii", $category_id, $id);
          $stmtSimilar->execute();
          $similarProducts = $stmtSimilar->get_result();

          while ($sim = $similarProducts->fetch_assoc()):
            $simName = safe($sim['name']);
            $simImage = !empty($sim['image']) ?
              (str_starts_with($sim['image'], 'http') ? $sim['image'] : '/glamora/' . ltrim($sim['image'], './')) :
              'http://localhost:8888/glamora/assets/images/default.jpg';
            $simPrice = (float) $sim['price'];
            $simSale = isset($sim['sale_price']) ? (float) $sim['sale_price'] : null;
            $simOnSale = $sim['on_sale'] && $simSale;
            $simFinal = $simOnSale ? $simSale : $simPrice;
            $simDisc = $simOnSale ? round((($simPrice - $simSale) / $simPrice) * 100) : 0;
            ?>
            <div class="item">
              <a href="view.php?id=<?= $sim['id'] ?>" title="<?= $simName ?>">
                <figure class="bg_img" style="background-image: url('<?= $simImage ?>');">
                  <?php if ($simDisc > 0): ?>
                    <span class="badge bg-success"><?= $simDisc ?>%</span>
                  <?php endif; ?>
                </figure>
              </a>
              <span class="snize-title"><?= $simName ?></span>
              <div class="flex_pric playSound" onclick='addQuickToCart(<?= $sim["id"] ?>)'>
                <button class="nav-link">Add To Cart</button>
                <div class="block_P">
                  <span class="price"><?= formatPrice($simFinal) ?></span>
                  <span>EGP</span>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </section>
  </main>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
  <script>
    $(document).ready(function () {
      // Initialize carousel
      $(".js-home-products").owlCarousel({
        items: 4,
        margin: 15,
        nav: true,
        loop: true,
        responsive: {
          0: { items: 1 },
          600: { items: 2 },
          900: { items: 3 },
          1200: { items: 4 }
        }
      });

      // Quantity buttons
      $('.btn-number').click(function () {
        const input = $('#quantity');
        let value = parseInt(input.val()) || 1;
        const max = parseInt(input.attr('max')) || 100;
        const min = parseInt(input.attr('min')) || 1;

        if ($(this).data('type') === 'minus') {
          value = Math.max(min, value - 1);
        } else {
          value = Math.min(max, value + 1);
        }
        input.val(value);
      });

      // Size selection (without changing price)
      $('.size-option').click(function () {
        $('.size-option').removeClass('selected');
        $(this).addClass('selected');
        // السعر مش هيتغير
      });

      // Color selection
      $('.color-option').click(function () {
        $('.color-option').removeClass('selected');
        $(this).addClass('selected');
      });
    });

    function addToCart(id) {
      const qty = parseInt(document.getElementById('quantity').value) || 1;
      const size = $('.size-option.selected').text().trim();
      const color = $('.color-option.selected').attr('title');

      const formData = new FormData();
      formData.append('productid', id);
      formData.append('qty', qty);
      if (size) formData.append('size', size);
      if (color) formData.append('color', color);

      fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json()).then(data => {
        alert(data.message || 'Added to cart');
      }).catch(console.error);
    }

    function addQuickToCart(id) {
      fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `productid=${id}&qty=1`
      }).then(res => res.json()).then(data => {
        alert(data.message || 'Added to cart');
      }).catch(console.error);
    }
  </script>

</body>

</html>