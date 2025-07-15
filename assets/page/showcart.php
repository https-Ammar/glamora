<?php
require('../db.php');

// Initialize variables
$i = 0;
$finalproducttotal = 0.0;

// Check if the userid cookie is set
if (isset($_COOKIE['userid'])) {
  // Prepare the statement to safely get user id
  $userid = $_COOKIE['userid'];

  // Prepared statement to count the number of products in the cart
  $stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
  $stmt->bind_param("s", $userid);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result) {
    $row = $result->fetch_assoc();
    $i = $row['product_count'];
  }
  $stmt->close();
}
?>

<div class="offcanvas-header justify-content-end">
  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>
<div class="offcanvas-body">
  <div class="order-md-last">
    <h4 class="d-flex justify-content-between align-items-center mb-3">
      <span class="text-primary">Your cart</span>
      <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($i); ?></span>
    </h4>

    <div class="card_">
      <div class="table-responsive cart">
        <table class="table">
          <thead>
            <?php
            if ($i > 0) {
              // Prepare statement for getting cart products
              $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
              $stmt->bind_param("s", $userid);
              $stmt->execute();
              $getallcartproducts = $stmt->get_result();

              // Fetch cart products and their details
              while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
                $cartproduct = $getcartproducts['prouductid'];

                // Prepare statement to fetch product details
                $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                $productStmt->bind_param("i", $cartproduct);
                $productStmt->execute();
                $selectproduct = $productStmt->get_result();
                $fetchproduct = $selectproduct->fetch_assoc();
                $getfirstbyfirst = $fetchproduct['total_final_price'] * $getcartproducts['qty'];
                // Accumulate total price
                $finalproducttotal += $getfirstbyfirst;

                echo '<tr>
                      <td scope="row" class="py-4">
                        <div class="cart-info d-flex flex-wrap align-items-center">
                          <div class="col-lg-3">
                            <div class="card-image" style="background-image: url(\'../dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproduct['img']) . '\');">
                            </div>
                          </div>
                          <div class="col-lg-9">
                            <div class="card-detail ps-3">
                              <h5 class="card-title">' . htmlspecialchars($fetchproduct['name']) . '</h5>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="py-4">
                        <div class="input-group product-qty w-50">
                          <span class="input-group-btn">
                            <button onclick="removemoreone(' . $getcartproducts['id'] . ')" type="button" class="quantity-left-minus btn btn-light btn-number" data-type="minus">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dash" viewBox="0 0 16 16">
                                <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"/>
                              </svg>
                            </button>
                          </span>
                          <input type="text" id="quantity" name="quantity" class="form-control input-number text-center" value="' . $getcartproducts['qty'] . '">
                          <span class="input-group-btn">
                            <button type="button" onclick="addmoreone(' . $getcartproducts['id'] . ')" class="quantity-right-plus btn btn-light btn-number" data-type="plus" data-field="">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                              </svg>
                            </button>
                          </span>
                        </div>
                      </td>
                      <td class="py-4">
                        <div >
                          <span class="money text-dark">EGP ' . htmlspecialchars($fetchproduct['total_final_price']) . '</span>
                        </div>
                      </td>
                      <td class="py-4">
                        <div class="cart-remove" onclick="removecart( ' . $getcartproducts['id'] . ' )">
class="total-price"                          <a href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                              <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                            </svg>
                          </a>
                        </div>
                      </td>
                    </tr>';

                $productStmt->close();
              }
              $stmt->close();
            } else {
              echo '<tr><td colspan="4"><svg viewBox="656 573 264 182" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
      <rect id="bg-line" stroke="none" fill-opacity="0.2" fill="#FFE100" fill-rule="evenodd" x="656" y="624" width="206" height="38" rx="19"></rect>
      <rect id="bg-line" stroke="none" fill-opacity="0.2" fill="#FFE100" fill-rule="evenodd" x="692" y="665" width="192" height="29" rx="14.5"></rect>
      <rect id="bg-line" stroke="none" fill-opacity="0.2" fill="#FFE100" fill-rule="evenodd" x="678" y="696" width="192" height="33" rx="16.5"></rect>
      <g id="shopping-bag" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" transform="translate(721.000000, 630.000000)">
          <polygon id="Fill-10" fill="#FFA800" points="4 29 120 29 120 0 4 0"></polygon>
          <polygon id="Fill-14" fill="#FFE100" points="120 29 120 0 115.75 0 103 12.4285714 115.75 29"></polygon>
          <polygon id="Fill-15" fill="#FFE100" points="4 29 4 0 8.25 0 21 12.4285714 8.25 29"></polygon>
          <polygon id="Fill-33" fill="#FFA800" points="110 112 121.573723 109.059187 122 29 110 29"></polygon>
          <polygon id="Fill-35" fill-opacity="0.5" fill="#FFFFFF" points="2 107.846154 10 112 10 31 2 31"></polygon>
          <path d="M107.709596,112 L15.2883462,112 C11.2635,112 8,108.70905 8,104.648275 L8,29 L115,29 L115,104.648275 C115,108.70905 111.7365,112 107.709596,112" id="Fill-36" fill="#FFE100"></path>
          <path d="M122,97.4615385 L122,104.230231 C122,108.521154 118.534483,112 114.257931,112 L9.74206897,112 C5.46551724,112 2,108.521154 2,104.230231 L2,58" id="Stroke-4916" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <polyline id="Stroke-4917" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="2 41.5 2 29 122 29 122 79"></polyline>
          <path d="M4,50 C4,51.104 3.104,52 2,52 C0.896,52 0,51.104 0,50 C0,48.896 0.896,48 2,48 C3.104,48 4,48.896 4,50" id="Fill-4918" fill="#000000"></path>
          <path d="M122,87 L122,89" id="Stroke-4919" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <polygon id="Stroke-4922" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="4 29 120 29 120 0 4 0"></polygon>
          <path d="M87,46 L87,58.3333333 C87,71.9 75.75,83 62,83 L62,83 C48.25,83 37,71.9 37,58.3333333 L37,46" id="Stroke-4923" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M31,45 C31,41.686 33.686,39 37,39 C40.314,39 43,41.686 43,45" id="Stroke-4924" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M81,45 C81,41.686 83.686,39 87,39 C90.314,39 93,41.686 93,45" id="Stroke-4925" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M8,0 L20,12" id="Stroke-4928" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M20,12 L8,29" id="Stroke-4929" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M20,12 L20,29" id="Stroke-4930" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M115,0 L103,12" id="Stroke-4931" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M103,12 L115,29" id="Stroke-4932" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
          <path d="M103,12 L103,29" id="Stroke-4933" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
      </g>
      <g id="glow" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" transform="translate(768.000000, 615.000000)">
          <rect id="Rectangle-2" fill="#000000" x="14" y="0" width="2" height="9" rx="1"></rect>
          <rect fill="#000000" transform="translate(7.601883, 6.142354) rotate(-12.000000) translate(-7.601883, -6.142354) " x="6.60188267" y="3.14235449" width="2" height="6" rx="1"></rect>
          <rect fill="#000000" transform="translate(1.540235, 7.782080) rotate(-25.000000) translate(-1.540235, -7.782080) " x="0.54023518" y="6.28207994" width="2" height="3" rx="1"></rect>
          <rect fill="#000000" transform="translate(29.540235, 7.782080) scale(-1, 1) rotate(-25.000000) translate(-29.540235, -7.782080) " x="28.5402352" y="6.28207994" width="2" height="3" rx="1"></rect>
          <rect fill="#000000" transform="translate(22.601883, 6.142354) scale(-1, 1) rotate(-12.000000) translate(-22.601883, -6.142354) " x="21.6018827" y="3.14235449" width="2" height="6" rx="1"></rect>
      </g>
      <polygon id="plus" stroke="none" fill="#7DBFEB" fill-rule="evenodd" points="689.681239 597.614697 689.681239 596 690.771974 596 690.771974 597.614697 692.408077 597.614697 692.408077 598.691161 690.771974 598.691161 690.771974 600.350404 689.681239 600.350404 689.681239 598.691161 688 598.691161 688 597.614697"></polygon>
      <polygon id="plus" stroke="none" fill="#EEE332" fill-rule="evenodd" points="913.288398 701.226961 913.288398 699 914.773039 699 914.773039 701.226961 917 701.226961 917 702.711602 914.773039 702.711602 914.773039 705 913.288398 705 913.288398 702.711602 911 702.711602 911 701.226961"></polygon>
      <polygon id="plus" stroke="none" fill="#FFA800" fill-rule="evenodd" points="662.288398 736.226961 662.288398 734 663.773039 734 663.773039 736.226961 666 736.226961 666 737.711602 663.773039 737.711602 663.773039 740 662.288398 740 662.288398 737.711602 660 737.711602 660 736.226961"></polygon>
      <circle id="oval" stroke="none" fill="#A5D6D3" fill-rule="evenodd" cx="699.5" cy="579.5" r="1.5"></circle>
      <circle id="oval" stroke="none" fill="#CFC94E" fill-rule="evenodd" cx="712.5" cy="617.5" r="1.5"></circle>
      <circle id="oval" stroke="none" fill="#8CC8C8" fill-rule="evenodd" cx="692.5" cy="738.5" r="1.5"></circle>
      <circle id="oval" stroke="none" fill="#3EC08D" fill-rule="evenodd" cx="884.5" cy="657.5" r="1.5"></circle>
      <circle id="oval" stroke="none" fill="#66739F" fill-rule="evenodd" cx="918.5" cy="681.5" r="1.5"></circle>
      <circle id="oval" stroke="none" fill="#C48C47" fill-rule="evenodd" cx="903.5" cy="723.5" r="1.5"></circle>
      <circle id="oval" stroke="none" fill="#A24C65" fill-rule="evenodd" cx="760.5" cy="587.5" r="1.5"></circle>
      <circle id="oval" stroke="#66739F" stroke-width="2" fill="none" cx="745" cy="603" r="3"></circle>
      <circle id="oval" stroke="#EFB549" stroke-width="2" fill="none" cx="716" cy="597" r="3"></circle>
      <circle id="oval" stroke="#FFE100" stroke-width="2" fill="none" cx="681" cy="751" r="3"></circle>
      <circle id="oval" stroke="#3CBC83" stroke-width="2" fill="none" cx="896" cy="680" r="3"></circle>
      <polygon id="diamond" stroke="#C46F82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" points="886 705 889 708 886 711 883 708"></polygon>
      <path d="M736,577 C737.65825,577 739,578.34175 739,580 C739,578.34175 740.34175,577 742,577 C740.34175,577 739,575.65825 739,574 C739,575.65825 737.65825,577 736,577 Z" id="bubble-rounded" stroke="#3CBC83" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
  </svg>
</td></tr>';
            }
            ?>
          </thead>
        </table>
      </div>
    </div>

    <div class="cart-totals bg-grey py-5">
      <h4 class="text-dark pb-4">Cart Total</h4>
      <div class="total-price">
        <table cellspacing="0" class="table text-uppercase">
          <tbody>
            <tr class="d-flex align-items-center justify-content-between">
              <th>Total</th>
              <td data-title="Total">
                <span class="price-amount amount text-dark ps-5">
                  <bdi><?php echo htmlspecialchars($finalproducttotal); ?></bdi>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php
    if ($i > 0) {
      echo '<button onclick="window.location.href=\'page/Checkout.php\';" class="w-100 btn btn-primary btn-lg" type="button">Continue to checkout</button>';
    }
    ?>

  </div>
</div>

<script>
  function addmoreone(id) {
    $.ajax({
      type: "POST",
      url: "../addmoreone.php",
      data: {
        id: id,
      },
      success: function (response) {
        console.log(response);

      }
    });
  }

  function removemoreone(id) {
    $.ajax({
      type: "POST",
      url: "../removemoreone.php",
      data: {
        id: id,
      },
      success: function (response) {
        console.log(response);

      }
    });
  }

  function removecart(id) {
    $.ajax({
      type: "POST",
      url: "../removecart.php",
      data: {
        id: id,
      },
      success: function (response) {
        console.log(response);

      }
    });
  }
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>