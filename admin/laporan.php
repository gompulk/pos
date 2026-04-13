<?php
include '../config/db.php';
$data = $conn->query("SELECT * FROM transaksi ORDER BY id DESC");
include 'layout.php';
?>
<h2>Laporan Penjualan</h2>
<table class="table">
<tr><th>ID</th><th>Total</th></tr>
<?php while($d=$data->fetch_assoc()){ ?>
<tr>
<td><?= $d['id'] ?></td>
<td><?= $d['total'] ?></td>
</tr>
<?php } ?>
</table>
<?php include 'layout_footer.php'; ?>