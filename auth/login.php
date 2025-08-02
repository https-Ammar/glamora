<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

require('../config/db.php');

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
        $error = "Invalid credentials";
    } else {
        $stmt = $conn->prepare("SELECT id, password, name FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['logged_in'] = true;

                    header('Location: profile.php');
                    exit();
                }
            }
            $error = "Invalid credentials";
            $stmt->close();
        } else {
            $error = "Database error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require('../includes/link.php'); ?>
</head>

<body>
    <?php require('../includes/header.php'); ?>
    <main class="main-content">
        <div class="container">
            <div class="login-container text-center">
                <h2 class="mb-2">Login</h2>
                <p class="text-muted">Please enter your e-mail and password:</p>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="floating-label-group text-start">
                        <input name="email" type="email" id="email" class="form-control" placeholder=" " required
                            autocomplete="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                        <label for="email">E-mail</label>
                    </div>
                    <div class="floating-label-group text-start input-group-custom">
                        <input type="password" id="password" name="password" class="form-control" placeholder=" "
                            required autocomplete="current-password" />
                        <label for="password">Password</label>
                        <i class="fa-solid fa-eye input-icon" id="togglePassword"></i>
                    </div>
                    <div class="form-text-link mb-3">
                        <a href="./forgot_password.php">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-login w-100">LOGIN</button>
                    <div class="create-account mt-3">
                        New customer? <a href="register.php">Create an account</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require('../includes/footer.php'); ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const togglePassword = document.getElementById("togglePassword");
            const password = document.getElementById("password");

            if (togglePassword && password) {
                togglePassword.addEventListener("click", function () {
                    const type = password.getAttribute("type") === "password" ? "text" : "password";
                    password.setAttribute("type", type);
                    this.classList.toggle("fa-eye");
                    this.classList.toggle("fa-eye-slash");
                });
            }

            // Focus on email field by default
            const emailField = document.getElementById("email");
            if (emailField) {
                emailField.focus();
            }
        });
    </script>
</body>

</html>