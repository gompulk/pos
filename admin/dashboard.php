<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
}
?>

<h1>Dashboard Admin</h1>
<a href="produk.php">Kelola Produk</a>