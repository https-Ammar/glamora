<?php
session_start();
require '../config/db.php';
require './phpmailer/src/Exception.php';
require './phpmailer/src/PHPMailer.php';
require './phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $otp = random_int(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $stmt->bind_param("ssi", $otp, $expiry, $user['id']);
            $stmt->execute();

            $_SESSION['verification_code'] = $otp;
            $_SESSION['verification_email'] = $email;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ammar132004@gmail.com';
            $mail->Password = 'urkeowrpkygnyrhl';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('no-reply@yourdomain.com', 'Glamora');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Glamora - Reset Code';
            $mail->Body = "<p>Hello,</p><p>Your password reset verification code is:</p><h2 style='color:#222;'>$otp</h2><p>This code will expire in 15 minutes.</p>";

            $mail->send();
            header("Location: verify_code.php");
            exit();
        } else {
            $error = "No user found with this email.";
        }
    } else {
        $error = "Invalid email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | ARTSY</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../style/main.css">
    <link rel="stylesheet" href="../style/register.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

</head>

<body>

    <?php require('./header.php'); ?>


    <main class="main-content">
        <div class="container">
            <div class="login-container text-center">
                <h2 class="mb-2">Forgot Password</h2>
                <p class="text-muted">Enter your email to receive a 6-digit code:</p>
                <form method="POST" autocomplete="off">
                    <div class="floating-label-group text-start">
                        <input name="email" type="email" id="email" class="form-control" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>
                    <?php if (isset($error)): ?>
                        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-login w-100">Send Code</button>
                    <div class="form-text-link mb-3 mt-3">
                        <a href="login.php">Back to login</a>
                    </div>
                </form>
            </div>
        </div>
    </main>


    <?php require('footer.php'); ?>

</body>

</html>