<?php
include '../config/db.php';
id=$_GET['id'];
$data=$conn->query("SELECT * FROM produk WHERE id=$id")->fetch_assoc();
if(isset($_POST['update'])){
 $conn->query("UPDATE produk SET nama='$_POST[nama]', harga='$_POST[harga]' WHERE id=$id");
 header('Location: produk.php');
}
?>
<form method="POST">
<input name="nama" value="<?= $data['nama'] ?>">
<input name="harga" value="<?= $data['harga'] ?>">
<button name="update">Update</button>
</form>