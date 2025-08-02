<?php
require('../config/db.php');

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

<body class="page-sidebar-collapsed">
  <div class="page-container">
    <div class="page-content">
      <div class="main-wrapper">
        <?php if ($i == 0) { ?>
          <!-- إذا كانت السلة فارغة -->
          <div class="row">
            <div class="col text-center">
              <h3>السلة فارغة</h3>
            </div>
          </div>
        <?php } else { ?>
          <!-- إذا كانت السلة تحتوي على عناصر -->
          <div class="row">
            <div class="col">
              <div class="card">
                <div class="offcanvas-header justify-content-end">
                  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="table-responsive">
                      <table class="table invoice-table">
                        <thead>
                          <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Name</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          // الكود الخاص بعرض المنتجات في السلة
                          $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
                          $stmt->bind_param("s", $userid);
                          $stmt->execute();
                          $getallcartproducts = $stmt->get_result();

                          while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
                            $cartproduct = $getcartproducts['prouductid'];

                            $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                            $productStmt->bind_param("i", $cartproduct);
                            $productStmt->execute();
                            $selectproduct = $productStmt->get_result();
                            $fetchproduct = $selectproduct->fetch_assoc();

                            $getfirstbyfirst = $fetchproduct['total_final_price'] * $getcartproducts['qty'];
                            $finalproducttotal += $getfirstbyfirst;

                            echo '
                            <tr>
                              <td scope="row" class="py-4">
                                <div class="card-image ol-lg-3 viwe_img" style="background-image: url(\'./dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproduct['img']) . '\');"></div>
                              </td>
                              <td class="py-4"><p>' . htmlspecialchars($fetchproduct['name']) . '</p></td>
                              <td class="py-4">
                                <div class="_flex_int">
                                  <button onclick="removemoreone(' . $getcartproducts['id'] . ')" type="button" class="quantity-left-minus btn btn-light btn-number" data-type="minus">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dash" viewBox="0 0 16 16">
                                      <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"/>
                                    </svg>
                                  </button>
                                  <input type="text" id="quantity" name="quantity" class="form-control input-number text-center" value="' . $getcartproducts['qty'] . '">
                                  <button onclick="addmoreone(' . $getcartproducts['id'] . ')" type="button" class="quantity-right-plus btn btn-light btn-number" data-type="plus">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                                      <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                                    </svg>
                                  </button>
                                </div>
                              </td>
                              <td class="py-4"><p>' . htmlspecialchars($getfirstbyfirst) . ' EGP</p></td>
                              <td class="py-4">
                                <a href="#" onclick="removecart(' . $getcartproducts['id'] . ')">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                  </svg>
                                </a>
                              </td>
                            </tr>';
                            $productStmt->close();
                          }
                          $stmt->close();
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="row invoice-last">
                    <div class="col-9">
                      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                    </div>
                    <div class="">
                      <div class="invoice-info">
                        <p>Subtotal <span><?php echo htmlspecialchars($finalproducttotal); ?> EGP</span></p>
                        <p>Product numbers <span>( <?php echo htmlspecialchars($i); ?> )</span></p>
                        <p>Total <span><?php echo htmlspecialchars($finalproducttotal); ?> EGP</span></p>
                        <div class="d-grid gap-2">
                          <button onclick="window.location.href='checkout.php';" class="btn btn-danger m-t-xs"
                            type="button">Print Invoice</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</body>






<!-- Javascripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src=" ../script/jquery-3.4.1.min.js"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>

<script src="../script/bootstrap.min.js"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script src="../script/perfect-scrollbar.min.js"></script>
<script src="../script/pace.min.js"></script>
<script src="../script/apexcharts.min.js"></script>
<script src="../script/jquery.sparkline.min.js"></script>
<script src="../script/main.min.js"></script>
<script src="../script/dashboard.js"></script>






<script>
  window.onload = function () {
    // Select all elements with the class 'text' (you can customize the selector)
    let elements = document.querySelectorAll('.text');

    // Loop through each element and modify the text
    elements.forEach(element => {
      let text = element.textContent;
      // Split the text at the first period and take the part before it
      let updatedText = text.split('.')[0];
      // Return the updated text back to the element
      element.textContent = updatedText;
    });
  };
</script>



<style>
  .roow {
    display: grid;
    align-items: center;
    justify-content: center;
    gap: 10px;
    grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
    text-align: end;
  }


  input#quantity {
    background: no-repeat;
    color: black !important;
  }


  ._flex_int button {
    width: min-content;
    height: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    border: navajowhite;
    background: no-repeat;
  }

  ._flex_int {
    display: flex;
    align-items: center;
  }

  ._flex_int input {


    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
  }


  .row.invoice-last {
    display: block;
  }

  tr {
    display: flex;
    align-items: center;
  }

  button.btn.btn-danger.m-t-xs {
    padding: 10px;
    background: black;
    color: white;
  }
</style>