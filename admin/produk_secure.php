<?php
session_start();
include '../config/db.php';
if($_SESSION['role']!='admin') die('Akses ditolak');

if(isset($_POST['tambah'])){
 $stmt=$conn->prepare("INSERT INTO produk(nama,harga) VALUES(?,?)");
 $stmt->bind_param("si",$_POST['nama'],$_POST['harga']);
 $stmt->execute();
}
$data=$conn->query("SELECT * FROM produk");
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<h2>Produk</h2>
<form method="POST">
<input name="nama" placeholder="Nama">
<input name="harga" placeholder="Harga">
<button name="tambah">Tambah</button>
</form>

<table id="tabel" border="1">
<tr><th>Nama</th><th>Harga</th></tr>
<?php while($d=$data->fetch_assoc()){ ?>
<tr>
<td><?= $d['nama'] ?></td>
<td><?= $d['harga'] ?></td>
</tr>
<?php } ?>
</table>

<script>
$(document).ready(function(){ $('#tabel').DataTable(); });
</script>