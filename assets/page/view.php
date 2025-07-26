<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true
]);

require('./db.php');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/glamora/";

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

// الصورة الأساسية للمنتج
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

$color_images = [];
foreach ($colors as $color) {
  $color_code = strtolower(str_replace('#', '', $color['hex']));
  $color_images[$color_code] = [];
  foreach ($gallery as $img) {
    if (strpos($img, $color_code) !== false) {
      $color_images[$color_code][] = $img;
    }
  }
  if (empty($color_images[$color_code])) {
    $color_images[$color_code] = $gallery;
  }
}

$current_color = !empty($colors) ? strtolower(str_replace('#', '', $colors[0]['hex'])) : '';
$current_images = !empty($current_color) ? $color_images[$current_color] : $gallery;
$current_color_image = !empty($colors[0]['image']) ?
  (str_starts_with($colors[0]['image'], 'http') ? $colors[0]['image'] : $base_url . 'dashboard/' . ltrim($colors[0]['image'], './')) :
  $image_path;

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
  <style>
    #mainImageContainer {
      transition: background-image 0.3s ease;
    }

    .color-option .color-wrapper {
      border-radius: 50%;
      padding: 2px;
    }

    .color-option .color-wrapper.active {
      border: 2px solid #000;
    }

    .color-circle {
      display: block;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <?php require('./header.php'); ?>
  <div class="container py-5">
    <div class="d-sm-flex justify-content-between container-fluid py-3">
      <nav aria-label="breadcrumb" class="breadcrumb-row">
        <ul class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="/">Home</a></li>
          <li class="breadcrumb-item">Product Thumbnail</li>
        </ul>
      </nav>
    </div>
    <div class="row gy-4">
      <div class="col-lg-6 product-image position-relative" id="mainImageContainer"
        style="background-image: url('<?php echo $image_path; ?>'); background-size: cover; background-position: center;">
        <button type="button" class="btn btn-light position-absolute top-0 end-0 m-3 rounded-circle shadow"
          data-bs-toggle="modal" data-bs-target="#imageModal">
          <i class="bi bi-arrows-fullscreen fs-4"></i>
        </button>

        <div class="d-flex thumbnails flex-column gap-3 mb-3" id="thumbnailsContainer">
          <?php $all_images = array_unique(array_merge([$image_path], $gallery)); ?>
          <?php foreach ($all_images as $index => $img): ?>
            <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>"
              style="background-image: url('<?php echo htmlspecialchars($img); ?>'); background-size: cover; background-position: center; cursor: pointer; width: 70px; height: 70px;"
              onclick="changeMainImage(this, '<?php echo htmlspecialchars($img); ?>')">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0">
              <div id="carouselImages" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                  <?php foreach ($all_images as $index => $img): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                      <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100" alt="Product Image">
                    </div>
                  <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselImages"
                  data-bs-slide="prev">
                  <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselImages"
                  data-bs-slide="next">
                  <span class="carousel-control-next-icon"></span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 product-details">
        <div class="social-media">
          <ul>
            <li><a href="https://www.instagram.com/dexignzone/">Instagram</a></li>
            <li><a href="https://www.facebook.com/dexignzone">Facebook</a></li>
            <li><a href="https://twitter.com/dexignzones">Twitter</a></li>
          </ul>
        </div>

        <?php if ($on_sale): ?>
          <span class="badge bg-black mb-2">SALE <?php echo htmlspecialchars($discount); ?>% Off</span>
        <?php endif; ?>

        <h4 class="mb-2"><?php echo htmlspecialchars($name); ?></h4>

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

        <h5><?php echo htmlspecialchars($description); ?></h5>
        <p class="mb-4">It is the perfect tee for any occasion.</p>

        <div class="meta-content m-b20">
          <span class="form-label">Price</span>
          <span class="price">$<?php echo formatPrice($final_price); ?>
            <?php if ($on_sale): ?>
              <del>$<?php echo formatPrice($price); ?></del>
            <?php endif; ?>
          </span>
        </div>

        <div class="product-num gap-md-2 gap-xl-0 mt-3 mb-3">
          <div class="btn-quantity light">
            <label class="form-label">Quantity</label>
            <div class="d-flex justify-content-center align-items-center">
              <button class="btn btn-danger btn-number quantity-btn dark-btn" data-type="minus">-</button>
              <input class="quantity-display" type="text" id="quantity" name="quantity" value="1" min="1"
                max="<?php echo htmlspecialchars($quantity); ?>" readonly>
              <button class="btn btn-success btn-number quantity-btn dark-btn" data-type="plus">+</button>
            </div>
          </div>

          <?php if (!empty($sizes)): ?>
            <div class="d-block">
              <label class="form-label">Size</label>
              <div class="btn-group product-size grid-media m-0">
                <?php foreach ($sizes as $index => $size): ?>
                  <input type="radio" class="btn-check" id="btnradio<?php echo (100 + $index); ?>" name="product_size"
                    autocomplete="off" data-price="<?php echo formatPrice($size['price'] ?? 0); ?>"
                    data-size-id="<?php echo htmlspecialchars($size['id'] ?? ''); ?>"
                    data-size-name="<?php echo htmlspecialchars($size['name'] ?? ''); ?>" <?php echo ($index === 0) ? 'checked' : ''; ?>>
                  <label class="size-btn" for="btnradio<?php echo (100 + $index); ?>">
                    <?php echo htmlspecialchars($size['name'] ?? ''); ?>
                    <?php if (isset($size['price']) && $size['price'] > 0): ?>
                      <span class="size-price">+$<?php echo formatPrice($size['price']); ?></span>
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="meta-content">
            <label class="form-label">Color</label>
            <?php if (!empty($colors)): ?>
              <div class="d-flex align-items-center color-filter flex-wrap gap-2" id="colorOptions">
                <?php foreach ($colors as $index => $color):
                  $color_code = isset($color['hex']) ? strtolower(str_replace('#', '', $color['hex'])) : '';
                  $color_name = isset($color['name']) ? htmlspecialchars($color['name']) : '';
                  $color_image = '';
                  if (!empty($color['image'])) {
                    $color_image = (strpos($color['image'], 'http') === 0) ? $color['image'] : $base_url . 'dashboard/' . ltrim($color['image'], '/');
                  }
                  ?>
                  <div class="color-option" style="cursor: pointer;" title="<?php echo $color_name; ?>">
                    <div class="color-wrapper p-1" style="transition: 0.3s;">
                      <span class="color-circle"
                        style="background-color:<?php echo htmlspecialchars($color['hex'] ?? '#ccc'); ?>;"
                        data-color-id="<?php echo htmlspecialchars($color['id'] ?? ''); ?>"
                        data-color-name="<?php echo $color_name; ?>"
                        data-image="<?php echo htmlspecialchars($color_image); ?>"
                        data-color-code="<?php echo $color_code; ?>"></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="d-flex gap-2 mb-4">
          <button class="btn btn-dark product-btn w-50" onclick='addToCart(<?php echo $product["id"]; ?>)'>Add to
            Cart</button>
          <button class="btn btn-outline-dark product-btn w-50">Add to Wishlist</button>
        </div>

        <hr>

        <p><strong>SKU:</strong> PRT584E63A</p>
        <p><strong>Category : </strong> <?php echo htmlspecialchars($parent_category); ?> /
          <?php echo htmlspecialchars($category_name); ?>
        </p>
        <p><strong>Tags : </strong> <?php echo htmlspecialchars($tags); ?></p>

        <div class="d-flex flex-wrap gap-3 fs-5 mt-4">
          <a href="#"><i class="bi bi-facebook"></i> Facebook</a>
          <a href="#"><i class="bi bi-instagram"></i> Instagram</a>
        </div>

        <div class="mt-4 d-flex flex-wrap gap-4">
          <div class="d-flex align-items-center">
            <img src="https://img.icons8.com/ios/50/shipped.png" width="24" alt="Shipping icon">
            <span class="ms-2">Free Shipping</span>
          </div>
          <div class="d-flex align-items-center">
            <img src="https://img.icons8.com/ios/50/return.png" width="24" alt="Return icon">
            <span class="ms-2">30 Days Easy Return</span>
          </div>
        </div>
      </div>
    </div>

    <div class="container my-4 mt-5">
      <div class="d-flex align-items-center text-center">
        <div class="flex-grow-1 border-bottom"></div>
        <span class="px-3 py-1 bg-dark text-white rounded-pill mx-2">ammar</span>
        <div class="flex-grow-1 border-bottom"></div>
      </div>
    </div>

    <style>
      .info-box {
        border: 1px solid #000;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        text-align: center;
        height: 100%;
      }

      .info-box h6 {
        font-weight: bold;
      }

      .model-img {
        border-radius: 15px;
        background-size: cover;
        background-position: center;
        width: 100%;
        height: 500px;
        background: #fafafa;
      }

      .thumb {
        width: 80px;
        height: 120px;
        border-radius: 10px;
        background-size: cover;
        background-position: center;
        cursor: pointer;
      }

      .small-thumbs {
        display: flex;
        gap: 10px;
      }

      .thumb {
        background: #fafafa;
      }

      .info-box {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
      }

      .info-box p {
        margin: 0;
        padding: 0;
        font-size: 14px;
        font-weight: 400;
        color: #5E626F;
        margin-bottom: 0;
      }

      @media screen and (max-width:767px) {
        .product-num.gap-md-2.gap-xl-0.mt-3.mb-3 {
          display: grid;
          grid-template-columns: 1fr 1fr;
          align-items: center;
        }
      }
    </style>

    <div class="mt-3 ">
      <div class="row">
        <div class="col-lg-7  ">
          <h2 class="fw-bold mt-3 mb-3">Fits Women</h2>
          <p>Designed for superior child comfort, OneFit™ provides extra rear-facing legroom and multiple recline
            options in every mode of use. With the widest range of height adjustments, the easy-adjust headrest system
            adjusts with the harness to grow with your child. OneFit™ accommodates tiny passengers from the very start
            with a removable head and body support insert for newborns weighing 5-11 lbs.</p>

          <h5 class="fw-bold mt-4">color</h5>
          <ul class="list-unstyled">
            <li>
              <?php if (!empty($colors)): ?>
                <?php
                $color_names = array_map(function ($color) {
                  return isset($color['name']) ? htmlspecialchars($color['name']) : '';
                }, $colors);
                echo implode(' / ', array_filter($color_names));
                ?>
              <?php else: ?>
                N/A
              <?php endif; ?>
            </li>
            <li>Assembled Product Weight: 25 lbs.</li>
          </ul>

          <div class="row">
            <div class="bg-white mt-5">
              <div class="row info-row">
                <div class="col-6 info-label">Brand</div>
                <div class="col-6 info-value"><?= $brand ?></div>
              </div>
              <div class="row info-row">
                <div class="col-6 info-label">Barcode</div>
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
                <div class="col-6 info-value"><?= $is_new ?></div>
              </div>
              <div class="row info-row">
                <div class="col-6 info-label">Featured</div>
                <div class="col-6 info-value"><?= $is_featured ?></div>
              </div>
              <div class="row info-row">
                <div class="col-6 info-label">Quantity</div>
                <div class="col-6 info-value"><?= $quantity ?></div>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <div class="row g-2">
              <?php foreach ($all_images as $img): ?>
                <div class="col-6 col-sm-4 col-lg-3">
                  <div class="thumb w-100"
                    style="background-image: url('<?php echo htmlspecialchars($img); ?>'); height: 120px; background-size: cover; background-position: center;">
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <style>
            .thumb.w-100 {
              height: 200px !important;
              background-size: cover;
              background-position: center;
              border-radius: 6px;
            }
          </style>
        </div>

        <div class="col-lg-5">
          <div class="row g-2 mb-4">
            <div class="col-12 col-sm-6 col-md-6">
              <div class="info-box">
                <h6>All-in-One Dress</h6>
                <p>Lorem Ipsum is simply dummy text </p>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6">
              <div class="info-box">
                <h6>Looking wise good</h6>
                <p>Lorem Ipsum is simply dummy text </p>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6">
              <div class="info-box">
                <h6>100% Made In India</h6>
                <p>Lorem Ipsum is simply dummy text </p>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6">
              <div class="info-box">
                <h6>100% Cotton</h6>
                <p>Lorem Ipsum is simply dummy text </p>
              </div>
            </div>
          </div>

          <div class="model-img position-relative"
            style="background-image: url('<?php echo $image_path; ?>'); background-size: cover; background-position: center; min-height: 400px;">
          </div>
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
                  $sim['image']
                  : $base_url . 'dashboard/' . ltrim($sim['image'], './')) :
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
    document.addEventListener("DOMContentLoaded", function () {
      // Initialize Owl Carousel
      const owl = $('.js-home-products');
      if (owl.hasClass('owl-loaded')) {
        owl.trigger('destroy.owl.carousel');
        owl.html(owl.find('.owl-stage-outer').html()).removeClass('owl-loaded');
      }
      owl.owlCarousel({
        loop: false,
        margin: 10,
        nav: true,
        dots: false,
        responsive: {
          0: { items: 2 },
          600: { items: 2 },
          1000: { items: 4 }
        }
      });

      const colorOptions = document.querySelectorAll('.color-option');
      const mainImageContainer = document.getElementById('mainImageContainer');
      const originalImage = '<?php echo $image_path; ?>';
      let selectedColor = null;

      // Function to handle color selection
      function handleColorSelection(colorOption) {
        const colorCircle = colorOption.querySelector('.color-circle');
        const colorWrapper = colorOption.querySelector('.color-wrapper');
        const colorImage = colorCircle.getAttribute('data-image');
        const colorCode = colorCircle.getAttribute('data-color-code');

        // If this color is already selected, deselect it
        if (selectedColor === colorCode) {
          colorWrapper.classList.remove('active');
          colorCircle.classList.remove('active');
          mainImageContainer.style.backgroundImage = `url('${originalImage}')`;
          selectedColor = null;
          return;
        }

        // Deselect all other colors
        document.querySelectorAll('.color-wrapper').forEach(wrapper => {
          wrapper.classList.remove('active');
        });
        document.querySelectorAll('.color-circle').forEach(circle => {
          circle.classList.remove('active');
        });

        // Select this color
        colorWrapper.classList.add('active');
        colorCircle.classList.add('active');

        // Update main image
        if (colorImage) {
          mainImageContainer.style.backgroundImage = `url('${colorImage}')`;
        } else {
          mainImageContainer.style.backgroundImage = `url('${originalImage}')`;
        }

        selectedColor = colorCode;
      }

      // Attach click event to color options
      colorOptions.forEach(option => {
        option.addEventListener('click', function () {
          handleColorSelection(this);
        });
      });

      // Quantity control
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
    });

    function changeMainImage(el, imgUrl) {
      document.getElementById('mainImageContainer').style.backgroundImage = `url('${imgUrl}')`;
      const allThumbnails = document.querySelectorAll('.thumbnail-item');
      allThumbnails.forEach(thumbnail => thumbnail.classList.remove('active'));
      el.classList.add('active');
    }

    function addToCart(id) {
      const qty = parseInt(document.getElementById('quantity').value) || 1;
      const size = $('.btn-check:checked').data('size-id');
      const sizeName = $('.btn-check:checked').data('size-name');

      // Get selected color data
      let colorId = null;
      let colorName = null;
      let colorImage = null;
      const activeColorCircle = document.querySelector('.color-circle.active');
      if (activeColorCircle) {
        colorId = activeColorCircle.getAttribute('data-color-id');
        colorName = activeColorCircle.getAttribute('data-color-name');
        colorImage = activeColorCircle.getAttribute('data-image');
      }

      const formData = new FormData();
      formData.append('csrf_token', '<?= $_SESSION["csrf_token"] ?? "" ?>');
      formData.append('product_id', id);
      formData.append('quantity', qty);

      if (size) {
        formData.append('size_id', size);
        formData.append('size_name', sizeName);
      }
      if (colorId) {
        formData.append('color_id', colorId);
        formData.append('color_name', colorName);
        formData.append('color_image', colorImage);
      }

      fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            updateCartCount(data.cart_count);
            showSuccessMessage(data.message || 'Product added to cart successfully');
          } else {
            showErrorMessage(data.message || 'Failed to add product to cart');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showErrorMessage('Network error occurred');
        });
    }

    function addQuickToCart(id) {
      const formData = new URLSearchParams();
      formData.append('csrf_token', '<?= $_SESSION["csrf_token"] ?? "" ?>');
      formData.append('product_id', id);
      formData.append('quantity', 1);

      fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            updateCartCount(data.cart_count);
            showSuccessMessage(data.message || 'Product added to cart');
          } else {
            showErrorMessage(data.message || 'Failed to add product');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showErrorMessage('Connection problem');
        });
    }

    function updateCartCount(count) {
      const cartCounter = document.querySelector('.cart-count');
      if (cartCounter) {
        cartCounter.textContent = count;
        cartCounter.style.display = count > 0 ? 'inline-block' : 'none';
      }
    }

    function showSuccessMessage(message) {
      const toast = document.createElement('div');
      toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
      toast.style.zIndex = '9999';
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    }

    function showErrorMessage(message) {
      const toast = document.createElement('div');
      toast.className = 'alert alert-danger position-fixed top-0 end-0 m-3';
      toast.style.zIndex = '9999';
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    }
  </script>

  <?php require('./footer.php'); ?>
</body>

</html>