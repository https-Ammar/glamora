<?php
require('./db.php');

if (isset($_GET['id'])) {
  $id = intval($_GET['id']);

  $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  $fetchchossenproduct = $result->fetch_assoc();

  if ($fetchchossenproduct) {
    $Cat = (int) $fetchchossenproduct['category_id'];
  } else {
    header('location: ./index.php');
    exit();
  }
} else {
  header('location: ./index.php');
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($fetchchossenproduct['name'], ENT_QUOTES, 'UTF-8'); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
  <link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
  <link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../style/main.css">
  <link rel="stylesheet" href="./style/viwe.css">
  <link rel="stylesheet" type="text/css" href="./style/style.css">
</head>

<body>
  <?php require('./loding.php'); ?>

  <section id="lod_file">
    <?php require('./header.php'); ?>

    <main>
      <section class="viwe_product">
        <div class="img_pro">
          <div class="img_viwe">
            <img class="img_co"
              src="<?php echo htmlspecialchars('./dashboard/dashboard_shop-main/' . $fetchchossenproduct['img'], ENT_QUOTES, 'UTF-8'); ?>"
              alt="">
          </div>
          <div class="small_img">
            <img class="img_co"
              src="<?php echo htmlspecialchars('./dashboard/dashboard_shop-main/' . $fetchchossenproduct['img'], ENT_QUOTES, 'UTF-8'); ?>"
              alt="">
          </div>
        </div>

        <div class="pric_viwe">
          <p data-testid="" role="" class="label-c right-side-detail_category__jKVX0">L'azurde</p>

          <div>
            <h1 data-testid="" class="heading-c right-side-detail_title__q11Fd" role="">
              <?php echo htmlspecialchars($fetchchossenproduct['name'], ENT_QUOTES, 'UTF-8'); ?>
            </h1>
          </div>

          <p data-testid="" role="" class="label-c right-side-detail_category__jKVX0">
            <?php echo htmlspecialchars($fetchchossenproduct['description'], ENT_QUOTES, 'UTF-8'); ?>
          </p>

          <div class="right-side-detail_review-section__kQE9S">
            <div class="right-side-detail_wishlist-icon__y7bxu">
              <div class="" role="button">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="white" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M3.85168 11.3334L10.3334 17.5834L16.8151 11.3334C17.9401 10.4863 18.6668 9.14337 18.6668 7.62961C18.6668 5.07418 16.5926 3 14.0372 3C12.5234 3 11.1759 3.73167 10.3334 4.85668C9.49088 3.73167 8.14337 3 6.62961 3C4.07418 3 2 5.07418 2 7.62961C2 9.14337 2.72667 10.4863 3.85168 11.3334ZM6.62961 3.83334C7.81712 3.83334 8.95213 4.40251 9.6663 5.35585L10.3334 6.24669L11.0005 5.35627C11.7146 4.40251 12.8496 3.83334 14.0372 3.83334C16.1305 3.83334 17.8334 5.53627 17.8334 7.62961C17.8334 8.83295 17.2797 9.94046 16.3134 10.6675L16.273 10.698L16.2363 10.7334L10.3334 16.4259L4.43043 10.7334L4.39376 10.698L4.35335 10.6675C3.38751 9.94046 2.83334 8.83295 2.83334 7.62961C2.83334 5.53627 4.53627 3.83334 6.62961 3.83334Z"
                    fill="black" stroke="black" stroke-width="0.3"></path>
                </svg>
              </div>
            </div>
            <div class="right-side-detail_rating-stars__QGOZ4">
              <div style="pointer-events: none">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M12.5748 7.15275L9.99984 1.6665L7.42484 7.15275L1.6665 8.03275L5.83317 12.3032L4.84942 18.3332L9.99984 15.4861L15.1503 18.3332L14.1665 12.3032L18.3332 8.03275L12.5748 7.15275ZM13.344 12.4373L14.0515 16.7736L10.4032 14.7569L9.99984 14.534L9.5965 14.7569L5.94817 16.7736L6.65567 12.4373L6.72317 12.0223L6.42942 11.7215L3.39567 8.6115L7.55067 7.9765L7.99025 7.90942L8.179 7.50692L9.99984 3.62775L11.8207 7.50692L12.0094 7.90942L12.449 7.9765L16.604 8.6115L13.5698 11.7211L13.2761 12.0219L13.344 12.4373Z"
                    fill="#C3A956" stroke="#C3A956" stroke-width="0.3"></path>
                  <path
                    d="M10.0011 3L12.3053 7.10213L16.6585 8.18237L14 12L14.1156 16.5676L12 16L10.0011 14.7L8.5 16L5.88665 16.5676L6.273 11.7979L4 10L3.34375 8.18237L7.69703 7.10213L10.0011 3Z"
                    fill="#C3A956"></path>
                </svg>
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M12.5748 7.15275L9.99984 1.6665L7.42484 7.15275L1.6665 8.03275L5.83317 12.3032L4.84942 18.3332L9.99984 15.4861L15.1503 18.3332L14.1665 12.3032L18.3332 8.03275L12.5748 7.15275ZM13.344 12.4373L14.0515 16.7736L10.4032 14.7569L9.99984 14.534L9.5965 14.7569L5.94817 16.7736L6.65567 12.4373L6.72317 12.0223L6.42942 11.7215L3.39567 8.6115L7.55067 7.9765L7.99025 7.90942L8.179 7.50692L9.99984 3.62775L11.8207 7.50692L12.0094 7.90942L12.449 7.9765L16.604 8.6115L13.5698 11.7211L13.2761 12.0219L13.344 12.4373Z"
                    fill="#C3A956" stroke="#C3A956" stroke-width="0.3"></path>
                  <path
                    d="M10.0011 3L12.3053 7.10213L16.6585 8.18237L14 12L14.1156 16.5676L12 16L10.0011 14.7L8.5 16L5.88665 16.5676L6.273 11.7979L4 10L3.34375 8.18237L7.69703 7.10213L10.0011 3Z"
                    fill="#C3A956"></path>
                </svg>
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M12.5748 7.15275L9.99984 1.6665L7.42484 7.15275L1.6665 8.03275L5.83317 12.3032L4.84942 18.3332L9.99984 15.4861L15.1503 18.3332L14.1665 12.3032L18.3332 8.03275L12.5748 7.15275ZM13.344 12.4373L14.0515 16.7736L10.4032 14.7569L9.99984 14.534L9.5965 14.7569L5.94817 16.7736L6.65567 12.4373L6.72317 12.0223L6.42942 11.7215L3.39567 8.6115L7.55067 7.9765L7.99025 7.90942L8.179 7.50692L9.99984 3.62775L11.8207 7.50692L12.0094 7.90942L12.449 7.9765L16.604 8.6115L13.5698 11.7211L13.2761 12.0219L13.344 12.4373Z"
                    fill="#C3A956" stroke="#C3A956" stroke-width="0.3"></path>
                  <path
                    d="M10.0011 3L12.3053 7.10213L16.6585 8.18237L14 12L14.1156 16.5676L12 16L10.0011 14.7L8.5 16L5.88665 16.5676L6.273 11.7979L4 10L3.34375 8.18237L7.69703 7.10213L10.0011 3Z"
                    fill="#C3A956"></path>
                </svg>
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M11.5748 6.15275L8.99984 0.666504L6.42484 6.15275L0.666504 7.03275L4.83317 11.3032L3.84942 17.3332L8.99984 14.4861L14.1503 17.3332L13.1665 11.3032L17.3332 7.03275L11.5748 6.15275ZM12.344 11.4373L13.0515 15.7736L9.40317 13.7569L8.99984 13.534L8.5965 13.7569L4.94817 15.7736L5.65567 11.4373L5.72317 11.0223L5.42942 10.7215L2.39567 7.6115L6.55067 6.9765L6.99025 6.90942L7.179 6.50692L8.99984 2.62775L10.8207 6.50692L11.0094 6.90942L11.449 6.9765L15.604 7.6115L12.5698 10.7211L12.2761 11.0219L12.344 11.4373Z"
                    fill="#C3A956" stroke="#C3A956" stroke-width="0.3"></path>
                </svg>
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M11.5748 6.15275L8.99984 0.666504L6.42484 6.15275L0.666504 7.03275L4.83317 11.3032L3.84942 17.3332L8.99984 14.4861L14.1503 17.3332L13.1665 11.3032L17.3332 7.03275L11.5748 6.15275ZM12.344 11.4373L13.0515 15.7736L9.40317 13.7569L8.99984 13.534L8.5965 13.7569L4.94817 15.7736L5.65567 11.4373L5.72317 11.0223L5.42942 10.7215L2.39567 7.6115L6.55067 6.9765L6.99025 6.90942L7.179 6.50692L8.99984 2.62775L10.8207 6.50692L11.0094 6.90942L11.449 6.9765L15.604 7.6115L12.5698 10.7211L12.2761 11.0219L12.344 11.4373Z"
                    fill="#C3A956" stroke="#C3A956" stroke-width="0.3"></path>
                </svg>
              </div>
            </div>
            <div class="right-side-detail_write-review-btn__wnDWn">
              <button id="" role="button" data-testid="button" data-style="black" data-size="md"
                class="button_button__e8dQY right-side-detail_btn__h714O" type="button" aria-disabled="false"
                data-loading="false" data-hover="false">
                Write a Review
              </button>
            </div>
          </div>
          <div class="undefined right-side-detail_price-wrapper__yydp4">
            <div class="right-side-detail_price-inner-div__HvZzO">
              <?php if ($fetchchossenproduct['price'] != $fetchchossenproduct['total_final_price']): ?>
                <p data-testid="" role=""
                  class="label-c right-side-detail_base-price__C9Hne right-side-detail_line-through__WhF9P">
                  EGP <?php echo htmlspecialchars($fetchchossenproduct['price'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
              <?php endif; ?>

              <?php if ($fetchchossenproduct['discount'] != 0) { ?>
                <p data-testid="" role="" class="label-c right-side-detail_discount__oIoo1">
                  <?php echo htmlspecialchars($fetchchossenproduct['discount'], ENT_QUOTES, 'UTF-8'); ?>% off
                </p>
              <?php } ?>
            </div>
            <div>
              <p data-testid="" role="" class="label-c right-side-detail_final-price__3i5Hb">
                EGP <?php echo htmlspecialchars($fetchchossenproduct['total_final_price'], ENT_QUOTES, 'UTF-8'); ?>
              </p>
            </div>
          </div>
          <div class="right-side-detail_buy-buttons__Ri9DB">
            <div class="container-fluid">
              <div class="input-group product-qty product__description">
                <span class="input-group-btn">
                  <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                      class="bi bi-dash" viewBox="0 0 16 16">
                      <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" />
                    </svg>
                  </button>
                </span>
                <input type="text" id="quantity" name="quantity" class="form-control input-number countme" value="1" />
                <span class="input-group-btn">
                  <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                      class="bi bi-plus" viewBox="0 0 16 16">
                      <path
                        d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
                    </svg>
                  </button>
                </span>
              </div>
            </div>

            <div class="flex_pric playSound" type="submit"
              onclick='addcart1(<?php echo (int) $fetchchossenproduct["id"] ?>)'>
              <button class="d-flex align-items-center nav-link click">Add To Cart</button>
              <div class="block_P">
                <span
                  class="price text"><?php echo htmlspecialchars($fetchchossenproduct['total_final_price'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span>EGP</span>
              </div>
            </div>
          </div>
          <div class="right-side-detail_payment-icons__h0wBJ undefined">
            <span><span><img alt="" aria-hidden="true"
                  src="data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20version=%271.1%27%20width=%2751%27%20height=%2725%27/%3e" /></span><img
                alt="mada"
                src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/payment-icons/visa-pdp.svg"
                decoding="async" data-nimg="intrinsic" /></span><span><span><img alt="" aria-hidden="true"
                  src="data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20version=%271.1%27%20width=%2752%27%20height=%2725%27/%3e" /></span><img
                alt="visa"
                src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/payment-icons/mastercard-pdp.svg"
                decoding="async" data-nimg="intrinsic" /></span><span><span><img alt="" aria-hidden="true"
                  src="data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20version=%271.1%27%20width=%2723%27%20height=%2725%27/%3e" /></span><img
                alt="mastercard"
                src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/payment-icons/valu_logo.svg"
                decoding="async" data-nimg="intrinsic" /></span><span>
              <span><span><img alt="" aria-hidden="true"
                    src="data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20version=%271.1%27%20width=%2751%27%20height=%2725%27/%3e" /></span><img
                  alt="tamara"
                  src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/payment-icons/cod-pdp.svg"
                  decoding="async" data-nimg="intrinsic" /></span>
          </div>

          <div class="right-side-detail_sub-detail-wrapper___3V70 ">
            <div class="style_sub-detail-point__OcLlg"><span
                style="box-sizing:border-box;display:inline-block;overflow:hidden;width:20px;height:20px;background:none;opacity:1;border:0;margin:0;padding:0;position:relative"><img
                  alt="usps-icon"
                  src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/process-icons/process-one.png"
                  decoding="async" data-nimg="fixed"
                  style="position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;padding:0;border:none;margin:auto;display:block;width:0;height:0;min-width:100%;max-width:100%;min-height:100%;max-height:100%"></span>
              <p class="style_label___J27p clamp-fontsize">Free Delivery on All Orders</p>
            </div>
            <div class="style_sub-detail-point__OcLlg"><span
                style="box-sizing:border-box;display:inline-block;overflow:hidden;width:20px;height:20px;background:none;opacity:1;border:0;margin:0;padding:0;position:relative"><img
                  alt="usps-icon"
                  src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/process-icons/process-two.svg"
                  decoding="async" data-nimg="fixed"
                  style="position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;padding:0;border:none;margin:auto;display:block;width:0;height:0;min-width:100%;max-width:100%;min-height:100%;max-height:100%"></span>
              <p class="style_label___J27p clamp-fontsize">FREE 30-Day Returns</p>
            </div>
            <div class="style_sub-detail-point__OcLlg"><span
                style="box-sizing:border-box;display:inline-block;overflow:hidden;width:20px;height:20px;background:none;opacity:1;border:0;margin:0;padding:0;position:relative"><img
                  alt="usps-icon"
                  src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/process-icons/process-third.svg"
                  decoding="async" data-nimg="fixed"
                  style="position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;padding:0;border:none;margin:auto;display:block;width:0;height:0;min-width:100%;max-width:100%;min-height:100%;max-height:100%"></span>
              <p class="style_label___J27p clamp-fontsize">Buy Now, Pay Later with </p>
            </div>
            <div class="style_sub-detail-point__OcLlg"><span
                style="box-sizing:border-box;display:inline-block;overflow:hidden;width:20px;height:20px;background:none;opacity:1;border:0;margin:0;padding:0;position:relative"><img
                  alt="usps-icon"
                  src="https://lazurde.bloomreach.io/delivery/resources/content/gallery/channel-templates/lazurde/process-icons/process-four.svg"
                  decoding="async" data-nimg="fixed"
                  style="position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;padding:0;border:none;margin:auto;display:block;width:0;height:0;min-width:100%;max-width:100%;min-height:100%;max-height:100%"></span>
              <p class="style_label___J27p clamp-fontsize">Free Next Day Delivery in Cairo</p>
            </div>
          </div>
        </div>
      </section>

      <br>

      <main class="main_main">
        <div class="product-add-form">
          <input type="hidden" name="product" value="33297">
          <input type="hidden" name="selected_configurable_option" value="">
          <input type="hidden" name="related_product" id="related-products-field" value="">
          <input type="hidden" name="item" value="33297">
          <input name="form_key" type="hidden" value="IvbfOiudt2hT4hGd">
          <input type="hidden" name="product_id" id="product_id" value="33297">

          <div id="instant-purchase" data-bind="scope:'instant-purchase'">
            <!-- ko template: getTemplate() -->
            <!-- ko if: showButton() --><!-- /ko -->
            <!-- /ko -->
          </div>
        </div>
      </main>

      <section class="py-5 overflow-hidden">
        <div class="_con">
          <div class="row">
            <div class="col-md-12">
              <div class="section-header d-flex justify-content-between">
                <a href="#" class="btn-link text-decoration-none">
                  <h3 class="title">viwe more</h3>
                </a>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="slider-container">
              <div class="owl-carousel js-home-products">
                <?php
                $stmtProducts = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
                $stmtProducts->bind_param("i", $Cat);
                $stmtProducts->execute();
                $selectproduct = $stmtProducts->get_result();

                while ($fetchproducts = $selectproduct->fetch_assoc()) {
                  if ($fetchproducts['id'] != $id) {
                    $productName = htmlspecialchars($fetchproducts['name'], ENT_QUOTES, 'UTF-8');
                    $productImage = './dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproducts['img'], ENT_QUOTES, 'UTF-8');
                    $productfinalprice = htmlspecialchars($fetchproducts['total_final_price'], ENT_QUOTES, 'UTF-8');
                    $productDiscount = htmlspecialchars($fetchproducts['discount'], ENT_QUOTES, 'UTF-8');
                    $productId = (int) $fetchproducts['id'];
                    ?>
                    <div class="item">
                      <a href="view.php?id=<?php echo $productId; ?>" title="<?php echo $productName; ?>">
                        <figure class="bg_img" style="background-image: url('<?php echo $productImage; ?>');">
                          <?php if ($fetchproducts['discount'] != 0) { ?>
                            <span class="badge bg-success text"><?php echo $productDiscount; ?> %</span>
                          <?php } ?>
                        </figure>
                      </a>

                      <span class="snize-attribute"><span class="snize-attribute-title"></span> Source Beauty</span>
                      <span class="snize-title"
                        style="max-height: 2.8em;-webkit-line-clamp: 2;"><?php echo $productName; ?></span>
                      <div class="ptn_" style="display: none;">
                        <div class="input-group product-qty wid">
                          <span class="input-group-btn">
                            <button type="button" class="quantity-left-minus btn btn-danger ptn_PM"
                              onclick="decreaseValue(this)">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-dash" viewBox="0 0 16 16">
                                <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"></path>
                              </svg>
                            </button>
                          </span>
                          <input type="text" id="quantity<?php echo $productId; ?>" name="quantity"
                            class="form-control input-number input_plus input_v" value="1">
                          <span class="input-group-btn">
                            <button type="button" class="quantity-right-plus btn btn-success ptn_PM"
                              onclick="increaseValue(this)">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-plus" viewBox="0 0 16 16">
                                <path
                                  d="M8 0a.5.5 0 0 1 .5.5V7h6.5a.5.5 0 0 1 0 1H8v6.5a.5.5 0 0 1-1 0V8H.5a.5.5 0 0 1 0-1H7V.5A.5.5 0 0 1 8 0z">
                                </path>
                              </svg>
                            </button>
                          </span>
                        </div>
                      </div>
                      <div class="flex_pric playSound" onclick='addcart(<?php echo $productId; ?>)'>
                        <button class="d-flex align-items-center nav-link click">Add To Cart</button>
                        <div class="block_P">
                          <span class="price text"><?php echo $productfinalprice; ?></span>
                          <span>EGP</span>
                        </div>
                      </div>
                    </div>
                    <?php
                  }
                }
                ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php require('footer.php'); ?>
  </section>

  <audio id="audio" src="./like.mp3"></audio>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
    crossorigin="anonymous"></script>
  <script src="./js/plugins.js"></script>
  <script src="./js/viwe.js"></script>

  <script>
    function loadCart() {
      $.ajax({
        type: "GET",
        url: "showcart.php",
        success: function (response) {
          $('#offcanvasCart').html(response);
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    loadCart();

    function addcart(productid) {
      var quantity = $('#quantity' + productid).val();

      if (quantity && quantity > 0) {
        $.ajax({
          type: "POST",
          url: "addcartproduct.php",
          data: {
            productid: productid,
            qty: quantity
          },
          success: function (response) {
            loadCart();
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
          }
        });
      } else {
        console.error("Invalid quantity");
      }
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "addmoreone.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "removemoreone.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "removecart.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addcart1(productid) {
      $.ajax({
        type: "POST",
        url: "addcartproduct.php",
        data: {
          productid: productid,
          qty: $('.countme').val()
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }
  </script>

  <script src="./js/app.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll(".click").forEach((button) => {
        button.addEventListener("click", function () {
          const template = document.querySelector("#template");
          const cardDone = template.cloneNode(true);

          cardDone.id = `card_done_${Date.now()}`;
          cardDone.style.display = "flex";
          document.body.appendChild(cardDone);

          setTimeout(() => {
            cardDone.style.display = "none";
            document.body.removeChild(cardDone);
          }, 1000);
        });
      });
    });
  </script>

  <script>
    let lod_file = document.getElementById('lod_file');
    let loading = document.getElementById('loading');

    window.onload = function () {
      lod_file.style.display = 'block'
      loading.style.display = 'none'
    }
  </script>

  <script>
    document.querySelectorAll(".playSound").forEach(function (button) {
      button.addEventListener("click", function () {
        var audio = document.getElementById("audio");
        audio.currentTime = 0;
        audio.play();

        if (navigator.vibrate) {
          navigator.vibrate(200);
        }
      });
    });
  </script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

  <script>
    $(".js-home-products").owlCarousel({
      loop: false,
      margin: 10,
      nav: true,
      autoplay: true,
      autoplayTimeout: 3000,
      responsive: {
        0: {
          items: 2
        },
        600: {
          items: 3
        },
        1000: {
          items: 6
        },
      },
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const minusButtons = document.querySelectorAll('.quantity-left-minus');
      const plusButtons = document.querySelectorAll('.quantity-right-plus');
      const quantityInputs = document.querySelectorAll('.countme');

      minusButtons.forEach((btn, index) => {
        btn.addEventListener('click', function () {
          let currentValue = parseInt(quantityInputs[index].value) || 0;
          if (currentValue > 1) {
            quantityInputs[index].value = currentValue - 1;
          }
        });
      });

      plusButtons.forEach((btn, index) => {
        btn.addEventListener('click', function () {
          let currentValue = parseInt(quantityInputs[index].value) || 0;
          quantityInputs[index].value = currentValue + 1;
        });
      });
    });
  </script>
</body>

</html>