<?php
require('./db.php');

// التحقق من وجود ID صحيح
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: ./index.php');
  exit();
}

$id = (int) $_GET['id'];

// جلب بيانات المنتج
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
  header('Location: ./index.php');
  exit();
}

// تنظيف البيانات
$name = htmlspecialchars($product['name']);
$description = htmlspecialchars($product['description']);
$brand = htmlspecialchars($product['brand']);
$tags = htmlspecialchars($product['tags']);
$slug = htmlspecialchars($product['slug']);
$barcode = htmlspecialchars($product['barcode']);
$expiry = $product['expiry_date'] ?? '-';
$category_id = (int) $product['category_id'];
$price = (float) $product['price'];
$sale_price = isset($product['sale_price']) ? (float) $product['sale_price'] : null;
$on_sale = $product['on_sale'] && $sale_price;
$final_price = $on_sale ? $sale_price : $price;
$discount = $on_sale ? round((($price - $sale_price) / $price) * 100) : 0;

$image_path = !empty($product['image']) ? '/admin/' . ltrim($product['image'], './') : '/images/default.jpg';
$stock_status = htmlspecialchars($product['stock_status']);
$is_new = $product['is_new'] ? 'Yes' : 'No';
$is_featured = $product['is_featured'] ? 'Yes' : 'No';
$rating = (float) $product['rating'];
$views = (int) $product['views'];
$created_at = $product['created_at'];
$updated_at = $product['updated_at'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $name ?> | GLAMORA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
  <link rel="stylesheet" href="../style/main.css">
</head>

<body>

  <?php require('./header.php'); ?>

  <main>
    <section class="viwe_product">
      <div class="img_pro">
        <div class="img_viwe">
          <img class="img_co" src="<?= $image_path ?>" alt="<?= $name ?>">
        </div>
        <div class="small_img">
          <img class="img_co" src="<?= $image_path ?>" alt="<?= $name ?>">
        </div>
      </div>

      <div class="pric_viwe">
        <p class="label-c">Brand</p>
        <h1 class="heading-c"><?= $name ?></h1>
        <p class="label-c"><?= $description ?></p>

        <div class="price-wrapper">
          <?php if ($price > $final_price): ?>
            <p class="text-muted"><s>EGP <?= number_format($price, 2) ?></s></p>
            <p class="text-success"><?= $discount ?>% off</p>
          <?php endif; ?>
          <p class="final-price">EGP <?= number_format($final_price, 2) ?></p>
        </div>

        <div class="buy-section">
          <div class="product-qty">
            <button class="btn btn-danger btn-number" data-type="minus">-</button>
            <input type="text" id="quantity" name="quantity" value="1" readonly>
            <button class="btn btn-success btn-number" data-type="plus">+</button>
          </div>
          <div class="flex_pric playSound" onclick='addToCart(<?= $product["id"] ?>)'>
            <button class="nav-link">Add To Cart</button>
            <div class="block_P">
              <span class="price"><?= number_format($final_price, 2) ?></span>
              <span>EGP</span>
            </div>
          </div>
        </div>

        <div class="product-details">
          <p><strong>Brand:</strong> <?= $brand ?></p>
          <p><strong>Tags:</strong> <?= $tags ?></p>
          <p><strong>Slug:</strong> <?= $slug ?></p>
          <p><strong>Barcode:</strong> <?= $barcode ?></p>
          <p><strong>Expiry Date:</strong> <?= $expiry ?></p>
          <p><strong>Stock Status:</strong> <?= $stock_status ?></p>
          <p><strong>New Product:</strong> <?= $is_new ?></p>
          <p><strong>On Sale:</strong> <?= $on_sale ? 'Yes' : 'No' ?></p>
          <p><strong>Featured:</strong> <?= $is_featured ?></p>
          <p><strong>Rating:</strong> <?= $rating ?>/5</p>
          <p><strong>Views:</strong> <?= $views ?></p>
          <p><strong>Created At:</strong> <?= $created_at ?></p>
          <p><strong>Last Updated:</strong> <?= $updated_at ?></p>
        </div>
      </div>
    </section>

    <section class="py-5">
      <div class="_con">
        <div class="row">
          <div class="col-md-12">
            <h3 class="title">Related Products</h3>
          </div>
        </div>
        <div class="owl-carousel js-home-products">
          <?php
          $stmtSimilar = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 10");
          $stmtSimilar->bind_param("ii", $category_id, $id);
          $stmtSimilar->execute();
          $similarProducts = $stmtSimilar->get_result();

          while ($sim = $similarProducts->fetch_assoc()):
            $simName = htmlspecialchars($sim['name']);
            $simImage = !empty($sim['image']) ? '/admin/' . ltrim($sim['image'], './') : '/images/default.jpg';
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
                  <span class="price"><?= number_format($simFinal, 2) ?></span>
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
      $(".js-home-products").owlCarousel({
        items: 4,
        margin: 15,
        nav: true,
        loop: true
      });

      $('.btn-number').click(function () {
        const input = $('#quantity');
        let value = parseInt(input.val()) || 1;
        value = $(this).data('type') === 'minus' ? Math.max(1, value - 1) : value + 1;
        input.val(value);
      });
    });

    function addToCart(id) {
      const quantity = parseInt(document.getElementById('quantity').value) || 1;
      fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `productid=${id}&qty=${quantity}`
      })
        .then(res => res.text())
        .then(data => alert(data))
        .catch(console.error);
    }

    function addQuickToCart(id) {
      fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `productid=${id}&qty=1`
      })
        .then(res => res.text())
        .then(data => alert(data))
        .catch(console.error);
    }
  </script>

</body>

</html>