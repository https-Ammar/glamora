<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true
]);

require('./db.php');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
  . "://" . $_SERVER['HTTP_HOST'] . "/glamora/";

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
  header('Location: ./index.php');
  exit();
}

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

function safe($value)
{
  return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatPrice($price)
{
  return number_format((float) $price, 2, '.', '');
}

$name = safe($product['name']);
$description = safe($product['description']);
$brand = safe($product['brand']);
$tags = safe($product['tags']);
$barcode = safe($product['barcode']);
$category_id = (int) $product['category_id'];
$category_name = safe($product['category_name']);
$parent_category = safe($product['parent_category_name']);

$price = (float) $product['price'];
$sale_price = isset($product['sale_price']) ? (float) $product['sale_price'] : null;
$on_sale = $product['on_sale'] && $sale_price;
$final_price = $on_sale ? $sale_price : $price;
$discount = $on_sale ? round((($price - $sale_price) / $price) * 100) : 0;

$image_path = !empty($product['image'])
  ? (str_starts_with($product['image'], 'http')
    ? $product['image']
    : $base_url . 'dashboard/' . ltrim($product['image'], './'))
  : $base_url . 'assets/images/default.jpg';

$gallery = [];
if (!empty($product['gallery'])) {
  $gallery = json_decode($product['gallery'], true);
  foreach ($gallery as &$img) {
    if (!str_starts_with($img, 'http')) {
      $img = $base_url . 'dashboard/' . ltrim($img, './');
    }
  }
}

$sizes = !empty($product['sizes']) ? json_decode($product['sizes'], true) : [];
$colors = !empty($product['colors']) ? json_decode($product['colors'], true) : [];

$stock_status = safe($product['stock_status']);
$is_new = $product['is_new'] ? 'Yes' : 'No';
$is_featured = $product['is_featured'] ? 'Yes' : 'No';
$quantity = (int) $product['quantity'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $name ?> | GLAMORA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../style/main.css">
  <link rel="stylesheet" href="../style/viwe.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
</head>

<body>
  <?php require('./header.php'); ?>

  <div class="container py-5">
    <div class="d-sm-flex justify-content-between container-fluid py-3">
      <nav aria-label="breadcrumb" class="breadcrumb-row">
        <ul class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="/"> Home</a></li>
          <li class="breadcrumb-item">Product Thumbnail</li>
        </ul>
      </nav>
    </div>
    <div class="row gy-4">
      <div class="col-lg-6 product-image" id="mainImageContainer" style="background-image: url('<?= $image_path ?>');">
        <div class="d-flex thumbnails flex-column gap-3 mb-3">
          <?php if (!empty($gallery)): ?>
            <?php foreach ($gallery as $img): ?>
              <div class="thumbnail-item"
                style="background-image: url('<?= $img ?>'); background-size: cover; background-position: top center; cursor: pointer;"
                onclick="changeMainImage(this, '<?= $img ?>')">
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-6 product-details">
        <div class="social-media">
          <ul>
            <li><a href="https://www.instagram.com/dexignzone/">Instagram</a></li>
            <li><a href="https://www.facebook.com/dexignzone">Facebook</a></li>
            <li><a href="https://twitter.com/dexignzones">twitter</a></li>
          </ul>
        </div>
        <span class="badge bg-black mb-2">SALE <?= $discount ?>% Off</span>
        <h4 class="mb-2"><?= $name ?></h4>

        <div class="d-flex align-items-center mb-2">
          <ul class="list-inline mb-0 me-2">
            <li class="list-inline-item text-warning"><i class="bi bi-star-fill"></i></li>
            <li class="list-inline-item text-warning"><i class="bi bi-star-fill"></i></li>
            <li class="list-inline-item text-warning"><i class="bi bi-star-fill"></i></li>
            <li class="list-inline-item text-secondary"><i class="bi bi-star"></i></li>
            <li class="list-inline-item text-secondary"><i class="bi bi-star"></i></li>
          </ul>
          <span class="text-secondary me-2">4.7 Rating</span>
          <a href="#">(5 customer reviews)</a>
        </div>

        <h5><?= $description ?></h5>
        <p class="mb-4">It is the perfect tee for any occasion.</p>

        <div class="meta-content m-b20">
          <span class="form-label">Price</span>
          <span class="price">$<?= formatPrice($final_price) ?>
            <?php if ($on_sale): ?>
              <del>$<?= formatPrice($price) ?></del>
            <?php endif; ?>
          </span>
        </div>

        <div class="product-num gap-md-2 gap-xl-0 mt-3 mb-3">
          <div class="btn-quantity light">
            <label class="form-label">Quantity</label>
            <div class="d-flex justify-content-center align-items-center">
              <button class="btn btn-danger btn-number quantity-btn dark-btn" data-type="minus">-</button>
              <input class="quantity-display" type="text" id="quantity" name="quantity" value="1" min="1"
                max="<?= $quantity ?>" readonly>
              <button class="btn btn-success btn-number quantity-btn dark-btn" data-type="plus">+</button>
            </div>
          </div>

          <?php if (!empty($sizes)): ?>
            <div class="d-block">
              <label class="form-label">Size</label>
              <div class="btn-group product-size grid-media m-0">
                <?php foreach ($sizes as $index => $size): ?>
                  <input type="radio" class="btn-check" id="btnradio<?= 100 + $index ?>" name="btnradio1" autocomplete="off"
                    data-price="<?= formatPrice($size['price']) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                  <label class="size-btn" for="btnradio<?= 100 + $index ?>">
                    <?= safe($size['name']) ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="meta-content">
            <label class="form-label">Color</label>
            <?php if (!empty($colors)): ?>
              <div class="d-flex align-items-center color-filter">
                <?php foreach ($colors as $index => $color): ?>
                  <label class="form-check d-flex align-items-center" style="cursor: pointer; margin-right: 10px;">
                    <input class="form-check-input d-none" type="radio" id="radioNoLabel<?= $index ?>" name="radioNoLabel"
                      value="<?= safe($color['hex']) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                    <span class="color-circle" style="background-color:<?= safe($color['hex']) ?>;"></span>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          </div>
        </div>
        <div class="d-flex gap-2 mb-4">
          <button class="btn btn-dark product-btn w-50" onclick='addToCart(<?= $product["id"] ?>)'>Add to Cart</button>
          <button class="btn btn-outline-dark product-btn w-50">Add to Wishlist</button>
        </div>

        <hr>

        <p><strong>SKU:</strong> PRT584E63A</p>
        <p><strong>Category : </strong> <?= $parent_category ?> / <?= $category_name ?></p>
        <p><strong>Tags : </strong> <?= $tags ?></p>

        <div class="d-flex flex-wrap gap-3 fs-5 mt-4">
          <a href="#"><i class="bi bi-facebook"></i> Facebook</a>
          <a href="#"><i class="bi bi-instagram"></i> Instagram</a>
        </div>

        <div class="mt-4 d-flex flex-wrap gap-4">
          <div class="d-flex align-items-center">
            <img src="https://img.icons8.com/ios/50/shipped.png" width="24">
            <span class="ms-2">Free Shipping</span>
          </div>
          <div class="d-flex align-items-center">
            <img src="https://img.icons8.com/ios/50/return.png" width="24">
            <span class="ms-2">30 Days Easy Return</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="bg-white  mt-5">
        <div class="row info-row">
          <div class="col-6 info-label">brand</div>
          <div class="col-6 info-value"> <?= $brand ?></div>
        </div>
        <div class="row info-row">
          <div class="col-6 info-label">barcode</div>
          <div class="col-6 info-value"><?= $barcode ?></div>
        </div>
        <div class="row info-row">
          <div class="col-6 info-label">Stock Status</div>
          <div class="col-6 info-value"><?= $stock_status ?></div>
        </div>
        <div class="row info-row">
          <div class="col-6 info-label">On Sale</div>
          <div class="col-6 info-value"><?= $on_sale ? 'Yes' : 'No' ?></div>
        </div>
        <div class="row info-row">
          <div class="col-6 info-label">New Product</div>
          <div class="col-6 info-value"><?= $is_new ?> </div>
        </div>
        <div class="row info-row">
          <div class="col-6 info-label">Featured</div>
          <div class="col-6 info-value"><?= $is_featured ?></div>
        </div>
        <div class="row">
          <div class="col-6 info-label">Stock Status</div>
          <div class="col-6 info-value"><?= $quantity ?> </div>
        </div>
      </div>
    </div>

    <div class="row">
      <section class="py-5">
        <div class="_con">
          <div class="row">
            <div class="col-md-12">
              <h3 class="title">Related Products</h3>
            </div>
          </div>
          <div class="owl-carousel js-home-products">
            <?php
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
                (str_starts_with($sim['image'], 'http') ?
                  $sim['image'] :
                  $base_url . 'dashboard/' . ltrim($sim['image'], './')) :
                $base_url . 'assets/images/default.jpg';

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
                      <span class="badge bg-success"><?= (int) $simDisc ?>%</span>
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
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const thumbnails = document.querySelectorAll('.thumbnail-img');
      const mainImage = document.querySelector('.product-image');
      const colorOptions = document.querySelectorAll('.form-check-input');

      thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function () {
          thumbnails.forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          mainImage.src = this.src;
        });
      });

      colorOptions.forEach(option => {
        option.addEventListener('change', function () {
          const colorSpan = this.nextElementSibling;
          document.querySelectorAll('.color-circle').forEach(span => {
            span.style.borderColor = 'transparent';
          });
          colorSpan.style.borderColor = 'black';
        });
      });
    });

    function changeMainImage(el, imgUrl) {
      document.getElementById('mainImageContainer').style.backgroundImage = `url('${imgUrl}')`;
      const allThumbnails = document.querySelectorAll('.thumbnail-item');
      allThumbnails.forEach(thumbnail => thumbnail.classList.remove('active'));
      el.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', function () {
      const thumbnails = document.querySelectorAll('.thumbnail-item');
      if (thumbnails.length > 0) {
        thumbnails[0].classList.add('active');
      }
    });

    $(document).ready(function () {
      $(".js-home-products").owlCarousel({
        items: 4,
        margin: 15,
        nav: true,
        loop: true,
        responsive: {
          0: { items: 2 },
          600: { items: 2 },
          900: { items: 3 },
          1200: { items: 4 }
        }
      });

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

      $('.size-option').click(function () {
        $('.size-option').removeClass('selected');
        $(this).addClass('selected');
      });

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
  <?php require('./footer.php'); ?>
</body>

</html>