function loadCart() {
  $.ajax({
    type: "GET",
    url: "showcart.php",
    success: (response) => $("#offcanvasCart").html(response),
    error: (xhr, status, error) => console.error("AJAX Error:", status, error),
  });
}

function addcart(productid) {
  var quantity = $(".quantity" + productid).val();
  $.ajax({
    type: "POST",
    url: "add_to_cart.php",
    data: { productid, qty: quantity },
    success: () => loadCart(),
    error: (xhr, status, error) => console.error("AJAX Error:", status, error),
  });
}

function addmoreone(id) {
  $.ajax({
    type: "POST",
    url: "addmoreone.php",
    data: { id },
    success: () => loadCart(),
    error: (xhr, status, error) => console.error("AJAX Error:", status, error),
  });
}

function removemoreone(id) {
  $.ajax({
    type: "POST",
    url: "removemoreone.php",
    data: { id },
    success: () => loadCart(),
    error: (xhr, status, error) => console.error("AJAX Error:", status, error),
  });
}

function removecart(id) {
  $.ajax({
    type: "POST",
    url: "removecart.php",
    data: { id },
    success: () => loadCart(),
    error: (xhr, status, error) => console.error("AJAX Error:", status, error),
  });
}

window.addEventListener("DOMContentLoaded", loadCart);
