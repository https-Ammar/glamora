<?php
session_start();
require('../config/db.php');

if (!isset($_SESSION['verification_email'])) {
    header("Location: reset_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($password === $confirm_password && strlen($password) >= 6) {
        $email = $_SESSION['verification_email'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();

        unset($_SESSION['verification_email']);
        unset($_SESSION['verification_code']);

        header("Location: login.php");
        exit();
    } else {
        $error = "Passwords must match and be at least 6 characters.";
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
                <h2 class="mb-2">Set New Password</h2>
                <p class="text-muted">Please enter your new password:</p>
                <form method="POST">
                    <div class="floating-label-group text-start input-group-custom">
                        <input type="password" name="password" id="newPassword" class="form-control" placeholder=" "
                            required />
                        <label for="newPassword">New Password</label>
                        <i class="fa-solid fa-eye input-icon" id="toggleNewPassword"></i>
                    </div>
                    <div class="floating-label-group text-start input-group-custom">
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control"
                            placeholder=" " required />
                        <label for="confirmPassword">Confirm Password</label>
                        <i class="fa-solid fa-eye input-icon" id="toggleConfirmPassword"></i>
                    </div>
                    <div class="form-text-link mb-3">
                        <a href="./forgot_password.php">Forgot password?</a>
                    </div>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-login w-100">Save</button>
                    <div class="create-account mt-3">
                        New customer? <a href="register.php">Create an account</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require('footer.php'); ?>
    <script>
        const toggleNewPassword = document.getElementById("toggleNewPassword");
        const newPassword = document.getElementById("newPassword");
        toggleNewPassword.addEventListener("click", function () {
            const type = newPassword.getAttribute("type") === "password" ? "text" : "password";
            newPassword.setAttribute("type", type);
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });

        const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");
        const confirmPassword = document.getElementById("confirmPassword");
        toggleConfirmPassword.addEventListener("click", function () {
            const type = confirmPassword.getAttribute("type") === "password" ? "text" : "password";
            confirmPassword.setAttribute("type", type);
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });
    </script>
</body>

</html>