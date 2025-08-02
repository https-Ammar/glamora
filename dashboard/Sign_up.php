<?php
session_start();
require('./db.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './phpmailer/src/Exception.php';
require './phpmailer/src/PHPMailer.php';
require './phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = htmlspecialchars($_POST['full-name']);
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $password = $_POST['password'];
  $randomNumber = rand(1000, 9999);

  // Check if the email already exists
  $stmt = $conn->prepare("SELECT id FROM usersadmin WHERE email = ?");
  if ($stmt === false) {
    die("Failed to prepare statement: " . $conn->error);
  }

  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    die("User with this email already exists.");
  }

  // Insert new user into the database (اضف كود الإدخال هنا)

  if ($stmt->execute()) {
    // Store user data in session
    $_SESSION['fullName'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['password'] = $password;
    $_SESSION['randomNumber'] = $randomNumber;

    // إعدادات SMTP
    $smtpHost = 'smtp.gmail.com';
    $smtpUsername = 'ammar132004@gmail.com';
    $smtpPassword = 'uaya wmrk igpl lkqa'; // كلمة مرور التطبيقات
    $smtpPort = 587;
    $smtpSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // بيانات الإرسال والاستلام
    $senderEmail = 'ammar132004@gmail.com'; // المرسل نفس البريد
    $senderName = 'Verification Code';
    $adminEmail = 'ammar132004@gmail.com'; // بريد الإدمن
    $userEmail = $email; // إرسال الكود للمستخدم
    $recipientName = $fullName;

    // Create a PHPMailer instance
    $mail = new PHPMailer(true);

    try {
      // Server settings
      $mail->SMTPDebug = 0; // تغيير إلى 3 للتصحيح إذا لزم
      $mail->isSMTP();
      $mail->Host = $smtpHost;
      $mail->SMTPAuth = true;
      $mail->Username = $smtpUsername;
      $mail->Password = $smtpPassword;
      $mail->SMTPSecure = $smtpSecure;
      $mail->Port = $smtpPort;

      // Send to Admin
      $mail->setFrom($senderEmail, $senderName);
      $mail->addAddress($adminEmail, 'Admin'); // إرسال الكود للإدمن
      $mail->isHTML(true);
      $mail->Subject = 'New User Verification Code';
      $mail->Body = "Hello Admin, <br>A new user has registered. Their verification code is: <b>$randomNumber</b><br>User details: Name: $fullName, Email: $userEmail.";

      $mail->send();

      // Redirect to the verification page
      header('Location: verification.php');
      exit;
    } catch (Exception $e) {
      die("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
  } else {
    die("Error: " . $stmt->error);
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
              <p>Enter your details to create your account</p>
            </div>

            <form class="w-full space-y-2 content__form" action="" method="POST">
              <div class="mb-3">
                <div class="form-floating">
                  <input type="text" id="full-name" name="full-name" placeholder="Full Name" required
                    class="form-control" placeholder="Fullname" />
                  <label for="floatingInput">Fullname</label>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-floating">
                  <input type="email" name="email" required id="email" class="form-control"
                    placeholder="name@example.com" />
                  <label for="email">Email address</label>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-floating">
                  <input class="form-control" type="password" name="password" id="password" required
                    placeholder="Password" />
                  <label for="password">Password</label>
                </div>
              </div>
              <?php if (!empty($message)): ?>
                <p class="error-message"><?php echo $message; ?></p>
              <?php endif; ?>



              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="exampleCheck1" />
                <label class="form-check-label" for="exampleCheck1">I agree the <a href="#">Terms and
                    Conditions</a></label>
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary m-b-xs">
                  Register
                </button>
              </div>
            </form>
            <div class="authent-login">
              <p>Already have an account? <a href="./login.php">Sign in</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Javascripts -->
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
</body>




<!-- ######################################################################################################################## -->
<!-- ######################################################################################################################## -->
<!-- ######################################################################################################################## -->












</html>