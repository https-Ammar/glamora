<?php
session_start();
require('./db.php'); // Adjust the path as necessary

$message = ""; // Variable to store error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = htmlspecialchars($_POST['email']);
  $password = $_POST['password'];

  // Prepare and execute the query to find the user by email
  $stmt = $conn->prepare("SELECT id, password FROM usersadmin WHERE email = ?");
  if ($stmt === false) {
    die("Failed to prepare statement: " . $conn->error);
  }

  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  // Check if a user with the provided email exists
  if ($stmt->num_rows > 0) {
    $stmt->bind_result($userId, $hashedPassword);
    $stmt->fetch();

    // Verify the password
    if (password_verify($password, $hashedPassword)) {
      // Store user ID in session
      $_SESSION['userId'] = $userId;

      // Redirect to index.php
      header('Location: ../index.php');
      exit;
    } else {
      // Password is incorrect
      $message = "Invalid password.";
    }
  } else {
    // No user found with the provided email
    $message = "No user found with that email.";
  }

  $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="dark" style="color-scheme: dark">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="utf-8">
  <title>Dashboard : Employees</title>
  <meta name="description" content="Basic dashboard with Next.js and Shadcn">
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
              <a href="#">Neo</a>
            </div>
            <div class="authent-text">
              <p>Welcome to Neo</p>
              <p>Please Sign-in to your account.</p>
            </div>

            <form class="w-full space-y-2 content__form" action="" method="POST">
              <div class="mb-3">
                <div class="form-floating">
                  <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" />




                  <label for="email">Email address</label>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-floating">
                  <input type="password" name="password" class="form-control" required id="password"
                    placeholder="Password" />





                  <label for="password">Password</label>
                </div>
              </div>
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="exampleCheck1" />
                <label class="form-check-label" for="exampleCheck1">Check me out</label>
              </div>

              <?php if (!empty($message)): ?>
                <p class="error-message"><?php echo $message; ?></p>
              <?php endif; ?>

              <div class="d-grid">
                <button type="submit" class="btn btn-info m-b-xs">
                  Sign In
                </button>

              </div>
            </form>
            <div class="authent-reg">
              <p>
                Not registered? <a href="./Sign_up.php">Create an account</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


</body>






</html>