<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true,
  'cookie_samesite' => 'Strict'
]);

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once('./config/db.php');
$imagePath = './dashboard/';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($conn) {
  $check_stmt = $conn->prepare("SELECT id FROM site_visits WHERE ip_address = ? AND DATE(visit_time) = CURDATE()");
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
    $insert_stmt->bind_param("ss", $ip, $country);
    $insert_stmt->execute();
    $insert_stmt->close();
  }
  $check_stmt->close();
} else {
  header("HTTP/1.1 500 Internal Server Error");
  exit("Database connection failed");
}

function sanitize_output($data)
{
  return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo sanitize_output($_SESSION['csrf_token']); ?>">
  <title>GLAMORA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"
    integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="./assets/css/main.css">
</head>

<body>
  <canvas id="message"></canvas>
  <?php require('./includes/loding.php'); ?>
  <div class="_Tiket">
    <p>GLAMORA</p>
  </div>
  <section id="lod_file">
    <?php require('./includes/header.php'); ?>

    <div class="slider owl-carousel">
      <?php
      $sliderLimit = 10;

      $stmtSlider = $conn->prepare("SELECT * FROM sliders ORDER BY id DESC LIMIT ?");
      $stmtSlider->bind_param("i", $sliderLimit);
      $stmtSlider->execute();
      $selectSlider = $stmtSlider->get_result();

      if ($selectSlider->num_rows > 0) {
        while ($fetchSlider = $selectSlider->fetch_assoc()) {
          $photo = ltrim($fetchSlider['image_url'] ?? '', './');
          $photoPath = sanitize_output($imagePath . $photo);
          $link = sanitize_output($fetchSlider['link_url'] ?? '#');

          echo '<a class="slider-item" href="' . $link . '" target="_blank">
                <div class="banner-content p-5 add_link main_slider" style="background-image: url(\'' . $photoPath . '\');"></div>
              </a>';
        }
      } else {
        $defaultPhoto = sanitize_output($imagePath . 'default-banner.jpg');
        echo '<a class="slider-item" href="#">
            <div class="banner-content p-5 add_link" style="background-image: url(\'' . $defaultPhoto . '\');"></div>
          </a>';
      }
      $stmtSlider->close();
      ?>
    </div>

    <div class="Categories_ads owl-carousel">
      <?php
      $sqlcat = $conn->prepare("SELECT * FROM categories WHERE parent_id = 0 OR parent_id IS NULL LIMIT 8");
      $sqlcat->execute();
      $result = $sqlcat->get_result();

      while ($fetchcat = $result->fetch_assoc()) {
        $image = sanitize_output($fetchcat['image'] ?? 'default.jpg');
        $name = sanitize_output($fetchcat['name'] ?? '');
        $id = (int) $fetchcat['id'];
        echo '<div class="main_cat item">
                          <a href="./pages/category.php?id=' . $id . '">
                              <div class="_Categories_img" style="background-image: url(\'' . $imagePath . $image . '\');" onerror="this.style.backgroundImage=\'url(default.jpg)\'"></div>
                              <h2>' . $name . '</h2>
                          </a>
                        </div>';
      }
      $sqlcat->close();
      ?>
    </div>

    <main>
      <?php
      $mainCategoriesQuery = $conn->prepare("SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0");
      $mainCategoriesQuery->execute();
      $mainCategories = $mainCategoriesQuery->get_result();

      while ($mainCategory = $mainCategories->fetch_assoc()) {
        $catidneed = (int) $mainCategory['id'];
        $namecat = sanitize_output($mainCategory['name']);

        $stmtAds = $conn->prepare("SELECT * FROM ads WHERE categoryid = ?");
        $stmtAds->bind_param("i", $catidneed);
        $stmtAds->execute();
        $selectad = $stmtAds->get_result();

        if ($selectad->num_rows > 0) {
          echo '<div class="slider owl-carousel">';
          while ($fetchad = $selectad->fetch_assoc()) {
            $photo = ltrim($fetchad['photo'] ?? '', './');
            $photoPath = sanitize_output($imagePath . $photo);
            $link = sanitize_output($fetchad['linkaddress'] ?? '#');
            echo '<a class="slider-item" href="' . $link . '">
                              <div class="banner-content p-5 add_link" style="background-image: url(\'' . $photoPath . '\');"></div>
                            </a>';
          }
          echo '</div>';
        }

        $productQuery = "SELECT * FROM products WHERE category_id = ? OR category_id IN (SELECT id FROM categories WHERE parent_id = ?)";
        $stmtProducts = $conn->prepare($productQuery);
        $stmtProducts->bind_param("ii", $catidneed, $catidneed);
        $stmtProducts->execute();
        $selectproduct = $stmtProducts->get_result();

        if ($selectproduct->num_rows > 0) {
          echo '<section class="container__">
                          <div class="row">
                            <a href="./pages/categories.php?id=' . $catidneed . '" class="btn-link text-decoration-none">
                              <h3 class="title">' . $namecat . '</h3>
                            </a>
                            <div class="slider-container">
                              <div class="owl-carousel js-home-products">';

          while ($fetchproducts = $selectproduct->fetch_assoc()) {
            $productId = (int) $fetchproducts['id'];
            $productName = sanitize_output($fetchproducts['name'] ?? '');
            $productImage = sanitize_output($imagePath . ltrim($fetchproducts['image'] ?? 'default.jpg', './'));
            $price = (float) ($fetchproducts['price'] ?? 0);
            $salePrice = isset($fetchproducts['sale_price']) ? (float) $fetchproducts['sale_price'] : null;
            $discountPercent = (int) ($fetchproducts['discount_percent'] ?? 0);
            $finalPrice = $salePrice ?? ($price - ($price * $discountPercent / 100));
            ?>
            <div class="item">
              <a href="./pages/view.php?php echo $productId; ?>" title="<?php echo $productName; ?>">
                <figure class="bg_img" style="background-image: url('<?php echo $productImage; ?>');">
                  <?php if ($discountPercent > 0): ?>
                    <span class="badge bg-success text"><?php echo $discountPercent; ?>%</span>
                  <?php endif; ?>
                  <?php if (!empty($fetchproducts['is_featured'])): ?>
                    <span class="badge bg-success text">Featured</span>
                  <?php endif; ?>
                  <?php if (!empty($fetchproducts['is_new'])): ?>
                    <span class="badge bg-success text">New</span>
                  <?php endif; ?>
                </figure>
              </a>

              <div class="product-info">
                <span class="text-muted small"><?php echo sanitize_output($fetchproducts['brand'] ?? ''); ?></span><br>
                <span class="snize-title">
                  <?php echo $productName; ?>
                </span>
              </div>

              <div class="flex_pric playSound" onclick="addcart(<?php echo $productId; ?>)">
                <button class="d-flex align-items-center nav-link click">Add to Cart</button>
                <div class="block_P">
                  <span class="price text"><?php echo number_format($finalPrice, 2); ?></span><span>EGP</span>
                </div>
              </div>

              <div class="ptn_" style="display:none;">
                <div class="input-group product-qty">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                      <svg width="16" height="16" fill="currentColor" class="bi bi-dash">
                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" />
                      </svg>
                    </button>
                  </span>
                  <input type="text" name="quantity" class="form-control input-number quantity<?php echo $productId; ?>"
                    value="1">
                  <span class="input-group-btn">
                    <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
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
          echo '</div>
                        </div>
                      </div>
                    </section>';
        }
      }
      ?>
    </main>
    <?php require('./includes/footer.php'); ?>
  </section>


  <script>
    document.addEventListener("DOMContentLoaded", function () {
      document.querySelectorAll('.text').forEach(el => {
        el.textContent = el.textContent.split('.')[0];
      });

      document.body.addEventListener('click', function (e) {
        if (e.target.closest('.playSound')) {
          const audio = document.getElementById("audio");
          audio.currentTime = 0;
          audio.play();
          if (navigator.vibrate) navigator.vibrate(200);
        }
      });

      const message = document.getElementById("message");
      if (!localStorage.getItem("messageDisplayed")) {
        localStorage.setItem("messageDisplayed", "true");
        setTimeout(() => {
          message.style.opacity = "0";
          setTimeout(() => message.style.display = "none", 500);
        }, 10000);
      } else {
        message.style.display = "none";
      }

      const lod_file = document.getElementById('lod_file');
      const loading = document.getElementById('loading');
      if (lod_file && loading) {
        lod_file.style.display = 'block';
        loading.style.display = 'none';
      }
    });

    function loadCart() {
      fetch("./pages/show_cart.php")
        .then(response => {
          if (!response.ok) throw new Error('Network error');
          return response.text();
        })
        .then(html => {
          document.getElementById('offcanvasCart').innerHTML = html;
        })
        .catch(error => console.error('Error:', error));
    }

    function addcart(productid) {
      const quantity = document.querySelector('.quantity' + productid)?.value || 1;
      const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

      fetch("./pages/add_cart.php", {
        method: "POST",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productid}&quantity=${quantity}&csrf_token=${encodeURIComponent(csrfToken)}`
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) loadCart();
          else console.error(data.message);
        })
        .catch(error => console.error('Error:', error));
    }

    function addmoreone(id) {
      fetch("./pages/addmoreone.php", {
        method: "POST",
        body: `id=${id}`
      })
        .then(loadCart)
        .catch(error => console.error('Error:', error));
    }

    function removemoreone(id) {
      fetch("./pages/removemoreone.php", {
        method: "POST",
        body: `id=${id}`
      })
        .then(loadCart)
        .catch(error => console.error('Error:', error));
    }

    function removecart(id) {
      fetch("./pages/remove_cart.php", {
        method: "POST",
        body: `id=${id}`
      })
        .then(loadCart)
        .catch(error => console.error('Error:', error));
    }

    document.addEventListener("DOMContentLoaded", loadCart);
    $(document).ready(function () {
      var categoriesItems = $(".Categories_ads .item").length;

      $(".Categories_ads").owlCarousel({
        loop: categoriesItems > 1,
        margin: 10,
        nav: categoriesItems > 1,
        responsive: {
          0: { items: Math.min(3, categoriesItems) },
          600: { items: Math.min(3, categoriesItems) },
          1000: { items: Math.min(5, categoriesItems) }
        }
      });

      $(".js-home-products").owlCarousel({
        loop: false,
        margin: 10,
        nav: true,
        autoplay: true,
        autoplayTimeout: 3000,
        responsive: {
          0: { items: 2 },
          600: { items: 3 },
          1000: { items: 5 }
        }
      });

      $(".slider.owl-carousel").owlCarousel({
        items: 1,
        loop: true,
        autoplay: true,
        autoplayTimeout: 5000
      });
    });
  </script>
  <audio id="audio" src="./audio/like.mp3"></audio>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
  <style>

  </style>
</body>

</html>