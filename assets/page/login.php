<?php
session_start();
require('./db.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['userId'] = $user['id'];
            header('Location: profile.php');
            exit();
        } else {
            $error = "كلمة المرور خاطئة";
        }
    } else {
        $error = "البريد غير موجود";
    }
}
?>

<!-- HTML Form -->
<h2>تسجيل الدخول</h2>
<?php if (isset($error))
    echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST">
    <input name="email" type="email" required placeholder="البريد الإلكتروني"><br>
    <input name="password" type="password" required placeholder="كلمة المرور"><br>
    <button type="submit">دخول</button>
</form>
<p>ليس لديك حساب؟ <a href="register.php">سجّل الآن</a></p>