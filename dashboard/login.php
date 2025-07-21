<?php
session_start();
require('./db.php');

$message = "";

// إنشاء مشرف افتراضي لو مش موجود
$defaultEmail = "ammar132004@gmail.com";
$defaultPassword = "123456";
$defaultHashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
$defaultName = "Ammar";

$stmt = $conn->prepare("SELECT id FROM usersadmin WHERE email = ?");
$stmt->bind_param("s", $defaultEmail);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
  $stmt->close();
  $insert = $conn->prepare("INSERT INTO usersadmin (name, email, password) VALUES (?, ?, ?)");
  $insert->bind_param("sss", $defaultName, $defaultEmail, $defaultHashedPassword);
  $insert->execute();
  $insert->close();
}
$stmt->close();

// تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = htmlspecialchars($_POST['email']);
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT id, password FROM usersadmin WHERE email = ?");
  if ($stmt === false) {
    die("Failed to prepare statement: " . $conn->error);
  }

  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $stmt->bind_result($userId, $hashedPassword);
    $stmt->fetch();

    if (password_verify($password, $hashedPassword)) {
      $_SESSION['userId'] = $userId;
      header('Location: ../index.php');
      exit;
    } else {
      $message = "Invalid password.";
    }
  } else {
    $message = "No user found with that email.";
  }

  $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="dark" style="color-scheme: dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard: Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../style/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../style/css/all.min.css" />
  <link rel="stylesheet" href="../style/css/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../style/css/pace.css" />
  <link rel="stylesheet" href="../style/css/apexcharts.css" />
  <link rel="stylesheet" href="../style/css/main.min.css" />
  <link rel="stylesheet" href="../style/css/custom.css" />
  <style>
    body {
      font-family: 'Cairo', sans-serif;
    }

    .error-message {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>

<body class="login-page">
  <div class="container">
    <div class="row justify-content-md-center">
      <div class="col-md-12 col-lg-4">
        <div class="card login-box-container mt-5">
          <div class="card-body">
            <div class="authent-logo text-center mb-3">
              <a href="#" class="h3 text-primary">Neo</a>
            </div>
            <div class="authent-text text-center mb-4">
              <p>Welcome to Neo</p>
              <p>Please Sign-in to your account.</p>
            </div>
            <form method="POST" action="">
              <div class="mb-3">
                <div class="form-floating">
                  <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com"
                    required>
                  <label for="email">Email address</label>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-floating">
                  <input type="password" name="password" class="form-control" id="password" placeholder="Password"
                    required>
                  <label for="password">Password</label>
                </div>
              </div>
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="exampleCheck1">
                <label class="form-check-label" for="exampleCheck1">Remember me</label>
              </div>
              <?php if (!empty($message)): ?>
                <div class="error-message"><?php echo $message; ?></div>
              <?php endif; ?>
              <div class="d-grid">
                <button type="submit" class="btn btn-info">Sign In</button>
              </div>
            </form>
            <div class="authent-reg text-center mt-3">
              <p>Not registered? <a href="./Sign_up.php">Create an account</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>