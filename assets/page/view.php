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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $name ?> | GLAMORA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
      <div class="col-lg-6 product-image" style="background-image: url(./lady-1.webp);">
        <div class="d-flex thumbnails flex-column gap-3 mb-3">

          <?php if (!empty($gallery)): ?>
            <?php foreach ($gallery as $img): ?>
              <div class="thumbnail-item"
                style="background-image: url('<?= $img ?>'); background-size: cover; background-position: top  center;  cursor: pointer;"
                onclick="document.getElementById('mainImage').src = '<?= $img ?>'">
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

        <h5>This comfortable cotton crop-top features the Divi Engine logo on the front expressing how easy
          "data Divi Engine life" is.</h5>
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
                  <div class="form-check">
                    <input class="form-check-input" type="radio" id="radioNoLabel<?= $index ?>" name="radioNoLabel"
                      value="<?= safe($color['hex']) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                    <span class="color-circle" style="background-color:<?= safe($color['hex']) ?>"></span>
                  </div>
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


      <p class="label-c"><?= $description ?></p>


      <p><strong>Stock Status:</strong> (<?= $quantity ?> available)</p>


      <?php if (!empty($product['expiry_date'])): ?>
        <p><strong>Expiry Date:</strong> <?= safe($product['expiry_date']) ?></p>
      <?php endif; ?>
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
          <div class="col-6 info-label">Manufacture</div>
          <div class="col-6 info-value">Indra Hosiery Mills</div>
        </div>
      </div>

    </div>
  </div>

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


  </script>

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




<!--  -->

<style>
  body {
    font-family: sans-serif;
    background-color: #fdf8f3;
  }

  .thumbnail-img {
    width: 70px;
    height: 90px;
    object-fit: cover;
    border: 2px solid transparent;
    cursor: pointer;
    border-radius: 5px;
  }

  .thumbnail-img.active {
    border-color: black;
  }

  .price del {
    color: #999;
    margin-left: 10px;
    font-size: 18px;
    opacity: .6;
    color: #5E626F;
    font-weight: 400;
    text-decoration: line-through;
  }

  .btn-dark {
    background-color: black;
    border: none;
  }

  .btn-dark:hover {
    background-color: #333;
  }

  .badge {
    padding: 5px 10px;
    font-weight: 600;
    background-color: black;
    color: white;
    border-radius: 4px;
    font-size: 12px;
    min-width: 22px;
    min-height: 22px;
    text-transform: uppercase;
    text-align: center;
  }

  h4.mb-2 {
    font-size: 35px;
    color: black;
    font-weight: 600;
    line-height: 1.4;
  }

  li.list-inline-item {
    margin: 0;
  }

  i.bi.bi-star-fill {
    color: #ff8a00;
  }

  span.text-secondary.me-2 {
    color: black !important;
  }

  a {
    text-decoration: none;
    color: black;
  }

  h5 {
    font-size: 15px;
    margin-top: 2vh !important;
  }

  .form-label {
    font-size: 16px;
    font-weight: 800;
    margin-bottom: 10px;
    display: block;
  }

  .price {
    margin-bottom: 0;
    font-size: 24px;
    font-weight: bold;
  }

  .price del {
    font-size: 18px;
    opacity: .6;
    color: #5E626F;
  }

  .product-num {
    display: flex;
    align-items: center;

    gap: 3vh !important;
  }

  .product-btn {
    margin: 5px;
    border-radius: 10px;
    padding: 12px 20px;
  }

  .size-btn {

    border: 2px solid black;

    height: 34px;
    width: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 1px solid;
    border-radius: 50%;
    margin-right: 10px;
  }

  .color-circle {
    display: inline-block;
    width: 1.125em;
    height: 1.125em;
    border-radius: 50%;
    margin-right: 10px;
    cursor: pointer;
    border: 2px solid transparent;

  }

  .color-circle.active {
    border-color: black;
  }

  .form-check {
    display: inline-block;
    padding: 0;
    margin: 0;
  }

  .form-check-input {
    display: none;
  }

  .form-check-input:checked+.color-circle {
    border-color: black;
  }

  .product-image {
    background-size: cover;
    background-position: top center;
    background-repeat: no-repeat;
    border-radius: 20px;
    height: 82vh;
    padding: 25px;
    position: sticky;
    top: 0;
  }

  .thumbnail-item {
    height: 70px;
    width: 70px !important;
    min-width: 70px !important;
    background: white;
    border-radius: 10px;
    border: 2px solid black;
    cursor: pointer;
  }

  .product-details {
    padding-top: 50px;
    max-width: 660px;
    padding-left: 40px;
  }

  .social-media {
    position: absolute;
    top: 250px;
    right: 25px;
    z-index: 1;
  }

  .social-media ul {
    display: flex;
    align-items: center;
    flex-direction: column;
    list-style: none;
  }

  .social-media ul li {
    padding: 20px 0;
    writing-mode: tb-rl;
  }

  .social-media ul li a {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: black;
  }

  .quantity-btn {
    padding: 0;
    height: 34px;
    width: 34px;
    line-height: 36px;
    font-size: 15px;
    font-weight: 400;
    background-color: transparent !important;
    justify-content: center;
    border: 1px solid;
    color: black;
    margin-right: 10px !important;
    border-radius: 50% !important;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .quantity-display {
    height: 34px;
    width: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 1px solid;
    border-radius: 50%;
    margin-right: 10px;
  }

  .dark-btn {
    background: black !important;
    color: white;
  }

  @media (max-width: 768px) {
    .thumbnails {
      flex-direction: row !important;
      justify-content: center;
      margin-bottom: 1rem;
    }

    .thumbnail-img {
      width: 60px;
      height: 80px;
    }
  }
</style>
<style>
  .size-btn {
    border: 1px solid #ccc;
    padding: 8px 16px;
    cursor: pointer;
    background-color: transparent;
    color: #000;
  }

  .btn-check:checked+.size-btn {
    background-color: #000;
    color: #fff;
    border-color: #000;
  }

  .product-size {
    display: flex;
    gap: 10px;
  }
</style>
<style>
  @media screen and (max-width: 768px) {
    .col-lg-6.product-image {
      position: unset;
    }

    .product-num.gap-md-2.gap-xl-0.mt-3.mb-3 {
      flex-direction: column;
      justify-content: inherit;
      align-items: baseline;
    }

    .col-lg-6.product-details {
      max-width: 100%;
      padding: 25px;
    }

    .d-flex.thumbnails.flex-column.gap-3.mb-3 {
      flex-direction: column !important;
    }

    .col-lg-6.product-image {
      margin: 20px;
      width: -webkit-fill-available;
      height: 65vh;
    }

    .social-media {
      display: none;
    }

    .thumbnail-item {
      height: 55px !important;
      width: 55px !important;
      min-width: 55px !important;
    }

    .d-flex.align-items-center.mb-2 {
      display: block !important;
    }
  }
</style>
<!--  -->

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../style/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">

</head>

<body>



  <main>
    <section class="viwe_product">
      <img id="mainImage" class="img_co" src="<?= $image_path ?>" alt="<?= $name ?>">

      <div class="pric_viwe">








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

  <style>
    input#quantity {
      width: 50px !important;
      height: 50px;
    }

    input#quantity {
      padding: 0;
      /* height: 34px; */
      width: 34px;
      line-height: 36px;
      font-size: 15px;
      /* font-weight: 400; */
      background-color: transparent !important;
      justify-content: center;
      border: 1px solid;
      color: black;
      /* margin-right: 10px !important; */
      border-radius: 50% !important;
      display: flex;
      align-items: center;
      justify-content: center;
      border: black 1px solid;
    }
  </style>
</body>

</html>
<style>
  .info-label {
    font-weight: 500;
    color: #555;
  }

  .info-value {
    font-weight: 500;
    text-align: right;
  }

  .info-row {
    border-bottom: 1px solid #ddd;
    padding: 10px 0;
  }
</style>