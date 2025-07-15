<?php
require('./db.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $country = $_POST['country'];

    // حفظ الصورة إذا تم رفعها
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $targetDir = "uploads/";
        $profile_image = $targetDir . time() . "_" . basename($_FILES["profile_image"]["name"]);
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image);
    }

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, country, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $password, $phone, $address, $city, $country, $profile_image);
    $stmt->execute();

    header("Location: login.php");
    exit();
}
?>

<!-- HTML Form -->
<h2>تسجيل حساب جديد</h2>
<form method="POST" enctype="multipart/form-data">
    <input name="name" type="text" required placeholder="الاسم"><br>
    <input name="email" type="email" required placeholder="البريد الإلكتروني"><br>
    <input name="password" type="password" required placeholder="كلمة المرور"><br>
    <input name="phone" type="text" placeholder="رقم الهاتف"><br>
    <input name="address" type="text" placeholder="العنوان"><br>
    <input name="city" type="text" placeholder="المدينة"><br>
    <input name="country" type="text" placeholder="الدولة"><br>
    <label>صورة شخصية:</label>
    <input name="profile_image" type="file"><br><br>
    <button type="submit">تسجيل</button>
</form>