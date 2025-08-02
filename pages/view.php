<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true,
  'cookie_samesite' => 'Lax'
]);

require('../config/db.php');

$base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://");
$base_url .= htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') . "/glamora/";

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
  header('Location: ./index.php');
  exit();
}

try {
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
} catch (Exception $e) {
  error_log("Database error: " . $e->getMessage());
  header('Location: ./error.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['delete_comment'])) {
    if (!isset($_SESSION['user_id'])) {
      die("Unauthorized access");
    }

    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
      die("Invalid CSRF token");
    }

    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    if (!$comment_id) {
      die("Invalid comment ID");
    }

    try {
      $check_stmt = $conn->prepare("
        SELECT user_id FROM product_comments 
        WHERE id = ? AND (user_id = ? OR ? IN (SELECT id FROM users WHERE is_admin = 1))
      ");
      $user_id = $_SESSION['user_id'];
      $check_stmt->bind_param("iii", $comment_id, $user_id, $user_id);
      $check_stmt->execute();
      $check_result = $check_stmt->get_result();

      if ($check_result->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM product_comments WHERE id = ?");
        $delete_stmt->bind_param("i", $comment_id);
        if ($delete_stmt->execute()) {
          $_SESSION['message'] = "Comment deleted successfully";
        } else {
          $_SESSION['error'] = "Error deleting comment";
        }
      } else {
        die("You don't have permission to delete this comment");
      }
    } catch (Exception $e) {
      error_log("Comment deletion error: " . $e->getMessage());
      $_SESSION['error'] = "A system error occurred";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
  }

  if (isset($_POST['submit_comment'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
      die("Invalid CSRF token");
    }

    if (!isset($_SESSION['user_id'])) {
      die("You must be logged in to post a comment");
    }

    $comment = trim(htmlspecialchars($_POST['comment'] ?? '', ENT_QUOTES, 'UTF-8'));
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, [
      'options' => ['min_range' => 1, 'max_range' => 5]
    ]);

    $user_id = $_SESSION['user_id'];

    try {
      $user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
      $user_stmt->bind_param("i", $user_id);
      $user_stmt->execute();
      $user_result = $user_stmt->get_result();
      $user = $user_result->fetch_assoc();

      if (!$user) {
        die("User not found");
      }

      $name = $user['name'];
      $email = $user['email'];
    } catch (Exception $e) {
      error_log("User fetch error: " . $e->getMessage());
      die("Error fetching user details");
    }

    if (empty($comment) || !$rating) {
      $comment_error = "Please fill all required fields and provide a valid rating.";
    } elseif (strlen($comment) > 1000) {
      $comment_error = "Comment is too long. Maximum 1000 characters allowed.";
    } else {
      try {
        $stmt = $conn->prepare("
          INSERT INTO product_comments 
          (product_id, user_id, name, email, comment, rating, status) 
          VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iissii", $id, $user_id, $name, $email, $comment, $rating);

        if ($stmt->execute()) {
          $comment_success = "Thank you for your comment! It will be reviewed before publishing.";
        } else {
          $comment_error = "Error submitting your comment. Please try again.";
        }
      } catch (Exception $e) {
        error_log("Comment submission error: " . $e->getMessage());
        $comment_error = "A system error occurred. Please try again later.";
      }
    }
  }

  if (isset($_POST['submit_reply'])) {
    if (!isset($_SESSION['user_id'])) {
      die("You must be logged in to post a reply");
    }

    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
      die("Invalid CSRF token");
    }

    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    $reply_text = trim(htmlspecialchars($_POST['reply_text'] ?? '', ENT_QUOTES, 'UTF-8'));

    if (!$comment_id || empty($reply_text)) {
      die("Invalid input");
    }

    try {
      $stmt = $conn->prepare("
        INSERT INTO comment_replies 
        (comment_id, user_id, reply_text, status) 
        VALUES (?, ?, ?, 'pending')
      ");
      $stmt->bind_param("iis", $comment_id, $_SESSION['user_id'], $reply_text);

      if ($stmt->execute()) {
        $_SESSION['message'] = "Reply submitted successfully! It will be reviewed before publishing.";
      } else {
        $_SESSION['error'] = "Error submitting your reply.";
      }
    } catch (Exception $e) {
      error_log("Reply submission error: " . $e->getMessage());
      $_SESSION['error'] = "A system error occurred. Please try again later.";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
  }

  if (isset($_POST['like_comment'])) {
    if (!isset($_SESSION['user_id'])) {
      die(json_encode(['status' => 'error', 'message' => 'Login required']));
    }

    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
      die(json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']));
    }

    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    if (!$comment_id) {
      die(json_encode(['status' => 'error', 'message' => 'Invalid comment ID']));
    }

    try {
      $check_stmt = $conn->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
      $check_stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);
      $check_stmt->execute();
      $result = $check_stmt->get_result();

      if ($result->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);
        $delete_stmt->execute();
        $action = 'unliked';
      } else {
        $insert_stmt = $conn->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);
        $insert_stmt->execute();
        $action = 'liked';
      }

      $count_stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM comment_likes WHERE comment_id = ?");
      $count_stmt->bind_param("i", $comment_id);
      $count_stmt->execute();
      $count_result = $count_stmt->get_result();
      $count_data = $count_result->fetch_assoc();

      echo json_encode([
        'status' => 'success',
        'action' => $action,
        'like_count' => $count_data['like_count']
      ]);
      exit();
    } catch (Exception $e) {
      error_log("Like error: " . $e->getMessage());
      die(json_encode(['status' => 'error', 'message' => 'System error']));
    }
  }
}

try {
  $comments_stmt = $conn->prepare("
    SELECT pc.*, u.profile_image, u.id as user_id,
    (SELECT COUNT(*) FROM comment_likes WHERE comment_id = pc.id) as like_count
    FROM product_comments pc
    LEFT JOIN users u ON pc.user_id = u.id
    WHERE pc.product_id = ? AND pc.status = 'approved'
    ORDER BY pc.created_at DESC
  ");
  $comments_stmt->bind_param("i", $id);
  $comments_stmt->execute();
  $comments_result = $comments_stmt->get_result();
  $comments = $comments_result->fetch_all(MYSQLI_ASSOC);

  foreach ($comments as &$comment) {
    $comment_id = $comment['id'];
    if (isset($_SESSION['user_id'])) {
      $like_stmt = $conn->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
      $like_stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);
      $like_stmt->execute();
      $like_result = $like_stmt->get_result();
      $comment['user_liked'] = $like_result->num_rows > 0;
    } else {
      $comment['user_liked'] = false;
    }

    $replies_stmt = $conn->prepare("
      SELECT cr.*, u.name, u.profile_image 
      FROM comment_replies cr
      JOIN users u ON cr.user_id = u.id
      WHERE cr.comment_id = ? AND cr.status = 'approved'
      ORDER BY cr.created_at ASC
    ");
    $replies_stmt->bind_param("i", $comment_id);
    $replies_stmt->execute();
    $replies_result = $replies_stmt->get_result();
    $comment['replies'] = $replies_result->fetch_all(MYSQLI_ASSOC);
  }
} catch (Exception $e) {
  error_log("Comments fetch error: " . $e->getMessage());
  $comments = [];
}

try {
  $avg_rating_stmt = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
    FROM product_comments 
    WHERE product_id = ? AND status = 'approved'
  ");
  $avg_rating_stmt->bind_param("i", $id);
  $avg_rating_stmt->execute();
  $avg_rating_result = $avg_rating_stmt->get_result();
  $rating_data = $avg_rating_result->fetch_assoc();
} catch (Exception $e) {
  error_log("Rating calculation error: " . $e->getMessage());
  $rating_data = ['avg_rating' => 0, 'total_reviews' => 0];
}

$average_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total_reviews'] ?? 0;

function safe($value)
{
  return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatPrice($price)
{
  return number_format((float) $price, 2, '.', '');
}

function renderStars($rating)
{
  $rating = min(5, max(0, $rating));
  $full_stars = floor($rating);
  $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
  $empty_stars = 5 - $full_stars - $half_star;

  $html = '';
  for ($i = 0; $i < $full_stars; $i++) {
    $html .= '<i class="bi bi-star-fill text-warning"></i>';
  }
  if ($half_star) {
    $html .= '<i class="bi bi-star-half text-warning"></i>';
  }
  for ($i = 0; $i < $empty_stars; $i++) {
    $html .= '<i class="bi bi-star text-warning"></i>';
  }
  return $html;
}

$name = safe($product['name']);
$description = safe($product['description']);
$brand = safe($product['brand']);
$tags = safe($product['tags']);
$barcode = safe($product['barcode']);
$category_id = (int) ($product['category_id'] ?? 0);
$category_name = safe($product['category_name'] ?? 'Uncategorized');
$parent_category = safe($product['parent_category_name'] ?? '');

$price = max(0, (float) ($product['price'] ?? 0));
$sale_price = isset($product['sale_price']) ? max(0, (float) $product['sale_price']) : null;
$on_sale = !empty($product['on_sale']) && $sale_price !== null && $sale_price < $price;
$final_price = $on_sale ? $sale_price : $price;
$discount = $on_sale ? round((($price - $sale_price) / $price) * 100) : 0;

function validateImageUrl($url, $base_url)
{
  if (empty($url))
    return $base_url . 'assets/images/default.jpg';
  if (str_starts_with($url, 'http'))
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : $base_url . 'assets/images/default.jpg';
  return $base_url . 'dashboard/' . ltrim($url, './');
}

$image_path = validateImageUrl($product['image'] ?? '', $base_url);

$gallery = [];
if (!empty($product['gallery'])) {
  try {
    $gallery = json_decode($product['gallery'], true) ?? [];
    foreach ($gallery as &$img) {
      $img = validateImageUrl($img, $base_url);
    }
  } catch (Exception $e) {
    error_log("Gallery processing error: " . $e->getMessage());
    $gallery = [];
  }
}

$sizes = [];
$colors = [];

if (!empty($product['sizes'])) {
  try {
    $sizes = json_decode($product['sizes'], true) ?? [];
  } catch (Exception $e) {
    error_log("Sizes processing error: " . $e->getMessage());
  }
}

if (!empty($product['colors'])) {
  try {
    $colors = json_decode($product['colors'], true) ?? [];
  } catch (Exception $e) {
    error_log("Colors processing error: " . $e->getMessage());
  }
}

$color_images = [];
foreach ($colors as $color) {
  $color_code = strtolower(str_replace('#', '', $color['hex'] ?? ''));
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

$current_color = !empty($colors) ? strtolower(str_replace('#', '', $colors[0]['hex'] ?? '')) : '';
$current_images = $color_images[$current_color] ?? $gallery;
$current_color_image = !empty($colors[0]['image']) ? validateImageUrl($colors[0]['image'], $base_url) : $image_path;

$stock_status = safe($product['stock_status'] ?? 'Out of Stock');
$is_new = !empty($product['is_new']) ? 'Yes' : 'No';
$is_featured = !empty($product['is_featured']) ? 'Yes' : 'No';
$quantity = max(0, (int) ($product['quantity'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title><?= $name ?> | GLAMORA</title>
  <?php require('../includes/link.php'); ?>
</head>

<body>
  <?php require('../includes/header.php'); ?>
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
      <div class="col-lg-6 ">
        <div class="social-media">
          <ul>
            <li><a href="#">Instagram</a></li>
            <li><a href="#">Facebook</a></li>
            <li><a href="#">Twitter</a></li>
          </ul>
        </div>

        <?php if ($on_sale): ?>
          <span class="badge bg-black mb-2">SALE <?php echo htmlspecialchars($discount); ?>% Off</span>
        <?php endif; ?>

        <h4 class="mb-2"><?php echo htmlspecialchars($name); ?></h4>

        <div class="d-flex align-items-center mb-2">
          <?php echo renderStars($average_rating); ?>
          <span class="text-secondary m-2"><?= number_format($average_rating, 1) ?> Rating</span>
          <a href="#reviews">( <?= $total_reviews ?> customer reviews )</a>
        </div>

        <h5
          style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
          <?php echo htmlspecialchars($description); ?>
        </h5>
        <p class="mb-4">It is the perfect tee for any occasion.</p>

        <div class="meta-content m-b20">
          <span class="form-label">Price</span>
          <span class="price"> <?php echo formatPrice($final_price); ?> <sub>EG</sub>
            <?php if ($on_sale): ?>
              <del><?php echo formatPrice($price); ?> <sub>EG</sub></del>
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
        <div class="flex-grow-1 border-bottom"></div>
      </div>
    </div>

    <div class="mt-3 ">
      <div class="row">
        <div class="col-lg-12  ">

          <h3 class="mb-4">Customer Reviews</h3>

          <p><?php echo htmlspecialchars($description); ?></p>

          <div class="row">
            <div class="bg-white mt-5">
              <div class="row info-row">
                <div class="col-6 info-label">Brand</div>
                <div class="col-6 info-value"><?= $brand ?></div>
              </div>

              <div class="row info-row">
                <div class="col-6 info-label">color</div>
                <div class="col-6 info-value"> <?php if (!empty($colors)): ?>
                    <?php
                    $color_names = array_map(function ($color) {
                      return isset($color['name']) ? htmlspecialchars($color['name']) : '';
                    }, $colors);
                    echo implode(' / ', array_filter($color_names));
                    ?>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </div>
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
        </div>
      </div>
      <div class="comment-section" id="reviews">
        <div class="row">
          <div class="col-lg-12">
            <h3 class="mb-4">Customer Reviews</h3>

            <div class="current-time mb-3">
              <i class="bi bi-calendar"></i> <?= date('l, F j, Y') ?> |
              <i class="bi bi-clock"></i> <?= date('h:i A') ?>
            </div>

            <div class="average-rating mb-5">
              <div class="average-rating-number"><?= number_format($average_rating, 1) ?></div>
              <div>
                <div class="comment-rating">
                  <?= renderStars($average_rating) ?>
                </div>
                <div class="rating-count">Based on <?= $total_reviews ?> reviews</div>
              </div>
            </div>

            <?php if ($total_reviews > 0): ?>
              <?php foreach ($comments as $comment): ?>
                <div class="comment-card mb-4" id="comment-<?= $comment['id'] ?>">
                  <div class="comment-header">
                    <div class="comment-avatar">
                      <?php if (!empty($comment['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($comment['profile_image']) ?>" alt="User Avatar">
                      <?php else: ?>
                        <i class="bi bi-person-fill" style="font-size: 24px;"></i>
                      <?php endif; ?>
                    </div>
                    <div>
                      <div class="comment-author"><?= htmlspecialchars($comment['name']) ?></div>
                      <div class="comment-date"><?= date('F j, Y \a\t h:i A', strtotime($comment['created_at'])) ?></div>
                    </div>

                  </div>
                  <div class="comment-rating">
                    <?= renderStars($comment['rating']) ?>
                  </div>
                  <div class="comment-body">
                    <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                  </div>

                  <div class="comment-actions ms-auto">
                    <button class="btn btn-sm btn-outline-secondary like-btn" data-comment-id="<?= $comment['id'] ?>">
                      <i class="bi bi-hand-thumbs-up<?= $comment['user_liked'] ? '-fill text-primary' : '' ?>"></i>
                      <span class="like-count"><?= $comment['like_count'] ?></span>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary reply-btn" data-comment-id="<?= $comment['id'] ?>">
                      <i class="bi bi-reply"></i> Reply
                    </button>
                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || ($_SESSION['is_admin'] ?? false))): ?>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                        <button type="submit" name="delete_comment" class="btn btn-sm btn-outline-danger">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    <?php endif; ?>

                  </div>

                  <?php if (!empty($comment['replies'])): ?>
                    <div class="replies-container mt-3 ps-4 border-start">
                      <?php foreach ($comment['replies'] as $reply): ?>
                        <div class="reply-card mb-3">
                          <div class="comment-header">
                            <div class="comment-avatar">
                              <?php if (!empty($reply['profile_image'])): ?>
                                <img src="<?= htmlspecialchars($reply['profile_image']) ?>" alt="User Avatar">
                              <?php else: ?>
                                <i class="bi bi-person-fill" style="font-size: 20px;"></i>
                              <?php endif; ?>
                            </div>
                            <div>
                              <div class="comment-author"><?= htmlspecialchars($reply['name']) ?></div>
                              <div class="comment-date"><?= date('F j, Y \a\t h:i A', strtotime($reply['created_at'])) ?></div>
                            </div>
                          </div>
                          <div class="comment-body">
                            <p><?= nl2br(htmlspecialchars($reply['reply_text'])) ?></p>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <div class="reply-form-container mt-3" id="reply-form-<?= $comment['id'] ?>" style="display: none;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                      <form method="post" action="#comment-<?= $comment['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                        <div class="mb-3">
                          <textarea class="form-control" name="reply_text" rows="3" required maxlength="500"
                            placeholder="Write your reply..."></textarea>
                          <div class="form-text">Maximum 500 characters</div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                          <button type="button" class="btn btn-outline-secondary cancel-reply-btn">Cancel</button>
                          <button type="submit" name="submit_reply" class="btn btn-dark">Post Reply</button>
                        </div>
                      </form>
                    <?php else: ?>
                      <div class="alert alert-info py-2">
                        You must <a href="<?= $base_url ?>login.php" class="alert-link">login</a> to reply to this comment.
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="no-comments text-center py-5">
                <i class="bi bi-chat-square-text" style="font-size: 48px; color: #ddd;"></i>
                <h4 class="mt-3">No reviews yet</h4>
                <p class="text-muted">There are currently no reviews for this product.</p>
                <p>Be the first to share your experience!</p>
              </div>
            <?php endif; ?>

            <div class="comment-form mt-5">
              <h4 class="mb-4">Write a Review</h4>

              <?php if (isset($comment_success)): ?>
                <div class="alert alert-success"><?= $comment_success ?></div>
              <?php endif; ?>

              <?php if (isset($comment_error)): ?>
                <div class="alert alert-danger"><?= $comment_error ?></div>
              <?php endif; ?>

              <?php if (isset($_SESSION['user_id'])): ?>
                <form method="post" action="#reviews">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                  <div class="mb-3">
                    <label for="rating" class="form-label">Your Rating</label>
                    <div class="rating-input">
                      <input type="radio" id="star5" name="rating" value="5" required>
                      <label for="star5" title="5 stars"><i class="bi bi-star-fill"></i></label>
                      <input type="radio" id="star4" name="rating" value="4">
                      <label for="star4" title="4 stars"><i class="bi bi-star-fill"></i></label>
                      <input type="radio" id="star3" name="rating" value="3">
                      <label for="star3" title="3 stars"><i class="bi bi-star-fill"></i></label>
                      <input type="radio" id="star2" name="rating" value="2">
                      <label for="star2" title="2 stars"><i class="bi bi-star-fill"></i></label>
                      <input type="radio" id="star1" name="rating" value="1">
                      <label for="star1" title="1 star"><i class="bi bi-star-fill"></i></label>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label for="comment" class="form-label">Your Review *</label>
                    <textarea class="form-control" id="comment" name="comment" rows="5" required maxlength="1000"
                      placeholder="Share your experience with this product..."></textarea>
                    <div class="form-text">Maximum 1000 characters</div>
                  </div>

                  <button type="submit" name="submit_comment" class="btn btn-dark">Submit Review</button>
                </form>
              <?php else: ?>
                <div class="alert alert-info">
                  You must <a href="./login.php" class="alert-link">login</a> to write a review.
                  Don't have an account? <a href="<?= $base_url ?>register.php" class="alert-link">Register here</a>.
                </div>
              <?php endif; ?>
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
  </div>


  <?php require('../includes/footer.php'); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function () {
          const commentId = this.dataset.commentId;
          const likeIcon = this.querySelector('i');
          const likeCount = this.querySelector('.like-count');

          if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
            window.location.href = '<?= $base_url ?>login.php';
            return;
          }

          fetch('', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              like_comment: '1',
              comment_id: commentId,
              csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
            })
          })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                likeCount.textContent = data.like_count;
                if (data.action === 'liked') {
                  likeIcon.classList.remove('bi-hand-thumbs-up');
                  likeIcon.classList.add('bi-hand-thumbs-up-fill', 'text-primary');
                } else {
                  likeIcon.classList.remove('bi-hand-thumbs-up-fill', 'text-primary');
                  likeIcon.classList.add('bi-hand-thumbs-up');
                }
              } else if (data.message === 'Login required') {
                window.location.href = '<?= $base_url ?>login.php';
              }
            });
        });
      });

      document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function () {
          const commentId = this.dataset.commentId;
          const replyForm = document.getElementById(`reply-form-${commentId}`);

          if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
            window.location.href = '<?= $base_url ?>login.php';
            return;
          }

          document.querySelectorAll('.reply-form-container').forEach(form => {
            if (form.id !== `reply-form-${commentId}`) {
              form.style.display = 'none';
            }
          });

          if (replyForm.style.display === 'none') {
            replyForm.style.display = 'block';
            replyForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          } else {
            replyForm.style.display = 'none';
          }
        });
      });

      document.querySelectorAll('.cancel-reply-btn').forEach(button => {
        button.addEventListener('click', function () {
          this.closest('.reply-form-container').style.display = 'none';
        });
      });

      initProductCarousel();
      initColorSelection();
      initQuantityControls();
    });

    function initProductCarousel() {
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
    }

    function initColorSelection() {
      const colorOptions = document.querySelectorAll('.color-option');
      const mainImageContainer = document.getElementById('mainImageContainer');
      const originalImage = '<?php echo $image_path; ?>';
      let selectedColor = null;

      colorOptions.forEach(option => {
        option.addEventListener('click', function () {
          const colorCircle = this.querySelector('.color-circle');
          const colorWrapper = this.querySelector('.color-wrapper');
          const colorImage = colorCircle?.dataset.image;
          const colorCode = colorCircle?.dataset.colorCode;

          if (selectedColor === colorCode) {
            colorWrapper?.classList.remove('active');
            colorCircle?.classList.remove('active');
            mainImageContainer.style.backgroundImage = `url('${originalImage}')`;
            selectedColor = null;
            return;
          }

          document.querySelectorAll('.color-wrapper, .color-circle').forEach(el => {
            el.classList.remove('active');
          });

          colorWrapper?.classList.add('active');
          colorCircle?.classList.add('active');
          mainImageContainer.style.backgroundImage = `url('${colorImage || originalImage}')`;
          selectedColor = colorCode;
        });
      });
    }

    function initQuantityControls() {
      $('.btn-number').click(function (e) {
        e.preventDefault();
        const input = $('#quantity');
        let value = parseInt(input.val()) || 1;
        const max = parseInt(input.attr('max')) || 100;
        const min = parseInt(input.attr('min')) || 1;

        if ($(this).data('type') === 'minus') {
          value = Math.max(min, value - 1);
        } else {
          value = Math.min(max, value + 1);
        }

        input.val(value).trigger('change');
      });
    }

    function changeMainImage(el, imgUrl) {
      document.getElementById('mainImageContainer').style.backgroundImage = `url('${imgUrl}')`;
      document.querySelectorAll('.thumbnail-item').forEach(thumb => {
        thumb.classList.remove('active');
      });
      el.classList.add('active');
    }

    async function addToCart(id, quickAdd = false) {
      try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= $_SESSION["csrf_token"] ?? "" ?>');
        formData.append('product_id', id);
        formData.append('product_name', '<?= $name ?>');
        formData.append('product_price', '<?= $price ?>');
        formData.append('product_sale_price', '<?= $on_sale ? $sale_price : 0 ?>');
        formData.append('product_image', '<?= $image_path ?>');

        if (!quickAdd) {
          const qty = parseInt(document.getElementById('quantity').value) || 1;
          formData.append('quantity', qty);

          const sizeInput = document.querySelector('.btn-check:checked');
          if (sizeInput) {
            formData.append('size_id', sizeInput.value);
            formData.append('size_name', sizeInput.dataset.sizeName || sizeInput.value);
            formData.append('size_code', sizeInput.value);
          } else {
            formData.append('size_name', 'Not specified');
            formData.append('size_code', '');
          }

          const colorCircle = document.querySelector('.color-circle.active');
          if (colorCircle) {
            formData.append('color_id', colorCircle.dataset.colorId || '');
            formData.append('color_name', colorCircle.dataset.colorName || '');
            formData.append('color_hex', colorCircle.dataset.colorCode || '');
            formData.append('color_image', colorCircle.dataset.image || '');
          } else {
            formData.append('color_name', 'Not specified');
            formData.append('color_hex', '');
            formData.append('color_image', '');
          }
        } else {
          formData.append('quantity', 1);
          formData.append('size_name', 'Not specified');
          formData.append('color_name', 'Not specified');
        }

        const response = await fetch('../cart/add_cart.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (!response.ok) throw new Error(data.message || 'Network response was not ok');

        if (data.success) {
          updateCartCount(data.cart_count);
          showToast('success', data.message || 'تم إضافة المنتج إلى السلة بنجاح');
        } else {
          showToast('error', data.message || 'فشل إضافة المنتج إلى السلة');
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('error', error.message || 'حدث خطأ في الاتصال');
      }
    }

    function addQuickToCart(id) {
      addToCart(id, true);
    }

    function updateCartCount(count) {
      const cartCounter = document.querySelector('.cart-count');
      if (cartCounter) {
        cartCounter.textContent = count;
        cartCounter.style.display = count > 0 ? 'inline-block' : 'none';
      }
    }

    function showToast(type, message) {
      const toast = document.createElement('div');
      toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
      toast.style.zIndex = '9999';
      toast.style.transition = 'all 0.3s ease';
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
  </script>
</body>

</html>