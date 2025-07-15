<?php
session_start();
require('./db.php');

$error_message = '';  // Initialize the error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Check if required session variables are set
  if (isset($_SESSION['fullName'], $_SESSION['email'], $_SESSION['password'], $_SESSION['randomNumber'])) {
    // Retrieve session variables
    $fullName = $_SESSION['fullName'];
    $email = $_SESSION['email'];
    $password = $_SESSION['password'];
    $randomNumber = $_SESSION['randomNumber'];
    $passwordhash = password_hash($password, PASSWORD_BCRYPT);

    // Retrieve the code entered by the user
    $code = trim($_POST['code']);  // Trim any extra spaces

    // Verify that the code matches the random number sent via email
    if ($code == $randomNumber) {
      // Insert user data into the database
      $stmt = $conn->prepare("INSERT INTO usersadmin (name, email, password) VALUES (?, ?, ?)");
      if ($stmt === false) {
        die("Failed to prepare statement: " . $conn->error);
      }

      $stmt->bind_param("sss", $fullName, $email, $passwordhash);

      if ($stmt->execute()) {
        // Optionally destroy session after successful registration
        session_destroy();
        header("Location: login.php"); // Redirect to login page
        exit();
      } else {
        echo "Error: " . $stmt->error;
      }

      $stmt->close();
      $conn->close();
    } else {
      $error_message = "Invalid verification code.";  // Set the error message
    }
  } else {
    $error_message = "Session expired or incomplete data.";
  }
}
?>

<!DOCTYPE html>
<html lang="en" class="__className_1dd84e dark" style="color-scheme: dark">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta charset="utf-8" />
  <title>Dashboard : Employees</title>
  <meta name="description" content="Basic dashboard with Next.js and Shadcn" />
  <link rel="icon" href="/favicon.ico" type="image/x-icon" sizes="16x16" />

  <meta name="next-size-adjust" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap">

  <link rel="stylesheet" href="../style/css/pace.css" />
  <link rel="stylesheet" href="../style/css/perfect-scrollbar.css" />
  <!-- ######################################################################################################################## -->
  <!-- ######################################################################################################################## -->
  <link rel="stylesheet" href="../style/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../style/css/all.min.css" />
  <link rel="stylesheet" href="../style/css/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../style/css/pace.css" />
  <link rel="stylesheet" href="../style/css/apexcharts.css" />
  <link rel="stylesheet" href="../style/css/main.min.css" />

  <link rel="stylesheet" href="../style/css/custom.css" />
  <!-- ######################################################################################################################## -->




</head>




<body class="login-page">
  <div class="container">
    <div class="row justify-content-md-center">
      <div class="col-md-12 col-lg-4">
        <div class="card login-box-container">
          <div class="card-body">
            <div class="authent-logo">
              <img src="../../assets/images/avatars/avatar1.jpeg" width="60" alt="" />
            </div>
            <div class="authent-text">
              <p>Welcome back!</p>
              <p>Enter your password to unlock.</p>
            </div>
            <form class="w-full space-y-2" method="POST" action="">
              <div class="mb-3">
                <div class="form-floating">


                  <input type="text" name="code" class="form-control" maxlength="4" id="verification-code"
                    placeholder="Password" />
                  <label for="verification-code">Password</label>
                </div>
              </div>
              <?php if (!empty($error_message)): ?>
                  <p class="text-sm text-red-500"><?php echo $error_message; ?></p> <!-- Display the error message -->
              <?php endif; ?>
              <div class="d-grid">
                <button type="submit" class="btn btn-secondary m-b-xs">
                  Unlock
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>








</body>






<!-- ######################################################################################################################## -->
<!-- ######################################################################################################################## -->
<!-- ######################################################################################################################## -->
<!-- ######################################################################################################################## -->
<!-- ######################################################################################################################## -->
<!-- ######################################################################################################################## -->








</html>