<?php
session_start();
include '../config/db.php';
if (!isset($_SESSION['login'])) header('Location: login.php');
include 'layout.php';

$produk = $conn->query("SELECT * FROM produk");
?>

<h2>Kasir</h2>
<div class="row">
<div class="col-md-8">
<div class="row">
<?php while($p = $produk->fetch_assoc()) { ?>
<div class="col-md-3">
<div class="card p-2 mb-2" onclick="tambah(<?= $p['id'] ?>,'<?= $p['nama'] ?>',<?= $p['harga'] ?>)">
<h6><?= $p['nama'] ?></h6>
<p>Rp <?= $p['harga'] ?></p>
</div>
</div>
<?php } ?>
</div>
</div>

<div class="col-md-4">
<h4>Keranjang</h4>
<ul id="cart"></ul>
<h5>Total: Rp <span id="total">0</span></h5>
<input id="bayar" class="form-control" placeholder="Bayar">
<button onclick="bayar()" class="btn btn-success mt-2">Bayar</button>
<p id="kembalian"></p>
</div>
</div>

<script>
let cart = [];
function tambah(id,nama,harga){
 cart.push({id,nama,harga}); render();
}
function render(){
 let html=''; let total=0;
 cart.forEach(i=>{html+=`<li>${i.nama} - ${i.harga}</li>`; total+=i.harga});
 document.getElementById('cart').innerHTML=html;
 document.getElementById('total').innerText=total;
}
function bayar(){
 let total=parseInt(document.getElementById('total').innerText);
 let bayar=parseInt(document.getElementById('bayar').value);
 document.getElementById('kembalian').innerText='Kembalian: '+(bayar-total);
}
</script>

<?php include 'layout_footer.php'; ?>