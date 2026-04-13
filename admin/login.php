<?php
session_start();
include '../config/db.php';

if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $q = $conn->query("SELECT * FROM users WHERE username='$user'");
    $data = $q->fetch_assoc();

    if ($data && password_verify($pass, $data['password'])) {
        $_SESSION['login'] = true;
        header("Location: dashboard.php");
    } else {
        echo "Login gagal";
    }
}
?>

<form method="POST">
<input type="text" name="username" placeholder="Username">
<input type="password" name="password" placeholder="Password">
<button name="login">Login</button>
</form>