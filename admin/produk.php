<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
}

if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $conn->query("INSERT INTO produk (nama, harga) VALUES ('$nama','$harga')");
}

$data = $conn->query("SELECT * FROM produk");
?>

<h2>Produk</h2>
<form method="POST">
<input name="nama" placeholder="Nama">
<input name="harga" placeholder="Harga">
<button name="tambah">Tambah</button>
</form>

<table border="1">
<tr><th>Nama</th><th>Harga</th></tr>
<?php while($d = $data->fetch_assoc()) { ?>
<tr>
<td><?= $d['nama'] ?></td>
<td><?= $d['harga'] ?></td>
</tr>
<?php } ?>
</table>