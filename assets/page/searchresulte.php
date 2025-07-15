<!--  -->
<?php
require('db.php');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Sanitize the search term
  $search = isset($_POST['search']) ? trim($_POST['search']) : '';

  // Prevent SQL injection by sanitizing input
  $search = mysqli_real_escape_string($conn, $search);

  // Check for connection errors
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLAMORA</title>
  <link rel="stylesheet" type="text/css" href="./style/style.css" />

  <!--  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
</head>

<body>

  <?php require('./header.php') ?>


  <main>








    <section>
      <div class="container-fluid">
        <div class="row">
 
 
         <div class="product-grid ">

                    <?php
                    require('db.php'); // Ensure this file sets up the $conn variable properly

                    // Check if 'search' parameter is set and sanitize it
                    $search = isset($_POST['search']) ? trim($_POST['search']) : '';

                    if (!empty($search)) {
                      // Prepare the SQL query to search for products containing the search term
                      $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ?");
                      $likeTerm = "%" . $search . "%"; // Add % for partial matching
                      $stmt->bind_param("s", $likeTerm); // Bind the search term to prevent SQL injection

                      // Execute the query
                      $stmt->execute();
                      $result = $stmt->get_result();

                      // Check if any results are found
                      if ($result->num_rows > 0) {
                        // Output the results in a loop
                        while ($fetchproducts = $result->fetch_assoc()) {
                          $productName = htmlspecialchars($fetchproducts['name']);
                          $productImage = './dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproducts['img']); // Ensure the correct image path
                          $productfinalprice = htmlspecialchars($fetchproducts['total_final_price']);
                          $productDiscount = htmlspecialchars($fetchproducts['discount']);
                          $productId = htmlspecialchars($fetchproducts['id']); // Fetch the product ID

                          echo '
                          
                          
                          
                                    <div class="product-item swiper-slide">
                                    
                                    
<a href="view.php?id=' . $productId . '" title="' . $productName . '">
                    <figure class="bg_img" style="background-image: url(\'' . $productImage . '\');">
                     
                     
                        <span class="badge bg-success  text">-' . $productDiscount . '%</span>
                 

                    </figure>
                  </a>
                                    
                                    
                          
                          
                          
                                       <span class="snize-attribute"><span class="snize-attribute-title"></span> Source Beauty</span>
                  <span class="snize-title" style="max-height: 2.8em;-webkit-line-clamp: 2;">' . $productName . '</span>
                          
                          
                          
                          
                          
                          
                          
                          
                          
                          
                          
<div class="flex_pric">


           

                    <button class="d-flex align-items-center nav-link click"  onclick="addcart(' . $productId . ')"> Add to Cart</button>
                    <div class="block_P">
                      <span class="price text">' . $productfinalprice . ' </span>
                      <span>EGP</span>
                    </div>
                  </div>
                          
          

                   
            

          
                  <div class="input-group product-qty" style="display: none;">
                                  <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                                        
                                        _
                                        </button>
                                    <input type="text" id="quantity" name="quantity" class="form-control input-number quantity' . $productId . '" value="1">
                             
                             
                                        <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                      +
                                        </button>
                                </div>
       
       
                  </div>
                  
                  
                  
                  
                  
                  ';
                        }
                      } else {
                        echo "<p>No results found.</p>";
                      }

                      // Close the statement and connection
                      $stmt->close();
                    } else {
                      echo "<p>Please enter a search term.</p>";
                    }

                    $conn->close();
                    ?>











                </div>
 <!---->
 
      </div>
               </div>
    </section>




  </main>

  <script src="./js/plugins.js"></script>
  <script src="./js/script.js"></script>





  <script>
    function loadCart() {
      $.ajax({
        type: "GET",
        url: "showcart.php",
        success: function(response) {
          $('#offcanvasCart').html(response);
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    // Load the cart initially
    loadCart();

    function addcart(productid) {
      var quantity = $('.quantity' + productid).val(); // Get the quantity value

      $.ajax({
        type: "POST",
        url: "./addcartproduct.php",
        data: {
          productid: productid,
          qty: quantity // Pass the quantity value correctly
        },
        success: function(response) {
          loadCart()
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "./addmoreone.php",
        data: {
          id: id,
        },
        success: function(response) {
          loadCart()
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "./removemoreone.php",
        data: {
          id: id,
        },
        success: function(response) {
          loadCart()
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "./removecart.php",
        data: {
          id: id,
        },
        success: function(response) {
          loadCart()
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }
  </script>





  <script>
    function loadCart() {
      $.ajax({
        type: "GET",
        url: "showcart.php",
        success: function(response) {
          $('#offcanvasCart').html(response);
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    // Load the cart initially
    loadCart();

    function addcart(productid) {
      var quantity = $('.quantity' + productid).val(); // Get the quantity value

      $.ajax({
        type: "POST",
        url: "addcartproduct.php",
        data: {
          productid: productid,
          qty: quantity // Pass the quantity value correctly
        },
        success: function(response) {
          loadCart();
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "addmoreone.php",
        data: {
          id: id,
        },
        success: function(response) {
          loadCart();
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "removemoreone.php",
        data: {
          id: id,
        },
        success: function(response) {
          loadCart();
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "removecart.php",
        data: {
          id: id,
        },
        success: function(response) {
          loadCart();
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }
  </script>








  <?php require('footer.php') ?>
</body>

</html>